<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\StrUtils;
use Log;

class DebtorEvent extends Model
{

    const SMS_EVENT = 12;
    const AUTOINFORMER_OMICRON_EVENT = 15;
    const WHATSAPP_EVENT = 23;
    const EMAIL_EVENT = 24;

    protected $table = 'debtors.debtor_events';
    protected $fillable = [
        'date',
        'created_at',
        'customer_id_1c',
        'loan_id_1c',
        'event_type_id',
        'debt_group_id',
        'overdue_reason_id',
        'report',
        'user_id',
        'event_result_id',
        'debtor_id',
        'completed',
        'id_1c',
        'last_user_id',
        'debtor_id_1c',
        'user_id_1c',
        'refresh_date',
    ];

    /**
     * Генерирует номер мероприятия для 1с
     * @return string
     */
    static function getNextNumber($connection = 'mysql')
    {
        $number = 'А000000001';
        $lastEvent = DB::connection($connection)->select('select debtors.debtor_events.id_1c from debtors.debtor_events order by SUBSTRING(debtors.debtor_events.id_1c, 2) desc limit 1');
        if (count($lastEvent) > 0) {
            $intNumber = intval(StrUtils::removeNonDigits($lastEvent[0]->id_1c));
            $number = 'А' . StrUtils::addChars(strval($intNumber + 1), 9, '0', false);
        }
        return $number;
    }

    static function generateNumberById($id)
    {
        return 'А' . str_pad($id, 9, '0', STR_PAD_LEFT);
    }

    /**
     * Загружает старые мероприятия в новые
     * @param array $debtorsList список айдишников должников из 1с
     */
    static function uploadFromOldEvents($debtorsList)
    {
        if (!is_array($debtorsList)) {
            $debtorsList = $debtorsList->toArray();
        }
//        \PC::debug($debtorsList);
        $oldevents = DB::connection('oldevents')->table('debtor_events')->whereIn('debtor_id_1c', $debtorsList)->get();
//        \PC::debug($oldevents,'oldevents');
        \PC::debug(count($oldevents), 'oldevents');
        $toupload = 0;
        $uploaded = 0;
        foreach ($oldevents as $oe) {
//            \PC::debug($oe);
            $e = DebtorEvent::where('id_1c', $oe->id_1c)->first();
            if (is_null($e)) {
                $toupload++;
                if (DB::connection("debtors")->table("debtor_events")->insert(get_object_vars($oe))) {
                    $uploaded++;
                }
            }
        }
        \PC::debug($toupload, 'toupload');
        \PC::debug($uploaded, 'uploaded');
    }

    public function update(array $attributes = array())
    {
        if (!is_null(Auth::user())) {
            $this->last_user_id = Auth::user()->id;
        } else {
            $this->last_user_id = null;
        }
        parent::update($attributes);
    }

    public function save(array $options = array())
    {
        if (!is_null(Auth::user())) {
            $this->last_user_id = Auth::user()->id;
        } else {
            $this->last_user_id = null;
        }
        parent::save($options);
    }

    static function getFields()
    {
        return [
            'id' => 'ИД',
            'date' => 'Дата',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления в АРМ',
            'customer_id_1c' => 'Номер контрагента в 1С',
            'loan_id_1c' => 'Номер договора в 1С',
            'event_type_id' => 'Тип мероприятия',
            'debt_group_id' => 'Группа должника',
            'overdue_reason_id' => 'Причина просрочки',
            'event_result_id' => 'Результат',
            'report' => 'Отчет',
            'debtor_id' => 'ИД должника',
            'user_id' => 'ИД ответственного',
            'completed' => 'Завершено',
            'id_1c' => 'Номер мероприятия в 1С',
            'last_user_id' => 'Последний редактировавший пользователь'
        ];
    }

    static function getPlannedForUser($user, $firstDate, $daysNum = 10)
    {
        $res = [];
        $totalTypes = [];
        $totalDays = [];
        $tableData = [];
        $total = 0;
        $dates = [];
        $cols = [];
        $rows = [];
        for ($i = 0; $i < $daysNum; $i++) {
            $date = $firstDate->copy()->addDays($i);
            $intname = $date->format('d.m.y');
            $dates[$intname] = [
                $date->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $date->setTime(23, 59, 59)->format('Y-m-d H:i:s')
            ];
            $cols[] = $intname;
            $totalDays[$intname] = 0;
        }
        $usersId = array_merge([$user->id], json_decode(DebtorUsersRef::getDebtorSlaveUsers($user->id), true));
        foreach ($dates as $intk => $intv) {
            $data = collect(DB::table('debtor_events')
                ->select(DB::raw('count(*) as num, event_type_id'))
                ->whereIn('user_id', $usersId)
                ->whereBetween('date', $intv)
                ->where('completed', 0)
                ->groupBy('event_type_id')
                ->get());
            if($user->hasRole('missed_calls')){
                $missedCallsUsersId = User::where('banned', 0)
                    ->where('user_group_id', $user->user_group_id)
                    ->get()
                    ->pluck('id')
                    ->toArray();
                $missedCallsEvent = DB::table('debtor_events')
                    ->select(DB::raw('count(*) as num, event_type_id'))
                    ->whereIn('user_id', $missedCallsUsersId)
                    ->whereBetween('date', $intv)
                    ->where('completed', 0)
                    ->where('event_type_id', 4)
                    ->get();
                foreach ($missedCallsEvent as $mce) {
                    if ($mce->num != 0 && !is_null($mce->event_type_id)) {
                        $data = $data->merge(collect($missedCallsEvent));
                    }
                }
            }
            logger('testHUI1',[$data]);
            foreach ($data as $item) {
                logger('testHUI2',[$item]);

                $tableData[$item->event_type_id][$intk] = $item->num;

                if (!array_key_exists($item->event_type_id, $totalTypes)) {
                    $totalTypes[$item->event_type_id] = 0;
                }
                if (!array_key_exists($intk, $totalDays)) {
                    $totalDays[$intk] = 0;
                }
            }
        }
        foreach ($tableData as $tdk => $tdv) {
            foreach ($cols as $col) {
                if (!array_key_exists($col, $tableData[$tdk])) {
                    $tableData[$tdk][$col] = 0;
                }
                $totalDays[$col] += $tableData[$tdk][$col];
                $totalTypes[$tdk] += $tableData[$tdk][$col];
                $total += $tableData[$tdk][$col];
            }
        }
        $res['data'] = $tableData;
        $res['cols'] = $cols;
        $res['total_types'] = $totalTypes;
        $res['total_days'] = $totalDays;
        $res['total'] = $total;
        \PC::debug($res);
        return $res;
    }

    /**
     * Набор полей для поиска по мероприятиям
     * @return type
     */
    static function getSearchFields()
    {
        return [
            [
                'name' => 'debtor_events@date_from',
                'input_type' => 'date',
                'label' => 'Дата c'
            ],
            [
                'name' => 'debtor_events@date_to',
                'input_type' => 'date',
                'label' => 'Дата по'
            ],
            [
                'name' => 'passports@fio',
                'input_type' => 'text',
                'label' => 'ФИО',
                'hidden_value_field' => 'passports@id'
            ],
            [
                'name' => 'debtors_event_types@name',
                'input_type' => 'text',
                'label' => 'Тип мероприятия',
                'hidden_value_field' => 'debtors_event_types@id'
            ],
            [
                'name' => 'debt_groups@name',
                'input_type' => 'text',
                'label' => 'Группа долга',
                'hidden_value_field' => 'debt_groups@id'
            ],
            [
                'name' => 'debtors@loan_id_1c',
                'input_type' => 'text',
                'label' => 'Договор',
                //'hidden_value_field' => 'debtors@loan_id_1c'
            ],
            [
                'name' => 'passports@fact_timezone',
                'input_type' => 'text',
                'label' => 'Разница в часах',
            ],
            [
                'name' => 'debtor_events@completed',
                'input_type' => 'checkbox',
                'label' => 'Выполнено',
            ],
            [
                'name' => 'passports@series',
                'input_type' => 'text',
                'label' => 'Серия паспорта',
            ],
            [
                'name' => 'passports@number',
                'input_type' => 'text',
                'label' => 'Номер паспорта',
            ],
            [
                'name' => 'users@name',
                'input_type' => 'text',
                'label' => 'Ответственный',
                'hidden_value_field' => 'users@id_1c'
            ],
        ];
    }

    static function getGroupPlanFields()
    {
        return [
            [
                'name' => 'debtor_events@date_plan',
                'input_type' => 'date',
                'label' => 'Дата план'
            ],
            [
                'name' => 'debt_groups@name',
                'input_type' => 'text',
                'label' => 'Группа долга',
                'hidden_value_field' => 'debt_groups@id'
            ],
            [
                'name' => 'users@name',
                'input_type' => 'text',
                'label' => 'Ответственный',
                'hidden_value_field' => 'users@id_1c'
            ],
            [
                'name' => 'debtors_event_types@name',
                'input_type' => 'text',
                'label' => 'Тип мероприятия',
                'hidden_value_field' => 'debtors_event_types@id'
            ]
        ];
    }

    static function saveEventsIn1c($debtor_id_1c, $dateStart)
    {
        $connection = (config('app.version_type') == 'debtors') ? 'mysql' : 'debtors';
        \PC::debug($debtor_id_1c);
        $events = DB::connection($connection)->table('debtor_events')->where('refresh_date', '>=',
            $dateStart)->whereNull('id_1c')->where('debtor_id_1c', $debtor_id_1c)->get();
        Log::info('DebtorEvent.saveEventsIn1c events to send', ['events' => $events]);
        DebtorEvent::updateId1cForEvents($events, $connection);
        $xml = [
            'user_id_1c' => DB::connection($connection)->table('debtors')->where('debtor_id_1c',
                $debtor_id_1c)->value('responsible_user_id_1c'),
            'debtor_id_1c' => $debtor_id_1c,
            'events' => [],
            'type' => 'EditDebtorCreateEvent'
        ];
        \PC::debug($events);
        foreach ($events as $event) {
            $e = json_decode(json_encode($event), true);
            foreach (['created_at', 'date', 'refresh_date'] as $d) {
                $e[$d] = (!empty($event->{$d}) && $event->{$d} != '0000-00-00 00:00:00') ? with(new Carbon($event->{$d}))->format('YmdHis') : '00010101000000';
            }
            $xml['events'][] = $e;
        }
        \PC::debug($events, 'events');
        \PC::debug($xml, 'xml');
        $res1c = MySoap::sendExchangeArm(MySoap::createXML($xml));
        return ((int)$res1c->result == 1);
    }

    /**
     * заполняет переданные мероприятия номерами для 1с
     * @param \Illuminate\Database\Eloquent\Collection $events
     * @param string $connection
     * @return type
     */
    static function updateId1cForEvents(&$events, $connection)
    {
        foreach ($events as &$event) {
            if (empty($event->id_1c)) {
                $event_id_1c = DebtorEvent::generateNumberById($event->id);
                $event->id_1c = $event_id_1c;
                DB::connection($connection)->table('debtor_events')->where('id',
                    $event->id)->update(['id_1c' => $event_id_1c]);
            }
        }
        return $events;
    }

}

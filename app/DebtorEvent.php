<?php

namespace App;

use App\Model\ConnectionStatus;
use App\Model\DebtorEventEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Model\DebtorsForgotten;
use Log;

/**
 * Class DebtorEvent
 * @package App
 *
 * @property \DateTime date
 * @property \DateTime created_at
 * @property \DateTime updated_at
 * @property string customer_id_1c
 * @property string loan_id_1c
 * @property int event_type_id
 * @property int debt_group_id
 * @property int overdue_reason_id
 * @property int event_result_id
 * @property string report
 * @property int amount
 * @property int debtor_id
 * @property int user_id
 * @property int completed
 * @property string id_1c
 * @property int last_user_id
 * @property string debtor_id_1c
 * @property string user_id_1c
 * @property \DateTime refresh_date
 *
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 */

class DebtorEvent extends Model
{

    const SMS_EVENT = 12;
    const AUTOINFORMER_OMICRON_EVENT = 15;
    const WHATSAPP_EVENT = 23;
    const EMAIL_EVENT = 24;
    const REASON_OTHER = 0;
    const RES_INFO = 17;
    const RES_EMAIL= 29;
    const COMPLETED = 1;
    const NOT_COMPLETED = 0;

    protected $table = 'debtor_events';
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
        'refresh_date'
    ];

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($event) {
            if (in_array($event->event_result_id, [0, 1, 6, 9, 10, 11, 12, 13, 22, 24, 27, 29])) {
                $debtorsIds = Debtor::where('customer_id_1c', $event->customer_id_1c)
                    ->get()
                    ->pluck('id')
                    ->toArray();
                DebtorsForgotten::whereIn('debtor_id', $debtorsIds)->delete();
            }
        });
    }

    public function eventEmail()
    {
        $this->hasMany(DebtorEventEmail::class, 'debtor_id');
    }

    public function connectionStatus(): BelongsToMany
    {
        return $this->belongsToMany(ConnectionStatus::class, 'debtor_events_connection_status', 'debtor_event_id', 'connection_status_id');
    }

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
        $oldevents = DB::connection('oldevents')->table('debtor_events')->whereIn('debtor_id_1c', $debtorsList)->get();
        $toupload = 0;
        $uploaded = 0;
        foreach ($oldevents as $oe) {
            $e = DebtorEvent::where('id_1c', $oe->id_1c)->first();
            if (is_null($e)) {
                $toupload++;
                if (DB::connection("debtors")->table("debtor_events")->insert(get_object_vars($oe))) {
                    $uploaded++;
                }
            }
        }
    }

    public function update(array $attributes = array(),array $options = array())
    {
        if (!is_null(Auth::user())) {
            $this->last_user_id = Auth::user()->id;
        } else {
            $this->last_user_id = null;
        }
        parent::update($attributes,$options);
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
        foreach ($events as $event) {
            $e = json_decode(json_encode($event), true);
            foreach (['created_at', 'date', 'refresh_date'] as $d) {
                $e[$d] = (!empty($event->{$d}) && $event->{$d} != '0000-00-00 00:00:00') ? with(new Carbon($event->{$d}))->format('YmdHis') : '00010101000000';
            }
            $xml['events'][] = $e;
        }

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

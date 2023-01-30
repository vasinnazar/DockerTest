<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;


/**
 * Class Debtor
 * @package App
 *
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 */

class Debtor extends Model
{

    protected $table = 'debtors.debtors';
    protected $fillable = ['od', 'pc', 'exp_pc', 'fine', 'tax', 'customer_id_1c', 'loan_id_1c', 'is_debtor'];

    public static function getDebtorsWithEmptyCustomer($offset, $limit = 50)
    {
        $debtors = DB::select(DB::raw('SELECT debtors.debtors.debtor_id_1c, debtors.debtors.passport_series, debtors.debtors.passport_number, debtors.debtors.customer_id_1c, debtors.debtors.loan_id_1c FROM debtors.debtors 
                                        left join debtors.loans on debtors.loans.id_1c = debtors.debtors.loan_id_1c
                                        left join debtors.claims on debtors.loans.claim_id = debtors.claims.id
                                        left join debtors.passports on debtors.passports.id = debtors.claims.passport_id
                                        where debtors.passports.fio is null
                                        order by debtors.debtors.id asc
                                        limit ' . $offset . ', ' . $limit));
        return $debtors;
    }

    public static function countEmptyDebtors()
    {
        $debtors = DB::select(DB::raw('SELECT count(*) as num FROM debtors.debtors 
                                        left join debtors.loans on debtors.loans.id_1c = debtors.debtors.loan_id_1c
                                        left join debtors.claims on debtors.loans.claim_id = debtors.claims.id
                                        left join debtors.passports on debtors.passports.id = debtors.claims.passport_id
                                        where debtors.passports.fio is null'));
        return $debtors[0]->num;
    }

    public function isEmptyCustomer()
    {
        return (count(DB::select(DB::raw("SELECT debtors.debtors.id FROM debtors.debtors 
                                        left join armf.loans on armf.loans.id_1c = debtors.debtors.loan_id_1c
                                        left join armf.claims on armf.loans.claim_id = armf.claims.id
                                        left join armf.passports on armf.passports.id = armf.claims.passport_id
                                        where armf.passports.fio is null
                                        and debtors.debtors.id='" . $this->id . "'"))) > 0) ? true : false;
    }

    public function debtorLogs()
    {
        return $this->hasMany('\App\DebtorLog', 'debtor_id');
    }

    public function courtOrder()
    {
        return $this->hasOne('App\CourtOrder');
    }

    public function debtorEventLogs()
    {
        $data = DebtorLog::leftJoin('debtor_events', 'debtor_events.id', '=',
            'debtor_logs.debtor_event_id')->where('debtor_events.debtor_id', $this->id)->get();
        return (!is_null($data)) ? $data : [];
    }

    /**
     * Получает логи по должнику вместе с логами по мероприятиям связанным с этим должником
     * @return type
     */
    public function getAllDebtorLogs()
    {
        $arDebtGroups = DebtGroup::getDebtGroups();

        $data = DB::table('debtor_logs')
            ->select([
                'users.name as username',
                'debtor_logs.created_at as debtor_log_created_at',
                'debtor_logs.before as before',
                'debtor_logs.after as after',
                'debtor_logs.debtor_id as debtor_id',
                'debtor_logs.debtor_event_id as debtor_event_id'
            ])
            ->leftJoin('debtor_events', 'debtor_events.id', '=', 'debtor_logs.debtor_event_id')
            ->leftJoin('users', 'debtor_logs.user_id', '=', 'users.id')
            ->where('debtor_logs.debtor_id', $this->id)
            ->orWhere('debtor_events.debtor_id', $this->id)
            ->orderBy('debtor_logs.created_at')
            ->get();
        foreach ($data as $item) {
            $item->before = json_decode($item->before);
            $item->after = json_decode($item->after);
            foreach ([$item->before, $item->after] as $json) {
                if (is_null($json)) {
                    continue;
                }
                foreach ($json as $k => $v) {
                    if ($k == 'event_type_id') {
                        $json->{$k} = (array_key_exists($v,
                            config('debtors.event_types'))) ? config('debtors.event_types')[$v] : $v;
                    }
                    if ($k == 'overdue_reason_id') {
                        $json->{$k} = (array_key_exists($v,
                            config('debtors.overdue_reasons'))) ? config('debtors.overdue_reasons')[$v] : $v;
                    }
                    if ($k == 'event_result_id') {
                        $json->{$k} = (array_key_exists($v,
                            config('debtors.event_results'))) ? config('debtors.event_results')[$v] : $v;
                    }
                    if ($k == 'debt_group_id') {
                        $json->{$k} = (array_key_exists($v, $arDebtGroups)) ? $arDebtGroups[$v] : $v;
                    }
                    if ($k == 'user_id') {
                        $user = User::find($v);
                        $json->{$k} = (!is_null($user)) ? $user->name : $v;
                    }
                    if ($k == 'last_user_id') {
                        $user = User::find($v);
                        $json->{$k} = (!is_null($user)) ? $user->name : $v;
                    }
                    if ($k == 'completed') {
                        $json->{$k} = ($v == 1) ? 'Завершено' : 'Не завершено';
                    }
                }
            }
            $item->debtor_log_created_at = with(new Carbon($item->debtor_log_created_at))->format('d.m.Y H:i:s');
            if (!is_null($item->debtor_event_id)) {
                $item->doctype = 1;
            } else {
                $item->doctype = 0;
            }
            if (is_null($item->username)) {
                $item->username = '1C';
            }
        }
        return (!is_null($data)) ? $data : [];
    }

    /**
     * Возвращает массив данных с прочими контактами для страницы прочих контактов с контролем уникальности
     * @return type
     */
    public function getContactsData()
    {
        $data = [];
        $arFieldNames = [
            'telephonehome' => 'Домашний телефон',
            'telephoneorganiz' => 'Телефон организации',
            'telephonerodstv' => 'Телефон родственников',
            'anothertelephone' => 'Телефон другой',
            'recomend_phone_1' => 'Телефон рекомендаций 1',
            'recomend_phone_2' => 'Телефон рекомендаций 2',
            'recomend_phone_3' => 'Телефон рекомендаций 3',
            'claim_date' => 'Дата заявки'
        ];
        Config::set('database.default', 'arm');
        $contacts = \App\Claim::leftJoin('about_clients', 'about_clients.id', '=', 'claims.about_client_id')
            ->leftJoin('customers', 'customers.id', '=', 'claims.customer_id')
            ->where('customers.id_1c', $this->customer_id_1c)
            ->select([
                'claims.created_at as claim_date',
                'about_clients.telephonehome',
                'about_clients.telephoneorganiz',
                'about_clients.telephonerodstv',
                'about_clients.anothertelephone',
                'about_clients.recomend_phone_1',
                'about_clients.recomend_phone_2',
                'about_clients.recomend_phone_3'
            ])
            ->orderBy('claims.created_at', 'desc')
            ->get()
            ->toArray();
        if (count($contacts) > 0) {
            $firstClaim = $contacts[0];
            foreach ($contacts as $con) {
                $item = [];
                foreach ($con as $k => $v) {
                    if ($con != $firstClaim && $k != 'claim_date') {
                        $foundInFirstClaim = false;
                        foreach ($firstClaim as $fk => $fv) {
                            if ($fv == $v) {
                                $foundInFirstClaim = true;
                                break;
                            }
                        }
                        if (!$foundInFirstClaim) {
                            $item[$k]['name'] = $arFieldNames[$k];
                            $item[$k]['value'] = $v;
                        }
                    } else {
                        $item[$k]['name'] = $arFieldNames[$k];
                        $item[$k]['value'] = $v;
                    }
                }
                $data[] = $item;
            }
        }
        return $data;
    }

    public function loan()
    {
        $loan_id = Loan::leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
            ->leftJoin('customers', 'customers.id', '=', 'claims.customer_id')
            ->where('customers.id_1c', $this->customer_id_1c)
            ->where('loans.id_1c', $this->loan_id_1c)
            ->select('loans.id')->first();
        if (!is_null($loan_id)) {
            return Loan::find($loan_id->id);
        } else {
            return null;
        }
    }

    /**
     * Получить все кредитники по должнику
     * @return type
     */
    public function getAllLoans()
    {
        $customer = $this->customer();
        $loansList = DB::connection('arm')->table('loans')
            ->select(DB::raw('loans.created_at as loan_created_at,loans.id as loan_id,loans.id_1c as arm_loan_id_1c,passports.fio as fio,users.name as user_name,subdivisions.name as subdiv_name,loans.closed as closed, passports.series as pseries, passports.number as pnumber'))
            ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
            ->leftJoin('customers', 'customers.id', '=', 'claims.customer_id')
            ->leftJoin('passports', 'passports.id', '=', 'claims.passport_id')
            ->leftJoin('subdivisions', 'subdivisions.id', '=', 'loans.subdivision_id')
            ->leftJoin('users', 'users.id', '=', 'loans.user_id')
            ->where('customers.id_1c', $this->customer_id_1c)
            ->orderBy('loans.created_at', 'desc')
            ->get();
        \PC::debug($loansList, 'loanslist');
//        $loans = json_decode(json_encode($loansList),true);
//        $loans = DB::connection('arm')->table('loans')->whereIn('id', $loansList)->orderBy('created_at', 'desc')->get();
//        \PC::debug($loans,'loans');
        return $loansList;
    }

    public function customer()
    {
        return Customer::where('id_1c', $this->customer_id_1c)->first();
    }

    static function getOverall($user = false)
    {
        $arResponsibleUserIds = DebtorUsersRef::getUserRefs();

        $usersDebtors = User::select('users.id_1c')
            ->whereIn('id', $arResponsibleUserIds);

        $arUsersDebtors = $usersDebtors->get()->toArray();
        $arIn = [];
        foreach ($arUsersDebtors as $tmpUser) {
            $arIn[] = $tmpUser['id_1c'];
        }

        $items = DB::table('debtors')->select(DB::raw('base, count(*) as num, sum(sum_indebt) as sum_debt,sum(od) as sum_od'))->groupBy('base');

        if ($user) {
            $items->where('responsible_user_id_1c', $user->id_1c);
        }

        if (count($arIn) && !$user) {
            $items->whereIn('responsible_user_id_1c', $arIn);
        }

        $items = $items->get();

        $res = [
            'total' => [
                'base' => 'Общий итог',
                'num' => 0,
                'sum_debt' => 0,
                'sum_od' => 0
            ]
        ];
        foreach ($items as $item) {
            $item->sum_debt = number_format($item->sum_debt / 100, 2, '.', '');
            $item->sum_od = number_format($item->sum_od / 100, 2, '.', '');
            $res['total']['num'] += $item->num;
            $res['total']['sum_debt'] += $item->sum_debt;
            $res['total']['sum_od'] += $item->sum_od;
        }
        $res['items'] = $items;
        return $res;
    }

    static function getFields()
    {
        return [
            'id' => 'ИД',
            'customer_id_1c' => 'Номер контрагента',
            'loan_id_1c' => 'Номер договора',
            'is_debtor' => 'Должник',
            'od' => 'ОД',
            'pc' => 'Проценты',
            'exp_pc' => 'Пр. проценты',
            'fine' => 'Пеня',
            'tax' => 'Гос. пошлина',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления в АРМ',
            'last_doc_id_1c' => 'Номер последнего документа',
            'base' => 'База',
            'responsible_user_id_1c' => 'Ответственный',
            'fixation_date' => 'Дата закрепления',
            'refresh_date' => 'Дата обновления',
            'qty_delays' => 'Кол-во дней просрочки',
            'sum_indebt' => 'Сумма задолженности',
            'debt_group' => 'Группа задолженности',
            'debtor_id_1c' => 'Номер должника',
            'last_user_id' => 'ИД последнего редактировавшего',
            'passport_series' => 'Серия паспорта',
            'passport_number' => 'Номер паспорта',
            'str_podr' => 'Структурное подразделение',
            'uploaded' => 'Контрагент загружен',
        ];
    }

    static function getSearchFields()
    {
        $currentUser = auth()->user();
        $by_address = ($currentUser->hasRole('debtors_personal')) ? 'address_city' : 'fact_address_city';
        return [
            [
                'name' => 'debtors@fixation_date',
                'input_type' => 'date',
                'label' => 'Дата закрепления'
            ],
            [
                'name' => 'passports@fio',
                'input_type' => 'text',
                'label' => 'ФИО',
                'hidden_value_field' => 'passports@id'
            ],
            [
                'name' => 'debtors@loan_id_1c',
                'input_type' => 'text',
                'label' => 'Договор',
                //'hidden_value_field' => 'debtors@loan_id_1c'
            ],
            [
                'name' => 'debtors@qty_delays_from',
                'input_type' => 'number',
                'label' => 'Дней просрочки, от'
            ],
            [
                'name' => 'debtors@qty_delays_to',
                'input_type' => 'number',
                'label' => 'Дней просрочки, до'
            ],
            [
                'name' => 'debtors@sum_indebt',
                'input_type' => 'text',
                'label' => 'Сумма задолженности'
            ],
            [
                'name' => 'debtors@od',
                'input_type' => 'text',
                'label' => 'Сумма ОД'
            ],
            [
                'name' => 'debtors@base',
                'input_type' => 'text',
                'label' => 'База',
                'hidden_value_field' => 'debtors@base'
            ],
            [
                'name' => 'customers@telephone',
                'input_type' => 'text',
                'label' => 'Телефон'
            ],
            [
                'name' => 'other_phones@phone',
                'input_type' => 'text',
                'label' => 'Телефон прочий'
            ],
            [
                'name' => 'debt_groups@name',
                'input_type' => 'text',
                'label' => 'Группа долга',
                'hidden_value_field' => 'debt_groups@id'
            ],
            [
                'name' => 'passports@address_region',
                'input_type' => 'text',
                'label' => 'Регион (прописка)',
            ],
            [
                'name' => 'passports@fact_address_region',
                'input_type' => 'text',
                'label' => 'Регион (фактический)',
            ],
            [
                'name' => 'passports@' . $by_address,
                'input_type' => 'text',
                'label' => 'Город',
                //'hidden_value_field' => 'passports@fact_address_city'
            ],
            [
                'name' => 'users@name',
                'input_type' => 'text',
                'label' => 'Ответственный',
                'hidden_value_field' => 'users@id_1c'
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
                'name' => 'struct_subdivisions@name',
                'input_type' => 'text',
                'label' => 'Структурное подразделение',
                'hidden_value_field' => 'struct_subdivisions@id_1c'
            ],
        ];
    }

    public static function changeLoadStatus($debtor_id)
    {
        $debtor = Debtor::where('id', $debtor_id)->first();
        if (!is_null($debtor)) {
            $debtor->uploaded = ($debtor->uploaded == 1) ? 0 : 1;
            $debtor->save();
            return true;
        }

        return false;
    }

    public static function checkForPaymentInArm()
    {
        $after = Option::getByName('last_debtor_payments_update_date', '2017-07-03 00:00:00');
        $cols = [
            'passports.series as passport_series',
            'passports.number as passport_number',
            'orders.money as orderMoney',
            'orders.created_at as orderDate',
            'orders.number as orderNumber',
            'loans.id_1c as loan_id_1c'
        ];
        $arm_orders = DB::connection('arm')
            ->table('orders')
            ->select($cols)
            ->leftJoin('passports', 'passports.id', '=', 'orders.passport_id')
            ->leftJoin('loans', 'loans.id', '=', 'orders.loan_id')
            ->where('orders.created_at', '>=', $after)
            ->get();
        foreach ($arm_orders as $order) {
            \PC::debug($order);
            $debtor = Debtor::where('passport_series', $order->passport_series)->where('passport_number',
                $order->passport_number)->where('loan_id_1c', $order->loan_id_1c)->first();
            if (!is_null($debtor)) {
                $user = $debtor->getUser();
                if (!is_null($user)) {
                    Message::addPaymentMessage($debtor, $order->orderNumber, $order->orderMoney, $order->orderDate,
                        $user->id);
                }
            }
        }
        Option::updateByName('last_debtor_payments_update_date', Carbon::now()->format('Y-m-d H:i:s'));
    }

    public static function updateDebtorOnClose($customer_id_1c, $loan_id_1c)
    {
        try {
            DB::connection('debtors')->table('debtors')->where('customer_id_1c', $customer_id_1c)->where('loan_id_1c',
                $loan_id_1c)->update(['is_debtor' => 0, 'base' => 'Архив ЗД']);
            Log::info('Debtor.updateDebtorOnClose', ['customer_id_1c' => $customer_id_1c, 'loan_id_1c' => $loan_id_1c]);
        } catch (Exception $ex) {
            Log::error('Debtor.updateDebtorOnClose',
                ['customer_id_1c' => $customer_id_1c, 'loan_id_1c' => $loan_id_1c, 'ex' => $ex]);
        }
    }

    /**
     * @return bool
     */
    public function printCourtOrder()
    {
        return
            (($this->debt_group_id == DebtGroup::DIFFICULT || $this->debt_group_id == DebtGroup::HOPLESS)
                && $this->qty_delays >= 95 && is_null($this->courtOrder));
    }

    public function scopeByQty($query, int $qtyStart = null, int $qtyEnd = null)
    {
        $query = $qtyStart ? $query->where('qty_delays', '>=', $qtyStart) : $query;
        $query = $qtyEnd ? $query->where('qty_delays', '<=', $qtyEnd) : $query;
        return $query;
    }

    public function scopeByFixation($query, Carbon $dateStart = null, Carbon $dateEnd = null)
    {
        $query = $dateStart ? $query->where('fixation_date', '>=', $dateStart) : $query;
        $query = $dateEnd ? $query->where('fixation_date', '<=', $dateEnd) : $query;
        return $query;

    }
}

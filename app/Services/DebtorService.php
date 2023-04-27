<?php

namespace App\Services;

use App\Customer;
use App\Debtor;
use App\DebtorEvent;
use App\DebtorUsersRef;
use App\Passport;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DebtorService
{
    public function getForgottenById1c(User $user,string $id1c = null)
    {
        $structSubdivision = false;

        if ($user->isDebtorsPersonal()) {
            $structSubdivision = '000000000007';
        }

        if ($user->isDebtorsRemote()) {
            $structSubdivision = '000000000006';
        }

        if (!$structSubdivision) {
            return redirect()->backWithErr('Вы не привязаны к структурным подразделениям взыскания.');
        }

        $debtors = Debtor::where('is_debtor', 1)
            ->whereNotNull('forgotten_date')
            ->where('str_podr', $structSubdivision);

        if ($id1c && $user->hasRole('debtors_chief')) {
            $debtors->where('responsible_user_id_1c', $id1c);
        } else {
            if ($user->hasRole('debtors_chief')) {
                $arResponsibleUserIds = DebtorUsersRef::getUserRefs();
                $usersDebtors = User::where('banned', 0)
                    ->whereIn('id', $arResponsibleUserIds)
                    ->get()
                    ->pluck('id_1c')
                    ->toArray();

                $debtors->whereIn('debtors.responsible_user_id_1c', $usersDebtors);
            } else {
                $debtors->where('debtors.responsible_user_id_1c', $user->id_1c);
            }
        }
        return $debtors->get();
    }

    private function getTableColumns($forPersonalDepartment = false)
    {
        if ($forPersonalDepartment) {
            return [
                'debtors.fixation_date' => 'debtors_fixation_date',
                'debtors.debtors_events_promise_pays.promise_date' => 'debtors_promise_date',
                'debtors.passports.fio' => 'passports_fio',
                'debtors.debtors.customer_id_1c' => 'debtor_customer_id_1c',
                'debtors.loan_id_1c' => 'debtors_loan_id_1c',
                'debtors.qty_delays' => 'debtors_qty_delays',
                'debtors.sum_indebt' => 'debtors_sum_indebt',
                'debtors.od' => 'debtors_od',
                'debtors.base' => 'debtors_base',
                'debtors.customers.telephone' => 'customers_telephone',
                'debtors.debt_groups.name' => 'debtors_debt_group_id',
                'debtors.id' => 'debtors_id',
                'debtors.users.name' => 'debtors_username',
                'debtors.debtor_id_1c' => 'debtor_id_1c',
                'debtors.struct_subdivisions.name' => 'debtor_str_podr',
                'debtors.passports.id' => 'passport_id',
                'debtors.debt_group_id' => 'debtors_debt_group',
                'debtors.responsible_user_id_1c' => 'debtors_responsible_user_id_1c',
                'debtors.is_bigmoney' => 'debtor_is_bigmoney',
                'debtors.is_pledge' => 'debtor_is_pledge',
                'debtors.is_pos' => 'debtor_is_pos',
                'debtors.subdivisions.is_lead' => 'debtor_is_online',
                'debtors.debtors.od_after_closing' => 'debtors_od_after_closing',
                'debtors.passports.fact_timezone' => 'passports_fact_timezone'
            ];
        }
        return [
            'debtors.fixation_date' => 'debtors_fixation_date',
            'debtors.passports.fio' => 'passports_fio',
            'debtors.debtors.customer_id_1c' => 'debtor_customer_id_1c',
            'debtors.loan_id_1c' => 'debtors_loan_id_1c',
            'debtors.qty_delays' => 'debtors_qty_delays',
            'debtors.sum_indebt' => 'debtors_sum_indebt',
            'debtors.od' => 'debtors_od',
            'debtors.base' => 'debtors_base',
            'debtors.customers.telephone' => 'customers_telephone',
            'debtors.debt_groups.name' => 'debtors_debt_group_id',
            'debtors.id' => 'debtors_id',
            'debtors.users.name' => 'debtors_username',
            'debtors.debtor_id_1c' => 'debtor_id_1c',
            'debtors.struct_subdivisions.name' => 'debtor_str_podr',
            'debtors.uploaded' => 'uploaded',
            'debtors.debt_group_id' => 'debtors_debt_group',
            'debtors.responsible_user_id_1c' => 'debtors_responsible_user_id_1c',
            'debtors.is_bigmoney' => 'debtor_is_bigmoney',
            'debtors.is_pledge' => 'debtor_is_pledge',
            'debtors.is_pos' => 'debtor_is_pos',
            'debtors.subdivisions.is_lead' => 'debtor_is_online',
            'debtors.debtors.od_after_closing' => 'debtors_od_after_closing',
            'debtors.passports.fact_timezone' => 'passports_fact_timezone'
        ];
    }

    public function getDebtors($req, $forPersonalDepartment = false)
    {
        $cols = [];
        $tCols = $this->getTableColumns($forPersonalDepartment);
        foreach ($tCols as $k => $v) {
            $cols[] = $k . ' as ' . $v;
        }
        $arResponsibleUserIds = DebtorUsersRef::getUserRefs();
        $usersId1c = User::where('banned', 0)
            ->whereIn('id', $arResponsibleUserIds)
            ->get()
            ->pluck('id_1c')
            ->toArray();


        $input = $req->input();
        $by_address = (auth()->user()->isDebtorsPersonal()) ? 'address_city' : 'fact_address_city';

        $filterFields = [
            'search_field_debtors@fixation_date',
            'search_field_debtors_events_promise_pays@promise_date',
            'search_field_passports@id',
            'search_field_debtors@loan_id_1c',
            'search_field_debtors@qty_delays_from',
            'search_field_debtors@qty_delays_to',
            'search_field_debtors@sum_indebt',
            'search_field_debtors@base',
            'search_field_debtors@od',
            'search_field_customers@telephone',
            'search_field_other_phones@phone',
            'search_field_debtors@debt_group',
            'search_field_passports@' . $by_address,
            'search_field_users@id_1c',
            'search_field_passports@series',
            'search_field_passports@number',
            'search_field_struct_subdivisions@id_1c',
            'search_field_debt_groups@id',
            'search_field_passports@fact_timezone',
        ];

        $boolSearchAll = false;
        $arrFields = [];
        foreach ($filterFields as $fieldName) {
            $tmpVarValue = $req->get($fieldName);
            if (!is_null($tmpVarValue) && !empty($tmpVarValue)) {
                $arrFields[$fieldName]['value'] = $tmpVarValue;
                $arrFields[$fieldName]['condition'] = (empty($req->get($fieldName . '_condition'))) ? '=' : $req->get($fieldName . '_condition');
                if (!is_null($arrFields[$fieldName]['value']) && mb_strlen($arrFields[$fieldName]['value'])) {
                    $boolSearchAll = true;
                }
            }
        }

        $debtors = Debtor::select($cols)
            ->leftJoin('debtors.loans', 'debtors.loans.id_1c', '=', 'debtors.loan_id_1c')
            ->leftJoin('debtors.subdivisions', 'debtors.subdivisions.id', '=', 'debtors.loans.subdivision_id')
            ->leftJoin('debtors.claims', 'debtors.claims.id', '=', 'debtors.loans.claim_id')
            ->leftJoin('debtors.customers', 'debtors.customers.id', '=', 'debtors.claims.customer_id')
            ->leftJoin('debtors.passports', function ($join) {
                $join->on('debtors.passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('debtors.passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->leftJoin('debtors.users', 'debtors.users.id_1c', '=', 'debtors.debtors.responsible_user_id_1c')
            ->leftJoin('debtors.struct_subdivisions', 'debtors.struct_subdivisions.id_1c', '=',
                'debtors.debtors.str_podr')
            ->leftJoin('debtors.debt_groups', 'debtors.debt_groups.id', '=', 'debtors.debtors.debt_group_id')
            ->leftJoin('debtors.debtors_events_promise_pays',
                'debtors.debtors_events_promise_pays.debtor_id', '=', 'debtors.id')
            ->groupBy('debtors.id');

        if (isset($input['search_field_passports@fact_address_region']) && mb_strlen($input['search_field_passports@fact_address_region'])) {
            $debtors->where('debtors.passports.fact_address_region', 'like',
                '%' . $input['search_field_passports@fact_address_region'] . '%');
        }

        if (isset($input['search_field_passports@address_region']) && mb_strlen($input['search_field_passports@address_region'])) {
            $debtors->where('debtors.passports.address_region', 'like',
                '%' . $input['search_field_passports@address_region'] . '%');
        }

        if (isset($arrFields['search_field_passports@fact_timezone']) && mb_strlen($arrFields['search_field_passports@fact_timezone']['value'])) {
            $debtors->where('debtors.passports.fact_timezone',
                $arrFields['search_field_passports@fact_timezone']['condition'],
                $arrFields['search_field_passports@fact_timezone']['value']
            );
        }


        if ($boolSearchAll) {
            foreach ($arrFields as $key => $arrField) {
                if ($key == 'search_field_planned_departures@debtor_id') {

                    $debtors->leftJoin('debtors.planned_departures', 'debtors.planned_departures.debtor_id', '=',
                        'debtors.debtors.id');
                    $debtors->whereNotNull('debtors.planned_departures.debtor_id');
                    $debtors->whereIn('debtors.responsible_user_id_1c', $usersId1c);
                    continue;
                }
                if ($key == 'search_field_debtors@qty_delays_from') {
                    $debtors->where('debtors.debtors.qty_delays', '>=', $arrField['value']);
                    continue;
                }
                if ($key == 'search_field_debtors@qty_delays_to') {
                    $debtors->where('debtors.debtors.qty_delays', '<=', $arrField['value']);
                    continue;
                }
                if ($key == 'search_field_debt_groups@id') {
                    $debtors->where('debtors.debtors.debt_group_id', $arrField['value']);
                    continue;
                }
                if ($key == 'search_field_debtors@fixation_date') {
                    if ($arrField['condition'] == '=') {
                        $sDate = new Carbon($arrField['value']);
                        $debtors->whereBetween('debtors.debtors.fixation_date', array(
                            $sDate->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                            $sDate->setTime(23, 59, 59)->format('Y-m-d H:i:s')
                        ));
                        continue;
                    }
                }
                if ($key == 'search_field_debtors_events_promise_pays@promise_date') {
                    if ($arrField['condition'] == '=') {
                        logger(1);
                        $sDate = new Carbon($arrField['value']);
                        $debtors->whereBetween('debtors.debtors_events_promise_pays.promise_date', array(
                            $sDate->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                            $sDate->setTime(23, 59, 59)->format('Y-m-d H:i:s')
                        ));
                        continue;
                    }
                }
                if ($key == 'search_field_customers@telephone') {
                    if (isset($arrFields['search_field_customers@telephone'])) {
                        $debtors->where('debtors.customers.telephone', $arrField['condition'], $arrField['value']);
                    }
                    continue;
                }
                if ($key == 'search_field_other_phones@phone' && isset($arrFields['search_field_other_phones@phone'])) {
                    $debtors->leftJoin('debtors.debtors_other_phones', 'debtors.debtors_other_phones.debtor_id_1c', '=',
                        'debtors.debtors.debtor_id_1c');

                    if ($arrField['condition'] == 'like') {
                        $arrField['value'] = '%' . $arrField['value'] . '%';
                    } else {
                        $arrField['condition'] = '=';
                    }
                    $debtors->where('debtors.debtors_other_phones.phone', $arrField['condition'], $arrField['value']);
                    continue;
                }
                $tmpStr = str_replace('search_field_', '', $key);
                $arrDbData = explode('@', $tmpStr);
                $tblName = (isset($arrDbData[0])) ? $arrDbData[0] : '';
                $colName = (isset($arrDbData[1])) ? $arrDbData[1] : '';

                $condition = ($arrField['condition'] == 'подобно') ? 'like' : $arrField['condition'];
                $valField = ($condition == 'like') ? '%' . $arrField['value'] . '%' : $arrField['value'];

                if (!empty($tblName) && !empty($colName)) {
                    if ($colName == 'od' || $colName == 'sum_indebt') {
                        $valField = $valField * 100;
                    }

                    $debtors->where('debtors.' . $tblName . '.' . $colName, $condition, $valField);
                }
            }
            if ((count($arrFields) == 1 && array_key_exists('search_field_passports@fact_address_city', $arrFields))) {
                $debtors->whereIn('debtors.responsible_user_id_1c', $usersId1c);
            }
        } else {
            // если по какой-то причине массив с ответственными будет пустым - выводим всех
            if (count($usersId1c)) {
                $debtors->whereIn('debtors.responsible_user_id_1c', $usersId1c);
            }
        }

        $is_bigmoney = (isset($input['search_field_debtors@is_bigmoney']) && $input['search_field_debtors@is_bigmoney'] == 1) ? 1 : 0;
        $is_pledge = (isset($input['search_field_debtors@is_pledge']) && $input['search_field_debtors@is_pledge'] == 1) ? 1 : 0;
        $is_pos = (isset($input['search_field_debtors@is_pos']) && $input['search_field_debtors@is_pos'] == 1) ? 1 : 0;

        if ($is_bigmoney || $is_pledge || $is_pos) {
            $debtors->where(function ($query) use ($is_bigmoney, $is_pledge, $is_pos) {
                if ($is_bigmoney) {
                    $query->where('debtors.debtors.is_bigmoney', 1);
                    if ($is_pledge) {
                        $query->orWhere('debtors.debtors.is_pledge', 1);
                    }
                    if ($is_pos) {
                        $query->orWhere('debtors.debtors.is_pos', 1);
                    }
                } else {
                    if ($is_pledge) {
                        $query->where('debtors.debtors.is_pledge', 1);
                        if ($is_pos) {
                            $query->orWhere('debtors.debtors.is_pos', 1);
                        }
                    } else {
                        if ($is_pos) {
                            $query->where('debtors.debtors.is_pos', 1);
                        }
                    }
                }
            });
        }

        return $debtors;
    }

}

<?php

namespace App\Http\Controllers;

use App\Services\RepaymentOfferService;
use Illuminate\Http\Request;
use App\Utils\PermLib;
use App\Permission;
use App\Utils\StrLib;
use App\Debtor;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Utils\HtmlHelper;
use Carbon\Carbon;
use App\User;
use App\MySoap;

class DebtorTransferController extends BasicController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('debtortransfer.index', [
            'debtorTransferFilterFields' => self::getSearchFields()
        ]);
    }

    function getDebtorsTableColumns()
    {
        return [
            'debtors.passports.fio' => 'passports_fio',
            'debtors.od' => 'debtors_od',
            'debtors.base' => 'debtors_base',
            'debtors.passports.fact_address_city' => 'passports_fact_address_city',
            'debtors.passports.fact_address_city1' => 'passports_fact_address_city1',
            'debtors.passports.fact_address_district' => 'passports_fact_address_district',
            'debtors.passports.fact_address_street' => 'passports_fact_address_street',
            'debtors.passports.fact_address_house' => 'passports_fact_address_house',
            'debtors.fixation_date' => 'debtors_fixation_date',
            'debtors.qty_delays' => 'debtors_qty_delays',
            'debtors.responsible_user_id_1c' => 'debtors_responsible_user_id_1c',
            'debtors.last_user_id' => 'debtors_last_user_id',
            'debtors.debt_group_id' => 'debtors_debt_group_id',
            'debtors.id' => 'debtors_id',
            'debtors.str_podr' => 'debtors_str_podr'
        ];
    }

    /**
     * Проверяет заполнены ли поля поиска в таблице
     * @param array $input
     * @return boolean
     */
    function hasFilterFilled($input)
    {
        $filled = false;
        foreach ($input as $k => $v) {
            if (((strpos($k, 'search_field_') === 0 && strpos($k,
                            '_condition') === false) || $k == 'users@login' || $k == 'debtors@base') && !empty($v)) {
                $filled = true;
                return $filled;
            }
        }
        return $filled;
    }

    public function ajaxList(Request $req)
    {
        $cols = [];
        $tCols = $this->getDebtorsTableColumns();
        foreach ($tCols as $k => $v) {
            $cols[] = $k . ' as ' . $v;
        }

        $input = $req->input();

        $debtors = Debtor::select($cols)
            ->leftJoin('debtors.debt_groups', 'debtors.debt_groups.id', '=', 'debtors.debt_group_id')
            ->leftJoin('debtors.passports', function ($join) {
                $join->on('debtors.passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('debtors.passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->leftJoin('users', 'users.id_1c', '=', 'debtors.responsible_user_id_1c')
            ->leftJoin('struct_subdivisions', 'struct_subdivisions.id_1c', '=', 'debtors.str_podr')
            ->groupBy('debtors.id');

        if (!$this->hasFilterFilled($input)) {
            $debtors->where('debtors.debtors.id', 0);
        }

        if (isset($input['overdue_from']) && mb_strlen($input['overdue_from'])) {
            $debtors->where('qty_delays', '>=', $input['overdue_from']);
        }
        if (isset($input['overdue_till']) && mb_strlen($input['overdue_till'])) {
            $debtors->where('qty_delays', '<=', $input['overdue_till']);
        }
        if (isset($input['passports@fact_address_region']) && mb_strlen($input['passports@fact_address_region'])) {
            $debtors->where('passports.fact_address_region', 'like',
                '%' . $input['passports@fact_address_region'] . '%');
        }
        if (isset($input['passports@fact_address_district']) && mb_strlen($input['passports@fact_address_district'])) {
            $debtors->where('passports.fact_address_district', 'like',
                '%' . $input['passports@fact_address_district'] . '%');
        }

        if (isset($input['search_field_debtors@kratnost']) && $input['search_field_debtors@kratnost'] == 1) {
            $debtors = $debtors->where('kratnost', 1);
        }

        $is_online = (isset($input['search_field_debtors@is_lead']) && $input['search_field_debtors@is_lead'] == 1) ? 1 : 0;

        $is_bigmoney = (isset($input['search_field_debtors@is_bigmoney']) && $input['search_field_debtors@is_bigmoney'] == 1) ? 1 : 0;
        $is_pledge = (isset($input['search_field_debtors@is_pledge']) && $input['search_field_debtors@is_pledge'] == 1) ? 1 : 0;
        $is_pos = (isset($input['search_field_debtors@is_pos']) && $input['search_field_debtors@is_pos'] == 1) ? 1 : 0;

        if ($is_online) {
            $debtors->leftJoin('debtors.subdivisions', 'debtors.subdivisions.id', '=', 'debtors.loans.subdivision_id');
            $debtors->where('debtors.subdivisions.is_lead', 1);
        }

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
        foreach ($input as $k => $v) {
            if (strpos($k, 'search_field_') === 0 && strpos($k, '_condition') === false && !empty($v)) {
                $fieldName = str_replace('search_field_', '', $k);
                $tableName = substr($fieldName, 0, strpos($fieldName, '@'));
                $colName = substr($fieldName, strlen($tableName) + 1);
                $condColName = $k . '_condition';
                $condition = (array_key_exists($condColName, $input)) ? $input[$condColName] : '=';
                if ($condition == 'like') {
                    $v = '%' . $v . '%';
                }
                $debtors->where($tableName . '.' . $colName, $condition, $v);
            }
        }
        $debtors = $debtors->get();
        return DataTables::of($debtors)
            ->editColumn('debtors_fixation_date', function ($item) {
                return (!is_null($item->debtors_fixation_date)) ? date('d.m.Y',
                    strtotime($item->debtors_fixation_date)) : '-';
            })
            ->editColumn('debtors_od', function ($item) {
                return number_format($item->debtors_od / 100, 2, '.', '');
            })
            ->editColumn('debtors_debt_group_id', function ($item) {
                return (array_key_exists($item->debtors_debt_group_id,
                    config('debtors')['debt_groups'])) ? config('debtors')['debt_groups'][$item->debtors_debt_group_id] : '';
            })
            ->editColumn('debtors_last_user_id', function ($item) {
                if (!is_null($item->debtors_last_user_id) && mb_strlen($item->debtors_last_user_id)) {
                    $lastUser = User::find($item->debtors_last_user_id);
                    return $lastUser->login;
                } else {
                    return '-';
                }
            })
            ->editColumn('passports_fact_address_city', function ($item) {
                $tmpCity = (empty($item->passports_fact_address_city)) ? $item->passports_fact_address_city1 : $item->passports_fact_address_city;
                $tmpDistrict = (empty($item->passports_fact_address_district) || is_null($item->passports_fact_address_district)) ? '' : $item->passports_fact_address_district . '<br>';
                return $tmpCity . '<br><span style="font-size: 80%; color: #555555; font-style: italic;">' . $tmpDistrict . $item->passports_fact_address_street . ', д. ' . $item->passports_fact_address_house . '</span>';
            })
            ->editColumn('debtors_str_podr', function ($item) {
                switch ($item->debtors_str_podr) {
                    case '000000000007':
                        $struct_podr = 'Отдел личного взыскания';
                        break;

                    case '000000000006':
                        $struct_podr = 'Отдел удаленного взыскания';
                        break;

                    case '0000000000001':
                        $struct_podr = 'СБиВЗ';
                        break;

                    default:
                        $struct_podr = $item->debtors_str_podr;
                        break;
                }

                return $struct_podr;
            })
            ->addColumn('actions', function ($item) {
                $html = '';
                $html .= '<input type="checkbox" name="debtor_transfer_id[]" value="' . $item->debtors_id . '" />';
                return $html;
            }, 0)
            ->addColumn('links', function ($item) {
                $html = '';
                $html .= HtmlHelper::Buttton(url('debtors/debtorcard/' . $item->debtors_id),
                    ['glyph' => 'eye-open', 'size' => 'xs', 'target' => '_blank']);
                return $html;
            }, 1)
            ->removeColumn('debtors_id')
            ->removeColumn('passports_fact_address_city1')
            ->removeColumn('passports_fact_address_district')
            ->removeColumn('passports_fact_address_street')
            ->removeColumn('passports_fact_address_house')
            ->rawColumns(['actions', 'links', 'passports_fact_address_city'])
            ->toJson();
    }

    public function transferHistory(Request $req)
    {
        $date = $req->get('date', date('Y-m-d', time()));

        $transfers = \App\DebtorTransferHistory::where('transfer_time', '>=',
            $date . ' 00:00:00')->where('transfer_time', '<=', $date . ' 23:59:59')->orderBy('row', 'asc')->get();

        $arTransfers = [];

        if (!is_null($transfers)) {
            foreach ($transfers as $transfer) {
                if (!isset($arTransfers[$transfer->row])) {
                    $operation_user = User::find($transfer->operation_user_id);
                    $arTransfers[$transfer->row]['operation_user_name'] = $operation_user->login;
                    $arTransfers[$transfer->row]['operation_number'] = $transfer->row;
                    $arTransfers[$transfer->row]['transfer_time'] = date('d.m.Y H:i:s',
                        strtotime($transfer->transfer_time));
                }
                $arTransfers[$transfer->row]['transfers'][] = $transfer;
            }
            $arSubdivisions = \App\StructSubdivision::get()->pluck('name', 'id_1c')->toArray();

            foreach ($arSubdivisions as $k => $v) {
                $kn = trim($k);
                $arSubdivisions[$kn] = $v;
            }
        }

        return view('debtortransfer.transfers', [
            'arTransfers' => $arTransfers,
            'arSubdivisions' => $arSubdivisions
        ]);
    }

    /**
     * Назначает нового ответственного по должникам и отправляет запрос в 1С с изменениями
     * @param Request $req
     * @return int
     */
    public function changeResponsibleUser(RepaymentOfferService $repaymentOfferService, Request $req)
    {
        $input = $req->input();

        $user = User::find($input['new_user_id']);
        if (is_null($user)) {
            return $this->backWithErr("Новый пользователь указан некорректно.");
        }
        $base = $req->get('base', null);

        if ($user->id == 1901 || $user->id == 1135 || $user->id == 2486 || $user->id == 2860 || $user->id == 951 || $user->id == 3654 || $user->id == 4204) {
            $str_podr = '0000000000001';
        } else {
            if ($user->hasRole('debtors_remote')) {
                $str_podr = '000000000006';
            } else {
                if ($user->hasRole('debtors_personal')) {
                    $str_podr = '000000000007';
                } else {
                    if ($user->id == 1894) {
                        $str_podr = '00000000000010';
                    } else {
                        if ($user->id == 1029) {
                            $str_podr = '';
                        } else {
                            if ($base == 'ХПД') {
                                $str_podr = 'СУЗ';
                            } else {
                                return $this->backWithErr("Некорректное структурное подразделение для нового пользователя.");
                            }
                        }
                    }
                }
            }
        }

        $items = []; // массив для передачи id должников в 1С
        $arr_history = [];
        $debtor_ids = []; // массив для формирования файла с новым ответственным и кодов должников в 1С

        $i = 0;
        $num_1c = 0;

        foreach ($input['debtor_ids'] as $debtor_id) {
            $debtor = Debtor::find($debtor_id);

            $last_user = User::where('id_1c', $debtor->responsible_user_id_1c)->first();
            $last_user_id = (is_null($last_user)) ? null : $last_user->id;

            $arr_history[$i]['responsible_user_id_1c_before'] = $debtor->responsible_user_id_1c;
            $arr_history[$i]['base_before'] = $debtor->base;
            $arr_history[$i]['str_podr_before'] = $debtor->str_podr;
            $arr_history[$i]['fixation_date_before'] = $debtor->fixation_date;

            $arr_history[$i]['debtor_id_1c'] = $debtor->debtor_id_1c;
            $arr_history[$i]['auto_transfer'] = 0;

            if ($debtor->str_podr == '000000000006' && $str_podr == '000000000007') {
                $repaymentOfferService->closeOfferIfExist($debtor);
            }

            $debtor_ids[] = $debtor->id_1c;

            if ($user->id == 951) {
                $debtor->str_podr = '0000000000001';
            } else {
                if ($user->id == 1877 || $user->id == 956) {
                    $debtor->str_podr = 'СУЗ';
                } else {
                    $debtor->str_podr = $str_podr;
                }
            }

            $debtor->last_user_id = $last_user_id;

            $debtor->responsible_user_id_1c = $user->id_1c;
            $debtor->fixation_date = Carbon::now()->format('Y-m-d H:i:s');

            $old_base = $debtor->base;

            if (!empty($base)) {
                $debtor->base = $base;
            }
            $debtor->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
            $debtor->save();

            $items[$num_1c]['number'] = $num_1c;
            $items[$num_1c]['debtor_id'] = $debtor->debtor_id_1c;
            $items[$num_1c]['debtor_base'] = $debtor->base;

            $arr_history[$i]['responsible_user_id_1c_after'] = $debtor->responsible_user_id_1c;
            $arr_history[$i]['base_after'] = $debtor->base;
            $arr_history[$i]['str_podr_after'] = $debtor->str_podr;
            $arr_history[$i]['fixation_date_after'] = $debtor->fixation_date;
            $arr_history[$i]['transfer_time'] = $debtor->refresh_date;

            if ($base == 'Б-3' && $old_base == 'Б-1' && ($debtor->qty_delays >= 59 && $debtor->qty_delays <= 94)) {
                $repaymentOfferService->sendPeaceForUDR($debtor);
            }

            $debtor_unclosed = Debtor::where('customer_id_1c', $debtor->customer_id_1c)->where('is_debtor', 1)->get();
            foreach ($debtor_unclosed as $unclosed) {
                if ($debtor_id == $unclosed->id) {
                    continue;
                }
                $arStopBase = [];
                if ($unclosed->base == 'Архив ЗД' || $unclosed->base == 'З-ДС') {
                    continue;
                }

                $i++;

                $u_last_user = User::where('id_1c', $unclosed->responsible_user_id_1c)->first();
                $unclosed->last_user_id = (is_null($u_last_user)) ? null : $u_last_user->id;

                $arr_history[$i]['responsible_user_id_1c_before'] = $unclosed->responsible_user_id_1c;
                $arr_history[$i]['base_before'] = $unclosed->base;
                $arr_history[$i]['str_podr_before'] = $unclosed->str_podr;
                $arr_history[$i]['fixation_date_before'] = $unclosed->fixation_date;

                $arr_history[$i]['debtor_id_1c'] = $unclosed->debtor_id_1c;
                $arr_history[$i]['auto_transfer'] = 1;

                if ($user->id == 951) {
                    $unclosed->str_podr = '0000000000001';
                } else {
                    if ($user->id == 1877 || $user->id == 956) {
                        $unclosed->str_podr = 'СУЗ';
                    } else {
                        $unclosed->str_podr = $str_podr;
                    }
                }

                $unclosed_old_base = $unclosed->base;

                $unclosed->responsible_user_id_1c = $user->id_1c;
                $unclosed->fixation_date = Carbon::now()->format('Y-m-d H:i:s');
                if (!empty($base)) {
                    if ($user->id_1c == 'Котельникова Е. А.' && $base == 'Б-4' && ($unclosed->base == 'Б-МС' || $unclosed->base == 'Б-риски' || $unclosed->base == 'Б-График' || $unclosed->base == 'КБ-График')) {
                        $unclosed->base = $base;
                    } else {
                        if ($unclosed->base == 'З-ДС' || $unclosed->base == 'Б-КДС') {
                            $unclosed->base = $base;
                        } else {
                            if ($base == 'КБ-График' && ($unclosed->base == 'Б-1' || $unclosed->base == 'Б-3')) {
                                $unclosed->base = $unclosed->base;
                            } else {
                                if ($base == 'КБ-График' && $unclosed->base == 'Б-0') {
                                    $unclosed->base = 'Б-1';
                                } else {
                                    if ($base == 'Б-1' && $unclosed->base == 'КБ-График') {
                                        $unclosed->base = 'КБ-График';
                                    } else {
                                        if ($base == 'Б-3' && $unclosed->base == 'КБ-График') {
                                            $unclosed->base = 'КБ-График';
                                        } else {

                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    if ($unclosed->base == 'Б-0') {
                        $unclosed->base = 'Б-1';
                    }
                }
                $unclosed->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
                $unclosed->save();

                if ($base == 'Б-3' && $unclosed_old_base == 'Б-1' && ($debtor->qty_delays >= 59 && $debtor->qty_delays <= 94)) {

//                    $armf_loan = DB::Table('armf.loans')->where('id_1c', $unclosed->loan_id_1c)->first();
//
//                    if (!is_null($armf_loan)) {
//                        $date_order_from = date('Y-m-d H:i:s', strtotime('-30 day'));
//
//                        $armf_orders = DB::Table('armf.orders')
//                            ->where('loan_id', $armf_loan->id)
//                            ->where('created_at', '>=', $date_order_from)
//                            ->sum('money');
//
//                        $armf_orders_sum = $armf_orders / 100;
//
//                        if (!is_null($armf_orders_sum) && $armf_orders_sum < 500) {
//                            $repaymentOfferService->sendPeaceForUDR($debtor);
//                        }
//                    }
                    $repaymentOfferService->sendPeaceForUDR($debtor);
                }

                $items[$num_1c]['number'] = $num_1c;
                $items[$num_1c]['debtor_id'] = $unclosed->debtor_id_1c;
                $items[$num_1c]['debtor_base'] = $unclosed->base;

                $num_1c++;

                $arr_history[$i]['responsible_user_id_1c_after'] = $unclosed->responsible_user_id_1c;
                $arr_history[$i]['base_after'] = $unclosed->base;
                $arr_history[$i]['str_podr_after'] = $unclosed->str_podr;
                $arr_history[$i]['fixation_date_after'] = $unclosed->fixation_date;
                $arr_history[$i]['transfer_time'] = $unclosed->refresh_date;
            }

            $i++;
            $num_1c++;
        }

        $this->addTransferHistory($arr_history);

        $arSend1c = [
            'type' => 'ChangeRespInDebt',
            'responsible_user_id_1c' => $user->id_1c,
            'data' => $items
        ];

        $str_xml = MySoap::createXML($arSend1c);

        $responseXml = MySoap::sendExchangeArm($str_xml);

        return 1;
    }

    public function getActPdf(Request $req)
    {
        $input = $req->input();
        $html = '<table width="100%" style="border-collapse: collapse;">';

        $html .= '<tr>';
        $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">№</td>';
        $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">Должник</td>';
        $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">Сумма<br>задолженности</td>';
        $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">№ договора</td>';
        $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">Дата<br>договора</td>';
        $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">База</td>';
        $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">Кол-во<br>дней<br>просрочки</td>';
        $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">Ответственный</td>';
        $html .= '</tr>';

        $new_user = User::find($input['new_user_id']);
        $old_user = User::find($input['old_user_id']);

        $cnt = 0;
        foreach ($input['debtor_transfer_id'] as $debtor_id) {
            $cnt++;
            $debtors = Debtor::select(DB::raw('*, debtors.loans.id_1c as loan_id_1c, debtors.loans.created_at as loan_created_at, debtors.passports.fio as passports_fio'))
                ->leftJoin('users', 'users.id_1c', '=', 'debtors.responsible_user_id_1c')
                ->leftJoin('debtors.loans', 'debtors.loans.id_1c', '=', 'debtors.loan_id_1c')
                ->leftJoin('debtors.claims', 'debtors.claims.id', '=', 'debtors.loans.claim_id')
                ->leftJoin('debtors.passports', 'debtors.passports.id', '=', 'debtors.claims.passport_id')
                ->where('debtors.id', $debtor_id);

            $debtordata = $debtors->get()->toArray();

            $html .= '<tr>';
            $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">' . $cnt . '</td>';
            $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">' . $debtordata[0]['passports_fio'] . '</td>';
            $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">' . number_format($debtordata[0]['sum_indebt'] / 100,
                    2, ',', ' ') . '</td>';
            $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">' . $debtordata[0]['loan_id_1c'] . '</td>';
            $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">' . date('d.m.Y',
                    strtotime($debtordata[0]['loan_created_at'])) . '</td>';
            $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">' . $debtordata[0]['base'] . '</td>';
            $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">' . $debtordata[0]['qty_delays'] . '</td>';
            $html .= '<td style="border: 1px #000000 solid; text-align: center; vertical-align: middle; padding: 5px;">' . $new_user->login . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        $contract = \App\ContractForm::where('text_id', 'debtors_transfer')->first();
        if (is_null($contract)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }

        $template = $contract->template;

        $dataForTemplate = [
            'act_number' => $input['act_number'],
            'current_date' => date('d.m.Y', time()),
            'old_user_position' => (!is_null($old_user)) ? $old_user->position : '',
            'old_user_fio' => (!is_null($old_user)) ? $old_user->id_1c : '',
            'new_user_position' => $new_user->position,
            'new_user_fio' => $new_user->id_1c,
            'debtors_table' => $html
        ];

        foreach ($dataForTemplate as $k => $v) {
            $template = str_replace('{{' . $k . '}}', $v, $template);
        }

        return \App\Utils\PdfUtil::getPdf($template);
    }

    public function addTransferHistory($arr_history)
    {
        $current_user = auth()->user();
        $data['operation_user_id'] = $current_user->id;

        $now = date('Y-m-d H:i:s', time());

        $max_row = \App\DebtorTransferHistory::max('row');
        $data['row'] = (is_null($max_row) || $max_row == 0) ? 1 : $max_row + 1;

        if (is_array($arr_history)) {
            foreach ($arr_history as $elem) {
                $history_row = new \App\DebtorTransferHistory();

                $history_row->operation_user_id = $data['operation_user_id'];
                $history_row->row = $data['row'];

                $history_row->debtor_id_1c = $elem['debtor_id_1c'];
                $history_row->transfer_time = $now;
                $history_row->responsible_user_id_1c_before = $elem['responsible_user_id_1c_before'];
                $history_row->responsible_user_id_1c_after = $elem['responsible_user_id_1c_after'];
                $history_row->base_before = $elem['base_before'];
                $history_row->base_after = $elem['base_after'];
                $history_row->str_podr_before = $elem['str_podr_before'];
                $history_row->str_podr_after = $elem['str_podr_after'];
                $history_row->fixation_date_before = $elem['fixation_date_before'];
                $history_row->fixation_date_after = $elem['fixation_date_after'];
                $history_row->auto_transfer = $elem['auto_transfer'];

                $history_row->save();
            }
        }
    }

    static function getSearchFields()
    {
        return [
            [
                'name' => 'debtors@fixation_date',
                'input_type' => 'date',
                'label' => 'Дата'
            ],
            [
                'name' => 'users@login',
                'input_type' => 'text',
                'label' => 'Старый пользователь',
                'hidden_value_field' => 'users@id',
                'field_id' => 'old_user_id'
            ],
            [
                'name' => 'passports@fio',
                'input_type' => 'text',
                'label' => 'Должник',
                'hidden_value_field' => 'passports@id',
                'field_id' => 'passports_id'
            ],
            [
                'name' => 'passports@fact_address_city',
                'input_type' => 'text',
                'label' => 'Город',
                'hidden_value_field' => 'passports@fact_address_city',
                'field_id' => ''
            ],
            [
                'name' => 'passports@fact_address_district',
                'input_type' => 'text',
                'label' => 'Район',
                'field_id' => ''
            ],
            [
                'name' => 'passports@fact_address_region',
                'input_type' => 'text',
                'label' => 'Регион',
                'field_id' => ''
            ],
            [
                'name' => 'debtors@base',
                'input_type' => 'text',
                'label' => 'База',
                'hidden_value_field' => 'debtors@base',
                'field_id' => ''
            ],
            [
                'name' => 'debt_groups@name',
                'input_type' => 'text',
                'label' => 'Группа долга',
                'hidden_value_field' => 'debt_groups@id',
                'field_id' => ''
            ],
            [
                'name' => 'struct_subdivisions@name',
                'input_type' => 'text',
                'label' => 'Подразделение',
                'hidden_value_field' => 'struct_subdivisions@id_1c',
                'field_id' => ''
            ],
        ];
    }

}

<?php

namespace App\Http\Controllers;

use App\Claim,
    Illuminate\Support\Facades\DB,
    Auth,
    Illuminate\Http\Request,
    App\ContractForm,
    App\Loan,
    Yajra\DataTables\Facades\DataTables,
    Carbon\Carbon,
    App\Spylog\Spylog,
    App\MySoap,
    Log,
    App\Utils\HtmlHelper,
    App\Utils\StrLib,
    App\Synchronizer,
    Input,
    App\WorkTime,
    App\Promocode;
use App\Utils\HelperUtil;
use App\Scorista;

class HomeController extends Controller {

    const EDITABLE_STATUSES = [
        Claim::STATUS_NEW, Claim::STATUS_ONEDIT, Claim::STATUS_DINNER,
        Claim::STATUS_BADPHONE, Claim::STATUS_BADWORK, Claim::STATUS_INWORK,
        Claim::STATUS_NOANSWER_CLIENT, Claim::STATUS_BADPASSPORT, Claim::STATUS_NOANSWER_RELATIVES,
        Claim::STATUS_NOANSWER_WORK, Claim::STATUS_NOPASSPORT, Claim::STATUS_NOPASSPORT_DATA,
        Claim::STATUS_PRECONFIRM, Claim::STATUS_REGISTRATION, Claim::STATUS_SPRAVKA,
        Claim::STATUS_TILL_MONDAY, Claim::STATUS_TILL_TOMORROW, Claim::STATUS_SCORISTA,
        Claim::STATUS_TELEPORT, Claim::STATUS_BADDOCS, Claim::STATUS_FINTERRA
    ];

    public function __construct() {
        $this->middleware('auth');
        $this->editableStatuses = HomeController::EDITABLE_STATUSES;
    }

    public function index(Request $req) {
        \App\MysqlThread::addToStat();
        if(config('app.dev')){
            Scorista::checkStatuses();
        }        
        if (!Auth::user()->isAdmin() && !Auth::user()->isCC() && WorkTime::where('user_id', Auth::user()->id)->where('created_at', '>=', Carbon::now()->format('Y-m-d'))->count() == 0) {
            return view('dashboard')->with('show_worktime_form', 1);
        }
        return view('dashboard')->with('show_worktime_form', 0);
    }

    public function startWork() {
        Spylog::log(Spylog::ACTION_LOGIN, 'users', Auth::user()->id);
        return redirect('home')->with('msg_suc', 'Добро пожаловать, ' . Auth::user()->name);
    }

    public function endWork() {
        Spylog::log(Spylog::ACTION_LOGOUT, 'users', Auth::user()->id);
        return redirect(url('/auth/logout'))->with('msg_suc', 'До свиданья, ' . Auth::user()->name);
    }

    public function getComment(Request $req) {
        $claim = Claim::find($req->id);
        if (!is_null($claim)) {
            return (!is_null($claim)) ? $claim->comment : "";
        }
    }

    /**
     * возвращает по аяксу список документов доступных для печати из рабочего стола
     * @param int $claim_id идентификатор заявки
     * @return type
     */
    public function contractsList($claim_id) {
        $res = [];
        $claim = Claim::find($claim_id);
        $loan = Loan::where('claim_id', $claim_id)->first();
        $res[] = ContractForm::where('text_id', config('options.claim_contract'))->first();
        if (!is_null($loan)) {
            $res[] = ContractForm::where('text_id', config('options.grid'))->first();
            if ($loan->uki && $loan->closed) {
                $res[] = ContractForm::where('text_id', config('options.uki_report'))->first();
            }
            if (!$loan->subdivision->is_terminal) {
                if ($loan->claim->about_client->postclient && $loan->created_at->lt(new Carbon(config('options.new_rules_day_010117')))) {
                    if ($loan->in_cash) {
                        $res[] = ContractForm::find($loan->loantype->perm_contract_form_id);
                        if (!is_null($loan->loantype->additional_contract_perm_id)) {
                            if ($loan->created_at->gte(new Carbon(config('options.perm_new_rules_day')))) {
                                $res[] = ContractForm::where('text_id', 'pc_notification_perm_20_05')->first();
                            } else {
                                $res[] = ContractForm::find($loan->loantype->additional_contract_perm_id);
                            }
                        }
                    } else {
                        $res[] = ContractForm::find($loan->loantype->perm_card_contract_form_id);
                        if (!is_null($loan->loantype->additional_card_contract_perm_id)) {
                            if ($loan->created_at->gte(new Carbon(config('options.perm_new_rules_day')))) {
                                $res[] = ContractForm::where('text_id', 'pc_notification_card_20_05')->first();
                            } else {
                                $res[] = ContractForm::find($loan->loantype->additional_card_contract_id);
                            }
                        }
                        $res[] = ContractForm::where('text_id', 'tranche')->first();
                        $res[] = ContractForm::where('text_id','sms_annex')->first();
                    }
                } else {
                    if (!is_null($loan->loantype)) {
                        if ($loan->in_cash) {
                            $res[] = ContractForm::find($loan->loantype->contract_form_id);
                            if (!is_null($loan->loantype->additional_contract_id)) {
                                $res[] = ContractForm::find($loan->loantype->additional_contract_id);
                            }
                        } else {
                            $res[] = ContractForm::find($loan->loantype->card_contract_form_id);
                            if (!is_null($loan->loantype->additional_card_contract_id)) {
                                $res[] = ContractForm::find($loan->loantype->additional_card_contract_id);
                            }
                            $res[] = ContractForm::where('text_id', 'tranche')->first();
                            $res[] = ContractForm::where('text_id','sms_annex')->first();
                        }
                    }
                }
                if (!is_null($loan->order_id)) {
                    if ($loan->in_cash) {
                        $res[] = ContractForm::where('text_id', config('options.orderRKO'))->first();
                    }
                    $res[] = ContractForm::where('text_id', config('options.pay_reminder'))->first();
                }
                if ($loan->uki || $loan->claim->uki) {
                    $res[] = ContractForm::where('text_id', config('options.uki_agreement'))->first();
                }
            }
        }
        $res = [
            'contracts' => $res,
            'comment' => $claim->comment
        ];
        return $res;
    }

    /**
     * возвращает данные по карте для переданных номера карты и идентификатора заявки
     * @param Request $request
     * @return array
     */
    public function getCardData(Request $request) {
        if (is_null($request->card_number) || is_null($request->claim_id) ||
                $request->card_number == '' || $request->claim_id == '') {
            return 'Ошибка! Неправильный номер карты или заявки.';
        }
        $card = DB::table('cards')->where('card_number', $request->card_number)->first();
        $claim = Claim::find($request->claim_id);
        if (!is_null($card) && !is_null($claim) && $card->customer_id != $claim->customer_id) {
            return 'Ошибка! Карта уже оформлена!';
        }
        $cols = ['passports.fio', 'passports.series', 'passports.number',
            'passports.birth_city', 'passports.birth_date', 'customers.telephone',
            'passports.issued', 'passports.issued_date', 'passports.subdivision_code',
            'passports.address_city', 'passports.address_street', 'passports.address_house',
            'passports.address_building', 'passports.address_apartment'];
        $r = DB::table('claims')
                        ->leftJoin('customers', 'claims.customer_id', '=', 'customers.id')
                        ->leftJoin('passports', 'passports.customer_id', '=', 'customers.id')
                        ->select($cols)
                        ->where('claims.id', $request->claim_id)->first();
        $r->birth_date = with(new Carbon($r->birth_date))->format('dmY');
        $r->issued_date = with(new Carbon($r->issued_date))->format('dmY');

        Log::info('HomeController.getCardData', ['res' => $r]);
        return ((array) $r);
    }

    /**
     * возвращает по аяксу номер промокода для кредитника
     * @param int $loan_id идентификатор кредитника
     * @return int
     */
    public function getPromocode($loan_id) {
        $loan = Loan::find($loan_id);

        if (!is_null($loan) && !is_null($loan->promocode)) {
            return $loan->promocode->number;
        } else {
            return null;
        }
    }

    /**
     * возвращает список заявок для таблицы рабочего стола по аяксу
     * @param Request $request
     * @return type
     */
    public function claimsList(Request $request, $getFrom1cOnFail = true)
    {
        $cols = [
            'claims.id as claim_id',
            'claims.created_at',
            'claims.updated_at as claim_updated_at',
            'passports.fio',
            'customers.telephone',
            'claims.summa',
            'claims.srok',
            'loans.id as loan',
            'orders.number as rko',
            'customers.id as customer',
            'claims.status as claimstatus',
            'passports.series as pass_series',
            'passports.number as pass_number',
            'users.subdivision_id as subdivision',
            'loans.enrolled as enrolled',
            'loans.in_cash as in_cash',
            'claims.promocode_id as promocode_id',
            'claims.id_1c as claim_id_1c',
            'claims.seb_phone',
            'subdivisions.name as subdiv_name',
            'users.name as user_name',
            'claims.claimed_for_remove as claim_claimed_for_remove',
            'loans.claimed_for_remove as loan_claimed_for_remove',
            'claims.subdivision_id as claim_subdivision',
            'loans.on_balance as on_balance',
            'subdivisions.is_terminal as is_terminal',
            'claims.comment as claim_comment',
            'loans.uki as loan_uki',
            'claims.uki as claim_uki',
            'loans.closed as loan_closed'
        ];
        $claims = DB::table('claims')
            ->groupBy('claims.id')
            ->distinct()
            ->leftJoin('customers', 'claims.customer_id', '=', 'customers.id')
            ->leftJoin('loans', 'loans.claim_id', '=', 'claims.id')
            ->leftJoin('users', 'claims.user_id', '=', 'users.id')
            ->leftJoin('passports', 'passports.id', '=', 'claims.passport_id')
            ->leftJoin('orders', 'loans.order_id', '=', 'orders.id')
            ->leftJoin('subdivisions', 'subdivisions.id', '=', 'claims.subdivision_id')
            ->select($cols)
            ->limit(25)
            ->where('claims.deleted_at', null)
            ->where('loans.deleted_at', null);
        if (!$request->has('fio') && !$request->has('telephone') && !$request->has('series') && !$request->has('number') && !$request->has('subdivision_id')) {
            if (Auth::user()->isCC() || (Auth::user()->isAdmin() && $request->has('onlyTerminals') && $request->onlyTerminals == 1)) {
                $terminal_subdivs = \App\Subdivision::where('is_terminal', "1")->pluck('id');
                $claims->whereIn('claims.subdivision_id', $terminal_subdivs);
            } else {
                if (Auth::user()->id == 5) {
                    \PC::debug($request->all(), 'noparams');
                }
                if (!config('app.dev')) {
                    $claims->whereIn('claims.subdivision_id', [Auth::user()->subdivision_id]);
                }
            }
        }
        if ($request->has('date_start')) {
            $claims->whereBetween('claims.created_at', [
                with(new Carbon($request->date_start))->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                with(new Carbon($request->date_start))->setTime(23, 59, 59)->format('Y-m-d H:i:s')
            ]);
        } else {
            if (!$request->has('series') && !$request->has('number') && !$request->has('telephone') && !$request->has('fio') && !$request->has('subdivision_id')) {
                if (Auth::user()->isCC() || config('app.dev') || (Auth::user()->isAdmin() && $request->has('onlyTerminals') && $request->onlyTerminals == 1)) {
                    $claims->whereBetween('claims.created_at', [
                        Carbon::now()->setTime(0, 0, 0)->subDays(7)->format('Y-m-d H:i:s'),
                        Carbon::now()->setTime(23, 59, 59)->format('Y-m-d H:i:s')
                    ])->limit(50);
                } else {
                    $claims->whereBetween('claims.created_at', [
                        Carbon::now()->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                        Carbon::now()->setTime(23, 59, 59)->format('Y-m-d H:i:s')
                    ]);
                }
            }
        }
        $dt = Datatables::of($claims)
            ->editColumn('claim_id', function ($claim) {
                if (Auth::user()->isAdmin()) {
                    return $claim->claim_id . '<br><small style="display:block; margin-top:-5px; color:#A9A9A9;">(' . $claim->claim_id_1c . ')</small>';
                } else {
                    return $claim->claim_id;
                }
            })
            ->editColumn('created_at', function ($claim) {
                return $claim->created_at ? with(new Carbon($claim->created_at))->format('d.m.Y H:i') : '';
            })
            ->editColumn('telephone', function ($claim) {
                return HtmlHelper::Buttton(null, [
                    'glyph' => 'earphone',
                    'href' => '#',
                    'onclick' => '$.dashboardCtrl.showTelephone(' . $claim->customer . '); return false;'
                ]);
            })
            ->addColumn('edit', function ($claim) {
                $is_teleport = ($claim->subdiv_name == 'Teleport' && $claim->claimstatus != Claim::STATUS_ACCEPTED && $claim->claimstatus != Claim::STATUS_DECLINED);
                //блокирует кнопку редактирования заявки если пользователь не админ и статус заявки не позволяет редактирование
                return '<div class="btn-group btn-group-sm">' .
                    ((in_array($claim->claimstatus,
                            $this->editableStatuses) || Auth::user()->isAdmin() || Auth::user()->isCC()) ?
                        HtmlHelper::Buttton(url('claims/edit/' . $claim->claim_id), ['glyph' => 'pencil']) :
                        HtmlHelper::Buttton(null, ['glyph' => 'pencil'])) . '</div>';
            })
            ->addColumn('photos', function ($claim) {
                $is_teleport = ($claim->subdiv_name == 'Teleport' && $claim->claimstatus != Claim::STATUS_ACCEPTED && $claim->claimstatus != Claim::STATUS_DECLINED);
                //блокирует кнопку с фотками если пользователь не админ и статус заявки не позволяет редактирование
                return (Auth::user()->isAdmin() || Auth::user()->isCC() || in_array($claim->claimstatus,
                        $this->editableStatuses) || $claim->claimstatus == Claim::STATUS_ACCEPTED || $is_teleport) ?
                    HtmlHelper::Buttton(null, [
                        'size' => 'sm',
                        'glyph' => 'picture',
                        'onclick' => '$.photosCtrl.openAddPhotoModal(' . $claim->claim_id . ',' . $claim->customer . '); return false;'
                    ]) :
                    HtmlHelper::Buttton(null, ['size' => 'sm', 'glyph' => 'picture', 'disabled' => true]);
            })
            ->addColumn('sendclaim', function ($claim) {
                $is_teleport = ($claim->subdiv_name == 'Teleport' && $claim->claimstatus != Claim::STATUS_ACCEPTED && $claim->claimstatus != Claim::STATUS_DECLINED);
                //блокирует кнопку отправки заявки если пользователь не админ и статус заявки не позволяет редактирование
                if (Auth::user()->isSuperAdmin()) {
                    $html = HtmlHelper::OpenDropDown(HtmlHelper::Buttton(url('claims/status/' . $claim->claim_id . '/1'),
                        [
                            'onclick' => '$.app.blockScreen(true)',
                            'size' => 'sm',
                            'text' => 'Отправить',
                            'class' => (($claim->claimstatus == Claim::STATUS_ONEDIT) ? 'btn btn-primary' : 'btn btn-default')
                        ]));
                    $html .= '<li><a href="#" onclick="$.dashboardCtrl.refreshClaim(\'' . $claim->pass_series . '\',\'' . $claim->pass_number . '\'); return false;">Обновить</a></li>';
                    for ($i = Claim::STATUS_NEW; $i <= Claim::STATUS_PRECONFIRM; $i++) {
                        $html .= '<li><a href="' . url('claims/status/' . $claim->claim_id . '/' . $i) . '">' . Claim::getStatusName($i) . '</a></li>';
                    }
                    $html .= HtmlHelper::CloseDropDown();
                    return $html;
                } else {
                    if (!$claim->enrolled && (in_array($claim->claimstatus,
                                $this->editableStatuses) || Auth::user()->isAdmin())) {
                        $html = '<div class="btn-group">';
                        $html .= HtmlHelper::Buttton(url('claims/status/' . $claim->claim_id . '/1'), [
                            'onclick' => '$.app.blockScreen(true)',
                            'size' => 'sm',
                            'text' => 'Отправить',
                            'class' => (($claim->claimstatus == Claim::STATUS_ONEDIT) ? 'btn btn-primary' : 'btn btn-default')
                        ]);
                        $html .= HtmlHelper::Buttton(null, [
                            'glyph' => 'refresh',
                            'href' => '#',
                            'size' => 'sm',
                            'onclick' => '$.dashboardCtrl.refreshClaim(\'' . $claim->pass_series . '\',\'' . $claim->pass_number . '\'); return false;',
                            'class' => 'btn-success btn',
                            'title' => 'Обновить статус'
                        ]);
                        $html .= '</div>';
                        return $html;
                    } else {
                        $html = HtmlHelper::Label('Отправлено', ['class' => 'label-success']) . ' ';
                        if ($claim->claimstatus != Claim::STATUS_ACCEPTED) {
                            $html .= HtmlHelper::Buttton(null, [
                                'glyph' => 'refresh',
                                'href' => '#',
                                'size' => 'sm',
                                'onclick' => '$.dashboardCtrl.refreshClaim(\'' . $claim->pass_series . '\',\'' . $claim->pass_number . '\'); return false;',
                                'class' => 'btn-success btn',
                                'title' => 'Обновить статус'
                            ]);
                        }
                        return $html;
                    }
                }
            })
            ->addColumn('promocode', function ($claim) {
                $html = '';
                if (!is_null($claim->claim_id_1c) && $claim->enrolled == 0) {
                    if (is_null($claim->promocode_id)) {
                        $html .= HtmlHelper::Buttton(null, [
                            'onclick' => '$.dashboardCtrl.openAddPromocodeModal(\'' . $claim->claim_id_1c . '\',' . $claim->claim_id . ');',
                            'size' => 'sm',
                            'glyph' => 'plus'
                        ]);
                    } else {
                        $html .= HtmlHelper::Buttton(null, ['size' => 'sm', 'glyph' => 'plus', 'disabled' => true]);
                    }
                } else {
                    $html .= HtmlHelper::Buttton(null, ['size' => 'sm', 'glyph' => 'plus', 'disabled' => true]);
                }
                if (Auth::user()->isAdmin()) {
                    $html = HtmlHelper::OpenDropDown($html);
                    $html .= HtmlHelper::DropDownItem("Добавить промокод, вручную только в арм", [
                        'onclick' => '$.dashboardCtrl.openManualAddPromocodeModal(' . $claim->claim_id . '); return false;',
                        "href" => '#'
                    ]);
                }
                $html .= HtmlHelper::CloseDropDown();
                return $html;
            })
            ->addColumn('status', function ($claim) {
                $res = HtmlHelper::StatusLabel($claim->claimstatus);
                if (!is_null($claim->seb_phone)) {
                    $res .= '<br><small title="Телефон специалиста СЭБ"><span class="glyphicon glyphicon-earphone"></span>' . $claim->seb_phone . '</small>';
                }
                $res .= '<button title="Показать комментарий" class="btn btn-default btn-xs" onclick="$.dashboardCtrl.getClaimComment(' . $claim->claim_id . '); return false;"><span class="glyphicon glyphicon-info-sign"></span></buton>';
                $res .= '</span>';
                return $res;
            })
            ->addColumn('createloan', function ($claim) {
                $html = '';
                $disabled_dropdown = '';
                $html = '<div class="btn-group btn-group-sm">';
                if (in_array($claim->claimstatus,
                        [Claim::STATUS_ACCEPTED, Claim::STATUS_CREDITSTORY]) && is_null($claim->loan)) {
                    if (!Auth::user()->isAdmin()) {
                        $disabled_dropdown = 'disabled';
                    }
                    $html .= HtmlHelper::Buttton(null, [
                        'onclick' => '$.dashboardCtrl.beforeLoanCreate(' . $claim->claim_id . ',' . $claim->customer . ');',
                        'size' => 'sm',
                        'text' => 'Сформировать'
                    ]);
                } else {
                    if (!is_null($claim->loan)) {
                        $html .= HtmlHelper::Buttton(null,
                            ['disabled' => true, 'size' => 'sm', 'text' => 'Сформирован']);
                    } else {
                        if (!Auth::user()->isAdmin()) {
                            $disabled_dropdown = 'disabled';
                        }
                        $html .= HtmlHelper::Buttton(null,
                            ['disabled' => true, 'size' => 'sm', 'text' => 'Сформировать']);
                    }
                }
                $html .= '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" ' . $disabled_dropdown . '>
                                        <span class="caret"></span>
                                        <span class="sr-only">Меню с переключением</span>
                                    </button>
                                    <ul class="dropdown-menu" role="menu">';
                if (!is_null($claim->loan)) {
                    $html .= '<li><a href="' . url('loans/summary/' . $claim->loan) . '">Перейти к кредитному договору</a></li>';
                    if (Auth::user()->isAdmin()) {
                        if ($claim->in_cash || !$claim->enrolled) {
                            $html .= '<li><a href="' . url('loans/edit/' . $claim->loan) . '">Редактировать</a></li>';
                        }
                        $html .= '<li><a href="' . url('loans/clearenroll/' . $claim->loan) . '">Снять пометку о зачислении</a></li>';
                        $html .= '<li><a href="' . url('loans/set/cccall') . '?loan_id=' . $claim->loan . '&val=1' . '">Поставить пометку о звонке в КЦ</a></li>';
                        $html .= '<li><a href="' . url('loans/set/cccall') . '?loan_id=' . $claim->loan . '&val=0' . '">Убрать пометку о звонке в КЦ</a></li>';
                    }
                    if (!$claim->loan_closed) {
                        if (Auth::user()->isAdmin()) {
                            $html .= '<li><a href="' . url('loans/uki/toggle') . '?loan_id=' . $claim->loan . '">' . (($claim->loan_uki) ? 'Убрать УКИ из договора' : 'Поставить УКИ в договоре') . '</a></li>';
                        }
                    }
                    $html .= '<li><a href="#" onclick="$.dashboardCtrl.showPromocode(' . $claim->loan . ');  return false;">Посмотреть промокод</a></li>';
                }
                if (Auth::user()->isAdmin() && !is_null($claim->claim_id_1c)) {
                    if (is_null($claim->loan)) {
                        $html .= '<li><a href="' . url('claims/uki/toggle') . '?claim_id=' . $claim->claim_id . '">' . (($claim->claim_uki) ? 'Убрать УКИ из заявки' : 'Поставить УКИ в заявке') . '</a></li>';
                    }
                    $html .= '<li><a href="' . url('claims/cbupdate') . '?pensioner=1&claim_id=' . $claim->claim_id . '">Поставить\Убрать галочку "Пенсионер"</a></li>';
                    $html .= '<li><a href="' . url('claims/cbupdate') . '?postclient=1&claim_id=' . $claim->claim_id . '">Поставить\Убрать галочку "Постоянный клиент"</a></li>';
                    if ($claim->subdiv_name == 'Teleport') {
                        $html .= '<li><a href="' . url('teleport/status/send/' . $claim->claim_id) . '">Отправить статус в телепорт</a></li>';
                        $html .= '<li><a href="' . url('claims/setedit/' . $claim->claim_id) . '">Поставить статус "Поправить"</a></li>';
                    }
                }
                if (Auth::user()->id == 5) {
                    $html .= '<li><a href="" onclick="$.dashboardCtrl.openCardFrameForClaim(' . $claim->claim_id . '); return false;">Открыть окно с короной</a></li>';
                }
                $html .= '</ul></div>';
                return $html;
            })
            ->addColumn('print', function ($claim) {
                return HtmlHelper::Buttton(null, [
                    'size' => 'sm',
                    'glyph' => 'print',
                    'onclick' => '$.dashboardCtrl.showPrintDocsModal(' . $claim->claim_id . '); return false;'
                ]);
            })
            ->addColumn('enroll', function ($claim) {
                //убирает кнопку зачисления денег по займу если займ зачислен
                if (!is_null($claim->loan)) {
                    if ($claim->enrolled) {
                        if ($claim->is_terminal) {
                            if ($claim->on_balance) {
                                return HtmlHelper::Label('Зачислено на баланс', ['class' => 'label-success']);
                            } else {
                                if (Claim::hasSignedContract($claim->claim_id)) {
                                    return '<a onclick="$.app.blockScreen(true);" class="btn btn-default btn-sm" href="' . url('loans/sendtobalance/' . $claim->loan) . '" title="Перевести сумму на баланс клиента">'
                                        . '<span class="glyphicon glyphicon-export"></span> На баланс'
                                        . '</a>';
                                } else {
                                    return HtmlHelper::Label('Нет договора', ['class' => 'label-danger']);
                                }
                            }
                        } else {
                            return HtmlHelper::Label((($claim->in_cash) ? 'РКО сформирован' : 'Зачислено'),
                                ['class' => 'label-success']);
                        }
                    } else {
                        return HtmlHelper::Buttton(url('loans/enroll/' . $claim->loan),
                            ['onclick' => '$.app.blockScreen(true);', 'size' => 'sm', 'glyph' => 'export']);
                    }
                } else {
                    return HtmlHelper::Buttton(null, ['size' => 'sm', 'glyph' => 'export', 'disabled' => true]);
                }
            })
            ->filter(function ($query) use ($request) {
                //поиск по заявкам
                if ($request->has('fio')) {
                    $query->where('passports.fio', 'like', "%" . $request->get('fio') . "%");
                }
                if ($request->has('telephone')) {
                    $query->where('customers.telephone', '=', $request->get('tele'));
                }
                $list = ['series', 'number'];
                foreach ($list as $v) {
                    if ($request->has($v)) {
                        $query->where('passports.' . $v, '=', $request->get($v));
                    }
                }
                if ($request->has('subdivision_id')) {
                    $query->where('claims.subdivision_id', $request->subdivision_id);
                }
            })
            ->removeColumn('promocode_id')
            ->removeColumn('claim_id_1c')
            ->removeColumn('subdivision')
            ->removeColumn('loan')
            ->removeColumn('customer')
            ->removeColumn('pass_series')
            ->removeColumn('pass_number')
            ->removeColumn('claimstatus')
            ->removeColumn('rko')
            ->removeColumn('enrolled')
            ->removeColumn('seb_phone')
            ->removeColumn('user_name')
            ->removeColumn('subdiv_name')
            ->removeColumn('loan_claimed_for_remove')
            ->removeColumn('claim_claimed_for_remove')
            ->removeColumn('claim_subdivision')
            ->removeColumn('on_balance')
            ->removeColumn('is_terminal')
            ->removeColumn('claim_comment')
            ->removeColumn('claim_updated_at')
            ->removeColumn('loan_uki')
            ->removeColumn('claim_uki')
            ->removeColumn('loan_closed')
            ->removeColumn('in_cash');
        if (Auth::user()->isAdmin()) {
            $dt->addColumn('remove', function ($claim) {
                if (!$claim->enrolled) {
                    $ddItems = [
                        HtmlHelper::DropDownItem('Заявку (только с сайта)', [
                            'href' => url('claims/mark4remove2/' . $claim->claim_id),
                            'title' => 'Только если уже удалено в 1С!'
                        ]),
                        HtmlHelper::DropDownItem('Займ (только с сайта)', [
                            'href' => url('loans/remove2/' . $claim->loan),
                            'title' => 'Только если уже удалено в 1С!'
                        ]),
                        HtmlHelper::DropDownItem('Заявку', ['href' => url('claims/mark4remove/' . $claim->claim_id)])
                    ];
                    if (!is_null($claim->loan)) {
                        $ddItems[] = HtmlHelper::DropDownItem('Займ', ['href' => url('loans/remove/' . $claim->loan)]);
                    }
                    $html = HtmlHelper::DropDown('Удалить', $ddItems);
                } else {
                    $html = HtmlHelper::DropDown('Удалить', [
                        HtmlHelper::DropDownItem('Заявку (только с сайта)', [
                            'href' => url('claims/mark4remove2/' . $claim->claim_id),
                            'title' => 'Только если уже удалено в 1С!'
                        ]),
                        HtmlHelper::DropDownItem('Займ (только с сайта)', [
                            'href' => url('loans/remove2/' . $claim->loan),
                            'title' => 'Только если уже удалено в 1С!'
                        ])
                    ]);
                }
                return $html;
            });
            $dt->addColumn('userinfo', function ($claim) {
                return '<small style="line-height:1; display:inline-block">' . $claim->user_name . '<br><span style="font-size:xx-small">' . $claim->subdiv_name . '</span></small>';
            });
        } else {
            $dt->addColumn('remove', function ($claim) {
                $html = '<div class="btn-group btn-group-sm remove-dropdown">
                            <button type="button" class="btn ' . ((!is_null($claim->claim_claimed_for_remove) || !is_null($claim->loan_claimed_for_remove)) ? 'btn-danger' : 'btn-default') . ' dropdown-toggle" 
                            data-toggle="dropdown"><span class="glyphicon glyphicon-exclamation-sign"></span> <span class="caret"></span></button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">';
                if (is_null($claim->loan)) {
                    if (is_null($claim->claim_claimed_for_remove)) {
                        $html .= '<li><a href="#" title="Подать заявку" onclick="$.uReqsCtrl.claimForRemove(' . $claim->claim_id . ',' . \App\MySoap::ITEM_CLAIM . '); return false;">Заявка</a></li>';
                    } else {
                        $html .= '<li><a class="bg-danger" title="Заявка подана"><span class="glyphicon glyphicon-exclamation-sign"></span> Заявка</a></li>';
                    }
                } else {
                    if (is_null($claim->loan_claimed_for_remove)) {
                        $html .= '<li><a href="#" title="Подать заявку" onclick="$.uReqsCtrl.claimForRemove(' . $claim->loan . ',' . \App\MySoap::ITEM_LOAN . '); return false;">Кредитный договор</a></li>';
                    } else {
                        $html .= '<li><a class="bg-danger" title="Заявка подана"><span class="glyphicon glyphicon-exclamation-sign"></span> Кредитный договор</a></li>';
                    }
                }
                $html .= '</ul></div>';
                return $html;
            });
            if (Auth::user()->isCC()) {
                $dt->addColumn('userinfo', function ($claim) {
                    return '<small><span>' . $claim->subdiv_name . '</span></small>';
                });
            }
        }
        $collection = $dt->rawColumn([
            'actions',
            'remove',
            'userinfo',
            'claim_id',
            'telephone',
            'edit',
            'photos',
            'sendclaim',
            'promocode',
            'status',
            'createloan',
            'print',
            'enroll',
            'remove',
        ])->toJson();
        //если не найдено заявок, то сделать запрос в 1С
        if ($getFrom1cOnFail && $request->without1c == 0) {
            if ($request->has('series') && $request->has('number') && $request->series != '' && $request->number != '') {
                $loanCtrl = new LoanController();
                if (!is_null($loanCtrl->updateLoanRepayments($request->get('series'), $request->get('number')))) {
                    return $this->claimsList($request, false);
                }
            } else {
                if ($request->has('fio') && $request->fio != '' && substr_count($request->fio, ' ') > 1) {
                    $res1c = MySoap::getPassportsByFio($request->fio);
                    if (array_key_exists('fio', $res1c)) {
                        foreach ($res1c['fio'] as $item) {
                            if (is_array($item) && array_key_exists('passport_series',
                                    $item) && array_key_exists('passport_number', $item)) {
                                $loanCtrl = new LoanController();
                                $loanCtrl->updateLoanRepayments(\App\StrUtils::removeNonDigits($item['passport_series']),
                                    \App\StrUtils::removeNonDigits($item['passport_number']));
                            }
                        }
                        return $this->claimsList($request, false);
                    }
                }
            }
        }

        return $collection;
    }

    public function claimsList2(Request $req) {
        if ($req->has('series') && $req->has('number') && $req->series != '' && $req->number != '') {
            Synchronizer::updateLoanRepayments($req->series, $req->number);
        } else if ($req->has('fio') && $req->fio != '') {
            $res1c = MySoap::getPassportsByFio($req->fio);
            if (array_key_exists('fio', $res1c)) {
                foreach ($res1c['fio'] as $item) {
                    if (is_array($item) && array_key_exists('passport_series', $item) && array_key_exists('passport_number', $item)) {
                        Synchronizer::updateLoanRepayments($item['passport_series'], $item['passport_number']);
                    }
                }
            }
        }
        $cols = ['claims.id as claim_id', 'claims.created_at',
            'passports.fio', 'customers.telephone', 'claims.summa',
            'claims.srok', 'loans.id as loan', 'orders.number as rko', 'customers.id as customer',
            'claims.status as claimstatus', 'passports.series as pass_series', 'passports.number as pass_number',
            'users.subdivision_id as subdivision', 'loans.enrolled as enrolled', 'loans.in_cash as in_cash',
            'claims.promocode_id as promocode_id', 'claims.id_1c as claim_id_1c', 'claims.seb_phone',
            'subdivisions.name as subdiv_name', 'users.name as user_name', 'claims.claimed_for_remove as claim_claimed_for_remove',
            'loans.claimed_for_remove as loan_claimed_for_remove', 'claims.subdivision_id as claim_subdivision', 'loans.on_balance as on_balance',
            'subdivisions.is_terminal as is_terminal', 'claims.comment as claim_comment', 'orders.created_at as order_created_at', 'orders.id as order_id'];
        $claims = Claim::select($cols)
                ->groupBy('claims.id')
                ->distinct()
                ->leftJoin('customers', 'claims.customer_id', '=', 'customers.id')
                ->leftJoin('loans', 'loans.claim_id', '=', 'claims.id')
                ->leftJoin('users', 'claims.user_id', '=', 'users.id')
                ->leftJoin('passports', 'passports.customer_id', '=', 'customers.id')
                ->leftJoin('orders', 'loans.order_id', '=', 'orders.id')
                ->leftJoin('subdivisions', 'subdivisions.id', '=', 'claims.subdivision_id')
                ->where('claims.deleted_at', NULL)
                ->where('loans.deleted_at', NULL)
                ->orderBy('created_at', 'desc');
        if ($req->has('series') && $req->has('number') && $req->series != '' && $req->number != '') {
            $claims->where('passports.series', $req->series)->where('passports.number', $req->number);
        }
        if ($req->has('fio') && $req->fio != '') {
            $claims->where('passports.fio', 'like', '%' . $req->fio . '%');
        }
        if (!$req->has('fio') && !$req->has('series') && !$req->has('number') && !$req->has('telephone') && !$req->has('claim_id')) {
            if (Auth::user()->isCC() || config('app.dev')) {
                $claims->whereBetween('claims.created_at', [Carbon::now()->subDays(7)->setTime(0, 0, 0)->format('Y-m-d H:i:s'), Carbon::now()->addDays(7)->setTime(23, 59, 59)->format('Y-m-d H:i:s')]);
            } else {
                $claims->whereBetween('claims.created_at', [Carbon::now()->setTime(0, 0, 0)->format('Y-m-d H:i:s'), Carbon::now()->setTime(23, 59, 59)->format('Y-m-d H:i:s')]);
            }
        }
        return view('dashboard2')->with('claims', $claims->paginate(25))->withInput(Input::old());
    }

    public function manualAddPromocode(Request $req) {
        if (!$req->has('claim_id')) {
            return 0;
        }
        $claim = Claim::find($req->claim_id);
        if (is_null($claim)) {
            return 0;
        }
        DB::beginTransaction();
        if ($req->has('claim_promocode') && $req->claim_promocode != '') {
            $claimPromo = Promocode::where('number', $req->claim_promocode)->first();
            if (is_null($claimPromo)) {
                $claimPromo = new Promocode();
                $claimPromo->number = $req->claim_promocode;
                if (!$claimPromo->save()) {
                    DB::rollback();
                    return 0;
                }
            }
            $claim->promocode_id = $claimPromo->id;
            if (!$claim->save()) {
                DB::rollback();
                return 0;
            }
        } else {
            $claim->promocode_id = null;
            if (!$claim->save()) {
                DB::rollback();
                return 0;
            }
        }
        if ($req->has('loan_promocode') && $req->loan_promocode != '') {
            $loan = Loan::where('claim_id', $claim->id)->first();
            if (is_null($loan)) {
                DB::rollback();
                return 0;
            }
            $loanPromo = Promocode::where('number', $req->loan_promocode)->first();
            if (is_null($loanPromo)) {
                $loanPromo = new Promocode();
                $loanPromo->number = $req->loan_promocode;
                if (!$loanPromo->save()) {
                    DB::rollback();
                    return 0;
                }
            }
            $loan->promocode_id = $loanPromo->id;
            if (!$loan->save()) {
                DB::rollback();
                return 0;
            }
        } else {
            $loan = Loan::where('claim_id', $claim->id)->first();
            if (!is_null($loan)) {
                $loan->promocode_id = null;
                if (!$loan->save()) {
                    DB::rollback();
                    return 0;
                }
            }
        }
        DB::commit();
        return 1;
    }

    public function getPromocodeData(Request $req) {
        $res = [];
        if (!$req->has('claim_id')) {
            return $res;
        }
        $claim = Claim::find($req->claim_id);
        if (is_null($claim)) {
            return $res;
        }
        if (!is_null($claim->promocode)) {
            $res['claim_promocode'] = $claim->promocode->number;
        }
        $loan = Loan::where('claim_id', $claim->id)->first();
        if (!is_null($loan) && !is_null($loan->promocode)) {
            $res['loan_promocode'] = $loan->promocode->number;
        }
        return $res;
    }

    public function setCCcall(Request $req) {
        if (!$req->has('loan_id') || !$req->has('val')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS);
        }
        $loan = Loan::find($req->loan_id);
        if (is_null($loan)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL);
        }
        $loan->cc_call = ($req->val) ? 1 : 0;
        if (!$loan->save()) {
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }
        return redirect()->back()->with('msg_suc', StrLib::SUC);
    }

    public function toggleUki(Request $req) {
        if (!Auth::user()->isAdmin()) {
            return redirect()->back()->with('msg_err', 'Для того чтобы поставить или убрать УКИ, Вам необходимо позвонить в КЦ');
        }
        if (!$req->has('loan_id')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS);
        }
        $loan = Loan::find($req->loan_id);

        if (is_null($loan)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL);
        }

//        if(\App\Repayment::where('loan_id',$loan->id)->count()>0){
//            return redirect()->back()->with('msg_err', StrLib::ERR_LOAN_HAS_REPS);
//        }

        if ($loan->closed) {
            return redirect()->back()->with('msg_err', 'Договор уже закрыт');
        }
        DB::beginTransaction();
        $oldLoan = $loan->toArray();
        $inputLoan = $loan->toArray();
        if ($loan->uki) {
            $inputLoan['uki'] = 0;
            $loan->uki = 0;
        } else {
            $inputLoan['uki'] = 1;
            $loan->uki = 1;
        }

        if ($loan->uki && !$loan->isUkiActive()) {
            return redirect()->back()->with('msg_err', 'Оплатить УКИ по данному договору уже нельзя');
        }

        Spylog::logModelChange(Spylog::TABLE_LOANS, $oldLoan, $inputLoan);
        if (!$loan->save()) {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }

        if ($loan->saveThrough1c() === FALSE) {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR_1C);
        }
        DB::commit();
        return redirect()->back()->with('msg_suc', StrLib::SUC);
    }
    public function toggleUkiClaim(Request $req) {
        if (!Auth::user()->isAdmin()) {
            return redirect()->back()->with('msg_err', 'Для того чтобы поставить или убрать УКИ, Вам необходимо позвонить в КЦ');
        }
        if (!$req->has('claim_id')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS);
        }
        $claim = Claim::find($req->claim_id);
        if (is_null($claim)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL);
        }
        if(Loan::where('claim_id',$req->claim_id)->count()>0){
            return redirect()->back()->with('msg_err', StrLib::ERR_HAS_LOAN);
        }
        DB::beginTransaction();
        
        $oldClaim = $claim->toArray();
        $inputClaim = $claim->toArray();
        if ($claim->uki==1) {
            $inputClaim['uki'] = 0;
            $claim->uki = 0;
        } else {
            $inputClaim['uki'] = 1;
            $claim->uki = 1;
        }

        Spylog::logModelChange(Spylog::TABLE_LOANS, $oldClaim, $inputClaim);
        if (!$claim->save()) {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }
        $claimCtrl = new ClaimController();

        if ($claimCtrl->sendClaimTo1c($claim) === FALSE) {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR_1C);
        }
        DB::commit();
        return redirect()->back()->with('msg_suc', StrLib::SUC);
    }

    public function toggleAboutClientCheckbox(Request $req) {
        if (!$req->has('claim_id')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS);
        }
        $claim = Claim::find($req->claim_id);

        if (is_null($claim)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL);
        }

        $data1c = ['type' => 'SinchronizeCreditRequest', 'id' => $claim->id_1c];
        $old_about = $claim->about_client->toArray();
        if ($req->has('pensioner')) {
            $claim->about_client->pensioner = !$claim->about_client->pensioner;
            $data1c['pensioner'] = (int) $claim->about_client->pensioner;
        }
        if ($req->has('postclient')) {
            $claim->about_client->postclient = !$claim->about_client->postclient;
            $data1c['postclient'] = (int) $claim->about_client->postclient;
        }
        $res1c = MySoap::sendExchangeArm(MySoap::createXML($data1c));
        if ($res1c->result == 0) {
            return redirect()->back()->with('msg_err', StrLib::ERR_1C);
        }
        Spylog::logModelChange(Spylog::TABLE_ABOUT_CLIENTS, $old_about, $claim->about_client->toArray());
        $claim->about_client->save();
        return redirect()->back()->with('msg_suc', StrLib::SUC);
    }

}

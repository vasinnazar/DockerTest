<?php

namespace App\Http\Controllers;

use Input,
    Auth,
    App\Loan,
    App\Claim,
    App\Card,
    App\LoanType,
    App\Condition,
    App\ContractForm,
    Illuminate\Support\Facades\DB,
    App\Utils\StrLib,
    Carbon\Carbon,
    App\Spylog\Spylog;

class LoanTypeController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Показывает писок видов займа и условий
     * @return type
     */
    public function index() {
        if (!Auth::user()->isAdmin()) {
            return redirect('home')->with('msg', 'Нет доступа!')->with('class', 'alert-danger');
        }
        return view('loantypes.list')
                        ->with('loanTypes', LoanType::all())
                        ->with('conditions', Condition::all());
    }

    /**
     * Показывает редактор вида займа для нового займа и для редактирования сохранённого
     * @param int $loantype_id идентификатор вида займа, для редактирования
     * @return view
     */
    public function editor($loantype_id = null) {
        if (!Auth::user()->isAdmin()) {
            return redirect('home')->with('msg', 'Нет доступа!')->with('class', 'alert-danger');
        }
        Spylog::log(Spylog::ACTION_OPEN, 'loantypes', $loantype_id);
        return view('loantypes.editor')
                        ->with('loanType', LoanType::findOrNew($loantype_id))
                        ->with('conditions', Condition::whereNotNull('created_at')->get())
//                        ->with('conditions', [])
                        ->with('contract_forms', ContractForm::pluck('name', 'id'));
//                        ->with('contract_forms', []);
    }

    /**
     * Обновляет или сохраняет новый тип займа с данными пришедшими в Input
     * @return type
     */
    public function update() {
        if (!Auth::user()->isAdmin()) {
            return redirect('home')->with('msg', 'Нет доступа!')->with('class', 'alert-danger');
        }
        $input = Input::all();
        $loantype = LoanType::findOrNew($input['id']);
        $input['basic'] = (array_key_exists('basic', $input)) ? $input['basic'] : 0;
        $input['show_in_terminal'] = (array_key_exists('show_in_terminal', $input)) ? $input['show_in_terminal'] : 0;
        if (!is_null($loantype->id)) {
            Spylog::logModelChange('loantypes', $loantype, $input);
            $loantype->fill($input);
        } else {
            $loantype->fill($input);
            Spylog::logModelAction(Spylog::ACTION_CREATE, 'loantypes', $loantype);
        }
        if ($loantype->save()) {
            if (!is_null(Input::get('condition')) && Input::get('condition') != '') {
                $loantype->conditions()->sync(Input::get('condition'));
            }
            return redirect()->route('loantypes.list')
                            ->with('msg', 'Вид займа сохранён')
                            ->with('class', 'alert-success');
        } else {
            return redirect()->route('loantypes.list')
                            ->with('msg', 'Ошибка! Вид займа не сохранён!')
                            ->with('class', 'alert-danger');
        }
    }

    /**
     * Удаляет вид займа с переданным идентификатором
     * @param type $loantype_id идентификатор вида займа для удаления
     * @return type
     */
    public function delete($loantype_id) {
        if (!Auth::user()->isAdmin()) {
            return redirect('home')->with('msg', 'Нет доступа!')->with('class', 'alert-danger');
        }
        if (Loan::where('loantype_id', $loantype_id)->count() > 0) {
            return redirect()->route('loantypes.list')
                            ->with('msg', 'Ошибка! Существуют займы с этим видом займа')
                            ->with('class', 'alert-danger');
        }
        $loantype = LoanType::find($loantype_id);
        if ($loantype->delete()) {
            Spylog::logModelAction(Spylog::ACTION_DELETE, 'loantypes', $loantype);
            return redirect()->route('loantypes.list')
                            ->with('msg', 'Вид займа удален')
                            ->with('class', 'alert-success');
        }
    }

    public function cloneLoantype($loantype_id) {
        $loantype = LoanType::find($loantype_id);
        if (is_null($loantype)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL);
        }
        $newloantype = $loantype->replicate();
        if (!$newloantype->save()) {
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }
        return redirect()->back()->with('msg_suc', StrLib::SUC_SAVED);
    }

    /**
     * Возвращает список возожных видов займа для займа
     * Проходит по всем видам займов и на каждый делает запрос в базу с условиями взятыми из условий для вида займа,
     * если запрос не вернул ничего, то вид займа не добавляется в результат
     * @param int $claim_id айдишник займа
     * @return {'claim'={},'types'=[],'card'={}} объект с данными по заявке и возожными видами займа
     */
    public static function getPossible($claim_id) {
        $res = [];

        $claim = Claim::find($claim_id);
        $isPensioner = $claim->about_client->pensioner;
        $isPostclient = $claim->about_client->postclient;
        $onlyCredityStory = false;
        if ($claim->status == Claim::STATUS_CREDITSTORY) {
            $onlyCredityStory = true;
            $loanTypes = LoanType::
                    whereIn('status', [LoanType::STATUS_CREDITSTORY1, LoanType::STATUS_CREDITSTORY2, LoanType::STATUS_CREDITSTORY3])
                    ->where('money', '<=', $claim->summa)
                    ->where('time', '<=', $claim->srok)
                    ->get();
        } else if ($claim->subdivision->is_terminal) {
            $loanTypes = LoanType::where('show_in_terminal', "1")->get();
            foreach ($loanTypes as $t) {
                $item = $t->toArray();
                if ($t->id == $claim->terminal_loantype_id) {
                    $item['selected'] = 1;
                }
                $res[] = $item;
            }
            return ['claim' => $claim->toArray(), 'types' => $res, 'card' => '', 'pensioner' => $isPensioner, 'is_terminal' => 1];
        } else {
            $loanTypes = LoanType::with('conditions')->where('status', LoanType::STATUS_ACTIVE)->get();
        }
        $sql = sprintf('customer_id=(SELECT customer_id FROM claims WHERE id=%s) AND status=%s', $claim_id, Card::STATUS_ACTIVE);
        $card = Card::whereRaw($sql)->first();
        //проходим по всем видам в базе
        foreach ($loanTypes as $type) {
            if ($onlyCredityStory) {
                $res[] = $type->toArray();
            } else if ($type->id_1c == 'ARM000013' || $type->id_1c == 'ARM000027') {
                //если вид кредита под -70%, то смотрим есть ли на человека 9 
                //закрытых займов с определенными условиями после 1 сентября или после последнего такого займа
                if ($isPensioner || $isPostclient) {
                    $loanMinDate = '2016-09-01';
                    $lastStockLoan = Loan::leftJoin('loantypes', 'loantypes.id', '=', 'loans.loantype_id')
                            ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                            ->where('claims.customer_id', $claim->customer_id)
                            ->whereIn('loantypes.id_1c', ['ARM000013', 'ARM000027'])
                            ->orderBy('loans.created_at', 'desc')
                            ->first();
                    if (!is_null($lastStockLoan)) {
                        $loanMinDate = $lastStockLoan->created_at->format('Y-m-d');
                    }
                    $claimsCount = Loan::leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                            ->leftJoin('loantypes', 'loantypes.id', '=', 'loans.loantype_id')
                            ->leftJoin('repayments', 'repayments.loan_id', '=', 'loans.id')
                            ->leftJoin('repayment_types', 'repayment_types.id', '=', 'repayments.repayment_type_id')
                            ->where('claims.customer_id', $claim->customer->id)
                            ->where('repayment_types.text_id', config('options.rtype_closing'))
                            ->where('loans.time', '>=', 3)
                            ->where('loans.closed', 1)
                            ->where('loans.created_at', '>', $loanMinDate)
//                            ->whereIn('loantypes.id_1c', ['ARM000004', 'ARM000010', 'ARM000006', 'ARM000011', 'ARM000021', 'ARM000022', 'ARM000023'])
                            ->whereNotIn('loantypes.id_1c', ['ARM000013', 'ARM000027'])
                            ->count();
                    //проверить на Буйских Елена Валентиновна
                    $claimsCount2Req = DB::select(DB::raw(
                                            "SELECT count(*) as num FROM armf.repayments
                                            left join loans on repayments.loan_id=loans.id
                                            left join claims on loans.claim_id=claims.id
                                            left join repayment_types on repayment_types.id=repayments.repayment_type_id
                                            left join loantypes on loans.loantype_id=loantypes.id
                                            where loans.created_at>'" . $loanMinDate . "'
                                            and claims.customer_id='" . $claim->customer->id . "'
                                            and repayment_types.text_id='closing'
                                            and repayments.created_at>DATE_ADD(loans.created_at,interval 2 day)"
                    ));
                    $claimsCount2 = 0;
                    if (!is_null($claimsCount2Req) && count($claimsCount2Req) > 0 && isset($claimsCount2Req[0]->num)) {
                        $claimsCount2 = (int) $claimsCount2Req[0]->num;
                    }
                    if ($claimsCount2 >= 9) {
                        $res[] = $type->toArray();
                    }
                }
            } else if ($type->id_1c == '0000021' || $type->id_1c == 'ARM000029') {
                //затычка для акции день рождения, так как запрос в виде займа не учитывает если 3 дня назад или вперед - это уже другой месяц
                $curdateMin = Carbon::now()->setTime(0, 0, 0)->subDays(3);
                $curdateMax = Carbon::now()->setTime(0, 0, 0)->addDays(3);
                $birthdate = with(new Carbon($claim->passport->birth_date))->year(Carbon::now()->format('Y'));
                if ($birthdate->between($curdateMin, $curdateMax)) {
                    $res[] = $type->toArray();
                }
            } else {
                $conds = $type->conditions;
                $condsStr = '';
                //проходим по всем условиям для вида займа и составляем из них запрос для where
                foreach ($conds as $cond) {
                    $condsStr .= ($condsStr != '') ? 'AND ' : '';
                    $condsStr .= '(' . $cond->condition . ') ';
                }
                $sql = DB::table('claims')
                        ->select('claims.id', 'claims.srok', 'claims.summa')
                        ->leftJoin('passports', 'passports.id', '=', 'claims.passport_id')
                        ->leftJoin('customers', 'customers.id', '=', 'claims.customer_id')
                        ->leftJoin('about_clients', 'about_clients.id', '=', 'claims.about_client_id')
                        ->leftJoin('subdivisions', 'subdivisions.id', '=', 'claims.subdivision_id')
                        ->where('claims.id', $claim_id);
                $tmp_claim = (count($conds) > 0) ? $sql->whereRaw($condsStr)->first() : $sql->first();
                $curDate = date_create();
                $typeEndDate = date_create($type->end_date);
                $typeStartDate = date_create($type->start_date);
                if ($curDate >= $typeStartDate && $curDate <= $typeEndDate && !is_null($tmp_claim)) {
                    $res[] = $type->toArray();
                    /**
                     * меняем процент для пенсионеров для вывода в селекте при создании договора
                     */
//                    if($pensioner && !is_null($type->pc_after_exp) && $type->pc_after_exp != '0.00'){
//                        $res[count($res)-1]['percent'] = config('options.pensioner_percent');
//                    }
                    if ($isPensioner && ($type->id_1c == 'ARM000010' || $type->id_1c == 'ARM000022')) {
                        $res[count($res) - 1]['selected'] = '1';
                    }
                }
            }
        }
        foreach ($res as &$lt) {
            if ($lt['percent'] == $lt['exp_pc']) {
                $lt['percent'] = with(\App\LoanRate::getByDate())->pc;
            }
        }
        return ['claim' => ((!is_null($claim)) ? $claim->toArray() : null), 'types' => $res, 'card' => $card, 'pensioner' => $isPensioner, 'is_terminal' => (!is_null($claim) && $claim->subdivision->is_terminal) ? 1 : 0];
    }

}

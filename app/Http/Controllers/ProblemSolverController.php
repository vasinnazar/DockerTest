<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Auth,
    Input,
    Validator,
    Session,
    Redirect,
    App\Loan,
    App\Card,
    App\User,
    App\Subdivision,
    Illuminate\Support\Facades\DB,
    Carbon\Carbon,
    App\Spylog\Spylog,
    App\Spylog\SpylogModel,
    App\Utils\StrLib,
    App\NpfFond,
    App\Utils\HtmlHelper,
    App\Claim,
    App\Passport,
    App\about_client,
    App\Customer,
    App\Order,
    Illuminate\Support\Facades\Storage,
    Illuminate\Support\Facades\Hash;

class ProblemSolverController extends BasicController {

    public function __construct() {
//        $this->middleware('auth');
    }

    public function index() {
        return view('adminpanel.problemsolver');
//        return view('adminpanel.main')->with('today_orders',DB::raw());
    }

    public function getDocsByPassport(Request $req) {
        
    }

    public function getLoanByNumber(Request $req) {
        if (Loan::where('id_1c', $req->loan_id_1c)->count() > 0) {
            return $this->backWithErr('Договор уже есть в базе');
        }
        $claim = new Claim();
        $res = \App\Synchronizer::updateLoanRepayments(null, null, $req->loan_id_1c, $req->customer_id_1c);
        if ($res != null) {
            return $this->backWithSuc('Договор сохранен, перейди по ссылке: <a href="' . url('loans/summary/' . $res['loan']->id) . '">Открыть договор</a>');
        } else {
            return $this->backWithErr();
        }
    }

    public function createFakeClaimAndLoan(Request $req) {
        if (!$req->has('claim_id_1c') || !$req->has('loan_id_1c') || !$req->has('customer_id_1c') || !$req->has('passport_series') || !$req->has('passport_number')) {
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        DB::beginTransaction();
        $customer = Customer::where('id_1c', $req->customer_id_1c)->first();
        if (is_null($customer)) {
            $customer = new Customer();
            $customer->id_1c = $req->customer_id_1c;
            if (!$customer->save()) {
                DB::rollback();
                return $this->backWithErr('Контрагент не создался, попробовать создать через физлица');
            }
        }
//        if (is_null($customer)) {
//            return $this->backWithErr('Необходимо создать контрагента в физ лицах и сверить ID_1C с кодом в 1С');
//        }
        $about = new about_client();
        $passport = Passport::where('series', $req->passport_series)->where('number', $req->passport_number)->first();
        if (is_null($passport)) {
            $passport = new Passport();
            $res1c = \App\MySoap::passport(['series' => $req->passport_series, 'number' => $req->passport_number, 'old_series' => '', 'old_number' => '']);
            if ($res1c['res'] && array_key_exists('id', $res1c)) {
                $passport->fio = $res1c['fio'];
                $about->postclient = ($res1c['postclient'] == "Да") ? 1 : 0;
                $about->pensioner = ($res1c['pensioner'] == "Да") ? 1 : 0;
            }
            $passport->series = $req->passport_series;
            $passport->number = $req->passport_number;
            $passport->customer_id = $customer->id;
            if (!$passport->save()) {
                DB::rollback();
                return $this->backWithErr('Паспорт не создался, попробовать создать через физлица');
            }
        } else {
            $passport->customer_id = $customer->id;
        }
        $claim = Claim::where('id_1c', $req->claim_id_1c)->withTrashed()->first();
        if (!is_null($claim)) {
            if ($claim->trashed()) {
                $claim->forceDelete();
            } else if (!is_null($claim->loan_id)) {
                DB::rollback();
                return $this->backWithErr('Заявка с таким номером уже есть');
            }
        } else {
            $claim = new Claim();
            $claim->summa = 1000;
            $claim->srok = 3;
            $claim->id_1c = $req->claim_id_1c;
            $claim->user_id = Auth::user()->id;
            $claim->subdivision_id = Auth::user()->subdivision_id;
        }
        if (is_null($claim->id)) {
            $about->customer_id = $customer->id;
            if (!$about->save()) {
                DB::rollback();
                return $this->backWithErr('Данные о клиенте не сохранились');
            }
            $claim->about_client_id = $about->id;
        }
        $claim->passport_id = $passport->id;
        $about->customer_id = $customer->id;
        $claim->customer_id = $customer->id;

        if (!$claim->save()) {
            DB::rollback();
            return $this->backWithErr('Заявка не сохранилась');
        }
        $loan = Loan::where('id_1c', $req->loan_id_1c)->first();
        if (!is_null($loan)) {
            DB::rollback();
            return $this->backWithErr('Кредитник с таким номером уже есть');
        }
        $loan = new Loan();
        $loan->id_1c = $req->loan_id_1c;
        $loan->claim_id = $claim->id;
        $loan->enrolled = 1;
        $loan->user_id = Auth::user()->id;
        $loan->subdivision_id = Auth::user()->subdivision_id;
        $loan->in_cash = 1;
        $loan->money = 1000;
        $loan->time = 3;
        $loan->last_payday = Carbon::now()->format('Y-m-d H:i:s');
        if (!$loan->save()) {
            return $this->backWithErr('Кредитник не сохранился');
        }
        DB::commit();
        return $this->backWithSuc(StrLib::SUC)->with('new_loan_id', $loan->id);
    }

    public function removeLoan(Request $req) {
        if (!$req->has('loan_id') && !$req->has('loan_id_1c') && !$req->has('claim_id')) {
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        if ($req->has('loan_id')) {
            $loan = Loan::find($req->loan_id);
        } else if ($req->has('loan_id_1c')) {
            $loan = Loan::where('id_1c', $req->loan_id_1c)->first();
        } else if ($req->has('claim_id')) {
            $loan = Loan::where('claim_id', $req->claim_id)->first();
        }
        if (is_null($loan)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        if (!$loan->delete()) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        return $this->backWithSuc('Договор удален, необходимо заново найти его через поиск');
//        if($req->has('loan_id_1c'))
    }

    public function removeRepayment(Request $req) {
        if (!$req->has('repayment_id_1c') && !$req->has('repayment_id')) {
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        if($req->has('repayment_id_1c')){
            $repayment = \App\Repayment::where('id_1c', $req->repayment_id_1c)->first();
        } else if($req->has('repayment_id')){
            $repayment = \App\Repayment::where('id', $req->repayment_id)->first();
        }
        if (is_null($repayment)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        if (!$repayment->delete()) {
            return $this->backWithErr(StrLib::ERR_CANT_DELETE);
        }
        return $this->backWithSuc('Допник удален с сайта');
    }

    public function removeCard(Request $req) {
        if (!$req->has('card_number')) {
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        $card = Card::where('card_number', $req->card_number)->first();
        if (is_null($card)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        if (!$card->delete()) {
            return $this->backWithErr(StrLib::ERR_CANT_DELETE);
        }
        return $this->backWithSuc('Карта удалена с сайта. Добавь его в физ лицах в карты');
    }

    public function getPhotosFolder(Request $req) {
        
    }

    public function removeOrder(Request $req) {
        if (!$req->has('order_number')) {
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        $order = Order::where('number', $req->order_number)->get();
        if (is_null($order)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        foreach($order as $o){
            if (!$o->delete()) {
                return $this->backWithErr(StrLib::ERR_CANT_DELETE);
            }
        }
        return $this->backWithSuc('Ордер удален ТОЛЬКО с сайта.');
    }

    public function changePhotoFolder(Request $req) {
        if (!$req->has('claim_id')) {
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        $claim = Claim::find($req->claim_id);
        if (is_null($claim)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        $photos = \App\Photo::where('claim_id', $req->claim_id)->get();
        DB::beginTransaction();
        foreach ($photos as $p) {
            if (strstr($p->path, $req->oldseriesnumber) === FALSE) {
                DB::rollback();
                return redirect()->back()->with('msg_err', 'Фотографии на такой паспорт не найдено, проверьте значение в поле старый паспорт');
            }
            $p->path = str_replace($req->oldseriesnumber, $req->seriesnumber, $p->path);
            if (!$p->save()) {
                DB::rollback();
                return redirect()->back()->with('msg_err', 'Ошибка при сохранении пути в фотографии');
            }
        }
        Storage::move($req->oldseriesnumber, $req->seriesnumber);
        DB::commit();
        return redirect()->back()->with('msg_suc', StrLib::SUC);
    }

    public function getPromocodeInfo(Request $req) {
        if (!$req->has('promocode_number')) {
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        $promo = \App\Promocode::where('number', $req->promocode_number)->first();
        if (is_null($promo)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        $claims = Claim::where('claims.promocode_id', $promo->id)
                ->select(['claims.id_1c as claim_id_1c', 'passports.fio as fio'])
                ->leftJoin('passports', 'claims.passport_id', '=', 'passports.id')
                ->get();
        $loan = Loan::where('loans.promocode_id', $promo->id)
                ->select(['loans.id_1c as loan_id_1c', 'passports.fio as fio', 'loans.id as loan_id'])
                ->leftJoin('claims', 'loans.claim_id', '=', 'claims.id')
                ->leftJoin('passports', 'claims.passport_id', '=', 'passports.id')
                ->first();
        Session::set('promo_claims', $claims);
        Session::set('promo_loan', $loan);
        Session::set('promocode_number', $req->promocode_number);
        return redirect('adminpanel/problemsolver');
    }

    public function changePhone(Request $req) {
        $contracts = \App\ContractForm::get();
        $num = 0;
        DB::beginTransaction();
        foreach ($contracts as $item) {
            $num += substr_count($item->template, $req->needle);
            $item->template = str_replace($req->needle, $req->replacer, $item->template);
            if (!$item->save()) {
                DB::rollback();
                return $this->backWithErr('Ошибка ' . $item->name);
            }
        }
        DB::commit();
        return $this->backWithSuc('Заменено '.$num.' раз');
    }
    
    public function changeRepaymentUser(Request $req){
        if(!$req->has('id_1c')){
            return $this->backWithErr();
        }
        $rep = \App\Repayment::where('id_1c',$req->id_1c)->first();
        if(is_null($rep)){
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        $rep->user_id = $req->user_id;
        if(!$rep->save()){
            return $this->backWithErr();
        }
        return $this->backWithSuc();
    }
    
    public function removeClaim(Request $req){
        if(!$req->has('id_1c')){
            return $this->backWithErr();
        }
        $claim = Claim::where('id_1c',$req->id_1c)->first();
        if(is_null($claim)){
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        if($claim->delete()){
            return $this->backWithSuc();
        }
        return $this->backWithErr();
    }

}

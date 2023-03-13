<?php

namespace App\Http\Controllers;

use App\Http\Requests,
    App\Http\Controllers\Controller,
    Illuminate\Http\Request,
    Auth,
    App\Claim,
    App\Card,
    App\Photo,
    \App\Customer,
    App\Passport,
    App\about_client,
    Validator,
    Input,
    Redirect,
    App\ClaimForm,
    App\AdSource,
    App\LoanGoal,
    App\Promocode,
    Illuminate\Support\Facades\DB,
    App\LiveCondition,
    App\EducationLevel,
    App\Loan,
    Carbon\Carbon,
    App\Spylog\Spylog,
    App\MySoap,
    App\MaritalType,
    Illuminate\Support\Facades\URL,
    App\Synchronizer,
    App\Utils\StrLib,
    Illuminate\Support\Facades\Storage,
    App\Utils\HelperUtil,
    Log;

class ClaimController extends Controller {

    private $validatorMessages;
    private $validatorFields = [
        'fio' => 'required',
        'series' => 'required|numeric',
        'number' => 'required|numeric',
        'birth_city' => 'required',
        'issued' => 'required',
        'subdivision_code' => 'required',
        'issued_date' => 'required',
        'birth_date' => 'required',
        'address_region' => 'required',
        'fact_address_region' => 'required',
        'anothertelephone' => 'required',
    ];
    static $claimCheckboxes = ['drugs', 'alco', 'stupid', 'badspeak', 'pressure', 'dirty',
        'smell', 'badbehaviour', 'soldier', 'watch', 'other', 'pensioner',
        'postclient', 'armia', 'poruchitelstvo', 'zarplatcard', 'uki'];

    public function __construct() {
//        $this->middleware('auth');

        $this->validatorMessages = [
            'required' => 'Поле :attribute должно быть заполнено.',
            'integer' => 'Поле :attribute должно быть целым числом.',
            'alpha' => 'Поле :attribute должно быть буквы.',
            'numeric' => 'Поле :attribute должно быть числом.',
        ];
    }

    /**
     * Открывает редактор заявки для создания новой заявки. 
     * Ищет клиента по базе и в 1с, если не находит в базе.
     * @param Request $request
     * @return type
     */
    public function create(Request $request) {
        if (Auth::check()) {
            $data = [
                'loangoals' => LoanGoal::pluck('name', 'id'),
                'adsources' => AdSource::pluck('name', 'id'),
                'education_levels' => EducationLevel::pluck('name', 'id'),
                'live_conditions' => LiveCondition::pluck('name', 'id'),
                'maritaltypes' => MaritalType::pluck('name', 'id'),
                'stepenrodstv' => \App\Stepenrodstv::pluck('name', 'id'),
                'firstTime' => true
            ];
            $todayDateRange = [
                date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))),
                date('Y-m-d H:i:s', mktime(8, 0, 0, date('m'), date('d'), date('Y')))
            ];
            $claimExpDateRange = [
                Carbon::now()->subDays(config('options.claim_exp_time') + 1),
                Carbon::now()
            ];
            $loanKData = Synchronizer::updateLoanRepayments($request->series, $request->number);
            //ищем паспорт
            $passport = Passport::where('series', $request->series)->where('number', $request->number)->first();
            //если не нашли, то ищем старый паспорт
            if (is_null($passport) && $request->has('old_series') && $request->has('old_number') && $request->old_series != '' && $request->old_number != '' && !is_null($request->old_series) && !is_null($request->old_number)) {
                $old_passport = Passport::where('series', $request->old_series)->where('number', $request->old_number)->first();
            }
            //если паспорт найден
            if (!is_null($passport)) {
                $loans = $this->getUnclosedLoans($passport->customer_id);
                if (count($loans) > 0) {
                    $this->updateUnclosedLoans($loans);
                }
                $loans = $this->getUnclosedLoans($passport->customer_id);
                if (count($loans) > 0) {
                    Log::notice('ClaimController.create 1', $request->all());
                    return redirect('home')->with('msg_err', StrLib::ERR_HAS_LOAN);
                }
                $claim = Claim::where('passport_id', $passport->id)
                        ->whereBetween('created_at', $claimExpDateRange)
                        ->where('status', '<>', [Claim::STATUS_DECLINED])
                        ->orderBy('created_at', 'desc')
                        ->first();
                //найдена заявка за период действия одобренной заявки
                if (!is_null($claim)) {
                    $created_at = with(new Carbon($claim->created_at))->format('d.m.Y H:i');
                    //если заявка на сегодняшний день есть то возвращает на рабочий стол
                    if ($claim->created_at >= $todayDateRange[0] && $claim->created_at <= $todayDateRange[1]) {
                        if ($claim->status == Claim::STATUS_ONEDIT || $claim->status == Claim::STATUS_NEW) {
                            Log::notice('ClaimController.create 2', $request->all());
                            return redirect('claims/edit/' . $claim->id)
                                            ->with('msg', 'ВНИМАНИЕ! Открыта уже созданная для клиента заявка от ' . $created_at)
                                            ->with('class', 'alert-warning');
                        } else {
                            Log::notice('ClaimController.create 3', $request->all());
                            return redirect('home')->with('msg_err', 'На сегодня уже была заявка для данного контрагента от ' . $created_at);
                        }
                    } else {
                        $claimLoan = Loan::where('claim_id', $claim->id)->first();
                        if (is_null($claimLoan)) {
                            if ($claim->status == Claim::STATUS_ONEDIT || $claim->status == Claim::STATUS_NEW || $claim->status == Claim::STATUS_ONCHECK) {
                                Log::notice('ClaimController.create 4', $request->all());
                                return redirect('claims/edit/' . $claim->id)
                                                ->with('msg', 'ВНИМАНИЕ! Открыта уже созданная для клиента заявка от ' . $created_at)
                                                ->with('class', 'alert-warning');
                            } else if ($claim->status == Claim::STATUS_ACCEPTED) {
                                Log::notice('ClaimController.create 5', $request->all());
                                return redirect('home')->with('msg_err', 'У клиента уже есть одобренная заявка от ' . $created_at);
                            } else if ($claim->status == Claim::STATUS_ONCHECK) {
                                Log::notice('ClaimController.create 6', $request->all());
                                return redirect('home')->with('msg_err', 'У клиента уже есть заявка на проверке от ' . $created_at);
                            }
                        } else if (!$claimLoan->closed) {
                            Log::notice('ClaimController.create 7', $request->all());
                            return redirect('home')->with('msg_err', StrLib::ERR_HAS_LOAN . $created_at);
                        }
                    }
                }
                $customer = Customer::find($passport->customer_id);
                //проверка на то чтобы паспорт для займа не был старым
                $last_passport = Passport::where('customer_id', $passport->customer_id)->orderBy('created_at', 'desc')->first();
                if ($last_passport->id != $passport->id) {
                    $passport = $last_passport;
                }
                $claimForm = new ClaimForm(null, $customer, $passport);
                $data['claimForm'] = $claimForm;
                $data['header'] = ($claimForm->postclient) ? 'newpost' : 'new';
            } else if (isset($old_passport) && !is_null($old_passport)) {
                //если паспорт не найден, но найден предыдущий
                $loans = $this->getUnclosedLoans($old_passport->customer_id);
                if (count($loans) > 0) {
                    Log::notice('ClaimController.create 8', $request->all());
                    return redirect('home')->with('msg_err', StrLib::ERR_HAS_LOAN);
                }

                //если заявка на сегодняшний день есть то возвращает на рабочий стол
                if ((Claim::where('customer_id', $old_passport->customer_id)->whereBetween('date', $todayDateRange)->count()) > 0) {
                    Log::notice('ClaimController.create 9', $request->all());
                    return redirect('home')->with('msg_err', StrLib::ERR_HAS_CLAIM_TODAY);
                }

                $customer = Customer::find($old_passport->customer_id);
                $about_client = about_client::where('id', $customer->id)->orderBy('created_at', 'desc')->first();
                $passport = new Passport();
                $passport->birth_city = $old_passport->birth_city;
                $passport->birth_date = $old_passport->birth_date;
                $passport->series = $request->series;
                $passport->number = $request->number;
                $data['claimForm'] = new ClaimForm(null, $customer, $passport, $about_client);
                $data['header'] = ($about_client->postclient) ? 'newpost' : 'new';
            } else { //новый займ для нового клиента
                $data['claimForm'] = new ClaimForm();
                //проверяем чтобы это был не редирек, для того чтобы не запрашивать лишний раз данные из 1с
                if (count(Input::old()) == 0) {
                    $data['claimForm']->fill($this->getCustomerFrom1c($request->series, $request->number, $request->old_series, $request->old_number));
                }
                $data['claimForm']->series = $request->series;
                $data['claimForm']->number = $request->number;
                $data['header'] = 'new';
            }

            if (is_array($loanKData)) {
                if (array_key_exists('loan', $loanKData)) {
                    $data['claimForm']->loank_loan_id_1c = $loanKData['loan']->id_1c;
                }
                $data['claimForm']->loank_claim_id_1c = $loanKData['claim']->id_1c;
                $data['claimForm']->loank_customer_id_1c = $loanKData['claim']->customer->id_1c;
                $data['claimForm']->loank_closing_id_1c = '';
                if (array_key_exists('repayments', $loanKData)) {
                    foreach ($loanKData['repayments'] as $rep) {
                        if ($rep->repaymentType->isClosing()) {
                            $data['claimForm']->loank_closing_id_1c = $rep->id_1c;
                        }
                    }
                }
            }
            $data['claimForm']->timestart = Carbon::now()->format('Y-m-d H:i:s');
            Spylog::log(Spylog::ACTION_NEW, 'claims', null, 'Открытие формы заявки для паспорта: ' . $request->series . ' ' . $request->number);
            //убираем данные о выдаче паспорта
            $data['claimForm']->issued = '';
            $data['claimForm']->issued_date = '';
            $data['claimForm']->subdivision_code = '';
            $data['claimForm']->birth_city = '';

            return view('claim.claim', $data);
        } else {
            return redirect('home');
        }
    }

    public function getUnclosedLoans($customerID) {
        $loans = Loan::leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                        ->where('loans.closed', Loan::STATUS_OPENED)
                        ->where('claims.customer_id', $customerID)
                        ->select('loans.id_1c')->get();
        return $loans;
    }

    public function updateUnclosedLoans($loans) {
        foreach ($loans as $loan) {
            Synchronizer::updateLoanRepayments(null, null, $loan->id_1c);
        }
    }

    /**
     * Получает данные по клиенту из 1с.
     * @param type $series серия текущего паспорта
     * @param type $number номер текущего паспорта
     * @param type $old_series серия предыдущего паспорта
     * @param type $old_number номер предыдущего паспорта
     * @return array возвращает массив с данными или пустой массив
     */
    public function getCustomerFrom1c($series, $number, $old_series, $old_number) {
        $res = MySoap::passport([
                    'series' => $series,
                    'number' => $number,
                    'old_series' => $old_series,
                    'old_number' => $old_number,
        ]);
        if (is_array($res)) {
            foreach ($res as $k => &$v) {
                if (in_array($k, ClaimController::$claimCheckboxes)) {
                    $res[$k] = ($v == 'Да') ? 1 : 0;
                }
                if ($k == 'sex') {
                    $res[$k] = ($v == 'Мужской') ? 1 : 0;
                }
            }
        }
        \PC::debug($res);
        return (is_array($res)) ? $res : [];
    }

    /**
     * Сохраняет новую заявку в базу. Так же сохраняет новые доп. данные по клиенту.
     * И может сохранять новые или обновлять старые данные по паспорту и клиенту.
     * @param Request $request
     * @return type
     */
    public function store(Request $request) {
        $validator = Validator::make($request->all(), $this->validatorFields, $this->validatorMessages);

        $age = Carbon::now()->diffInYears(new Carbon(Input::get('birth_date')));
        if ($age < 18 || $age > 90) {
            return redirect()->back()->withInput(Input::all())->with('msg_err', 'Внимание! Возраст клиента должен быть больше 18 и меньше 90 лет.');
        }
        if (Carbon::now()->lt(new Carbon(Input::get('issued_date'))) || Carbon::now()->lt(new Carbon(Input::get('birth_date'))) || Carbon::now()->lt(new Carbon(Input::get('address_reg_date')))) {
            return redirect()->back()->withInput(Input::all())->withErrors($validator->errors());
        }
        if ($validator->fails()) {
            return redirect()->back()->withInput(Input::all())->withErrors($validator->errors());
        }
        DB::beginTransaction();
        $spylog = new Spylog();
        $input = Input::all();
        $customer = Customer::findOrNew($request->customer_id);
        $input['creator_id'] = Auth::user()->id;
//        $spylog->addModelChangeData('customers', $customer, $input);
        $customer->fill($input);
        $customer->creator_id = $input['creator_id'];
        $customer->id_1c = $input['customer_id_1c'];
        $customer->snils = $input['snils'];
        if (!$customer->save()) {
            DB::rollback();
            return redirect()->back()->withInput(Input::all())->with('msg_err', 'Ошибка при сохранении контрагента.');
        }
        Spylog::logModelAction(Spylog::ACTION_CREATE, Spylog::TABLE_CUSTOMERS, $customer->toArray());

        if (array_key_exists('card_number', $input) && is_null(Card::where('card_number', $input['card_number'])->first())) {
            /**
             * Закрыть все остальные карты на этом клиенте
             */
            $cards = Card::where('customer_id', $customer->id)->get();
            foreach ($cards as $item) {
                $item->status = Card::STATUS_CLOSED;
                $item->save();
            }
            /**
             * добавить новую карту на клиента
             */
            $card = new Card();
            $card->card_number = $input['card_number'];
            $card->secret_word = $input['secret_word'];
            $card->customer_id = $customer->id;
            $card->save();
            Spylog::logModelAction(Spylog::ACTION_CREATE, Spylog::TABLE_CARDS, $card->toArray());
        }

        //на случай если паспорт с такой серией и номером есть в базе, то брать его
        $passport = Passport::where('series', $request->series)->where('number', $request->number)->first();
        if (is_null($passport)) {
            $passport = new Passport();
        }
        $input['customer_id'] = $customer->id;
//        $spylog->addModelChangeData('passports', $passport, $input);
        foreach (['birth_date', 'issued_date', 'address_reg_date'] as $dateField) {
            $input[$dateField] = \App\StrUtils::parseDate($input[$dateField]);
        }
        $passport->fill($input);
        if (!$passport->save()) {
            DB::rollback();
            return redirect()->back()->withInput(Input::all())->with('msg_err', 'Ошибка при сохранении паспортных данных.');
        }
        Spylog::logModelAction(Spylog::ACTION_CREATE, Spylog::TABLE_PASSPORTS, $passport->toArray());

        $about_client = new about_client();
        $about_client->fill($input);
        if (array_key_exists('recomend_phone_1', $input)) {
            $about_client->recomend_phone_1 = \App\StrUtils::removeNonDigits($input['recomend_phone_1']);
        }
        if (array_key_exists('recomend_phone_2', $input)) {
            $about_client->recomend_phone_2 = \App\StrUtils::removeNonDigits($input['recomend_phone_2']);
        }
        if (array_key_exists('recomend_phone_3', $input)) {
            $about_client->recomend_phone_3 = \App\StrUtils::removeNonDigits($input['recomend_phone_3']);
        }
        $about_client->customer_id = $customer->id;
        if (!$about_client->save()) {
            DB::rollback();
            return redirect()->back()->withInput(Input::all())->with('msg_err', 'Ошибка при сохранении доп. данных о клиенте.');
        }
        Spylog::logModelAction(Spylog::ACTION_CREATE, Spylog::TABLE_ABOUT_CLIENTS, $about_client->toArray());

        $claim = new Claim;
        $claim->fill($input);
        $claim->passport_id = $passport->id;
        $claim->about_client_id = $about_client->id;
        $claim->customer_id = $customer->id;
        $claim->summa = $input['sum'];
        $claim->date = date("Y-m-d H:i:s");
        $claim->user_id = Auth::id();
        $claim->subdivision_id = Auth::user()->subdivision_id;
        if (!$claim->save()) {
            DB::rollback();
            return redirect()->back()->withInput(Input::all())->with('msg_err', 'Ошибка при сохранении промокода.');
        }
        Spylog::logModelAction(Spylog::ACTION_CREATE, Spylog::TABLE_CLAIMS, $claim->toArray());
//        $spylog->addModelData('claims', $claim);
//        $spylog->addModelData('about_clients', $about_client);
//        $spylog->save(Spylog::ACTION_CREATE, 'claims', $claim->id);
        DB::commit();

        return redirect('home')->with('msg_suc', StrLib::SUC_SAVED);
    }

    /**
     * Открывает форму редактирования заявки
     * @param type $claim_id айди заявки
     * @return type
     */
    public function edit($claim_id) {
        $claim = Claim::find($claim_id);
        $is_teleport = ($claim->subdivision->name == 'Teleport' && $claim->status != Claim::STATUS_ACCEPTED && $claim->status != Claim::STATUS_DECLINED);
        if (is_null($claim) || (!in_array($claim->status, HomeController::EDITABLE_STATUSES) && !Auth::user()->isAdmin() && !$is_teleport)) {
            return redirect('home')->with('msg_err', StrLib::ERR_NOT_ADMIN);
        }
        if (Auth::check()) {
            Spylog::log(Spylog::ACTION_OPEN, 'claims', $claim_id);
            return view('claim.claim', [
                'loangoals' => LoanGoal::pluck('name', 'id'),
                'adsources' => AdSource::pluck('name', 'id'),
                'education_levels' => EducationLevel::pluck('name', 'id'),
                'live_conditions' => LiveCondition::pluck('name', 'id'),
                'maritaltypes' => \App\MaritalType::pluck('name', 'id'),
                'stepenrodstv' => \App\Stepenrodstv::pluck('name', 'id'),
                'claimForm' => new ClaimForm($claim_id),
                'header' => 'edit'
            ]);
        } else {
            return view('home');
        }
    }

    /**
     * Обновляет заявку, паспорт, доп.данные, контрагента. Вызывается по кнопке сохранить в редактировании заявки.
     * @param Request $request
     * @return type
     */
    public function update(Request $request) {
        $validator = Validator::make($request->all(), $this->validatorFields, $this->validatorMessages);

        $age = Carbon::now()->diffInYears(new Carbon(Input::get('birth_date')));
        if ($age < 18 || $age > 90) {
            return redirect()->back()->withInput(Input::all())->with('msg_err', 'Внимание! Возраст клиента должен быть больше 18 и меньше 90 лет.');
        }
        if ($validator->fails()) {
            return redirect()->back()->withInput($request->all())->withErrors($validator->errors());
        }
        $passport = Passport::find($request->passport_id);
        if (is_null($passport)) {
            return redirect()->back()->withInput($request->all())->with('msg_err', "Паспорт не найден");
        }
//        if ($request->has('series') && $request->has('number')) {
//            $passport = Passport::where('series', $request->series)->where('number', $request->number)->first();
//            if (is_null($passport)) {
//                return redirect()->back()->withInput($request->all())->with('msg_err', "Неверные серия и номер паспорта");
//            }
//            if ($passport->id != $request->passport_id) {
//                return redirect()->back()->withInput($request->all())->with('msg_err', StrLib::ERR_DUPLICATE_PASSPORT);
//            }
//        }
        DB::beginTransaction();
        $spylog = new Spylog();
        $input = Input::all();
        $customer = Customer::find($request->customer_id);
        if (is_null($customer)) {
            DB::rollback();
            return redirect('home')->with('msg_err', 'Клиент не найден.');
        }
        $spylog->addModelChangeData('customers', $customer, $input);
        $customer->fill($input);
        $customer->snils = $input['snils'];
        if (!$customer->save()) {
            DB::rollback();
            return redirect('home')->with('msg_err', 'Ошибка в данных клиента. Заявка не была сохранена.');
        }

        $claim = Claim::find($request->claim_id);
        if (is_null($claim)) {
            DB::rollback();
            return redirect('home')->with('msg_err', 'Заявка не найдена.');
        }
        //пересохранить фото на другого контрагента
        if ($request->customer_id != $claim->customer_id) {
            $photos = Photo::where('customer_id', $customer->id)->where('created_at', '>=', $claim->created_at->format('Y-m-d H:i:s'))->get();
            foreach ($photos as $p) {
                $p->customer_id = $customer->id;
                $p->save();
            }
        }

        $input['summa'] = $input['sum'];
        $input['date'] = date("Y-m-d H:i:s");
        $spylog->addModelChangeData('claims', $claim, $input);
        $claim->fill($input);
        if ($request->has('uki')) {
            $claim->uki = 1;
        } else {
            $claim->uki = 0;
        }
        //пересохраняет ид паспорта и контрагента, на случай если были изменены через проверку паспорта
        $claim->passport_id = $passport->id;
        $claim->customer_id = $customer->id;
        if (!is_null($claim->id_1c)) {
            $input['promocode_number'] = (in_array('promocode_number', $input)) ? $input['promocode_number'] : '';
            $res1c = $this->_checkPromocode($claim->id_1c, $input["promocode_number"]);
            if ($res1c['res'] == 1) {
                $promo = ($input["promocode_number"] != "") ? Promocode::where('number', $input["promocode_number"])->first() : null;
                if (is_null($promo)) {
                    $promo = new Promocode();
                    $promo->number = $input["promocode_number"];
                    if (!$promo->save()) {
                        DB::rollback();
                        return redirect('home')->withInput(Input::all())->with('msg_err', 'Ошибка при сохранении промокода.');
                    }
                }
                $claim->promocode_id = (!is_null($promo)) ? $promo->id : null;
            }
        }
        $claim->date = $input['date'];
        if (Auth::user()->isAdmin()) {
            if ($request->has('subdivision_id') && $request->subdivision_id != $claim->subdivision_id) {
                $claim->subdivision_id = $request->subdivision_id;
            }
            if ($request->has('created_at') && $request->created_at != $claim->created_at) {
                $claim->created_at = with(new Carbon($request->created_at))->format('Y-m-d H:i:s');
                $claim->date = with(new Carbon($request->created_at))->format('Y-m-d H:i:s');
            }
        }
        if (!$claim->save()) {
            DB::rollback();
            return redirect('home')->with('msg_err', 'Ошибка в данных заявки. Заявка не была сохранена.');
        }

        $passport = Passport::find($request->passport_id);
        if (is_null($passport)) {
            DB::rollback();
            return redirect('home')->with('msg_err', 'Паспорт не найден.');
        }
        if (array_key_exists('number', $input) && array_key_exists('series', $input)) {
            if (!is_null($claim->id_teleport) && $passport->series != $input['series'] || $passport->number != $input['number']) {
                $existingPassport = Passport::getBySeriesAndNumber($input['series'], $input['number']);
                if (!is_null($existingPassport)) {
                    $passport = $existingPassport;
                    $claim->passport_id = $existingPassport->id;
                    $claim->save();
                }
            }
        }
        $spylog->addModelChangeData('passports', $passport, $input);

        $passport->fill($input);
        if (!$passport->save()) {
            DB::rollback();
            return redirect('home')->with('msg_err', 'Ошибка в данных паспорта. Заявка не была сохранена.');
        }

        $about_client = about_client::find($request->about_client_id);
        if (is_null($about_client)) {
            DB::rollback();
            return redirect('home')->with('msg_err', 'Доп. данные по клиенту не найдены.');
        }
        $spylog->addModelChangeData('about_clients', $about_client, $input);
//        $checkboxes = ['drugs', 'alco', 'stupid', 'badspeak', 'pressure', 'dirty',
//            'smell', 'badbehaviour', 'soldier', 'watch', 'other', 'pensioner',
//            'postclient', 'armia', 'poruchitelstvo', 'zarplatcard'];
        foreach (ClaimController::$claimCheckboxes as $cb) {
            if (!array_key_exists($cb, $input)) {
                $input[$cb] = 0;
            }
        }
        $about_client->fill($input);
        if (array_key_exists('recomend_phone_1', $input)) {
            $about_client->recomend_phone_1 = \App\StrUtils::removeNonDigits($input['recomend_phone_1']);
        }
        if (array_key_exists('recomend_phone_2', $input)) {
            $about_client->recomend_phone_2 = \App\StrUtils::removeNonDigits($input['recomend_phone_2']);
        }
        if (array_key_exists('recomend_phone_3', $input)) {
            $about_client->recomend_phone_3 = \App\StrUtils::removeNonDigits($input['recomend_phone_3']);
        }
        if (!$about_client->save()) {
            DB::rollback();
            return redirect('home')->with('msg_err', 'Ошибка в доп. данных по клиенту. Заявка не была сохранена.');
        }
        //удалить контрагентов созданных с терминала, с таким же телефоном и без идентификатора в 1с
//        Customer::where('telephone', $customer->telephone)->whereNull('id_1c')->delete();

        if (!$spylog->save(Spylog::ACTION_UPDATE, 'claims', $claim->id)) {
            DB::rollback();
            return redirect('home')->with('msg_err', 'Ошибка в логах. Заявка не была сохранена.');
        }
        DB::commit();
        if (!is_null($claim->id_1c)) {
//            MySoap::sendXML(MySoap::createXML(['customer_id_1c'=>$customer->id_1c,'pens'=>$about_client->pensioner,'post'=>$about_client->postclient,'created_at'=>with(new Carbon($claim->created_at))->format('YmdHis')]));
        }
//        $docs = Synchronizer::getContractsFrom1c($claim->passport->series, $claim->passport->number);
//        if (is_array($docs) && array_key_exists('loan', $docs) && !$docs['loan']->closed) {
//            $claim->status = Claim::STATUS_DECLINED;
//            $claim->save();
//            return redirect('home')->with('msg_err', ' У клиента уже есть невыплаченный займ');
//        }
//        return redirect('claims/summary/' . $claim->id)->with('msg_suc', StrLib::SUC_SAVED);
        $this->controlPhotoFolder($claim);
        return redirect('home')->with('msg_suc', StrLib::SUC_SAVED);
    }

    public function summary($claim_id) {
        $claim = Claim::find($claim_id);
        if (is_null($claim)) {
            return redirect('home')->with('msg_err', StrLib::ERR_NULL);
        }
        return view('claim.summary')
                        ->with('loan', Loan::where('claim_id', $claim->id)->first())
                        ->with('claim', $claim)
                        ->with('photos', \App\Photo::where('claim_id', $claim_id)->get())
                        ->with('claims', Claim::where('customer_id', $claim->customer_id)->where('id', '<>', $claim->id)->get())
                        ->with('loans', Loan::leftJoin('claims', 'loans.claim_id', '=', 'claims.id')->where('claims.customer_id', $claim->customer_id));
    }

    /**
     * Меняет статус у заявки
     * @param type $claim_id идентификатор заявки
     * @param type $status статус, который нужно поставить
     * @return type
     */
    public function changeStatus($claim_id, $status) {
        if (!Auth::user()->isAdmin() && $status != Claim::STATUS_ONCHECK) {
            return redirect()->back()->with('msg_err', 'Недостаточно прав для изменения заявки');
        }
        $claim = Claim::find($claim_id);
        if (!is_null($claim)) {
            if (!is_null($claim->id_1c)) {
                $this->checkStatus($claim);
                $claimUpd = Claim::find($claim_id);
                if (!Auth::user()->isAdmin() && ($claimUpd->status == Claim::STATUS_DECLINED || $claimUpd->status == Claim::STATUS_ACCEPTED || !is_null(Loan::where('claim_id', $claim_id)->first()))) {
                    return redirect()->back()->with('msg_err', 'Смена статуса невозможна (статус заявки - "Отказано" или "Одобрено" или на заявку уже создан договор)');
                }
            }
        } else {
            return redirect()->back()->with('msg_err', 'Займ не найден');
        }
        if (is_null(Loan::where('claim_id', $claim_id)->first())) {
//            $docs = Synchronizer::updateLoanRepayments($claim->passport->series, $claim->passport->number);
//            if (is_array($docs) && array_key_exists('loan', $docs) && !$docs['loan']->closed) {
//                return redirect()->back()->with('msg_err', 'У клиента уже есть невыплаченный займ');
//            }

            $old_status = (string) $claim->status;
            $claim->status = $status;
            if ($status == Claim::STATUS_ONCHECK || $status == Claim::STATUS_ONEDIT) {
                $photosNum = \App\Photo::where('claim_id', $claim_id)->count('id');
                if ($photosNum == 0 && !Auth::user()->isAdmin() && Auth::user()->subdivision->id != config('options.office_subdivision_id')) {
                    return redirect('/')->with('msg_err', 'Невозможно отправить без фотографий. Добавьте фотографии для заявки!');
                }
                $res1c = $this->sendClaimTo1c($claim);
                if (!$res1c['res']) {
                    return redirect()->back()->with('msg_err', (is_array($res1c) && array_key_exists('msg_err', $res1c) ? $res1c['msg_err'] : 'Ошибка при связи с 1С'));
                }
            }
            if ($claim->save()) {
//                $this->checkForDebtor($claim);
//                Spylog::log(Spylog::ACTION_STATUS_CHANGE, 'claims', $claim_id, $old_status . '->' . $status);
                return redirect()->back()->with('msg_suc', 'Статус заявки изменён');
            } else {
                return redirect()->back()->with('msg_err', 'Ошибка при смене статуса, попробуйте еще раз.');
            }
        } else {
            return redirect()->back()->with('msg_err', 'Займ на заявку уже выдан');
        }
    }

    /**
     * Запрашивает из 1ски документы и сохраняет их в базе, и возвращает 0 или 1 если есть кредитник. 
     * @param int $claim_id ИД заявки
     * @return int
     */
    public function hasLoan($claim_id) {
        $claim = Claim::find($claim_id);

        if (!is_null($claim)) {
            if (Card::where('customer_id', $claim->customer_id)->count() == 0) {
                $p = $claim->passport;
                $res1c = MySoap::passport([
                            'series' => $p->series,
                            'number' => $p->number,
                            'old_series' => '',
                            'old_number' => '',
                ]);
                if ($res1c['res']) {
                    if (array_key_exists('card_number', $res1c) && array_key_exists('secret_word', $res1c)) {
                        Card::createCard($res1c['card_number'], $res1c['secret_word'], $claim->customer_id);
                    }
                }
            }

            $checkRes = $this->checkStatus($claim);
            if (!is_null($checkRes) && array_key_exists('loan', $checkRes)) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public function checkStatus(Claim $claim) {
        $loanCtrl = new LoanController();
        return $loanCtrl->updateLoanRepayments($claim->passport->series, $claim->passport->number);
    }

    /**
     * Отметить на удаление
     * @param type $claim_id id заявки
     * @return type
     */
    public function markForRemove($claim_id) {
        if (is_null(Loan::where('claim_id', $claim_id)->first())) {
            $claim = Claim::find($claim_id);
            if (is_null($claim)) {
                return redirect()->back()->with('msg', 'Заявка не найдена.')->with('class', 'alert-danger');
            }
            if (($claim->status == Claim::STATUS_NEW && $claim->delete()) || $claim->deleteThrough1c()) {
                \App\RemoveRequest::setDone($claim_id, MySoap::ITEM_CLAIM);
                Spylog::log(Spylog::ACTION_MARK4REMOVE, 'claims', $claim_id);
                return redirect()->back()->with('msg', 'Заявка помечена на удаление.')->with('class', 'alert-success');
            } else {
                return redirect()->back()->with('msg', 'Ошибка! Не удалось пометить на удаление.')->with('class', 'alert-danger');
            }
        } else {
            return redirect()->back()->with('msg', 'Ошибка. На заявку уже оформлен займ!')->with('class', 'alert-danger');
        }
    }

    public function markForRemove2($claim_id) {
        if (is_null(Loan::where('claim_id', $claim_id)->first())) {
            $claim = Claim::find($claim_id);
            if (is_null($claim)) {
                return redirect()->back()->with('msg', 'Заявка не найдена.')->with('class', 'alert-danger');
            }
            if ($claim->delete()) {
                \App\RemoveRequest::setDone($claim_id, MySoap::ITEM_CLAIM);
                Spylog::log(Spylog::ACTION_MARK4REMOVE, 'claims', $claim_id);
                return redirect()->back()->with('msg', 'Заявка помечена на удаление.')->with('class', 'alert-success');
            } else {
                return redirect()->back()->with('msg', 'Ошибка! Не удалось пометить на удаление.')->with('class', 'alert-danger');
            }
        } else {
            return redirect()->back()->with('msg', 'Ошибка. На заявку уже оформлен займ!')->with('class', 'alert-danger');
        }
    }

    /**
     * Удалить заявку
     * @param type $claim_id id заявки
     * @return type
     */
    public function remove($claim_id) {
        $claim = Claim::find($claim_id);
        if (is_null($claim)) {
            return redirect()->back()->with('msg', 'Ошибка при удалении заявки!')->with('class', 'alert-danger');
        }
        if ($claim->trashed()) {
            if ($claim->forceDelete()) {
                Spylog::logModelAction(Spylog::ACTION_DELETE, 'claims', $claim);
                return redirect()->back()->with('msg', 'Заявка удалена.')->with('class', 'alert-success');
            } else {
                return redirect()->back()->with('msg', 'Ошибка при удалении заявки!')->with('class', 'alert-danger');
            }
        } else {
            return redirect()->back()->with('msg', 'Заявка не помечена на удаление.')->with('class', 'alert-danger');
        }
    }

    /**
     * Отправить заявку в 1С и прописывает пришедший номер заявки из 1С в заявку
     * @param \App\Claim $claim модель заявки
     * @return type
     */
    public function sendClaimTo1c($claim) {
        $customer = $claim->customer;
        $subdiv = \App\Subdivision::find($claim->subdivision_id);
        $user = \App\User::find($claim->user_id);

        $passport = Passport::find($claim->passport_id);
        if (is_null($passport)) {
            Log::error('Не найден паспорт', ['passport' => $passport]);
            return ['res' => 0, 'msg' => 'Не найден паспорт'];
        }
        $old_passport = Passport::where('created_at', '<', $passport->created_at)
                ->where('customer_id', $claim->customer_id)
                ->orderBy('created_at', 'desc')
                ->first();
        $about = about_client::find($claim->about_client_id);
        //если не город то вписывать название города в отдельные поля для населенного пункта
//        $address_city1 = (str_replace('г.', '', $passport->address_city) == $passport->address_city) ? $passport->address_city : '';
//        $fact_address_city1 = (str_replace('г.', '', $passport->fact_address_city) == $passport->fact_address_city) ? $passport->fact_address_city : '';
        $address_city1 = (str_replace('г.', '', $passport->address_city) == $passport->address_city) ? $passport->address_city1 : '';
        $fact_address_city1 = (str_replace('г.', '', $passport->fact_address_city) == $passport->fact_address_city) ? $passport->fact_address_city1 : '';
        if (is_null($claim) || is_null($passport) || is_null($about) || is_null($subdiv) || is_null($user)) {
            Log::error('Не найдены все необходимые по заявке данные', ['claim' => $claim, 'passport' => $passport, 'about' => $about, 'subdiv' => $subdiv, 'user' => $user]);
            return ['res' => 0, 'msg' => 'Не найдены все необходимые по заявке данные'];
        }

        if (!is_null(Auth::user()) && !Auth::user()->isAdmin() && $claim->created_at->lt(Carbon::now()->subDays(31))) {
            Log::error('sendclaimto1c', ['claim' => $claim]);
            return ['res' => 0, 'msg' => 'Заявке более 30 дней'];
        }

        $data = [
            'issued_date' => $passport->issued_date,
            'issued' => $passport->issued,
            'subdivision_code' => $passport->subdivision_code,
            'number' => $passport->number,
            'series' => $passport->series,
            'address_reg_date' => with(new Carbon($passport->address_reg_date))->format('Ymd'),
            'address_region' => $this->handleAddress($passport->address_region),
//            'address_city' => ($address_city1 == '') ? $passport->address_city : '',
            'address_city' => $passport->address_city,
            'address_street' => $this->handleAddress($passport->address_street),
            'address_house' => $passport->address_house,
            'address_apartment' => $passport->address_apartment,
            'fact_address_region' => $this->handleAddress($passport->fact_address_region),
//            'fact_address_city' => ($fact_address_city1 == '') ? $passport->fact_address_city : '',
            'fact_address_city' => $passport->fact_address_city,
            'fact_address_street' => $this->handleAddress($passport->fact_address_street),
            'fio' => $passport->fio,
            'zip' => $passport->zip,
            'address_district' => $this->handleAddress($passport->address_district),
            'address_city1' => $address_city1,
            'address_building' => $passport->address_building,
            'fact_zip' => $passport->fact_zip,
            'fact_address_district' => $this->handleAddress($passport->fact_address_district),
            'fact_address_city1' => $fact_address_city1,
            'fact_address_house' => $passport->fact_address_house,
            'fact_address_building' => $passport->fact_address_building,
            'fact_address_apartment' => $passport->fact_address_apartment,
            'id' => (is_null($claim->id_1c)) ? "" : $claim->id_1c,
            'goal' => (is_null($about->goal)) ? 0 : $about->goal,
            'srok' => $claim->srok,
            'summa' => $claim->summa,
            'date' => Carbon::now()->format('YmdHis'),
            'comment' => $claim->comment,
            'user_id' => $user->id_1c,
            'subdivision_id' => $subdiv->name_id,
            'telephonehome' => $about->telephonehome,
            'stepenrodstv' => (is_null($about->stepenrodstv)) ? 0 : $about->stepenrodstv,
            'telephone' => \App\StrUtils::removeNonDigits($customer->telephone),
            'telephonerodstv' => $about->telephonerodstv,
            'avto' => (is_null($about->avto)) ? 0 : $about->avto,
            'stazlet' => (int) $about->stazlet,
            'organizacia' => $about->organizacia,
            'innorganizacia' => $about->innorganizacia,
            'dolznost' => $about->dolznost,
            'vidtruda' => $about->vidtruda,
            'fiorukovoditel' => $about->fiorukovoditel,
            'adresorganiz' => $about->adresorganiz,
            'telephoneorganiz' => $about->telephoneorganiz,
            'sex' => $about->sex,
            'zhusl' => (is_null($about->zhusl)) ? 1 : $about->zhusl,
            'deti' => $about->deti,
            'fiosuprugi' => $about->fiosuprugi,
            'fioizmena' => $about->fioizmena,
            'credit' => $about->credit,
            'dohod' => (int)$about->dohod,
            'dopdohod' => (int)$about->dopdohod,
            'pensionnoeudost' => \App\StrUtils::removeNonDigits($about->pensionnoeudost),
            'obrasovanie' => (is_null($about->obrasovanie)) ? 1 : $about->obrasovanie,
            'pensioner' => $about->pensioner,
            'postclient' => $about->postclient,
            'armia' => $about->armia,
            'poruchitelstvo' => $about->poruchitelstvo,
            'zarplatcard' => $about->zarplatcard,
            'alco' => $about->alco,
            'drugs' => $about->drugs,
            'stupid' => $about->stupid,
            'badspeak' => $about->badspeak,
            'pressure' => $about->pressure,
            'dirty' => $about->dirty,
            'smell' => $about->smell,
            'badbehaviour' => $about->badbehaviour,
            'soldier' => $about->soldier,
            'other' => $about->other,
            'watch' => $about->watch,
            'adsource' => (!is_null($about->adsource)) ? $about->adsource : 2,
            'birth_date' => with(new Carbon($passport->birth_date))->format('Ymd'),
            'birth_city' => $this->handleAddress($passport->birth_city),
            'anothertelephone' => $about->anothertelephone,
            'marital_type_id' => (!is_null($about->marital_type_id)) ? $about->marital_type_id : 1,
            'old_number' => (is_null($old_passport)) ? '' : $old_passport->number,
            'old_series' => (is_null($old_passport)) ? '' : $old_passport->series,
            'photo_url' => $claim->getPhotosFolderPath(true, false, true),
            'promocode_number' => (!is_null($claim->promocode)) ? $claim->promocode->number : '',
            'customer_id_1c' => (!is_null($customer->id_1c)) ? $customer->id_1c : '',
            'uki' => $claim->uki,
            'timestart' => (!is_null($claim->timestart) && $claim->timestart != '0000-00-00 00:00:00') ? with(new Carbon($claim->timestart))->format('YmdHis') : Carbon::now()->format('YmdHis'),
            'other_mfo' => (is_null($about->other_mfo)) ? '' : $about->other_mfo,
            'other_mfo_why' => (is_null($about->other_mfo_why)) ? '' : $about->other_mfo_why,
            'recomend_phone_1' => (is_null($about->recomend_phone_1)) ? '' : \App\StrUtils::parsePhone($about->recomend_phone_1),
            'recomend_fio_1' => (is_null($about->recomend_fio_1)) ? '' : $about->recomend_fio_1,
            'recomend_phone_2' => (is_null($about->recomend_phone_2)) ? '' : \App\StrUtils::parsePhone($about->recomend_phone_2),
            'recomend_fio_2' => (is_null($about->recomend_fio_2)) ? '' : $about->recomend_fio_2,
            'recomend_phone_3' => (is_null($about->recomend_phone_3)) ? '' : \App\StrUtils::parsePhone($about->recomend_phone_3),
            'recomend_fio_3' => (is_null($about->recomend_fio_3)) ? '' : $about->recomend_fio_3,
            'snils' => (!is_null($customer->snils)) ? $customer->snils : '',
            'last_arm_edited_user' => (is_null(Auth::user())) ? $user->id_1c : Auth::user()->id_1c,
            'id_teleport' => (is_null($claim->id_teleport)) ? '' : $claim->id_teleport,
            'dohod_husband' => (int)$about->dohod_husband,
            'pension' => (int)$about->pension
        ];
        if ($claim->subdivision->is_terminal) {
            $data['photo_url'] = $claim->getPhotosFolderPath(true, false, true);
        }

        $res1c = MySoap::createClaim($data);
        if ($res1c['res'] == 0) {
            Log::error('Ошибка при отправке заявки в 1с. ' . $res1c['msg_err'], (array) $res1c);
            return $res1c;
        }
        $old_id_1c = (string) $claim->id_1c;

        $oldclaim = Claim::where('id_1c', $res1c['id_1c'])->first();
        //если ид_1с пустой, то заявка в первый раз отправляется 
        $isNewClaim = is_null($claim->id_1c);
        if ($isNewClaim && !is_null($oldclaim)) {
            \PC::debug($oldclaim);
            return ['res' => 0, 'msg_err' => 'У контрагента уже была заявка за сегодня'];
        }
        $claim->id_1c = $res1c["id_1c"];
        if (array_key_exists('max_money', $res1c)) {
            if ($res1c["max_money"] > 0) {
                $claim->max_money = $res1c["max_money"];
            }
        }
        if (array_key_exists('money', $res1c)) {
            if ($res1c["money"] > 0) {
                $claim->summa = $res1c["money"];
            }
        }
        $customer->id_1c = $res1c["customer_id_1c"];
        if (array_key_exists('claim_status', $res1c)) {
            if($res1c['claim_status'] == "1"){
                $claim->status = Claim::STATUS_ACCEPTED;
                //если заявка новая и пришло автоодобрение то скинуть смску об одобрении, в сумме пишем максимальную сумму если она больше суммы в заявке
                if ($isNewClaim && !is_null($customer->telephone) && $customer->telephone != '70000000000') {
                    \App\Utils\SMSer::send($customer->telephone, 'Заявка одобрена на' . ((!is_null($claim->max_money) && $claim->max_money > $claim->summa) ? $claim->max_money : $claim->summa) . 'р. Обратитесь в отделение ФинТерра88003014344');
                }
            } else if($res1c['claim_status'] == "2"){
                $claim->status = Claim::STATUS_DECLINED;
            } else {
                $claim->status = Claim::STATUS_ONCHECK;
            }
        }
        DB::beginTransaction();
        if ($claim->save() && $customer->save()) {
            $old_customers = Customer::where('telephone', $customer->telephone)->whereNull('id_1c')->get();
            if (!is_null($old_customers)) {
                foreach ($old_customers as $old_customer) {
                    DB::table('photos')->where('customer_id', $old_customer->id)->update(['customer_id' => $customer->id]);
                    $old_customer->delete();
                }
            }
            Spylog::log(Spylog::ACTION_ID1C_CHANGE, 'claims', $claim->id, $old_id_1c . '->' . $claim->id_1c);
            DB::commit();
            
//            $this->checkForDebtor($passport);
            
            return ['res' => 1];
        } else {
            DB::rollback();
            Log::error('Ошибка при сохранении заявки', (array) $res1c);
            return ['res' => 0, 'msg_err' => 'Заявка не сохранилась'];
        }
    }

    /**
     * переносит фотки в новую папку если паспорт поменялся
     * @param type $claim
     */
    public function controlPhotoFolder($claim, $passport = null) {
        if (is_null($passport)) {
            $passport = $claim->passport;
        }
        $date = $claim->created_at->format('Y-m-d');
        $photos = Photo::where('claim_id', $claim->id)->get();
        if (!is_null($photos) && count($photos) > 0) {
            foreach ($photos as $p) {
                $correct_path = 'images/' . $passport->series . $passport->number . '/' . $date . '/' . substr($p->path, strrpos($p->path, '/') + 1);
                if ($correct_path != $p->path && strpos($p->path, 'terminal') === FALSE) {
                    if (
                            (config('filesystems.default') == 'local' && Storage::exists($p->path)) ||
                            (config('filesystems.default') != 'local' && HelperUtil::FtpFileExists($p->path))
                    ) {
                        Storage::move($p->path, $correct_path);
                        $oldp = $p->toArray();
                        $p->path = $correct_path;
                        $p->save();
                        Spylog::logModelChange('photos', $oldp, $p->toArray());
                    }
                }
            }
        }
    }

    function handleAddress($address) {
        return str_replace('=', '-', $address);
    }

    /**
     * Проверяет промокод в 1с
     * @param type $claim_id_1c номер заявки в 1с
     * @param type $promocode_number промокод
     * @return type
     */
    function _checkPromocode($claim_id_1c, $promocode_number) {
        $promocode_number = preg_replace('/[^0-9]/', '', $promocode_number);
        $claimsNum = Claim::leftJoin('promocodes', 'claims.promocode_id', '=', 'promocodes.id')
                ->leftJoin('loans', 'loans.claim_id', '=', 'claims.id')
                ->where('promocodes.number', $promocode_number)
                ->where('loans.promocode_id', '<>', 'claims.promocode_id')
                ->count('claims.id');
        if ($claimsNum >= config('options.promocode_activate_num')) {
            return ['res' => 0, 'err_msg' => 'Количество доступных промокодов израсходовано.'];
        }
        return MySoap::checkPromocode([
                    'claim_id_1c' => $claim_id_1c,
                    'promocode_number' => $promocode_number,
                    'number' => ''
                        //последний - просто пустой параметр, потом надо будет убрать
        ]);
    }

    /**
     * Обрабатывает аякс запрос на добавление промокода. Проверяет промокод в 1с. 
     * Если корректный, то находит промокод в базе, если нету - создаёт, а так же
     * записывает айди промокода в заявку.
     * @param Request $request
     * @return type
     */
    public function addPromocode(Request $request) {
        $send_sms_on_add = false;
        if ($request->has('promocode_number') && $request->has('claim_id_1c')) {
            $promocode_number = \App\StrUtils::removeNonDigits($request->get('promocode_number'));
            $promo = Promocode::where('number', $promocode_number)->first();

            $claim = Claim::where('id_1c', $request->get('claim_id_1c'))->first();
            if (is_null($claim)) {
                return ['res' => 0, 'msg_err' => 'Заявка не найдена'];
            }
            if (!is_null($promo) && Loan::leftJoin('claims', 'claims.id', '=', 'loans.claim_id')->where('claims.customer_id', $claim->customer_id)->where('loans.promocode_id', $promo->id)->count() > 0) {
                return ['res' => 0, 'msg_err' => 'Нельзя использовать промокод, на контрагента, которому он был выдан'];
            }

            $res1c = $this->_checkPromocode($request->get('claim_id_1c'), $promocode_number);
            if ($res1c['res'] == 1) {
                DB::beginTransaction();

                if (is_null($promo)) {
                    $promo = new Promocode();
                    $promo->number = $promocode_number;
                    if (!$promo->save()) {
                        DB::rollback();
                        return ['res' => 0, 'msg_err' => 'Промокод не сохранился'];
                    }
                }
                //добавляет промокод в заявку кредитника, в котором данный промокод был создан
                if (!is_null($promo)) {
                    $claim->promocode_id = $promo->id;
                    $loanWithThisPromocode = Loan::where('promocode_id', $promo->id)->first();
                    if (!is_null($loanWithThisPromocode) &&
                            !$loanWithThisPromocode->closed &&
                            is_null($loanWithThisPromocode->claim->promocode_id) &&
                            !$promo->usedByCustomer($loanWithThisPromocode->claim->customer_id)) {
                        $loanWithThisPromocode->claim->promocode_id = $promo->id;
                        if (!$loanWithThisPromocode->claim->save()) {
                            DB::rollback();
                            return ['res' => 0, 'msg_err' => 'Промокод не сохранился'];
                        }
                        $send_sms_on_add = true;
                    }
                } else {
                    $claim->promocode_id = null;
                }
                if (!$claim->save()) {
                    DB::rollback();
                    return ['res' => 0, 'msg_err' => 'Промокод не сохранился'];
                }
                DB::commit();
                if ($send_sms_on_add && isset($loanWithThisPromocode) && !is_null($loanWithThisPromocode)) {
                    TerminalController::sendSMS($loanWithThisPromocode->claim->customer->telephone, 'Скидка 500 рублей активирована');
                }
                return ['res' => 1, 'msg_err' => 'Промокод добавлен'];
            } else {
                return $res1c;
            }
        } else {
            return ['res' => 0, 'msg_err' => 'Переданы не все обязательные параметры'];
        }
    }

    public function getClaimFormDataByPassport(Request $req) {
        $claimForm = new ClaimForm();
        $data = [];
        $passport = Passport::where('series', $req->series)->where('number', $req->number)->first();
        $claim = Claim::find($req->claim_id);
//        $res1c = $this->getCustomerFrom1c($req->series, $req->number, '', '');
        if (is_null($passport)) {
            $docs = Synchronizer::updateLoanRepayments($req->series, $req->number);
            if (is_array($docs) && array_key_exists('loan', $docs) && !$docs['loan']->closed) {
                if (!is_null($claim)) {
                    $claim->status = Claim::STATUS_DECLINED;
                    $claim->comment = 'У клиента есть открытый займ №' . $docs['loan']->id_1c;
                    $claim->save();
                }
                return ['comment' => 'У клиента есть открытый займ!!!', 'redirect' => '1'];
            }
            $res1c = $this->getCustomerFrom1c($req->series, $req->number, '', '');
            if ((int)$res1c->result==1) {
                foreach (['adsource', 'zhusl', 'marital_type_id', 'obrasovanie'] as $item) {
                    if (array_key_exists($item, $res1c)) {
                        $res1c[$item] = (int) $res1c[$item];
                        if ($res1c[$item] == 0) {
                            $res1c[$item] = null;
                        }
                    }
                }
                $data = $res1c;
                DB::beginTransaction();
                $passport = new Passport();
                $passport->fill($res1c);
                
                $old_customer = Customer::where('id_1c', $res1c['customer_id_1c'])->first();
                $snils = '';
                if (array_key_exists('snils', $res1c)) {
                    $snils = \App\StrUtils::removeNonDigits($res1c['snils']);
                }
                if (!is_null($old_customer)) {
                    $passport->customer_id = $old_customer->id;
                    if ($snils != '') {
                        $old_customer->snils = $snils;
                        $old_customer->save();
                    }
                } else {
                    $customer = new Customer();
                    $customer->telephone = $res1c['telephone'];
                    $customer->id_1c = $res1c['customer_id_1c'];
                    if ($snils != '') {
                        $customer->snils = $snils;
                    }
                    if (!$customer->save()) {
                        DB::rollback();
                    }
                    $customer->save();
                    $passport->customer_id = $customer->id;
                }
                $cur_about = about_client::find($req->about_client_id);
                $cur_about->fill($res1c);
                if (!$cur_about->save() || !$passport->save()) {
                    DB::rollback();
                }
                DB::commit();
                $data['customer_id'] = $customer->id;
                $data['passport_id'] = $passport->id;
            }
        } else {
            $opened_loan = Loan::where('claims.passport_id', $passport->id)->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')->where('closed', '0')->first();
            if (!is_null($opened_loan)) {
                if (!is_null($claim)) {
                    $claim->status = Claim::STATUS_DECLINED;
                    $claim->comment = 'У клиента есть открытый займ №' . $opened_loan->id_1c;
                    $claim->save();
                }
                return ['comment' => 'У клиента есть открытый займ!!!', 'redirect' => '1'];
            }
            $data = $passport->toArray();
            $about = about_client::where('customer_id', $passport->customer_id)->orderBy('created_at', 'desc')->first();
            if (!is_null($about)) {
                $data = array_merge($data, $about->toArray());
            }
            $data['customer_id_1c'] = $passport->customer->id_1c;
            $data['passport_id'] = $passport->id;
            $data['customer_id'] = $passport->customer_id;
        }
        foreach ($data as $k => $v) {
            if (strstr($k, 'date') !== FALSE || in_array($k, ['created_at', 'updated_at'])) {
                $data[$k] = with(new Carbon($v))->format('d.m.Y');
            }
        }
        return $data;
    }

    public function sendToAutoApprove(Request $req) {
        $xml = new \SimpleXMLElement('<root/>');
        $item = $xml->addChild('item');
        $item->addAttribute('loan_id_1c', $req->loan_id_1c);
        $item->addAttribute('claim_id_1c', $req->claim_id_1c);
        $item->addAttribute('closing_id_1c', $req->closing_id_1c);
        $item->addAttribute('customer_id_1c', $req->customer_id_1c);
        \PC::debug($xml->asXML(), 'autoapprove xml');
        $res1c = MySoap::sendToAutoApprove($xml->asXML());
        return $res1c;
    }

    public function checkTelephone(Request $req) {
        $tel = \App\StrUtils::removeNonDigits($req->telephone);
        if ($req->has('hlr_id') && strlen($req->hlr_id) > 0) {
            $hlr_id = \App\StrUtils::removeNonDigits($req->hlr_id);
            $res = \App\Utils\SMSer::checkStatus($tel, $hlr_id);
            $json = json_decode($res);
            \PC::debug($json, 'check_hlr');
            if (!is_null($json) && $json->status > 0) {
                \App\HlrLog::create([
                    'telephone' => $tel,
                    'answer' => $res,
                    'postclient' => $req->get('postclient', 0),
                    'available' => ($json->status == 1) ? 1 : 0,
                    'user_id'=>(!is_null(Auth::user()))?Auth::user()->id:null,
                    'cost' =>  intval(floatval($json->cost)*100),
                    'passport_series' => $req->get('series',''),
                    'passport_number' => $req->get('number',''),
                ]);
            }
            if (isset($json->error) || !isset($json->status) || $json->status == '-1' || $json->status == '0') {
                return ['result' => 0];
            } else {
                return ['result' => 1, 'status' => $this->getHlrStatusName($json->status)];
            }
        } else {
            $res = \App\Utils\SMSer::sendHLR($tel);
            $json = json_decode($res);
            \PC::debug($res, 'send hlr');
            if (isset($json->id)) {
                return ['result' => 1, 'hlr_id' => $json->id];
            } else {
                return ['result' => 0];
            }
        }
    }

    function getHlrStatusName($status) {
        $statuses = [
            '-3' => 'Неверный ID',
            '-1' => 'Ожидает отправки',
            '0' => 'Передано оператору',
            '1' => 'В сети',
            '3' => 'Просрочено',
            '20' => 'Невозможно доставить',
            '22' => 'Неверный номер',
            '23' => 'Запрещено',
            '24' => 'Недостаточно средств',
            '25' => 'Недоступный номер'
        ];
        if (array_key_exists($status, $statuses)) {
            return $statuses[$status];
        } else {
            return 'Ошибка';
        }
    }

    public function updateFrom1C($claim_id) {
        $claim = Claim::find($claim_id);
        $res = $claim->updateFrom1c();
        \PC::debug($res, 'update result');
        return redirect()->back()->with('msg_suc', StrLib::SUC);
    }

    public function setStatusToEdit($id) {
        $claim = Claim::find($id);
        if (is_null($claim)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL);
        }
        $claim->status = Claim::STATUS_ONEDIT;
        $claim->save();
        return redirect()->back()->with('msg_suc', StrLib::SUC);
    }
    
    public function checkForDebtor($claim){
        $customerPassport = $claim->passport;
        $address_fields = ['zip','address_region','address_district','address_city','address_city1','address_street','address_building','address_apartment'];
        $address_fields_count = count($address_fields);
        $sql = 'SELECT passports.id,passports.customer_id,customers.telephone,customers.id_1c as customer_id_1c, passports.series, passports.number, passports.fio as fio, ';
        for($i=0;$i<$address_fields_count;$i++){
            $sql .= $address_fields[$i];
            if($i<$address_fields_count-1){
                $sql .= ',';
            }
        }
        $sql .= ' FROM passports ';
        $sql .= 'LEFT JOIN customers on customers.id=passports.customer_id ';
        $sql .= 'WHERE ';
        $sql .= "customer_id <> '".$customerPassport->customer_id."' AND (";
        $sql .= "(address_region='".$customerPassport->address_region."' ";
        $sql .= "AND address_district='".$customerPassport->address_district."' ";
        $sql .= "AND address_city='".$customerPassport->address_city."' ";
        $sql .= "AND address_city1='".$customerPassport->address_city1."' ";
        $sql .= "AND address_street='".$customerPassport->address_street."' ";
        $sql .= "AND address_building='".$customerPassport->address_building."' ";
        $sql .= "AND address_apartment='".$customerPassport->address_apartment."') ";
        $sql .= "OR (fact_address_region='".$customerPassport->address_region."' ";
        $sql .= "AND fact_address_district='".$customerPassport->address_district."' ";
        $sql .= "AND fact_address_city='".$customerPassport->address_city."' ";
        $sql .= "AND fact_address_city1='".$customerPassport->address_city1."' ";
        $sql .= "AND fact_address_street='".$customerPassport->address_street."' ";
        $sql .= "AND fact_address_building='".$customerPassport->address_building."' ";
        $sql .= "AND fact_address_apartment='".$customerPassport->address_apartment."') ";
        $sql .= "OR (address_region='".$customerPassport->fact_address_region."' ";
        $sql .= "AND address_district='".$customerPassport->fact_address_district."' ";
        $sql .= "AND address_city='".$customerPassport->fact_address_city."' ";
        $sql .= "AND address_city1='".$customerPassport->fact_address_city1."' ";
        $sql .= "AND address_street='".$customerPassport->fact_address_street."' ";
        $sql .= "AND address_building='".$customerPassport->fact_address_building."' ";
        $sql .= "AND address_apartment='".$customerPassport->fact_address_apartment."') ";
        $sql .= "OR (fact_address_region='".$customerPassport->fact_address_region."' ";
        $sql .= "AND fact_address_district='".$customerPassport->fact_address_district."' ";
        $sql .= "AND fact_address_city='".$customerPassport->fact_address_city."' ";
        $sql .= "AND fact_address_city1='".$customerPassport->fact_address_city1."' ";
        $sql .= "AND fact_address_street='".$customerPassport->fact_address_street."' ";
        $sql .= "AND fact_address_building='".$customerPassport->fact_address_building."' ";
        $sql .= "AND fact_address_apartment='".$customerPassport->fact_address_apartment."') ";
        $sql .= "OR customers.telephone='".$customerPassport->customer->telephone."'";
        $sql .= ')';
        \PC::debug($sql);
        $passports = DB::select(DB::raw($sql));
        \PC::debug($passports);
        
        foreach($passports as $p){
            $dsql = "SELECT debtor_id_1c,responsible_user_id_1c,qty_delays,is_debtor FROM debtors.debtors WHERE customer_id_1c='".$p->customer_id_1c."' AND passport_series='".$p->series."' AND passport_number='".$p->number."'";
            \PC::debug($dsql);
            $debtors = DB::connection('debtors215')->select(DB::raw($dsql));
            \PC::debug($debtors);
            foreach($debtors as $d){
                # id, debtor_fio, debtor_address, debtor_telephone, debtor_overdue, customer_fio, customer_address, customer_telephone, comment, date, responsible_user_id_1c, is_debtor, created_at, updated_at
                $isql = "INSERT INTO debtors.address_doubles";
                $isql .= "(debtor_fio, debtor_address, debtor_telephone, debtor_overdue, customer_fio, customer_address, customer_telephone, comment, date, responsible_user_id_1c, is_debtor, created_at, updated_at) ";
                $isql .= "VALUES (";
                $isql .= "'".$p->fio."'";
                $isql .= ",'";
                for($i=0;$i<$address_fields_count;$i++){
                    $af = $address_fields[$i];
                    if(!empty($p->{$af}) && $i>0){
                        $isql.=", ";
                    }
                    $isql.=$p->{$af};
                    
                }
                $isql .= "'";
                $isql .= ",'".$p->telephone."'";
                $isql .= ",'".$d->qty_delays."'";
                $isql .= ",'".$customerPassport->fio."'";
                $isql .= ",'";
                for($i=0;$i<$address_fields_count;$i++){
                    $af = $address_fields[$i];
                    if(!empty($customerPassport->{$af}) && $i>0){
                        $isql.=", ";
                    }
                    $isql.=$customerPassport->{$af};
                }
                $isql .= "'";
                $isql .= ",'".$claim->customer->telephone."'";
                $isql .= ",'".$claim->comment."'";
                $isql .= ",'".$claim->created_at->format('Y-m-d H:i:s')."'";
                $isql .= ",'".$d->responsible_user_id_1c."'";
                $isql .= ",'".$d->is_debtor."'";
                $isql .= ",'".Carbon::now()->format('Y-m-d H:i:s')."'";
                $isql .= ",'".Carbon::now()->format('Y-m-d H:i:s')."'";
                $isql .= ")";
                \PC::debug($isql);
                $res = DB::connection('debtors215')->select(DB::raw($isql));
                \PC::debug($res);
            }
        }
        
        return $passports;
    }

}

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
    App\Customer,
    App\Order,
    App\Passport,
    App\Claim,
    App\StrUtils,
    Illuminate\Support\Facades\DB,
    Yajra\DataTables\Facades\DataTables,
    Carbon\Carbon,
    App\Spylog\Spylog,
    App\Spylog\SpylogModel,
    App\CustomerForm,
    App\MySoap,
    App\Utils\HtmlHelper,
    App\Utils\StrLib,
    Illuminate\Support\Facades\Hash;

class CustomersController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function getListView($id = null) {
        return view('customers.customers', ['subdivision_id' => ((is_null($id)) ? Auth::user()->subdivision_id : $id)]);
    }

    public function getList(Request $request, $getFrom1cOnFail = true)
    {
        $cols = [
            'customers.id as customer_id',
            'passports.fio as fio',
            'passports.birth_date as birth_date',
            'passports.series as series',
            'passports.number as number',
            'customers.telephone as telephone',
            'cards.card_number as card_number'
        ];
        $customers = Customer::select($cols)->leftJoin('passports', 'passports.customer_id', '=',
            'customers.id')->leftJoin('cards', 'cards.customer_id', '=', 'customers.id')->distinct();
        if (!$request->has('fio') && !$request->has('series') && !$request->has('number') && !config('app.dev')) {
            $customers->whereBetween('customers.created_at', [
                Carbon::now()->setTime(0, 0, 0)->format('d.m.Y H:i:s'),
                Carbon::now()->setTime(23, 59, 59)->format('d.m.Y H:i:s')
            ]);
        }
        $collection = Datatables::of($customers)
            ->editColumn('telephone', function ($customer) {
                return $customer->telephone ? StrUtils::hidePhone($customer->telephone) : '';
            })
            ->editColumn('birth_date', function ($customer) {
                return with(new Carbon($customer->birth_date))->format('d.m.Y');
            })
            ->addColumn('actions', function ($customer) {
                $html = '<div class="btn-group">';
                foreach (Passport::where('customer_id', $customer->customer_id)->select('id')->get() as $passport) {
                    $html .= HtmlHelper::Buttton(url('customers/edit/' . $customer->customer_id . '/' . $passport->id),
                        ['size' => 'sm', 'glyph' => 'pencil']);
                }
                if (Auth::user()->isAdmin()) {
                    $html .= HtmlHelper::Buttton(url('customers/remove/' . $customer->customer_id),
                        ['size' => 'sm', 'glyph' => 'remove']);
                    $html .= HtmlHelper::Buttton(url('customers/remove2/' . $customer->customer_id),
                        ['size' => 'sm', 'glyph' => 'remove', 'text' => 'Удалить(в крайнем случае)']);
                    $html .= HtmlHelper::Buttton(null, [
                        'size' => 'sm',
                        'glyph' => 'credit-card',
                        'onclick' => '$.custCtrl.showCardsListModal(' . $customer->customer_id . ')'
                    ]);
                    $html .= HtmlHelper::Buttton(null, [
                        'size' => 'sm',
                        'glyph' => 'plus',
                        'onclick' => '$.custCtrl.openAddCardModal(' . $customer->customer_id . ')'
                    ]);
                }
                $html .= '</div>';
                return $html;
            })
            ->filter(function ($query) use ($request) {
                if ($request->has('fio')) {
                    $query->where('fio', 'like', "%" . $request->get('fio') . "%");
                }
                if ($request->has('series')) {
                    $query->where('series', '=', $request->get('series'));
                }
                if ($request->has('number')) {
                    $query->where('number', '=', $request->get('number'));
                }
            })
            ->setTotalRecords(1000)
            ->make();
        $colObj = $collection->getData();
        if ($getFrom1cOnFail) {
            if ($request->has('series') && $request->has('number')) {
                if (!is_null($this->findCustomerIn1C($request->get('series'), $request->get('number')))) {
                    return $this->getList($request, false);
                }
            } else {
                if ($request->has('fio')) {
                    if (!is_null($this->findCustomerIn1CByFio($request->fio))) {
                        return $this->getList($request, false);
                    }
                }
            }
        }
        return $collection;
    }

    public function createCustomer() {
        return view('customers.edit', ['customerForm' => new CustomerForm()]);
    }

    public function editCustomer($customer_id, $passport_id) {
        return view('customers.edit', ['customerForm' => new CustomerForm(Customer::find($customer_id), Passport::find($passport_id))]);
    }

    public function update(Request $req) {
        $validator = Validator::make($req->all(), [
                    'fio' => 'required',
                    'series' => 'required|numeric',
                    'number' => 'required|numeric',
                    'birth_city' => 'required',
                    'issued' => 'required',
                    'subdivision_code' => 'required',
                    'issued_date' => 'required',
                    'birth_date' => 'required',
                    'address_region' => 'required',
                    'address_house' => 'required',
                    'fact_address_region' => 'required',
                    'fact_address_house' => 'required',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withInput($req->all())->with('msg', StrLib::ERR_VALID_FORM);
        }

        $customer = Customer::findOrNew($req->get('customer_id', null));
        //если в телефоне пришли звездочки то значит телефон не менялся и оставить прежний
        $input = $req->all();
        if (array_key_exists('telephone', $input) && strpos($input['telephone'], '*') > 0) {
            $input['telephone'] = $customer->telephone;
        }
        $oldCustomer = $customer->toArray();
        $customer->fill($input);
        $customer->telephone = StrUtils::parsePhone($customer->telephone);
        $customer->creator_id = Auth::user()->id;
        $passport = Passport::findOrNew($req->get('passport_id', null));
        $oldPassport = $passport->toArray();
        $passport->fill($input);
        if (is_null($passport->id) && Passport::where('series', $passport->series)->where('number', $passport->number)->count() > 0) {
            return redirect()->back()->withInput($input)->with('msg_err', StrLib::ERR_DUPLICATE_PASSPORT);
        }
//        CreateFL(issued_date, issued, subdivision_code, number, series, address_reg_date, address_region, address_city, address_street, 
//        address_house, address_apartment, fact_address_region, fact_address_city, fact_address_street, fio, Struct, zip, address_district, 
//        address_city1, address_building, fact_zip, fact_address_district, fact_address_city1, fact_address_house, fact_address_building, fact_address_apartment)
//        $address_city1 = (str_replace('г.', '', $passport->address_city) == $passport->address_city) ? $passport->address_city : '';
//        $fact_address_city1 = (str_replace('г.', '', $passport->fact_address_city) == $passport->fact_address_city) ? $passport->fact_address_city : '';
//        $address_city1 = (str_replace('г.', '', $passport->address_city) == $passport->address_city) ? $passport->address_city1 : '';
//        $fact_address_city1 = (str_replace('г.', '', $passport->fact_address_city) == $passport->fact_address_city) ? $passport->fact_address_city1 : '';

//        $checkRes1c = MySoap::passport(['series' => $passport->series, 'number' => $passport->number, 'old_series' => '', 'old_number' => '']);
//        \PC::debug($checkRes1c,'checkres1c');
//        if (!is_null($checkRes1c['res']) && !$checkRes1c['res']) {
//            return redirect()->back()->withInput($input)->with('msg_err', StrLib::ERR_1C);
//        }
//        if(!is_null($checkRes1c) && array_key_exists('customer_id_1c', $checkRes1c)){
//            $customer->id_1c = $checkRes1c['customer_id_1c'];
//        }

        DB::beginTransaction();
//        $res1c = MySoap::addCustomer([
////                    'issued_date' => with(new Carbon($passport->issued_date))->format('Ymd'),
//                    'issued_date' => with(new Carbon($passport->issued_date))->format('Y-m-d'),
//                    'issued' => $passport->issued,
//                    'subdivision_code' => $passport->subdivision_code,
//                    'number' => $passport->number,
//                    'series' => $passport->series,
//                    'address_reg_date' => with(new Carbon($passport->address_reg_date))->format('Y-m-d'),
//                    'address_region' => $passport->address_region,
//                    'address_city' => $passport->address_city,
//                    'address_street' => $passport->address_street,
//                    'address_house' => $passport->address_house,
//                    'address_apartment' => $passport->address_apartment,
//                    'fact_address_region' => $passport->fact_address_region,
//                    'fact_address_city' => $passport->fact_address_city,
//                    'fact_address_street' => $passport->fact_address_street,
//                    'fio' => $passport->fio,
//                    'zip' => $passport->zip,
//                    'address_district' => $passport->address_district,
//                    'address_city1' => $address_city1,
//                    'address_building' => $passport->address_building,
//                    'fact_zip' => $passport->fact_zip,
//                    'fact_address_district' => $passport->fact_address_district,
//                    'fact_address_city1' => $fact_address_city1,
//                    'fact_address_house' => $passport->fact_address_house,
//                    'fact_address_building' => $passport->fact_address_building,
//                    'fact_address_apartment' => $passport->fact_address_apartment,
//                    'telephone' => $customer->telephone,
//                    'birth_city' => $passport->birth_city,
//                    'birth_date' => with(new Carbon($passport->birth_date))->format('Y-m-d'),
//                    'customer_id_1c' => (is_null($customer->id_1c)) ? '' : $customer->id_1c
//        ]);
//        \PC::debug($res1c);
//        if (!$res1c['res']) {
//            return redirect('customers')->with('msg_err', StrLib::ERR_1C);
//        }
//        $customer->id_1c = $res1c['value'];
        if (!$customer->save()) {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }
        Spylog::logModelChange(Spylog::TABLE_CUSTOMERS, $oldCustomer, $customer->toArray());
        $passport->customer_id = $customer->id;
        if (!$passport->save()) {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }
        Spylog::logModelChange(Spylog::TABLE_PASSPORTS, $oldPassport, $passport->toArray());
        DB::commit();
        return redirect()->back()->with('msg_suc', StrLib::SUC_SAVED);
    }

    public function removeCustomer($id) {
        $customer = Customer::find($id);
        if (Claim::where('customer_id', $id)->count() > 0) {
            return redirect()->back()->with('msg_err', StrLib::ERR_HAS_CLAIM);
        }
        if ($customer->delete()) {
            Spylog::logModelAction(Spylog::ACTION_DELETE, Spylog::TABLE_CUSTOMERS, $customer);
            return redirect()->back()->with('msg_suc', StrLib::SUC);
        } else {
            return redirect()->back()->with('msg_err', StrLib::ERR_CANT_DELETE);
        }
    }

    public function removeCustomer2($id) {
        DB::beginTransaction();
        $customer = Customer::find($id);
        if (is_null($customer)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL);
        }
        if (!Order::whereIn('passport_id', Passport::where('customer_id', $id)->pluck('id'))->delete()) {
            DB::rollback();
        }
        if ($customer->delete()) {
            DB::commit();
            Spylog::logModelAction(Spylog::ACTION_DELETE, Spylog::TABLE_CUSTOMERS, $customer);
            return redirect()->back()->with('msg_suc', StrLib::SUC);
        } else {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR_CANT_DELETE);
        }
    }

    public function findCustomers(Request $req) {
        if (!$req->has('series') && !$req->has('number') && !$req->has('fio')) {
            return null;
        }
        if (($req->has('series') && !$req->has('number')) || (!$req->has('series') && $req->has('number'))) {
            return null;
        }
        $query = DB::table('customers')
                ->select('customers.id as customer_id', 'passports.id as passport_id', 'passports.fio', 'passports.series', 'passports.number', 'customers.snils')
                ->leftJoin('passports', 'passports.customer_id', '=', 'customers.id');
        if ($req->has('series') && !empty($req->series) && $req->has('number') && !empty($req->number)) {
            $query->where('passports.series', $req->get('series'));
            $query->where('passports.number', $req->get('number'));
            $this->findCustomerIn1C($req->get('series'), $req->get('number'));
        } else if ($req->has('fio') && $req->fio != '') {
            $this->findCustomerIn1CByFio($req->fio);
        }
        if ($req->has('fio') && $req->fio != '') {
            $query->where('passports.fio', 'like', '%' . $req->fio . '%');
        }
        return $query->get();
    }

    static function getCustomerFrom1c($series, $number) {
        $ctrl = new CustomersController();
        return $ctrl->findCustomerIn1C($series, $number);
    }

    /**
     * ищет контрагента в 1с по паспорту и добавляет в базу
     * @param type $series
     * @param type $number
     * @return type
     */
    public function findCustomerIn1C($series, $number) {
        if ($series == '' || $number == '') {
            return null;
        }
        $passport = Passport::where('series', $series)->where('number', $number)->first();
        if (!is_null($passport)) {
            return ['customer' => $passport->customer, 'passport' => $passport];
        }
        $data = Customer::getFrom1c($series, $number);
        \PC::debug($data, 'cust');
        return $data;
    }

    public function findCustomerIn1CByFio($fio) {
        $res1c = \App\MySoap::getPassportsByFio($fio);
        if (!$res1c['res'] || !array_key_exists('fio', $res1c)) {
            return null;
        }
        $res = [];
        foreach ($res1c['fio'] as $p) {
            $res[] = $this->findCustomerIn1C($p['passport_series'], $p['passport_number']);
        }
        return $res;
    }

    public function getTelephone(Request $req) {
        if ($req->has('customer_id')) {
            $customer = Customer::select('telephone')->where('id', $req->customer_id)->first();
            return (!is_null($customer)) ? $customer->telephone : '';
        }
        return '';
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Customer extends Model {

    protected $table = 'customers';
    protected $fillable = ['telephone', 'id_1c'];

    /**
     * 
     * @return App\Passport
     */
    public function passports() {
        return $this->hasMany('App\Passport', 'customer_id');
    }

    /**
     * @return App\about_client
     */
    public function about_clients() {
        return $this->hasMany('App\about_client', 'customer_id');
    }

    public function setTelephoneAttribute($value) {
        $this->attributes['telephone'] = StrUtils::parsePhone($value);
    }

    public function cards() {
        $this->hasMany('App\Card', 'customer_id');
    }

    public function isPostClient() {
        $lastAbout = about_client::where('customer_id', $this->id)->first();
        if (!is_null($lastAbout) && $lastAbout->postclient) {
            return true;
        }
        $closedNum = Loan::leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->leftJoin('passports', 'passports.id', '=', 'claims.passport_id')
                ->where('loans.closed', '1')
                ->whereIn('claims.passport_id', Passport::where('customer_id', $this->id)->pluck('id'))
                ->count();
        return ($closedNum >= config('options.regular_client_loansnum'));
    }

    /**
     * 
     * @return \App\Passport
     */
    public function getLastPassport() {
        return Passport::where('customer_id', $this->id)->orderBy('created_at', 'desc')->first();
    }

    /**
     * 
     * @return \App\about_client
     */
    public function getLastAboutClient() {
        return about_client::where('customer_id', $this->id)->orderBy('created_at', 'desc')->first();
    }

    public function getActiveCard() {
        return Card::where('customer_id', $this->id)->where('status', Card::STATUS_ACTIVE)->first();
    }
    /**
     * Загружает контрагента из 1с в базу
     * @param string $series серия паспорта
     * @param string $number номер паспорта
     * @return array
     */
    public static function getFrom1c($series, $number) {
        $res1c = \App\MySoap::passport(['series' => $series, 'number' => $number, 'old_series' => '', 'old_number' => '']);
        if (!$res1c['res'] || !array_key_exists('id', $res1c)) {
            \PC::debug($res1c['res'], 'findcustomerin1c');
            return null;
        }
        DB::beginTransaction();
        $customer = Customer::where('id_1c', $res1c['id'])->first();
        if (is_null($customer)) {
            $customer = new Customer();
        }
        $customer->fill($res1c);
        $customer->id_1c = $res1c['id'];
        if (!$customer->save()) {
            DB::rollback();
            return null;
        }
        $passport = Passport::where('series', $series)->where('number', $number)->first();
        if (is_null($passport)) {
            $passport = new Passport();
        }
        $passport->fill($res1c);
        $passport->series = $series;
        $passport->number = $number;
        $passport->customer_id = $customer->id;
        $passport->updated_at = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        if (!$passport->save()) {
            DB::rollback();
            return null;
        }
        DB::commit();
        return ['customer' => $customer, 'passport' => $passport];
    }

}

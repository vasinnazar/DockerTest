<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClaimForm extends Model {

    protected $guarded = array();

//    protected $fillable = ['telephone',
//            'customer_id',
//            'customer_id_1c',
//            //passport
//            'fio','passport_id','birth_date','birth_city','series','number',
//            'issued','issued_date','subdivision_code','zip',
//            'address_region','address_district',
//            'address_city',
//            'address_street',
//            'address_house',
//            'address_building',
//            'address_apartment',
//            'fact_zip',
//            'fact_address_region',
//            'fact_address_district',
//            'fact_address_city',
//            'fact_address_street',
//            'fact_address_house',
//            'fact_address_building',
//            'fact_address_apartment',
//            'address_reg_date',
//            //about
//            'about_client_id','sex','goal','zhusl','deti','fiosuprugi','fioizmena',
//            'avto','telephonehome','organizacia','innorganizacia','dolznost','vidtruda',
//            'fiorukovoditel','adresorganiz','telephoneorganiz',
//            'credit','dohod','dopdohod','stazlet','adsource','pensionnoeudost',
//            'telephonerodstv','stepenrodstv','obrasovanie','pensioner','postclient',
//            'armia','poruchitelstvo','zarplatcard','alco','drugs','stupid',
//            'badspeak','pressure','dirty','smell','badbehaviour','soldier','watch',
//            'other','anothertelephone','stepenrodstv','marital_type_id','claim_date'];

    public function __construct($claim_id = null, $customer = null, $passport = null, $about_client = null) {
        parent::__construct();
        if (is_null($claim_id)) {
            //новый займ
            $claim = new Claim();
            if (is_null($customer)) {
                $customer = new Customer();
            }
        } else {
            //редактирование займа
            $claim = Claim::find($claim_id);
            if (is_null($customer) && !is_null($claim)) {
                $customer = Customer::find($claim->customer_id);
            }
        }
        if (!is_null($customer->id)) {
            if (is_null($about_client)) {
                if (is_null($claim->id)) {
                    $about_client = about_client::where('customer_id', $customer->id)->orderBy('created_at', 'desc')->first();
                } else {
                    $about_client = about_client::find($claim->about_client_id);
                }
            }
            if (is_null($passport)) {
                if (is_null($claim->id)) {
                    $passport = Passport::where('customer_id', $customer->id)->orderBy('created_at', 'desc')->first();
                } else {
                    $passport = Passport::find($claim->passport_id);
                }
            }
        } else {
            $passport = new Passport();
            $about_client = new about_client();
        }
        if (is_null($about_client)) {
            $about_client = new about_client();
        }
        $fdata = array(
            'telephone' => $customer->telephone,
            'customer_id' => $customer->id,
            'customer_id_1c' => $customer->id_1c,
            //passport
            'fio' => $passport->fio,
            'passport_id' => $passport->id,
            'birth_date' => (!is_null($passport->birth_date)) ? date_format(date_create($passport->birth_date), 'd.m.Y') : null,
            'birth_city' => $passport->birth_city,
            'series' => $passport->series,
            'number' => $passport->number,
            'issued' => $passport->issued,
            'issued_date' => (!is_null($passport->issued_date)) ? date_format(date_create($passport->issued_date), 'd.m.Y') : null,
            'subdivision_code' => $passport->subdivision_code,
            'zip' => $passport->zip,
            'address_region' => $passport->address_region,
            'address_district' => $passport->address_district,
            'address_city' => $passport->address_city,
            'address_city1' => $passport->address_city1,
            'address_street' => $passport->address_street,
            'address_house' => $passport->address_house,
            'address_building' => $passport->address_building,
            'address_apartment' => $passport->address_apartment,
            'fact_zip' => $passport->fact_zip,
            'fact_address_region' => $passport->fact_address_region,
            'fact_address_district' => $passport->fact_address_district,
            'fact_address_city' => $passport->fact_address_city,
            'fact_address_city1' => $passport->fact_address_city1,
            'fact_address_street' => $passport->fact_address_street,
            'fact_address_house' => $passport->fact_address_house,
            'fact_address_building' => $passport->fact_address_building,
            'fact_address_apartment' => $passport->fact_address_apartment,
            'address_reg_date' => (!is_null($passport->address_reg_date)) ? date_format(date_create($passport->address_reg_date), 'd.m.Y') : null,
            //about
            'about_client_id' => $about_client->id,
            'sex' => $about_client->sex,
            'goal' => $about_client->goal,
            'zhusl' => $about_client->zhusl,
            'deti' => $about_client->deti,
            'fiosuprugi' => $about_client->fiosuprugi,
            'fioizmena' => $about_client->fioizmena,
            'avto' => $about_client->avto,
            'telephonehome' => $about_client->telephonehome,
            'organizacia' => $about_client->organizacia,
            'innorganizacia' => $about_client->innorganizacia,
            'dolznost' => $about_client->dolznost,
            'vidtruda' => $about_client->vidtruda,
            'fiorukovoditel' => $about_client->fiorukovoditel,
            'adresorganiz' => $about_client->adresorganiz,
            'telephoneorganiz' => $about_client->telephoneorganiz,
            'credit' => $about_client->credit,
            'dohod' => $about_client->dohod,
            'dopdohod' => $about_client->dopdohod,
            'stazlet' => $about_client->stazlet,
            'adsource' => $about_client->adsource,
            'pensionnoeudost' => $about_client->pensionnoeudost,
            'telephonerodstv' => $about_client->telephonerodstv,
            'stepenrodstv' => $about_client->stepenrodstv,
            'obrasovanie' => $about_client->obrasovanie,
            'pensioner' => $about_client->pensioner,
            'postclient' => $about_client->postclient,
            'armia' => $about_client->armia,
            'poruchitelstvo' => $about_client->poruchitelstvo,
            'zarplatcard' => $about_client->zarplatcard,
            'alco' => $about_client->alco,
            'drugs' => $about_client->drugs,
            'stupid' => $about_client->stupid,
            'badspeak' => $about_client->badspeak,
            'pressure' => $about_client->pressure,
            'dirty' => $about_client->dirty,
            'smell' => $about_client->smell,
            'badbehaviour' => $about_client->badbehaviour,
            'soldier' => $about_client->soldier,
            'watch' => $about_client->watch,
            'other' => $about_client->other,
            'anothertelephone' => $about_client->anothertelephone,
            'stepenrodstv' => $about_client->stepenrodstv,
            'marital_type_id' => $about_client->marital_type_id,
            'claim_date' => $claim->created_at,
            'other_mfo' => $about_client->other_mfo,
            'other_mfo_why' => $about_client->other_mfo_why,
            'recomend_phone_1' => StrUtils::removeNonDigits($about_client->recomend_phone_1),
            'recomend_fio_1' => $about_client->recomend_fio_1,
            'recomend_phone_2' => StrUtils::removeNonDigits($about_client->recomend_phone_2),
            'recomend_fio_2' => $about_client->recomend_fio_2,
            'recomend_phone_3' => StrUtils::removeNonDigits($about_client->recomend_phone_3),
            'recomend_fio_3' => $about_client->recomend_fio_3,
            'email' => $about_client->email,
            'snils' => $customer->snils,
            'dohod_husband' => $about_client->dohod_husband,
            'pension' => $about_client->pension,
        );
        if (!is_null($claim->id)) {
            $fdata['claim_id'] = $claim->id;
            $fdata['claim_subdivision_id'] = $claim->subdivision_id;
            $fdata['srok'] = $claim->srok;
            $fdata['sum'] = $claim->summa;
            $fdata['comment'] = $claim->comment;
            $fdata['promocode_id'] = $claim->promocode_id;
            $fdata['uki'] = $claim->uki;
            $fdata['timestart'] = $claim->timestart;
            if (!is_null($claim->promocode)) {
                $fdata['promocode_number'] = $claim->promocode->number;
            }
        }
        $this->fill($fdata);
    }

}

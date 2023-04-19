<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Passport extends Model {

    protected $table = 'passports';
    protected $fillable = ['customer_id', 'birth_date', 'birth_city', 'series', 'number', 'issued',
        'issued_date', 'subdivision_code', 'address_reg_date',
        'zip', 'address_region', 'address_district', 'address_city', 'address_city1', 'address_street',
        'address_house', 'address_building', 'address_apartment',
        'fact_zip', 'fact_address_region', 'fact_address_district', 'fact_address_city', 'fact_address_city1',
        'fact_address_street', 'fact_address_house', 'fact_address_building', 'fact_address_apartment', 'fio'];

    public function customer() {
        return $this->belongsTo('App\Customer', 'customer_id');
    }

    public function setFioAttribute($value) {
        $this->attributes['fio'] = mb_convert_case($value, MB_CASE_TITLE);
    }

    public function setBirthDateAttribute($value) {
        $this->attributes['birth_date'] = with(new Carbon($value))->format('Y-m-d');
    }

    public function setIssuedDateAttribute($value) {
        $this->attributes['issued_date'] = with(new Carbon($value))->format('Y-m-d');
    }

    public function setAddressRegDateAttribute($value) {
        $this->attributes['address_reg_date'] = with(new Carbon($value))->format('Y-m-d');
    }

    /**
     * проверяет является ли данный паспорт паспортом ответственного бухгалтера, 
     * на которого делаются всякие ордеры на подотчет (Стельман)
     * @return boolean
     */
    public function isResponsibleBuh() {
        return (strstr($this->fio, Passport::getResponsibleBuhFIO()) !== FALSE && $this->birth_date == '1990-10-10');
    }

    /**
     * возвращает паспорт ответственного за всякие подотчеты бухгалтера (Стельман)
     * @return \App\Passport
     */
    static function getResponsibleBuh() {
        return Passport::where('fio', 'like', '%' . Passport::getResponsibleBuhFIO() . '%')->where('birth_date', '1990-10-10')->first();
    }

    static function getResponsibleBuhFIO() {
//        return 'Стельман Екатерина Игоревна';
        return 'Вознова Ольга Вячеславовна';
    }
    
    static function getFullAddress($passport, $fact = false) {
        $fields = ['region', 'district', 'city', 'city1', 'street', 'house', 'building', 'apartment'];
        $pfx = (($fact) ? 'fact_' : '') . 'address_';
        $zip = $passport[(($fact) ? 'fact_' : '') . 'zip'];
        $str = (!is_null($zip) && $zip != '') ? $zip : '';
        $city = '';
        foreach ($fields as $f) {
            if (!is_null($passport[$pfx . $f]) && $passport[$pfx . $f] != '') {
                if ($f == 'city') {
                    $city = $passport[$pfx . $f];
                }
                if ($f == 'city1') {
                    if ($city == $passport[$pfx . $f]) {
                        continue;
                    }
                }
                $str .= ($str != '') ? ', ' : '';
                $str .= ($f == 'house' && ctype_digit($passport[$pfx . $f])) ? 'д.' : '';
                $str .= ($f == 'building' && ctype_digit($passport[$pfx . $f])) ? 'стр.' : '';
                $str .= ($f == 'apartment' && ctype_digit($passport[$pfx . $f])) ? 'кв.' : '';
                $str .= ' ' . $passport[$pfx . $f];
            }
        }
        return $str;
    }

    public function getFullAddressAttribute()
    {
        $ar = [];
        $fields = ['zip', 'address_region', 'address_district', 'address_city', 'address_city1', 'address_street', 'address_house', 'address_building', 'address_apartment'];

        foreach ($fields as $field) {
            if ($this->$field && !empty($this->$field)) {
                switch($field) {
                    case 'address_house':
                        $ar[] = 'д. ' . $this->$field;
                        break;

                    case 'address_building':
                        $ar[] = 'к. ' . $this->$field;
                        break;

                    case 'address_apartment':
                        $ar[] = 'кв. ' . $this->$field;
                        break;

                    default:
                        $ar[] = $this->$field;
                }
            }
        }

        return implode(', ', $ar);
    }

    public function getFactFullAddressAttribute()
    {
        $ar = [];
        $fields = ['fact_zip', 'fact_address_region', 'fact_address_district', 'fact_address_city', 'fact_address_city1', 'fact_address_street', 'fact_address_house', 'fact_address_building', 'fact_address_apartment'];

        foreach ($fields as $field) {
            if ($this->$field && !empty($this->$field)) {
                switch($field) {
                    case 'fact_address_house':
                        $ar[] = 'д. ' . $this->$field;
                        break;

                    case 'fact_address_building':
                        $ar[] = 'к. ' . $this->$field;
                        break;

                    case 'fact_address_apartment':
                        $ar[] = 'кв. ' . $this->$field;
                        break;

                    default:
                        $ar[] = $this->$field;
                }
            }
        }

        return implode(', ', $ar);
    }
    
    /**
     * 
     * @param string $series
     * @param string $number
     * @return \App\Passport
     */
    static function getBySeriesAndNumber($series,$number){
        return Passport::where('series',$series)->where('number',$number)->first();
    }

}

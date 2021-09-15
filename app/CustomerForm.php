<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerForm extends Model {

    protected $fillable = [
        'customer_id', 'birth_date', 'birth_city', 'series', 'number', 'issued',
        'issued_date', 'subdivision_code', 'address_reg_date',
        'zip', 'address_region', 'address_district', 'address_city', 'address_street',
        'address_house', 'address_building', 'address_apartment',
        'fact_zip', 'fact_address_region', 'fact_address_district', 'fact_address_city',
        'fact_address_street', 'fact_address_house', 'fact_address_building',
        'fact_address_apartment', 'fio', 'telephone', 'passport_id', 'id_1c'
    ];
    protected $guarded = array();

    /**
     * 
     * @param \App\Customer $customer
     * @param \App\Passport $passport
     */
    public function __construct($customer = null, $passport = null) {
        parent::__construct();
        if (!is_null($customer)) {
            $this->fill($customer->attributesToArray());
            if (!is_null($this->telephone)) {
                $this->telephone = substr($this->telephone, 0, 4) . '***' . substr($this->telephone, 7, 10);
            }
            if (!is_null($passport)) {
                $this->fill($passport->attributesToArray());
                $this->passport_id = $passport->id;
            }
        }
    }

}

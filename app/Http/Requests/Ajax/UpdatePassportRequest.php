<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePassportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'zip' => 'string',
            'address_region' => 'string',
            'address_district' => 'string',
            'address_city' => 'string',
            'address_city1' => 'string',
            'address_street' => 'string',
            'address_house' => 'string',
            'address_building' => 'string',
            'address_apartment' => 'string',
            'okato' => 'string',
            'oktmo' => 'string',
            'kladr_id' => 'string',
            'fias_id' => 'string',
            'fias_code' => 'string',
            'fact_zip' => 'string',
            'fact_address_region' => 'string',
            'fact_address_district' => 'string',
            'fact_address_city' => 'string',
            'fact_address_city1' => 'string',
            'fact_address_street' => 'string',
            'fact_address_house' => 'string',
            'fact_address_building' => 'string',
            'fact_address_apartment' => 'string',
            'fact_okato' => 'string',
            'fact_oktmo' => 'string',
            'fact_kladr_id' => 'string',
            'fact_fias_id' => 'string',
            'fact_fias_code' => 'string',

        ];
    }
}

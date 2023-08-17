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
            'zip' => 'nullable|string',
            'address_region' => 'string',
            'address_district' => 'nullable|string',
            'address_city' => 'nullable|string',
            'address_city1' => 'nullable|string',
            'address_street' => 'nullable|string',
            'address_house' => 'nullable|string',
            'address_building' => 'nullable|string',
            'address_apartment' => 'nullable|string',
            'okato' => 'nullable|string',
            'oktmo' => 'nullable|string',
            'kladr_id' => 'nullable|string',
            'fias_id' => 'nullable|string',
            'fias_code' => 'nullable|string',
            'fact_zip' => 'nullable|string',
            'fact_address_region' => 'string',
            'fact_address_district' => 'nullable|string',
            'fact_address_city' => 'nullable|string',
            'fact_address_city1' => 'nullable|string',
            'fact_address_street' => 'nullable|string',
            'fact_address_house' => 'nullable|string',
            'fact_address_building' => 'nullable|string',
            'fact_address_apartment' => 'nullable|string',
            'fact_okato' => 'nullable|string',
            'fact_oktmo' => 'nullable|string',
            'fact_kladr_id' => 'nullable|string',
            'fact_fias_id' => 'nullable|string',
            'fact_fias_code' => 'nullable|string',

        ];
    }
}

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
            'fio' => 'string',
            'series' => 'digits:4',
            'number' => 'digits:6',
            'birth_date' => 'date',
            'issued_date' => 'date',
            'issued' => 'string',
            'zip' => 'nullable|string',
            'subdivision_code' => 'string',
            'birth_city' => 'string',
            'address_reg_date' => 'date',
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
        ];
    }
}

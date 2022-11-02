<?php

namespace App\Http\Requests\DebtorCard;

use App\Http\Requests\Request;

class MultiSumRequest extends Request
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
            'customer_id_1c'=>'string',
            'loan_id_1c'=>'string',
            'date'=>'date:NULL'
        ];
    }
}

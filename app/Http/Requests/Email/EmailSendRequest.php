<?php

namespace App\Http\Requests\Email;

use App\Http\Requests\Request;

class EmailSendRequest extends Request
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
            'debtors_id'=>'numeric',
            'email_id'=>'int',
            'dateAnswer'=>'date',
            'datePayment'=>'date',
            'discountPayment'=>'numeric',
            'debtor_money_on_day' => 'string',
        ];
    }
}

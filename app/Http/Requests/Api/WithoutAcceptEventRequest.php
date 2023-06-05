<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Request;

class WithoutAcceptEventRequest extends Request
{
    public function rules(): array
    {
        return [
            'customer_id_1c' => 'required|string',
            'loan_id_1c' => 'required|string',
            'amount' => 'required|integer',
            'card_number' => 'string'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

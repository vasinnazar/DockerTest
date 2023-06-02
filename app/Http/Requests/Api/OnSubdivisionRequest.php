<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Request;

class OnSubdivisionRequest extends Request
{
    public function rules(): array
    {
        return [
            'user_id_1c' => 'required|string',
            'customer_id_1c' => 'required|string',
            'loan_id_1c'=>'required|string',
            'is_debtor_personal' => 'boolean'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

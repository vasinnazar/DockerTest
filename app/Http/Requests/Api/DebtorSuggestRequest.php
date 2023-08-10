<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Request;

class DebtorSuggestRequest extends Request
{
    public function rules()
    {
        return [
            'address'  => 'required|string',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

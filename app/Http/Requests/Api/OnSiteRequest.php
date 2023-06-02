<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Request;

class OnSiteRequest extends Request
{
    public function rules(): array
    {
        return [
            'customer_id_1c' => 'required|string'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

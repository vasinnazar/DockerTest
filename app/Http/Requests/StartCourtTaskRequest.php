<?php

namespace App\Http\Requests;

class StartCourtTaskRequest extends Request
{
    public function rules(): array
    {
        return [
            "fixation_date_from" => "date",
            "fixation_date_to" => "date",
            "qty_delays_from" => "integer",
            "qty_delays_to" => "integer",
            "responsible_users_ids" => 'array'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

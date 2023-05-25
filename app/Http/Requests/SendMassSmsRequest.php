<?php

namespace App\Http\Requests;

class SendMassSmsRequest extends Request
{
    public function rules(): array
    {
        return [
            'smsId' => 'required|integer',
            'responsibleUserId' => 'required|integer',
            'debtorsIds' => 'required|array',
            'smsDate' => 'date'
        ];
    }
    public function messages()
    {
       return [
           'smsId.required' => 'Не выбран шаблон смс',
           'responsibleUserId.required' => 'Не удалось определить ответственного',
           'debtorsIds.required' => 'Не удалось определить должников',
       ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

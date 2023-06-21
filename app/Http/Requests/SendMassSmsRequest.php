<?php

namespace App\Http\Requests;

class SendMassSmsRequest extends Request
{
    public function rules(): array
    {
        return [
            'isSms' => 'required|integer',
            'templateId' => 'required|integer',
            'responsibleUserId' => 'required|integer',
            'debtorsIds' => 'required|array',
            'sendDate' => 'date',
            'dateAnswer'=>'date',
            'datePayment'=>'date',
            'discountPayment'=>'numeric'
        ];
    }
    public function messages()
    {
       return [
           'isSms.required' => 'Не выбран тип сообщений',
           'templateId.required' => 'Не выбран шаблон сообщений',
           'responsibleUserId.required' => 'Не удалось определить ответственного',
           'debtorsIds.required' => 'Не удалось определить должников',
       ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

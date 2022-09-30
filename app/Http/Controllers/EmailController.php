<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailListRequest;
use App\Http\Requests\EmailSendRequest;
use App\Services\EmailService;
use Carbon\Carbon;

class EmailController extends Controller
{
    public $emailService;

    public function __construct(EmailService $service)
    {
        $this->emailService = $service;
    }

    public function index(EmailListRequest $request)
    {
        $collectEmailsMessages = $this->emailService->getListEmailsMessages($request->user_id);
        return view('elements.messagesEmailListModal', ['collectEmailsMessages' => $collectEmailsMessages])->render();

    }

    public function sendEmail(EmailSendRequest $request)
    {
        logger($request->all());
        $arrayParam = [
            'debtor_id' => $request->debtor_id,
            'email_id'=>$request->email_id,
            'dateAnswer' => Carbon::parse($request->dateAnswer)->format('d.m.Y') ?? null,
            'datePayment'=>Carbon::parse($request->datePayment)->format('d.m.Y') ?? null,
            'discountPayment'=>$request->discountPayment ?? null,
            'debtor_money_on_day' =>$request->debtor_money_on_day ?? null,
        ];
        if($this->emailService->sendEmailDebtor($arrayParam)){
            return redirect()->back()->with('msg_suc','Email сообщение отправленно,мероприятие создано');
        }
        return redirect()->back()->with('msg_err','Email сообщение не отправленно');
    }
}

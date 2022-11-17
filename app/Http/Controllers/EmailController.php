<?php

namespace App\Http\Controllers;

use App\Debtor;
use App\Exceptions\DebtorException;
use App\Http\Requests\Email\EmailListRequest;
use App\Http\Requests\Email\EmailSendRequest;
use App\Services\DebtorEventService;
use App\Services\EmailService;
use Carbon\Carbon;

class EmailController extends Controller
{
    public $emailService;
    private $debtorEventService;


    public function __construct(EmailService $service, DebtorEventService $debtorEventservice)
    {
        $this->emailService = $service;
        $this->debtorEventService = $debtorEventservice;
    }

    public function index(EmailListRequest $request)
    {
        $collectEmailsMessages = $this->emailService->getListEmailsMessages($request->user_id);
        return view('elements.messagesEmailListModal', ['collectEmailsMessages' => $collectEmailsMessages])->render();

    }

    public function sendEmail(EmailSendRequest $request)
    {
        $arrayParam = [
            'debtor_id' => $request->debtor_id,
            'email_id' => $request->email_id,
            'dateAnswer' => Carbon::parse($request->dateAnswer)->format('d.m.Y') ?? null,
            'datePayment' => Carbon::parse($request->datePayment)->format('d.m.Y') ?? null,
            'discountPayment' => $request->discountPayment ?? null,
        ];

        $customer = (Debtor::find($request->debtor_id))->customer();
        $debtors = Debtor::where('customer_id_1c', $customer->id_1c)->get();


        try {
            foreach ($debtors as $debt) {
                $this->debtorEventService->checkLimitEvent($debt, 24);
            }

            if ($this->emailService->sendEmailDebtor($arrayParam)) {
                return redirect()->back()->with('msg_suc', 'Email сообщение отправленно,мероприятие создано');
            }

            return redirect()
                ->back()
                ->with('msg_err', 'Email сообщение не отправленно. Не удалось авторизироваться на почтовом сервере.
                      Возможно,неверные логин и пароль для входа в корпоративную почту,обратитесь в техподдержку.');

        } catch (DebtorException $e) {
            return redirect()->back()->with('msg_err', $e->errorMessage);
        }

    }
}

<?php

namespace App\Http\Controllers;

use App\Clients\ArmClient;
use App\Debtor;
use App\Exceptions\DebtorException;
use App\Http\Requests\Email\EmailListRequest;
use App\Http\Requests\Email\EmailSendRequest;
use App\Services\DebtorEventService;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{
    public $emailService;
    private $debtorEventService;
    private $armClient;

    public function __construct(EmailService $service, DebtorEventService $debtorEventservice, ArmClient $armClient)
    {
        $this->emailService = $service;
        $this->debtorEventService = $debtorEventservice;
        $this->armClient = $armClient;
    }

    public function index(EmailListRequest $request)
    {
        $collectEmailsMessages = $this->emailService->getListEmailsMessages($request->user_id);
        return view('elements.messagesEmailListModal', ['collectEmailsMessages' => $collectEmailsMessages])->render();

    }

    public function sendEmail(EmailSendRequest $request)
    {
        $user = Auth::user();
        $userArm = $this->armClient->getUserById1c($user->id_1c);
        $dataEmailUser = $userArm ? array_shift($userArm)['email_user'] : null;
        $userEmail = $dataEmailUser['email'] ?? $dataEmailUser;
        $userPassword = $dataEmailUser['password'] ?? $dataEmailUser;
        if (empty(trim($userEmail)) || empty($userPassword)) {
            return redirect()
                ->back()
                ->with('msg_err', 'Email сообщение не отправленно.
                Логин или пароль для входа в корпоративную почту не заполнен, обратитесь в техподдержку.');
        }

        $arrayParam = [
            'debtor_id' => $request->debtor_id,
            'email_id' => $request->email_id,
            'dateAnswer' => Carbon::parse($request->dateAnswer)->format('d.m.Y') ?? null,
            'datePayment' => Carbon::parse($request->datePayment)->format('d.m.Y') ?? null,
            'lastDate' => Carbon::parse($request->lastDate)->format('d.m.Y') ?? null,
            'discountPayment' => $request->discountPayment ?? null,
            'user' => $user,
            'userEmail' => $userEmail,
            'userPassword' => $userPassword,
        ];

        $customer = (Debtor::find($request->debtor_id))->customer;

        try {
            $this->debtorEventService->checkLimitEventByCustomerId1c($customer->id_1c);

            if ($this->emailService->sendEmailDebtor($arrayParam)) {
                return redirect()->back()->with('msg_suc', 'Email сообщение отправленно,мероприятие создано');
            }

            return redirect()
                ->back()
                ->with('msg_err', 'Email сообщение не отправленно. Не удалось авторизироваться на почтовом сервере.
                      Возможно,неверные логин и пароль для входа в корпоративную почту,обратитесь в техподдержку.');

        } catch (DebtorException $e) {
            Log::channel('exception')->error("$e->errorName:", [
                'customer' => $customer->id_1c,
                'file' => __FILE__,
                'method' => __METHOD__,
                'line' => __LINE__,
                'id' => $e->errorId,
                'message' => $e->errorMessage,
            ]);
            return redirect()->back()->with('msg_err', $e->errorMessage);
        }

    }
}

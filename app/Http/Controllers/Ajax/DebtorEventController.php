<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Repositories\DebtorEventsRepository;
use App\Utils\StrLib;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DebtorEventController extends Controller
{
    public $debtorEventsRepository;

    public function __construct(DebtorEventsRepository $debtorEventsRepository)
    {
        $this->debtorEventsRepository = $debtorEventsRepository;
    }
    public function destroyDebtorEvent(int $eventId)
    {
        $user = Auth::user();
        $event = $this->debtorEventsRepository->firstById($eventId);
        if ($this->debtorEventsRepository->destroy($eventId)) {
            Log::info(
                'DebtorsController.destroyDebtorEvent. Пользователь '.$user->name.' удалил мероприятие',
                [$event->toArray()]
            );
            redirect()->back()->with('msg_suc', StrLib::SUC);
        } else {
            redirect()->back()->with('msg_err', StrLib::ERR_CANT_DELETE);
        }
    }
}

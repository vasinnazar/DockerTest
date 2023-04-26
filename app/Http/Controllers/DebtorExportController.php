<?php

namespace App\Http\Controllers;

use App\Clients\ArmClient;
use App\Export\Excel\DebtorsExport;
use App\Export\Excel\DebtorsForgottenExport;
use App\Export\Excel\DebtorsOnAgreementExport;
use App\Export\Excel\EventsExport;
use App\Services\DebtorEventService;
use App\Services\DebtorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DebtorExportController extends Controller
{
    private $armClient;

    public function __construct(ArmClient $client)
    {
        $this->armClient = $client;
    }

    public function exportForgotten(Request $req, DebtorService $service)
    {
        $id1c = $req->get('search_field_users@id_1c') !== '' ? $req->get('search_field_users@id_1c') : null;
        $debtors = $service->getForgottenById1c($id1c);

        return Excel::download(
            new DebtorsForgottenExport($debtors),
            'debtorsForgotten' . Carbon::today()->format('d-m-Y') . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );

    }

    public function exportInExcelDebtors(Request $req, DebtorService $service)
    {
        $debtors = $service->getDebtors($req, true)->get()->sortBy('passports_fio');
        if ($req->get('search_field_debtors_events_promise_pays@promise_date') !== '') {
            return Excel::download(new DebtorsOnAgreementExport($this->armClient, $debtors), 'invoices.xlsx');
        }
        return Excel::download(
            new DebtorsExport($debtors),
            'debtors' . Carbon::today()->format('d-m-Y') . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );

    }


    public function exportEvents(Request $req, DebtorEventService $service)
    {
        return Excel::download(
            new EventsExport($service->getEventsForExport($req)),
            'debtorsEvents' . Carbon::today()->format('d-m-Y') . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }
}

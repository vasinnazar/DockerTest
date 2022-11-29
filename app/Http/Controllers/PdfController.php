<?php

namespace App\Http\Controllers;

use App\Debtor;
use App\Services\PdfService;
use App\Utils\PdfUtil;

class PdfController extends Controller
{
    private $pdfService;

    public function __construct(
        PdfService $pdfService
    ){
        $this->pdfService = $pdfService;
    }

    /**
     * @param int $debtor_id
     * @return void
     */
    public function getCourtOrderPdf(int $debtor_id)
    {
        $debtor = Debtor::find($debtor_id);
        $html = $this->pdfService->getCourtOrder($debtor);
        return PdfUtil::getPdfFromPrintServer($html);
    }


}

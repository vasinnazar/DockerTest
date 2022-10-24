<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class PdFController extends Controller
{

    /**
     * @param int $debtor_id
     * @return void
     */
    public function getCourtOrderPdf(int $debtor_id)
    {
        dd($debtor_id);

    }
}

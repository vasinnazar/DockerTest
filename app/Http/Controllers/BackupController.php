<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    App\NpfContract,
    App\NpfFond,
    App\Utils\StrLib,
    Auth,
    App\Utils\HtmlHelper,
    App\MySoap,
    App\Customer,
    App\Passport,
    Log,
    Illuminate\Support\Facades\DB,
    App\Spylog\Spylog,
    App\ContractForm,
    mikehaertl\wkhtmlto\Pdf,
    Input,
    Carbon\Carbon;

class BackupController extends BasicController {

    public function __construct() {
//        $this->table = 'npf_contracts';
//        $this->model = new NpfContract();
//        $this->mysoapItemID = MySoap::ITEM_NPF;
    }

    public function backupRequestsByDate(Request $req) {
        $sDate = with(new Carbon($req->get('start_date')))->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $eDate = with(new Carbon($req->get('end_date')))->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        $cols = [
            'logs.created_at as log_date',
            'log_data.data as log_data'
        ];
        $logs = DB::connection('spylogsDB')
                ->table('logs')
                ->leftJoin('log_data', 'log_data.id', '=', 'logs.data_id')
                ->where('created_at', '>=', $sDate)
                ->where('created_at', '<=', $eDate)
                ->where('logs.action', Spylog::ACTION_CALL1C)
                ->select($cols)
                ->get();
        foreach ($logs as $log) {
            Log::notice('BackupController.backupRequestsByDate', ['res' => $this->sendRequestTo1c($log->log_data), 'created_at' => $log->log_date, 'data' => $log->log_data]);
        }
    }

    public function backupOneRequest(Request $req) {
        if (!$req->has('id')) {
            return 0;
        }
        $log = DB::connection('spylogsDB')
                        ->table('log_data')
                        ->where('id', $req->id)
                        ->select(['data'])->first();
        if (is_null($log)) {
            return 0;
        }
        if (!$this->sendRequestTo1c($log->data)) {
            return 0;
        }
        return 1;
    }

    public function sendRequestTo1c($data) {
        if (is_null($data)) {
            return 0;
        }
        $json = json_decode($data, true);
        if (is_null($json)) {
            \PC::debug($json, 'error on decode');
            return 0;
        }

        switch ($json['name']) {
            case 'CreateK':
                $json['params']['id'] = $json['response']['claim_id_1c'];
                $res1c = MySoap::createClaim($json['params']);
                return $res1c['res'];
            case 'Create_KO':
                $json['params']['Number'] = $json['response']['result'];
                $res1c = MySoap::createOrder($json['params']);
                return $res1c['res'];
            case 'CreateZPP':
                $json['params']['Number'] = $json['response']['result'];
                $res1c = MySoap::createClaimRepayment($json['params']);
                return $res1c['res'];
            case 'CreateK_other':
                $json['params']['Number'] = $json['response']['result'];
                $res1c = MySoap::createRepayment($json['params']);
                return $res1c['res'];
            case 'CreateMS':
                $json['params']['Number'] = $json['response']['result'];
                $res1c = MySoap::createPeaceRepayment($json['params']);
                return $res1c['res'];
            case 'CreateСreditAgreement':
                $res1c = MySoap::createLoan($json['params']);
                return $res1c['res'];
            case 'CreateOrder':
                $res1c = MySoap::createOrder($json['params']);
                return $res1c['res'];
            case 'IAmMole':
                $xml = new \SimpleXMLElement($json['params']['params']);
                if ($xml->type != 0) {
                    return 0;
                }
//                $xml->addChild('number', '');
                $res1c = MySoap::sendXML($xml->asXML(), true);
                return $res1c->result;
        }
        return 0;
    }

}

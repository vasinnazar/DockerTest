<?php

namespace App;

use Carbon\Carbon;
use App\Claim;
use App\Utils\HelperUtil;
use App\MySoap;
use Log;

class Scorista {
    /**
     * Отбирает все заявки с заполненным идентификатором скористы и запрашивает статус на сайте скористы
     * В случае если скориста обработала заявку, разбирает ответ и отправляет в 1с данные о решении
     */
    static function checkStatuses() {
        $username = 't.popova@pdengi.ru';
        $token = '3d7f9600bd6b0352dae50eaac030f870e4dbe4c3';

        $date = Carbon::now()->subMinute()->format('Y-m-d H:i:s');

        $claims = Claim::whereRaw('agrid is not null and (scorista_status is null or scorista_status not in("ERROR","DONE")) and updated_at<\'' . $date . '\'')->orderBy('created_at', 'desc')->limit(50)->get();
        foreach ($claims as $claim) {
            $nonce = sha1(uniqid(true));
            $password = sha1($nonce . $token);
            $params = ['requestid' => $claim->agrid];
            $headers = ['username' => $username, 'password' => $password, 'nonce' => $nonce];
            $response = HelperUtil::SendPostByCurl('https://api.scorista.ru/mixed/json', $params, $headers);
            $data = json_decode($response);
            if (is_null($data)) {
                Log::error('Scorista', ['response' => $response]);
                continue;
            }
            Log::info('Scorista', ['nonce' => $nonce, 'params' => $params, 'headers' => $headers, 'requestid' => $claim->agrid, 'data' => $data]);
            $claim->scorista_status = $data->status;
            if ($data->status == 'DONE') {
                $claim->scorista_decision = $data->data->decision->decisionBinnar;
                $conscientiousnessNPL15 = $data->data->additional->creditHistory->score;
                $reliabilityNPL15 = $data->data->additional->trustRating->score;
                $res1c = MySoap::sendExchangeArm(MySoap::createXML([
                                    'type' => 'SetScoristaDecision',
                                    'claim_id_1c' => $claim->id_1c,
                                    'decisionBinnar' => $claim->scorista_decision,
                                    'conscientiousnessNPL15' => (empty($conscientiousnessNPL15) || $conscientiousnessNPL15 == 'null') ? '' : $conscientiousnessNPL15,
                                    'reliabilityNPL15' => (empty($reliabilityNPL15) || $reliabilityNPL15 == 'null') ? '' : $reliabilityNPL15
                ]));
            }
            $claim->save();
            sleep(1);
        }
    }

}

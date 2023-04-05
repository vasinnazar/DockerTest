<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as LaravelsVerifyCsrfToken;

class VerifyCsrfToken extends LaravelsVerifyCsrfToken {
    private $openRoutes = [
        '1c/users/add',
        '1c/claims/update',
        '1c/claims/update/status',
        '1c/debtors/upload',
        '1c/debtors/info/upload',
        '1c/debtors/addRecordToUploadSqlFilesType1',
        '1c/debtors/addRecordToUploadSqlFilesType2',
        '1c/seb',
        '1c/docs/get',
        '1c/sms/noloan',
        '1c/orders/update',
        '1c/asp/read',
        'terminal/preauth',
        'terminal/status',
        'terminal/auth',
        'terminal/pinauth',
        'terminal/tpitems',
        'terminal/order',
        'terminal/file',
        'terminal/fileinfo',
        'terminal/paypoints',
        'terminal/sendaction',
        'terminal/scmd',
        'terminal/promo',
        'debt',
        'reports/dailycashreport/1c/update',
        'test',
        'videoplayer',
        'teleport/claim/create',
        'teleport/statustest',
        'loans/promocode/get',
        'debtors/customers/upload',
        'debtors/photos/main',
        'debtors/debt',
        'debtors/orders/upload',
        'debtors/loans/upload',
        '1c/debtors/events/update_id_1c',
        '1c/debtors/changeRespUser',
        'debtors/getResponsibleUser',
        'debtors/events/from1c',
        'debtors/loan/closing',
        'debtors/msg/debtoronsubdivision',
        'debtors/onsite',
        'finterra/claim/create',
        'finterra/loan/create',
        'finterra/asp/secret',
        'finterra/asp/approve',
        'finterra/asp/data',
        'infinity/income',
        'api/customer/data',
        'api/claim/data',
        'api/claim/create',
        'api/loan/create',
        'infinity/losscall',
        '1c/debtors/upload/updateClientInfo',
        '1c/debtors/upload/clearOtherPhones',
        'infinity/is_debtor_time',
        'infinity/is_debtor_operator',
        'debtors/event/withoutAccept',
        'debtors/omicron/task',
        'ajax/loans/get/debt',
        'debtors/infinity/incomingCall',
        'debtors/infinity/closingModals'
    ];

    public function handle($request, Closure $next) {
        if (!empty($_FILES)) {
            return $this->addCookieToResponse($request, $next($request));
        }
        if($request->is('orders/pdf/*') || $request->is('contracts/pdf/npf/*')){
            return $this->addCookieToResponse($request, $next($request));
        }
        foreach ($this->openRoutes as $route) {
            if ($request->is($route)) {
                return $next($request);
            }
        }
        return parent::handle($request, $next);
    }

}

//namespace App\Http\Middleware;
//
//use Closure;
//use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;
//
//class VerifyCsrfToken extends BaseVerifier {
//
//    protected $except = [
//        '1c/*',
//    ];
//
//    /**
//     * Handle an incoming request.
//     *
//     * @param  \Illuminate\Http\Request  $request
//     * @param  \Closure  $next
//     * @return mixed
//     */
//    public function handle($request, Closure $next) {
//        if (!empty($_FILES)) {
//            return $this->addCookieToResponse($request, $next($request));
//        }
//
//        return parent::handle($request, $next);
//    }
//
//}

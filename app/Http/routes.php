<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

//use Log;

Route::middleware('admin_only', function() {
//    if (substr(Request::server('REMOTE_ADDR'), 0, 10) == '192.168.1.' || Request::server('REMOTE_ADDR') == '127.0.0.1') {
    if (is_null(Auth::user()) || !Auth::user()->isAdmin()) {
        return redirect('home')->with('msg', 'Недостаточно прав доступа!')->with('class', 'alert-danger');
    }
//    } else {
//        return redirect('home')->with('msg', 'Доступ запрещен!')->with('class', 'alert-danger');
//    }
});
Route::middleware('office_only', function() {
    if (!config('app.dev')) {
        if (is_null(Auth::user()) || is_null(Auth::user()->subdivision) || !in_array(Auth::user()->subdivision->name_id, ['000000012', 'НСК00014'])) {
            return redirect('home')->with('msg', 'Недостаточно прав доступа!')->with('class', 'alert-danger');
        }
    }
});
Route::middleware('infinity_only', function() {
    if (!(Request::server('REMOTE_ADDR') == '192.168.1.50' || Request::server('REMOTE_ADDR') == '192.168.1.60')) {
        return '';
    }
});
Route::middleware('auth_only', function() {
    if (is_null(Auth::user())) {
        return redirect('auth/login');
    }
});
Route::get('images/{passport}/{date}/{image}', ['before' => 'auth_only', function($passport = null, $date = null, $image = null) {
        if (strtotime($date) >= strtotime('14.06.2018')) {
            $disk = 'ftp31';
            $path = $passport . '/' . $date . '/' . $image;
        }  else if (strtotime($date) >= strtotime('22.12.2017')) {
            $disk = 'ftp31_111';
            $path = $passport . '/' . $date . '/' . $image;
        } else if (strtotime($date) >= strtotime('22.12.2017')) {
            $disk = 'ftp31_111';
            $path = $passport . '/' . $date . '/' . $image;
        }  else if (strtotime($date) >= strtotime('26.04.2017')) {
            $disk = 'ftp31_999';
            $path = $passport . '/' . $date . '/' . $image;
        } else if (strtotime($date) >= strtotime('07.04.2017')) {
            $disk = 'ftp125';
            $path = 'images/' . $passport . '/' . $date . '/' . $image;
        } else {
            $disk = 'ftp';
            $path = 'images/' . $passport . '/' . $date . '/' . $image;
        }
        if (!Storage::disk($disk)->exists($path)) {
            return;
        }
        $file = Storage::disk($disk)->get($path);
        $type = Storage::disk($disk)->mimeType($path);
        $img = Image::make($file);
        $img->resize(1000, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
        $response = Response::make($img->stream(), 200);
        $response->header("Content-Type", $type);
        return $response;
    }]
);
Route::get('userphotos/{subdivision_id_1c}/{image}', ['before' => 'auth_only', function($subdivision_id_1c = null, $image = null) {
        $disk = config('filesystems.default');
//        $disk = 'local';
        $path = 'userphotos/' . $subdivision_id_1c . '/' . $image;
        if (!Storage::disk($disk)->exists($path)) {
            return;
        }
        $file = Storage::disk($disk)->get($path);
        $type = Storage::disk($disk)->mimeType($path);
        $img = Image::make($file);
        $img->resize(1000, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
        $response = Response::make($img->stream(), 200);
        $response->header("Content-Type", $type);
        return $response;
    }]
);
Route::get('helpfiles/{id}/{ext}', ['before' => 'auth_only', 'uses' => 'HelpController@getFile']);
Route::get('images/{path}', [function($path) {
        $file = Storage::get($path);
        $type = Storage::mimeType($path);
        $img = Image::make($file);
        $img->resize(1000, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
        $response = Response::make($img->stream(), 200);
        $response->header("Content-Type", $type);
        return $response;
    }]);

Route::middleware('localCallOnly', function() {
//    if (Request::server('REMOTE_ADDR') != '192.168.1.34') {
    if (Request::server('REMOTE_ADDR') == '192.168.1.167' || Request::server('REMOTE_ADDR') == '192.168.1.47' || Request::server('REMOTE_ADDR') == '192.168.34.206') {
//    if (Request::server('REMOTE_ADDR') == '192.168.1.167' || Request::server('REMOTE_ADDR') == '192.168.1.47') {
        if (!config('app.dev')) {
            return ['res' => 1];
        }
    }
    if (!(
            substr(Request::server('REMOTE_ADDR'), 0, 10) == '192.168.1.'
            || substr(Request::server('REMOTE_ADDR'), 0, 11) == '192.168.32.'
            || substr(Request::server('REMOTE_ADDR'), 0, 11) == '192.168.34.'
            || in_array(substr(Request::server('REMOTE_ADDR'), 0, 10),['172.16.2.3','172.16.2.4','172.16.2.5','172.16.2.6','172.16.1.1','172.16.1.29'])
    )) {
        return App::abort(404);
    }
});


Route::get('auth/login',['uses' => 'Auth\AuthController@login']);
Route::post('auth/login',['uses' => 'Auth\AuthController@postLogin']);
Route::get('auth/logout',['uses' => 'Auth\AuthController@logout']);

Route::middleware('csrf', function() {
    $token = Request::ajax() ? Request::header('X-CSRF-Token') : Input::get('_token');
    if (Session::token() != $token)
        throw new Illuminate\Session\TokenMismatchException;
});

Event::listen('laravel.log', function($type, $message) {
//    $log = new \App\Spylog\Spylog();
//    $log->message = $message;
//    $log->type = $type;
//    $log->update;
    \App\Spylog\Spylog::logError(['type' => $type, 'message' => $message]);
});

Route::get('maintenance', function() {
    if (config('admin.maintenance_mode')) {
        return view('maintenance');
    } else {
        return redirect('/');
    }
});

//заявка
Route::group(['middleware' => 'auth'], function() {
    Route::post('claims/create', ['as' => 'claims.create', 'uses' => 'ClaimController@create']);
    Route::get('claims/create', ['as' => 'claims.create', 'uses' => 'ClaimController@create']);
    Route::post('claims/save', ['as' => 'claims.save', 'uses' => 'ClaimController@store']);
    Route::get('claims/edit/{claim_id}', ['as' => 'claims.edit', 'uses' => 'ClaimController@edit']);
    Route::post('claims/update', ['as' => 'claims.edit', 'uses' => 'ClaimController@update']);
    Route::get('claims/status/{claim_id}/{status}', ['as' => 'claims.status', 'uses' => 'ClaimController@changeStatus']);
    Route::get('claims/mark4remove/{claim_id}', ['as' => 'claims.mark4remove', 'before' => 'admin_only', 'uses' => 'ClaimController@markForRemove']);
    Route::get('claims/mark4remove2/{claim_id}', ['as' => 'claims.mark4remove2', 'before' => 'admin_only', 'uses' => 'ClaimController@markForRemove2']);
    Route::get('claims/remove/{claim_id}', ['as' => 'claims.remove', 'before' => 'admin_only', 'uses' => 'ClaimController@remove']);
    Route::get('ajax/claims/hasloan/{claim_id}', ['as' => 'claims.hasloan', 'uses' => 'ClaimController@hasLoan']);
    Route::get('ajax/claims/checkpassport/{claim_id}', ['as' => 'claims.checkpassport', 'uses' => 'ClaimController@checkPassport']);
    Route::get('claims/summary/{claim_id}', ['as' => 'claims.summary', 'uses' => 'ClaimController@summary']);
    Route::post('ajax/claims/customer', ['as' => 'claims.customer', 'uses' => 'ClaimController@getClaimFormDataByPassport']);
    Route::post('ajax/claims/autoapprove', ['as' => 'claims.autoapprove', 'uses' => 'ClaimController@sendToAutoApprove']);
    Route::post('ajax/claims/check/telephone', ['uses' => 'ClaimController@checkTelephone']);
    Route::get('claims/updatefrom1c/{claim_id}', ['uses' => 'ClaimController@updateFrom1c']);
    Route::get('claims/setedit/{claim_id}', ['uses' => 'ClaimController@setStatusToEdit']);
});
//Route::get('ajax/tinkoff/cardlist', ['uses' => 'TinkoffController@getCardList']);
//Route::post('ajax/tinkoff/card/add', ['uses' => 'TinkoffController@addCard']);

Route::post('claims/1c/update', ['as' => 'claims.update1c', 'uses' => 'ClaimController@updateFrom1c']);
Route::post('test', ['as' => 'test', 'uses' => 'TestController@test']);
Route::get('test', ['as' => 'test', 'uses' => 'TestController@test']);
Route::get('videoplayer', ['uses' => 'TestController@video']);
Route::get('home2', ['as' => 'claims.list2', 'uses' => 'HomeController@claimsList2']);

//редактор договоров
Route::get('contracts/list', ['as' => 'contracts.list', 'uses' => 'ContractEditorController@index']);
Route::get('contracts/create', ['as' => 'contracts.create', 'before' => 'admin_only', 'uses' => 'ContractEditorController@openEditor']);
Route::get('contracts/edit/{contract_id}', ['as' => 'contracts.edit', 'before' => 'admin_only', 'uses' => 'ContractEditorController@openEditor']);
Route::post('contracts/update', ['as' => 'contracts.update', 'before' => 'admin_only', 'uses' => 'ContractEditorController@update']);
Route::get('contracts/delete/{contract_id}', ['as' => 'contracts.delete', 'before' => 'admin_only', 'uses' => 'ContractEditorController@delete']);
Route::get('contracts/pdf/{contract_id}', ['as' => 'contracts.empty', 'uses' => 'ContractEditorController@createPdf']);
Route::get('contracts/pdf/{contract_id}/{zaim_id}', ['as' => 'contracts.filled', 'uses' => 'ContractEditorController@createPdf']);
Route::get('contracts/pdf/{contract_id}/{zaim_id}/{repayment_id}', ['as' => 'contracts.repayment', 'uses' => 'ContractEditorController@createPdf']);
Route::get('contracts/pdf/{contract_id}/{zaim_id}/{repayment_id}/{loan_id}', ['uses' => 'ContractEditorController@createPdf']);
Route::get('contracts/make/pdf', ['uses' => 'ContractEditorController@createPdfByRequest']);
Route::get('contracts/versions', ['uses' => 'ContractVersionsController@versionsEditor']);
Route::post('contracts/versions/add', ['uses' => 'ContractVersionsController@addVersion']);
Route::post('contracts/versions/update', ['uses' => 'ContractVersionsController@updateVersion']);
Route::get('contracts/versions/remove', ['uses' => 'ContractVersionsController@removeItem']);

Route::get('makepdf', ['as' => 'contracts.makepdf', 'uses' => 'ContractEditorController@makePdf']);
Route::post('makepdf', ['as' => 'contracts.makepdf', 'uses' => 'ContractEditorController@makePdf']);

//Route::get('contracts/pdf/npf/{contract_id}/{npf_id}', ['as' => 'contracts.npf', 'uses' => 'ContractEditorController@createPdfNpf']);
Route::get('npf/pdf/{contract_id}/{npf_id}', ['as' => 'contracts.npf', 'uses' => 'NpfController@createPdf']);
//Route::get('contracts/pdf/npf', ['as' => 'contracts.npf', 'uses' => 'ContractEditorController@createPdfNpf']);
//редактор условий
Route::get('conditions/list', ['as' => 'conditions.list', 'before' => 'admin_only', 'uses' => 'ConditionsController@index']);
Route::get('conditions/create', ['as' => 'conditions.create', 'before' => 'admin_only', 'uses' => 'ConditionsController@editor']);
Route::get('conditions/edit/{condition_id}', ['as' => 'conditions.edit', 'before' => 'admin_only', 'uses' => 'ConditionsController@editor']);
Route::post('conditions/update', ['as' => 'conditions.update', 'before' => 'admin_only', 'uses' => 'ConditionsController@update']);
Route::get('conditions/delete/{condition_id}', ['as' => 'conditions.delete', 'before' => 'admin_only', 'uses' => 'ConditionsController@delete']);

//виды займов
Route::get('loantypes/list', ['as' => 'loantypes.list', 'before' => 'admin_only', 'uses' => 'LoanTypeController@index']);
Route::get('loantypes/create', ['as' => 'loantypes.create', 'before' => 'admin_only', 'uses' => 'LoanTypeController@editor']);
Route::get('loantypes/edit/{condition_id}', ['as' => 'loantypes.edit', 'before' => 'admin_only', 'uses' => 'LoanTypeController@editor']);
Route::post('loantypes/update', ['as' => 'loantypes.update', 'before' => 'admin_only', 'uses' => 'LoanTypeController@update']);
Route::get('loantypes/delete/{condition_id}', ['as' => 'loantypes.delete', 'before' => 'admin_only', 'uses' => 'LoanTypeController@delete']);
Route::get('loantypes/clone/{loantype_id}', ['as' => 'loantypes.clone', 'before' => 'admin_only', 'uses' => 'LoanTypeController@cloneLoantype']);

//займы
Route::post('loans/create', ['as' => 'loans.create', 'uses' => 'LoanController@create']);
Route::get('loans', ['as' => 'loans', 'uses' => 'LoanController@getLoans']);
Route::get('loans/summary/{loan_id}', ['as' => 'loans', 'uses' => 'LoanController@getLoanSummary']);
Route::get('loans/summary/{pseries}/{pnumber}', ['as' => 'loans', 'uses' => 'LoanController@getLoanByPassport']);
Route::get('ajax/loans/list', ['as' => 'ajax.loans.list', 'uses' => 'LoanController@getLoansList']);
Route::post('loans/demo', ['as' => 'loans.demo', 'uses' => 'LoanController@demo']);
Route::get('loans/remove/{loan_id}', ['as' => 'loans.remove', 'before' => 'admin_only', 'uses' => 'LoanController@remove']);
Route::get('loans/edit/{loan_id}', ['as' => 'loans.edit', 'before' => 'admin_only', 'uses' => 'LoanController@edit']);
Route::get('loans/incash/{loan_id}', ['as' => 'loans.incash', 'before' => 'admin_only', 'uses' => 'LoanController@inCash']);
Route::post('loans/update', ['as' => 'loans.update', 'before' => 'admin_only', 'uses' => 'LoanController@update']);
Route::get('loans/enroll/{loan_id}', ['as' => 'loans.enroll', 'uses' => 'LoanController@enroll']);
Route::post('ajax/promocodes/create', ['as' => 'ajax.promocodes.create', 'before' => 'admin_only', 'uses' => 'LoanController@createPromocode']);

Route::get('loans/close/{loan_id}', ['as' => 'loans.close', 'uses' => 'LoanController@close']);
Route::get('ajax/loans/get/{loan_id}', ['as' => 'ajax.loans.get', 'uses' => 'LoanController@getLoan']);
Route::get('loans/makeandroll/{claim_id}', ['as' => 'loans.makeandroll', 'uses' => 'LoanController@createAndEnroll']);
Route::get('loans/sendtobalance/{loan_id}', ['as' => 'loans.sendtobalance', 'uses' => 'LoanController@sendMoneyToBalance']);
Route::get('loans/clearenroll/{loan_id}', ['as' => 'loans.clearenroll', 'before' => 'admin_only', 'uses' => 'LoanController@clearEnroll']);
Route::get('loans/remove2/{loan_id}', ['as' => 'loans.remove2', 'before' => 'admin_only', 'uses' => 'LoanController@removeOnlyFromDB']);
Route::post('ajax/loan/get/debt', ['uses' => 'LoanController@getDebt']);

Route::post('loans/uki/orders/create', ['uses' => 'LoanController@createUkiOrders']);

//отчёты
Route::get('reports/dailycashreportslist', ['as' => 'reports.dailycashreportslist', 'uses' => 'DailyCashReportController@getListView']);
Route::get('ajax/reports/dailycashreportslist', ['as' => 'ajax.reports.dailycashreportslist', 'uses' => 'DailyCashReportController@getList']);
Route::get('ajax/reports/matchWithCashbook/{report_id}', ['as' => 'ajax.reports.matchWithCashbook', 'uses' => 'DailyCashReportController@matchWithCashbookById']);
Route::get('reports/dailycashreport', ['as' => 'reports.dailycashreport', 'uses' => 'DailyCashReportController@getView']);
Route::get('reports/dailycashreport/{report_id}', ['as' => 'reports.dailycashreport', 'uses' => 'DailyCashReportController@getView']);
Route::get('reports/pdf/dailycashreport/{report_id}', ['as' => 'reports.dailycashreport', 'uses' => 'DailyCashReportController@createPdf']);
Route::get('reports/pdf/dailycashsummary', ['as' => 'reports.dailycashsummary', 'uses' => 'DailyCashReportController@summaryReport']);
Route::post('reports/update', ['as' => 'reports.update', 'uses' => 'DailyCashReportController@update']);
Route::get('reports/cashbook', ['as' => 'reports.cashbook', 'uses' => 'DailyCashReportController@cashbook']);
Route::get('reports/pdf/cashbook', ['as' => 'reports.pdf.cashbook', 'uses' => 'DailyCashReportController@createCashbook']);
Route::post('reports/dailycashreport/1c/update', ['as' => 'reports.dailycashreport.1c.update', 'uses' => 'From1cController@dailyCashReportUpdate']);
Route::get('reports/remove', ['as' => 'reports.remove', 'before' => 'admin_only', 'uses' => 'DailyCashReportController@remove']);
Route::get('reports', ['as' => 'reports', 'uses' => 'ReportsController@reports']);
Route::get('reports/setmatch', ['as' => 'reports.setmatch', 'uses' => 'DailyCashReportController@setMatch']);
Route::get('reports/dailycashreport/editable/toggle', ['before' => 'admin_only', 'uses' => 'DailyCashReportController@toggleReportEditable']);

//отчет по отсутствующим подразделениям
Route::get('reports/absentsubdivisions', ['uses' => 'ReportsController@getAbsentSubdivisions']);

//расчетный лист
Route::get('reports/paysheet', ['as' => 'paysheet', 'uses' => 'PaySheetController@index']);
Route::get('reports/paysheet/pdf', ['as' => 'paysheet.pdf', 'uses' => 'PaySheetController@createPdf']);

//реестр документов
Route::get('reports/docsregister', ['as' => 'docsregister', 'uses' => 'DocsRegisterController@index']);
Route::get('reports/docsregister/pdf', ['as' => 'docsregister.pdf', 'uses' => 'DocsRegisterController@createPdf']);
Route::get('reports/docsregister/pdf2', ['uses' => 'DocsRegisterController@createPdf2']);

//отчет по продажам
Route::get('reports/salesreports/1/pdf', ['uses' => 'SalesReportsController@getSalesReport1']);

Route::get('cashbook/sync', ['as' => 'cashbook.sync', 'uses' => 'DailyCashReportController@syncCashbook']);
Route::get('cashbook/sync2', ['as' => 'cashbook.sync2', 'uses' => 'DailyCashReportController@syncCashbookFromDailyCash']);

Route::get('reports/plan', ['as' => 'reports.plan', 'uses' => 'PlanController@getTableView']);
Route::get('reports/plan/loanslist', ['as' => 'reports.plan.loanslist', 'uses' => 'PlanController@getLoansList']);
Route::post('reports/plan/loanslist', ['as' => 'reports.plan.loanslist', 'uses' => 'PlanController@getLoansList']);
Route::get('reports/saldo', ['as' => 'reports.saldo', 'uses' => 'SaldoController@getSaldoView']);

//обзвон
Route::get('reports/phonecall', ['as' => 'reports.phonecall', 'uses' => 'PhoneCallController@getView']);
Route::post('reports/phonecall/save', ['as' => 'reports.phonecall.save', 'uses' => 'PhoneCallController@saveAndShowNext']);

Route::get('reports/rnko', ['before' => 'auth_only', 'as' => 'reports.rnko', 'uses' => 'RnkoController@index']);
Route::post('reports/rnko/update', ['before' => 'auth_only', 'uses' => 'RnkoController@update']);
Route::get('reports/rnko/admin', ['before' => 'auth_only', 'uses' => 'RnkoController@admin']);
Route::get('reports/rnko/bydates', ['before' => 'auth_only', 'uses' => 'RnkoController@getReportByDays']);
Route::get('reports/rnko/number', ['before' => 'auth_only', 'uses' => 'RnkoController@getRnkoByCardNumber']);
Route::get('reports/rnko/check/get', ['before' => 'auth_only', 'uses' => 'RnkoController@getUncheckedRnko']);
Route::get('reports/rnko/photos', ['before' => 'auth_only', 'uses' => 'RnkoController@openAllPhotos']);
Route::get('reports/rnko/skip', ['before' => 'auth_only', 'uses' => 'RnkoController@skipRnko']);
//Route::get('reports/rnko/upload', ['uses' => 'RnkoController@uploadXml']);
Route::get('phpinfo', ['uses' => 'RnkoController@phpinfo']);


//смена подразделения
Route::middleware('change_subdiv_once', function() {
    $now = Carbon::now();
    $scdb = Auth::user()->subdivision_change;
    $sc = new Carbon($scdb);
    if (is_null(Auth::user()) || (!Auth::user()->isAdmin() && $scdb != NULL && ($now->diffInHours($sc) < 8 && $now->day == $sc->day))) {
        return redirect('home')
                        ->with('msg', 'Нельзя менять подразделение более одного раза в день!')
                        ->with('class', 'alert-danger');
    }
});
Route::post('subdivisions/process', ['as' => 'subdivisions.process', 'before' => 'change_subdiv_once', 'uses' => 'SubdivisionController@process']);
Route::post('subdivisions/update', ['as' => 'subdivisions.update', 'before' => 'change_subdiv_once', 'uses' => 'SubdivisionController@update']);
Route::get('subdivisions/change', ['as' => 'subdivisions.change', 'before' => 'change_subdiv_once', 'uses' => 'SubdivisionController@index']);
Route::get('subdivisions/swapable/change', ['uses' => 'SubdivisionController@swapableChangePage']);
Route::post('subdivisions/swapable/update', ['uses' => 'SubdivisionController@swapableChangeSubdivision']);
Route::post('subdivisions/swapable/smspass', ['uses' => 'SubdivisionController@getSmsPassword']);
Route::get('ajax/subdivisions/get', ['uses' => 'SubdivisionController@getSubdivision']);

//загрузка фотографий
Route::get('photos/add/{claim_id}', ['as' => 'photos.add', 'uses' => 'PhotoController@upload_photo']);
Route::post('photos/upload', ['as' => 'photos.upload', 'uses' => 'PhotoController@uploads']);
//Route::group(['middleware' => ['web']], function () {
Route::post('photos/ajax/upload', ['as' => 'photos.ajax.upload', 'uses' => 'PhotoController@ajaxUpload']);
Route::post('photos/ajax/upload2', ['as' => 'photos.ajax.upload2', 'uses' => 'PhotoController@ajaxUpload2']);
Route::post('photos/ajax/webcam/upload', ['uses' => 'PhotoController@webcamUpload']);
Route::post('photos/ajax/userphoto/upload', ['uses' => 'UserPhotoController@userPhotoUpload']);
Route::post('photos/ajax/remove', ['as' => 'photos.ajax.remove', 'uses' => 'PhotoController@ajaxRemove']);
Route::post('photos/ajax/remove/all', ['uses' => 'PhotoController@removeAllForClaim']);
//});
Route::post('photos/update', ['as' => 'photos.update', 'uses' => 'PhotoController@editPhotos']);
Route::post('photos/main', ['as' => 'photos.main', 'uses' => 'PhotoController@setMain']);
Route::get('photos/get/all', ['as' => 'photos.get.all', 'uses' => 'PhotoController@getPhotos']);
Route::get('photos/view', ['as' => 'photos.view', 'uses' => 'PhotoController@viewPhoto']);

//рабочий стол
Route::get('/', function(){
    return redirect('home');
});
Route::get('home', 'HomeController@index');
//Route::get('/', 'HomeController@claimsList2');
//Route::get('home', 'HomeController@claimsList2');
//Route::group(['middleware' => ['web']], function () {
Route::post('ajax/contracts/list/{claim_id}', ['as' => 'ajax.contracts.list', 'uses' => 'HomeController@contractslist']);
Route::get('ajax/loantypes/list/{claim_id}', ['as' => 'ajax.loantypes.list', 'uses' => 'LoanTypeController@getPossible']);
Route::get('ajax/claims/list', ['as' => 'ajax.claims.list', 'uses' => 'HomeController@claimsList']);
Route::get('ajax/claims/filter', ['as' => 'ajax.claims.filter', 'uses' => 'HomeController@getCustomFilterData']);
Route::get('ajax/carddata', ['as' => 'ajax.carddata', 'uses' => 'HomeController@getCardData']);
Route::get('ajax/promocode/{loan_id}', ['as' => 'ajax.promocode', 'uses' => 'HomeController@getPromocode']);
Route::post('ajax/promocodes/add', ['as' => 'ajax.promocodes.add', 'uses' => 'ClaimController@addPromocode']);
Route::post('ajax/claims/comment', ['as' => 'ajax.claims.comment', 'uses' => 'HomeController@getComment']);
Route::post('ajax/promocodes/getdata', ['as' => 'ajax.promocodes.getdata', 'uses' => 'HomeController@getPromocodeData']);
Route::post('ajax/promocodes/add/manual', ['as' => 'ajax.promocodes.add.manual', 'before' => 'admin_only', 'uses' => 'HomeController@manualAddPromocode']);
Route::post('ajax/customers/telephone', ['as' => 'ajax.customers.telephone', 'uses' => 'CustomersController@getTelephone']);
Route::post('ajax/customers/asp', ['uses' => 'CustomersController@handleAsp']);
Route::get('customers/asp', ['uses' => 'CustomersController@handleAsp2']);
Route::get('customers/pdagreement/handle', ['uses' => 'CustomersController@handlePdAgreement']);
//});
Route::get('/startwork', ['as' => 'startwork', 'uses' => 'HomeController@startWork']);
Route::get('/endwork', ['as' => 'endwork', 'uses' => 'HomeController@endWork']);

Route::post('promocodes/add/manual', ['as' => 'promocodes.add.manual', 'uses' => 'HomeController@manualAddPromocode2']);
Route::get('loans/set/cccall', ['as' => 'loans.set.cccall', 'before' => 'admin_only', 'uses' => 'HomeController@setCCcall']);
Route::get('loans/uki/toggle', ['before' => 'auth_only', 'uses' => 'HomeController@toggleUki']);
Route::get('claims/uki/toggle', ['before' => 'auth_only', 'uses' => 'HomeController@toggleUkiClaim']);
Route::get('claims/cbupdate', ['before' => 'auth_only', 'uses' => 'HomeController@toggleAboutClientCheckbox']);

//аякс запросы
//Route::get('ajax/yaspeller', ['as' => 'ajax.yaspeller', 'uses' => 'AjaxController@yaspeller']);
Route::get('ajax/{table}/autocomplete', ['uses' => 'AjaxController@autocomplete']);
Route::get('ajax/autocomplete/label', ['uses' => 'AjaxController@getLabelById']);
Route::get('ajax/passports/autocomplete', ['uses' => 'AjaxController@passportsAutocomplete']);
Route::get('ajax/passports/get', ['uses' => 'AjaxController@getPassport']);
Route::get('ajax/subdivisions/autocomplete', ['as' => 'ajax.subdivisions.autocomplete', 'uses' => 'SubdivisionController@getAutocompleteList']);
Route::get('ajax/users/autocomplete', ['as' => 'ajax.users.autocomplete', 'uses' => 'UserController@getAutocompleteList']);
Route::get('ajax/user/info', ['uses' => 'UserController@getInfo']);
//логи
Route::get('spylog/list', ['as' => 'spylog.list', 'before' => 'admin_only', 'uses' => 'SpylogController@index']);
Route::get('ajax/spylog/list', ['as' => 'ajax.spylog.list', 'before' => 'admin_only', 'uses' => 'SpylogController@getList']);
Route::post('ajax/spylog/view/{log_id}', ['as' => 'ajax.spylog.view', 'before' => 'admin_only', 'uses' => 'SpylogController@viewLog']);
Route::post('ajax/spylog/repeat', ['as' => 'ajax.spylog.repeat', 'before' => 'admin_only', 'uses' => 'BackupController@backupOneRequest']);

//админ.панель
Route::get('adminpanel', ['as' => 'adminpanel.index', 'before' => 'admin_only', 'uses' => 'AdminPanelController@index']);
Route::get('adminpanel/users', ['as' => 'adminpanel.users', 'before' => 'admin_only', 'uses' => 'AdminPanelController@getUsers']);
Route::get('adminpanel/users/ssl/download', ['before' => 'admin_only', 'uses' => 'AdminPanelController@createUserCertificate']);
//Route::group(['middleware' => ['web']], function () {
Route::post('ajax/adminpanel/users/changesubdivision', ['as' => 'ajax.adminpanel.user.changesubdivision', 'before' => 'admin_only', 'uses' => 'AdminPanelController@changeSubdivision']);
Route::get('ajax/adminpanel/userslist', ['as' => 'ajax.adminpanel.userslist', 'before' => 'admin_only', 'uses' => 'AdminPanelController@getUsersList']);
Route::get('ajax/adminpanel/users/userlog', ['as' => 'ajax.adminpanel.user.userlog', 'before' => 'admin_only', 'uses' => 'AdminPanelController@getUserLog']);
Route::get('ajax/adminpanel/users/{user_id}', ['as' => 'ajax.adminpanel.user', 'before' => 'admin_only', 'uses' => 'AdminPanelController@getUser']);
Route::post('ajax/adminpanel/users/update', ['as' => 'ajax.adminpanel.user.update', 'before' => 'admin_only', 'uses' => 'AdminPanelController@updateUser']);
Route::post('ajax/adminpanel/users/{id}/createcustomer', ['as' => 'ajax.adminpanel.user.createcustomer', 'before' => 'admin_only', 'uses' => 'AdminPanelController@createCustomer']);
Route::post('ajax/adminpanel/users/bantime', ['as' => 'ajax.adminpanel.user.bantime', 'before' => 'admin_only', 'uses' => 'AdminPanelController@updateUserBantime']);
Route::post('ajax/adminpanel/users/changepass', ['as' => 'ajax.adminpanel.user.changepass', 'before' => 'admin_only', 'uses' => 'AdminPanelController@changePassword']);
Route::post('ajax/adminpanel/users/add', ['as' => 'ajax.adminpanel.user.add', 'before' => 'admin_only', 'uses' => 'AdminPanelController@addUser']);
Route::post('ajax/adminpanel/users/saveDebtorUserSlaves', ['as' => 'ajax.adminpanel.user.saveDebtorUserSlaves', 'before' => 'admin_only', 'uses' => 'AdminPanelController@saveDebtorUserSlaves']);
Route::post('ajax/adminpanel/refreshlastlogin/{user_id}', ['as' => 'ajax.adminpanel.refreshlastlogin', 'before' => 'admin_only', 'uses' => 'AdminPanelController@refreshUserLastLogin']);
Route::post('ajax/adminpanel/users/employment/{user_id}', ['before' => 'admin_only', 'uses' => 'UserController@setEmploymentFields']);
Route::get('ajax/adminpanel/subdivisions/list', ['as' => 'ajax.adminpanel.subdivisions.list', 'before' => 'admin_only', 'uses' => 'AdminPanelController@getSubdivisionsList']);
Route::post('ajax/adminpanel/users/terminals/grant', ['before' => 'admin_only', 'uses' => 'UserController@grantTerminals']);
Route::post('ajax/adminpanel/users/mail/add', ['before' => 'admin_only', 'uses' => 'UserController@addEmailAjax']);
Route::get('adminpanel/users/mail/index', ['before' => 'admin_only', 'uses' => 'UserController@emailIndex']);
Route::post('adminpanel/users/mail/add', ['before' => 'admin_only', 'uses' => 'UserController@addEmail']);
//});
Route::get('adminpanel/subdivisions', ['as' => 'adminpanel.subdivisions', 'before' => 'admin_only', 'uses' => 'AdminPanelController@subdivisionsList']);
Route::get('adminpanel/subdivisions/close/{id}', ['as' => 'adminpanel.subdivisions.close', 'before' => 'admin_only', 'uses' => 'AdminPanelController@closeSubdivision']);
Route::get('adminpanel/cashbook/{id}', ['as' => 'adminpanel.cashbook', 'before' => 'admin_only', 'uses' => 'AdminPanelController@getCashbook']);
Route::get('adminpanel/orders/remove/{id}', ['as' => 'adminpanel.orders.remove', 'before' => 'admin_only', 'uses' => 'AdminPanelController@removeOrder']);
Route::get('adminpanel/repaymenttypes', ['as' => 'adminpanel.repaymenttypes', 'before' => 'admin_only', 'uses' => 'RepaymentsEditorController@index']);
Route::get('adminpanel/repaymenttypes/create', ['as' => 'adminpanel.repaymenttypes.create', 'before' => 'admin_only', 'uses' => 'RepaymentsEditorController@editor']);
Route::get('adminpanel/repaymenttypes/edit/{id}', ['as' => 'adminpanel.repaymenttypes.edit', 'before' => 'admin_only', 'uses' => 'RepaymentsEditorController@editor']);
Route::post('adminpanel/repaymenttypes/update', ['as' => 'adminpanel.repaymenttypes.update', 'before' => 'admin_only', 'uses' => 'RepaymentsEditorController@update']);
Route::get('adminpanel/repaymenttypes/remove/{id}', ['as' => 'adminpanel.repaymenttypes.remove', 'before' => 'admin_only', 'uses' => 'RepaymentsEditorController@remove']);
Route::post('subdivisions/list/1c', ['as' => 'subdivisions.list.1c', 'before' => 'admin_only', 'uses' => 'SubdivisionController@getSubdivisionsFrom1c']);
Route::get('adminpanel/subdivisions/create', ['as' => 'adminpanel.subdivisions.create', 'before' => 'admin_only', 'uses' => 'SubdivisionController@editSubdivision']);
Route::get('adminpanel/subdivisions/edit/{id}', ['as' => 'adminpanel.subdivisions.edit', 'before' => 'admin_only', 'uses' => 'SubdivisionController@editSubdivision']);
Route::post('adminpanel/subdivisions/update', ['as' => 'adminpanel.subdivisions.update', 'before' => 'admin_only', 'uses' => 'SubdivisionController@updateSubdivision']);
Route::get('adminpanel/subdivisions/cities', ['as' => 'adminpanel.subdivisions.cities', 'before' => 'admin_only', 'uses' => 'SubdivisionController@updateCities']);
Route::get('adminpanel/terminals', ['as' => 'adminpanel.terminals', 'before' => 'admin_only', 'uses' => 'TerminalAdminController@index']);
Route::get('adminpanel/npffonds', ['as' => 'adminpanel.npffonds', 'before' => 'admin_only', 'uses' => 'AdminPanelController@getNpfFondsList']);
Route::get('adminpanel/npffonds/create', ['as' => 'adminpanel.npffonds.create', 'before' => 'admin_only', 'uses' => 'AdminPanelController@editNpfFond']);
Route::post('adminpanel/npffonds/update', ['as' => 'adminpanel.npffonds.update', 'before' => 'admin_only', 'uses' => 'AdminPanelController@updateNpfFond']);
Route::get('adminpanel/npffonds/edit', ['as' => 'adminpanel.npffonds.edit', 'before' => 'admin_only', 'uses' => 'AdminPanelController@editNpfFond']);
Route::get('adminpanel/npffonds/remove', ['as' => 'adminpanel.npffonds.remove', 'before' => 'admin_only', 'uses' => 'AdminPanelController@removeNpfFond']);
Route::get('adminpanel/problemsolver', ['as' => 'adminpanel.problemsolver', 'before' => 'admin_only', 'uses' => 'ProblemSolverController@index']);
Route::post('adminpanel/problemsolver/loan/remove2', ['as' => 'adminpanel.problemsolver.loan.remove2', 'before' => 'admin_only', 'uses' => 'ProblemSolverController@removeLoan']);
Route::post('adminpanel/problemsolver/card/remove', ['as' => 'adminpanel.problemsolver.card.remove', 'before' => 'admin_only', 'uses' => 'ProblemSolverController@removeCard']);
Route::post('adminpanel/problemsolver/repayment/remove', ['as' => 'adminpanel.problemsolver.repayment.remove', 'before' => 'admin_only', 'uses' => 'ProblemSolverController@removeRepayment']);
Route::post('adminpanel/problemsolver/order/remove', ['as' => 'adminpanel.problemsolver.order.remove', 'before' => 'admin_only', 'uses' => 'ProblemSolverController@removeOrder']);
Route::post('adminpanel/problemsolver/getloan', ['as' => 'adminpanel.problemsolver.loan.get', 'before' => 'admin_only', 'uses' => 'ProblemSolverController@getLoanByNumber']);
Route::post('adminpanel/problemsolver/fakeloan', ['as' => 'adminpanel.problemsolver.loan.fake', 'before' => 'admin_only', 'uses' => 'ProblemSolverController@createFakeClaimAndLoan']);
Route::post('adminpanel/problemsolver/promocode/info', ['as' => 'adminpanel.problemsolver.promocode.info', 'before' => 'admin_only', 'uses' => 'ProblemSolverController@getPromocodeInfo']);
Route::post('adminpanel/problemsolver/photos/changefolder', ['as' => 'adminpanel.problemsolver.photos.changefolder', 'before' => 'admin_only', 'uses' => 'ProblemSolverController@changePhotoFolder']);
Route::post('adminpanel/problemsolver/phone/change', ['before' => 'admin_only', 'uses' => 'ProblemSolverController@changePhone']);
Route::post('adminpanel/problemsolver/repayment/change', ['before' => 'admin_only', 'uses' => 'ProblemSolverController@changeRepaymentUser']);
Route::post('adminpanel/problemsolver/claim/remove', ['before' => 'admin_only', 'uses' => 'ProblemSolverController@removeClaim']);
Route::post('adminpanel/problemsolver/asp/remove', ['before' => 'admin_only', 'uses' => 'ProblemSolverController@removeAsp']);
Route::post('adminpanel/problemsolver/asp/add', ['before' => 'admin_only', 'uses' => 'ProblemSolverController@addAspSign']);
Route::post('adminpanel/problemsolver/asp/add1c', ['before' => 'admin_only', 'uses' => 'ProblemSolverController@addAspTo1c']);
Route::post('adminpanel/problemsolver/pdagreement/remove', ['before' => 'admin_only', 'uses' => 'ProblemSolverController@removePdAgreement']);
Route::post('adminpanel/problemsolver/solve', ['before' => 'admin_only', 'uses' => 'ProblemSolverController@solveProblem']);

Route::get('adminpanel/dataloader', ['before' => 'admin_only', 'uses' => 'DataLoaderController@index']);
Route::get('adminpanel/dataloader/customers', ['before' => 'admin_only', 'uses' => 'DataLoaderController@loadCustomers']);

Route::get('adminpanel/mailloader', ['before' => 'admin_only', 'uses' => 'MailLoaderController@index']);
Route::post('adminpanel/mailloader/load', ['before' => 'admin_only', 'uses' => 'MailLoaderController@loadMail']);
Route::get('adminpanel/mailloader/xml', ['before' => 'admin_only', 'uses' => 'MailLoaderController@saveBigXML']);
Route::get('adminpanel/mailloader/loadxml', ['before' => 'admin_only', 'uses' => 'MailLoaderController@loadMailXML']);

Route::get('adminpanel/massivechange', ['before' => 'admin_only', 'uses' => 'MassiveChangeController@index']);
Route::post('adminpanel/massivechange/execute', ['before' => 'admin_only', 'uses' => 'MassiveChangeController@executeChange']);

Route::get('adminpanel/tester', ['before' => 'admin_only', 'uses' => 'TesterController@index']);
Route::get('adminpanel/wannabethegirl', ['uses' => 'TesterController@changeAdminRole']);
Route::post('ajax/adminpanel/tester/exchangearm', ['before' => 'admin_only', 'uses' => 'TesterController@testExchangeArm']);
Route::post('ajax/adminpanel/tester/button', ['before' => 'admin_only', 'uses' => 'TesterController@testButton']);
Route::get('adminpanel/tester/loadtest', ['before' => 'admin_only', 'uses' => 'TesterController@soapLoadTesting']);
Route::post('ajax/adminpanel/tester/soaptest', ['before' => 'admin_only', 'uses' => 'TesterController@ajaxSendSoapRequest']);
Route::post('ajax/adminpanel/tester/loadparamscsv', ['before' => 'admin_only', 'uses' => 'TesterController@ajaxLoadTestParamsCsv']);

Route::get('adminpanel/config', ['before' => 'admin_only', 'uses' => 'ConfigController@index']);
Route::post('adminpanel/smser/update', ['before' => 'admin_only', 'uses' => 'ConfigController@smserUpdate']);
Route::post('adminpanel/smser/check', ['before' => 'admin_only', 'uses' => 'ConfigController@smserCheck']);
Route::post('adminpanel/printserver/update', ['before' => 'admin_only', 'uses' => 'ConfigController@printServerUpdate']);
Route::get('adminpanel/config/mysql/kill/{param}', ['before' => 'admin_only', 'uses' => 'ConfigController@killAllMysql']);
Route::post('adminpanel/config/mysql/threads', ['before' => 'admin_only', 'uses' => 'ConfigController@getMysqlThreadsCount']);
Route::post('adminpanel/config/mode/update', ['before' => 'admin_only', 'uses' => 'ConfigController@modeUpdate']);
Route::post('adminpanel/config/1c/update', ['before' => 'admin_only', 'uses' => 'ConfigController@server1cUpdate']);
Route::post('adminpanel/config/orders/sync', ['before' => 'admin_only', 'uses' => 'ConfigController@syncOrders']);
Route::get('adminpanel/config/mysql/threads/chart', ['before' => 'admin_only', 'uses' => 'ConfigController@mysqlThreadsChart']);
Route::post('adminpanel/config/mysql/threads/data', ['before' => 'admin_only', 'uses' => 'ConfigController@getMysqlThreadsData']);
Route::post('adminpanel/config/debtors/upload', ['before' => 'admin_only', 'uses' => 'ConfigController@uploadEmptyDebtorsToArm']);
Route::post('adminpanel/config/options/update', ['before' => 'admin_only', 'uses' => 'ConfigController@updateOption']);
Route::post('adminpanel/config/action', ['before' => 'admin_only', 'uses' => 'ConfigController@makeAction']);

Route::get('adminpanel/roles/index', ['before' => 'admin_only', 'uses' => 'RolesController@index']);
Route::get('adminpanel/roles/create', ['before' => 'admin_only', 'uses' => 'RolesController@create']);
Route::get('adminpanel/roles/edit/{id}', ['before' => 'admin_only', 'uses' => 'RolesController@edit']);
Route::post('adminpanel/roles/update', ['before' => 'admin_only', 'uses' => 'RolesController@update']);
Route::post('adminpanel/roles/grant', ['before' => 'admin_only', 'uses' => 'RolesController@grant']);
Route::post('adminpanel/permissions/grant', ['before' => 'admin_only', 'uses' => 'PermissionsController@grantToUser']);

Route::post('adminpanel/permissions/ajax/get/{id}', ['before' => 'admin_only', 'uses' => 'PermissionsController@getPermission']);
Route::post('adminpanel/permissions/update', ['before' => 'admin_only', 'uses' => 'PermissionsController@update']);
Route::get('adminpanel/permissions/destroy/{id}', ['before' => 'admin_only', 'uses' => 'PermissionsController@destroy']);

//запросы из 1с
Route::post('1c/users/add', ['as' => '1c.user.add', 'before' => 'localCallOnly', 'uses' => 'From1cController@addUserFrom1c']);
Route::post('1c/claims/update', ['as' => '1c.claims.update', 'before' => 'localCallOnly', 'uses' => 'From1cController@updateClaim']);
Route::post('1c/claims/update/status', ['before' => 'localCallOnly', 'uses' => 'From1cController@updateClaimStatus']);
Route::post('1c/seb', ['as' => '1c.seb', 'before' => 'localCallOnly', 'uses' => 'From1cController@addSEBNumber']);
Route::get('1c/docs/get', ['uses' => 'From1cController@getOrdersAndDocs']);
Route::post('1c/docs/get', ['uses' => 'From1cController@getOrdersAndDocs']);
Route::post('loans/promocode/get', ['uses' => 'From1cController@getPromocode']);
Route::get('loans/promocode/get', ['uses' => 'From1cController@getPromocode']);
Route::post('1c/debtors/upload', ['uses' => 'From1cController@uploadSqlFile']);
Route::post('1c/debtors/info/upload', ['uses' => 'From1cController@uploadCsvFile']);
Route::post('1c/debtors/upload/updateClientInfo', ['uses' => 'From1cController@updateClientInfo']);
Route::post('debtors/customers/upload', ['uses' => 'From1cController@uploadDebtorsWithEmptyCustomers']);
Route::post('1c/debtors/events/update_id_1c', ['uses' => 'From1cController@setId1cForEvents']);
Route::post('1c/debtors/changeRespUser', ['uses' => 'From1cController@changeResponsibleUserInDebtor']);
Route::post('1c/sms/noloan', ['uses' => 'From1cController@getSmsWithNoLoan']);
Route::post('1c/orders/update', ['uses' => 'From1cController@updateOrder']);
Route::post('1c/asp/approve', ['uses' => 'From1cController@updateAsp']);
Route::post('1c/asp/read', ['uses' => 'From1cController@readAsp']);

Route::post('1c/debtors/addRecordToUploadSqlFilesType1', ['uses' => 'From1cController@addRecordToUploadSqlFilesType1']);
Route::post('1c/debtors/addRecordToUploadSqlFilesType2', ['uses' => 'From1cController@addRecordToUploadSqlFilesType2']);

Route::post('1c/debtors/upload/clearOtherPhones', ['uses' => 'From1cController@clearOtherPhones']);

//бланки заявлений
Route::get('blanks', ['as' => 'blanks', 'uses' => 'BlanksController@blanksList']);
Route::get('blanks/customers', ['as' => 'blanks.customers', 'uses' => 'BlanksController@customersBlanksList']);
Route::get('blanks/users', ['as' => 'blanks.users', 'uses' => 'BlanksController@usersBlanksList']);
Route::get('blanks/pdf/{id}', ['as' => 'blanks.pdf', 'uses' => 'BlanksController@createPdf']);

//ордеры
Route::get('orders', ['as' => 'orders.list', 'uses' => 'OrdersController@getOrders']);
Route::post('orders/store', ['as' => 'orders.store', 'uses' => 'OrdersController@storeOrder']);
Route::post('orders/update', ['as' => 'orders.update', 'uses' => 'OrdersController@updateOrder']);
Route::get('orders/{id}', ['as' => 'orders.list', 'uses' => 'OrdersController@getOrders']);
Route::get('ajax/orders/order/{id}', ['as' => 'ajax.orders.order', 'uses' => 'OrdersController@getOrder']);
Route::get('ajax/orders/list', ['as' => 'ajax.orders.list', 'uses' => 'OrdersController@getOrdersList']);
Route::get('orders/remove/{id}', ['as' => 'orders.remove', 'before' => 'admin_only', 'uses' => 'OrdersController@removeOrder']);
Route::get('orders/pdf/{id}', ['as' => 'orders.pdf', 'uses' => 'OrdersController@createPDF']);
Route::get('orders/claimforremove/{id}', ['as' => 'orders.claimforremove', 'uses' => 'OrdersController@claimForRemove']);
Route::post('ajax/orders/claimforremove/{id}', ['as' => 'ajax.orders.claimforremove', 'uses' => 'OrdersController@claimForRemove']);

//контрагенты
Route::get('ajax/customers/find', ['as' => 'ajax.customers.find', 'uses' => 'CustomersController@findCustomers']);
Route::get('customers', ['as' => 'customers', 'uses' => 'CustomersController@getListView']);
Route::get('ajax/customers/list', ['as' => 'ajax.customers.list', 'uses' => 'CustomersController@getList']);
Route::get('customers/remove/{id}', ['as' => 'customers.remove', 'uses' => 'CustomersController@removeCustomer']);
Route::get('customers/remove2/{id}', ['as' => 'customers.remove', 'before' => 'admin_only', 'uses' => 'CustomersController@removeCustomer2']);
Route::get('customers/create', ['as' => 'customers.create', 'uses' => 'CustomersController@createCustomer']);
Route::post('customers/update', ['as' => 'customers.update', 'uses' => 'CustomersController@update']);
Route::get('customers/edit/{customer_id}/{passport_id}', ['as' => 'customers.edit', 'uses' => 'CustomersController@editCustomer']);

//карты
Route::get('customers/cards/{customer_id}', ['as' => 'customers.cards', 'uses' => 'CardsController@customerCards']);
Route::post('cards/disable/{card_id}', ['as' => 'cards.disable', 'before' => 'admin_only', 'uses' => 'CardsController@disableCard']);
Route::post('cards/enable/{card_id}', ['as' => 'cards.enable', 'before' => 'admin_only', 'uses' => 'CardsController@enableCard']);
Route::post('ajax/cards/add', ['as' => 'ajax.cards.add', 'before' => 'admin_only', 'uses' => 'CardsController@addCardByAjax']);
Route::post('cards/add', ['uses' => 'CardsController@addCard']);
Route::post('ajax/cards/check', ['uses' => 'CardsController@checkCard']);

//замена карт
Route::get('cardchanges', ['as' => 'cardchanges', 'uses' => 'CardsController@cardChangesList']);
Route::get('ajax/cardChanges/list', ['as' => 'ajax.cardchanges.list', 'uses' => 'CardsController@getCardChangesList']);
Route::post('cardchanges/add', ['as' => 'cardchanges.add', 'uses' => 'CardsController@addCardChange']);
Route::get('cardchanges/remove/{id}', ['as' => 'cardchanges.remove', 'before' => 'admin_only', 'uses' => 'CardsController@removeCardChange']);
Route::get('cardchanges/claimforremove/{id}', ['as' => 'cardchanges.claimforremove', 'uses' => 'CardsController@claimForRemoveCardChange']);

//гашение
Route::post('repayments/create', ['as' => 'repayments.create', 'uses' => 'RepaymentController@create']);
Route::post('repayments/update', ['as' => 'repayments.update', 'before' => 'admin_only', 'uses' => 'RepaymentController@update']);
Route::get('ajax/repayments/get/{id}', ['as' => 'ajax.repayments.get', 'uses' => 'RepaymentController@getRepayment']);
Route::get('repayments/remove/{id}', ['as' => 'repayments.remove', 'before' => 'admin_only', 'uses' => 'RepaymentController@remove']);
Route::get('ajax/peacepays/get/{id}', ['as' => 'ajax.peacepays.get', 'uses' => 'PeacePaysController@getPeacePay']);
Route::post('peacepays/update', ['as' => 'peacepays.update', 'before' => 'admin_only', 'uses' => 'PeacePaysController@update']);
Route::post('repayments/payment', ['as' => 'repayments.payment', 'uses' => 'RepaymentController@addPayment']);
Route::post('repayments/claimforremove', ['as' => 'repayments.claimforremove', 'uses' => 'RepaymentController@claimForRemove']);
Route::post('repayments/suzschedule/add', ['uses' => 'RepaymentController@addSuzSchedule']);
Route::post('repayments/dopcommission/calculate', ['uses' => 'RepaymentController@calculateDopCommissionMoney']);

//помощь
Route::get('help', ['as' => 'help', 'uses' => 'HelpController@menu']);
Route::get('help/cert', ['as' => 'help.cert', 'uses' => 'HelpController@cert']);
Route::get('help/docs', ['as' => 'help.docs', 'uses' => 'HelpController@docs']);
Route::get('help/rules', ['as' => 'help.rules', 'uses' => 'HelpController@rules']);
Route::get('help/addresses', ['uses' => 'HelpController@addresses']);
Route::get('help/videos', ['uses' => 'HelpController@videos']);
Route::get('help/videos/{id}', ['uses' => 'HelpController@videos']);
Route::get('help/page/{page}', ['uses' => 'HelpController@page']);
Route::get('help/instructions', ['uses' => 'HelpController@instructions']);

//запросы
Route::get('usersreqs/remove', ['as' => 'usersreqs.remove', 'before' => 'office_only', 'uses' => 'UsersRequestsController@remove']);
Route::get('usersreqs/remove/request', ['as' => 'usersreqs.remove.request', 'before' => 'office_only', 'uses' => 'UsersRequestsController@removeRequest']);
Route::get('ajax/removeRequests/list', ['as' => 'usersreqs.remove.list', 'before' => 'office_only', 'uses' => 'UsersRequestsController@removeRequestsList']);
Route::post('ajax/removeRequests/claim', ['as' => 'ajax.removeRequests.claim', 'uses' => 'UsersRequestsController@claimForRemove']);
Route::get('usersreqs/hide/{id}', ['as' => 'usersreqs.hide', 'before' => 'admin_only', 'uses' => 'UsersRequestsController@hideRequest']);
Route::get('ajax/usersreqs/removeRequests/info/{id}', ['as' => 'usersreqs.removeRequests.info', 'before' => 'office_only', 'uses' => 'UsersRequestsController@getRemoveRequestInfo']);

//НПФ
Route::get('ajax/npf/list', ['as' => 'npf.list', 'uses' => 'NpfController@getList']);
Route::get('npf', ['as' => 'npf.table', 'uses' => 'NpfController@getTableView']);
Route::get('npf/create', ['as' => 'npf.create', 'uses' => 'NpfController@editItem']);
Route::get('npf/edit', ['as' => 'npf.edit', 'uses' => 'NpfController@editItem']);
Route::get('npf/remove', ['as' => 'npf.remove', 'uses' => 'NpfController@removeItem']);
Route::get('npf/claimforremove', ['as' => 'npf.claimforremove', 'uses' => 'NpfController@claimForRemoveItem']);
Route::post('npf/update', ['as' => 'npf.create', 'uses' => 'NpfController@updateItem']);

//Заявления на материалы
Route::get('ajax/matclaims/list', ['as' => 'matclaims.list', 'uses' => 'MaterialsClaimsController@getList']);
Route::get('matclaims', ['as' => 'matclaims.table', 'uses' => 'MaterialsClaimsController@getTableView']);
Route::get('matclaims/create', ['as' => 'matclaims.create', 'uses' => 'MaterialsClaimsController@editItem']);
Route::get('matclaims/edit', ['as' => 'matclaims.edit', 'uses' => 'MaterialsClaimsController@editItem']);
Route::get('matclaims/remove', ['as' => 'matclaims.remove', 'uses' => 'MaterialsClaimsController@removeItem']);
Route::get('matclaims/claimforremove', ['as' => 'matclaims.claimforremove', 'uses' => 'MaterialsClaimsController@claimForRemoveItem']);
Route::post('matclaims/update', ['as' => 'matclaims.create', 'uses' => 'MaterialsClaimsController@updateItem']);

//договора
Route::get('ajax/repayments/list', ['as' => 'repayments.list', 'uses' => 'RepaymentViewerController@getList']);
Route::get('repayments', ['as' => 'repayments.table', 'uses' => 'RepaymentViewerController@getTableView']);
//Route::get('repayments/create', ['as' => 'matclaims.create', 'uses' => 'MaterialsClaimsController@editItem']);
//Route::get('repayments/edit', ['as' => 'matclaims.edit', 'uses' => 'MaterialsClaimsController@editItem']);
//Route::get('repayments/remove', ['as' => 'matclaims.remove', 'uses' => 'RepaymentViewerController@removeItem']);
//Route::get('repayments/claimforremove', ['as' => 'matclaims.claimforremove', 'uses' => 'MaterialsClaimsController@claimForRemoveItem']);
//Route::post('repayments/update', ['as' => 'matclaims.create', 'uses' => 'MaterialsClaimsController@updateItem']);
//учет рабочего времени
//Route::get('worktime/get', ['as' => 'wor.claimforremove', 'uses' => 'MaterialsClaimsController@claimForRemoveItem']);
Route::get('work_times/index', ['before'=>'admin_only', 'uses' => 'WorkTimeController@getTableView']);
Route::get('ajax/work_times/list', ['before'=>'admin_only', 'uses' => 'WorkTimeController@getList']);
Route::post('worktime/update2', ['before'=>'admin_only', 'uses' => 'WorkTimeController@update']);
Route::get('worktime/edit', ['before'=>'admin_only', 'uses' => 'WorkTimeController@editItem']);

Route::post('worktime/update', ['as' => 'worktime.update', 'uses' => 'WorkTimeController@updateItem']);
Route::get('worktime/today/get', ['as' => 'worktime.today.get', 'uses' => 'WorkTimeController@getTodayItem']);

//сообщения
Route::get('ajax/messages/list', ['as' => 'messages.list', 'uses' => 'MessagesController@getList']);
Route::get('messages', ['as' => 'messages.table', 'uses' => 'MessagesController@getTableView']);
Route::get('messages/create', ['as' => 'messages.create', 'uses' => 'MessagesController@editItem']);
Route::get('messages/edit', ['as' => 'messages.edit', 'uses' => 'MessagesController@editItem']);
Route::get('messages/remove', ['as' => 'messages.remove', 'uses' => 'MessagesController@removeItem']);
Route::post('messages/update', ['as' => 'messages.create', 'uses' => 'MessagesController@updateItem']);

//терминал
Route::get('terminal', ['as' => 'terminal', 'uses' => 'TerminalController@index']);
Route::post('terminal/preauth', ['as' => 'terminal.preauth', 'uses' => 'TerminalController@preauth']);
Route::post('terminal/status', ['as' => 'terminal.status', 'uses' => 'TerminalController@status']);
Route::post('terminal/auth', ['as' => 'terminal.auth', 'uses' => 'TerminalController@auth']);
Route::post('terminal/pinauth', ['as' => 'terminal.pinauth', 'uses' => 'TerminalController@pinauth']);
Route::post('terminal/tpitems', ['as' => 'terminal.tpitems', 'uses' => 'TerminalController@TPitems']);
Route::post('terminal/order', ['as' => 'terminal.order', 'uses' => 'TerminalController@order']);
Route::post('terminal/file', ['as' => 'terminal.file', 'uses' => 'TerminalController@file']);
Route::get('terminal/file', ['as' => 'terminal.file1', 'uses' => 'TerminalController@file']);
Route::post('terminal/fileinfo', ['as' => 'terminal.fileinfo', 'uses' => 'TerminalController@fileinfo']);
Route::get('terminal/fileinfo', ['as' => 'terminal.fileinfo1', 'uses' => 'TerminalController@fileinfo']);
Route::post('terminal/paypoints', ['as' => 'terminal.paypoints', 'uses' => 'TerminalController@paypoints']);
Route::post('terminal/sendaction', ['as' => 'terminal.sendaction', 'uses' => 'TerminalController@sendaction']);
Route::post('terminal/scmd', ['as' => 'terminal.scmd', 'uses' => 'TerminalController@scmd']);
Route::post('terminal/promo', ['as' => 'terminal.promo', 'uses' => 'TerminalController@promo']);
//Route::get('qwe', ['as' => 'qwe', 'uses' => 'HomeController@qwe']);
//терминалы в админпанели
Route::get('ajax/terminals/list', ['as' => 'terminals.list', 'before' => 'admin_only', 'uses' => 'TerminalAdminController@getTerminalsList']);
Route::get('ajax/terminals/refreshstatus', ['as' => 'terminals.refreshstatus', 'uses' => 'TerminalAdminController@refreshStatus']);
Route::post('terminals/addcash', ['as' => 'terminals.addcash', 'before' => 'admin_only', 'uses' => 'TerminalAdminController@addCash']);
Route::post('terminals/incass', ['as' => 'terminals.incass', 'before' => 'admin_only', 'uses' => 'TerminalAdminController@incass']);
Route::post('terminals/changelockstatus', ['as' => 'terminals.changelockstatus', 'before' => 'admin_only', 'uses' => 'TerminalAdminController@changeLockStatus']);
Route::get('adminpanel/terminals/create', ['as' => 'adminpanel.terminals.create', 'before' => 'admin_only', 'uses' => 'TerminalAdminController@editor']);
Route::get('adminpanel/terminals/edit', ['as' => 'adminpanel.terminals.edit', 'before' => 'admin_only', 'uses' => 'TerminalAdminController@editor']);
Route::post('adminpanel/terminals/update', ['as' => 'adminpanel.terminals.update', 'before' => 'admin_only', 'uses' => 'TerminalAdminController@update']);
Route::get('adminpanel/terminals/remove', ['as' => 'adminpanel.terminals.remove', 'before' => 'admin_only', 'uses' => 'TerminalAdminController@remove']);
Route::post('adminpanel/terminals/command/add', ['as' => 'adminpanel.terminals.command.add', 'before' => 'admin_only', 'uses' => 'TerminalAdminController@addCommand']);
Route::get('adminpanel/terminals/report', ['before' => 'admin_only', 'uses' => 'TerminalAdminController@getReport']);

//отчеты по терминалам для отвечающих за них специалистов
/*Route::get('reports/terminals/specreport', ['uses' => 'TerminalOrdersController@index']);
Route::get('reports/terminals/actions', ['uses' => 'TerminalOrdersController@getActionsList']);
Route::get('ajax/terminals/orders/list', ['uses' => 'TerminalOrdersController@getOrdersList']);*/

//данные о задолженности
Route::get('debt', ['as' => 'debt', 'uses' => 'DebtController@getDebtByPhone']);

//запросы с сайта Финтерра.рф
Route::get('fincustomer', ['uses' => 'FinterraController@getCustomerIdForFinterra']);
Route::get('finloaninfo', ['uses' => 'FinterraController@getLoanInfo']);
Route::get('finterra/claim/create', ['uses' => 'FinterraController@createClaimForCustomer']);
Route::get('finterra/loan/create', ['uses' => 'FinterraController@createLoanForCustomer']);
Route::get('finterra/asp/secret', ['uses' => 'FinterraController@aspSecret']);
Route::get('finterra/asp/approve', ['uses' => 'FinterraController@aspApprove']);
Route::get('finterra/asp/data', ['uses' => 'FinterraController@aspData']);

Route::get('test/claim', ['uses' => 'TestController@testClaimPage']);
Route::post('test/claim/create', ['uses' => 'TestController@createTestClaim']);
//Route::post('test', ['uses' => 'TestController@test']);

//исполнитель каких-то команд О_о
Route::post('command/execute', ['uses' => 'CommandsController@executeCommand', 'before' => 'admin_only']);
Route::get('command/execute', ['uses' => 'CommandsController@executeCommand', 'before' => 'admin_only']);

//ТЕЛЕПОРТ
//Route::post('teleport/claim/create', ['uses' => 'TeleportController@receiveClaimAndSendTo1c']);
//Route::get('teleport/claim/create', ['uses' => 'TeleportController@receiveClaimAndSendTo1c']);
//Route::get('teleport/status/send/{id}', ['uses' => 'TeleportController@sendStatus']);
//Route::post('teleport/statustest', ['uses' => 'TeleportController@statusTest']);
//Route::get('teleport/statustest', ['uses' => 'TeleportController@statusTest']);
//ОПРОС
Route::get('quizdept/create', ['uses' => 'QuizDepartmentController@create']);
Route::post('quizdept/store', ['uses' => 'QuizDepartmentController@store']);
Route::get('quizdept/report', ['before' => 'admin_only', 'uses' => 'QuizDepartmentController@getReport']);

//Кандидаты
Route::get('candidate/index', ['uses' => 'CandidateController@index']);
Route::get('candidate/create', ['uses' => 'CandidateController@create']);
Route::post('candidate/insert', ['uses' => 'CandidateController@insertCandidate']);
Route::get('candidate/delete', ['uses' => 'CandidateController@deleteCandidate']);
Route::get('candidate/update', ['uses' => 'CandidateController@update']);
Route::post('candidate/update', ['uses' => 'CandidateController@updateCandidate']);
Route::get('candidate/excel', ['uses' => 'CandidateController@exportToExcel']);
Route::post('candidate/excel/report', ['as'=>'candidate.excel.report', 'uses' => 'CandidateController@exportToExcelReport']);
Route::post('candidate/excel/report/city', ['as'=>'candidate.excel.report.city', 'uses' => 'CandidateController@exportToExcelReportCity']);

//ГРАФИК ПРОДАЖ
Route::get('graph/index', ['uses' => 'GraphController@index']);
Route::post('graph/index/graphdata', ['uses' => 'GraphController@getGraphData']);
//Route::get('ajax/graph/calendar', ['uses' => 'GraphController@calendar']);
//Route::post('ajax/graph/calendar', ['uses' => 'GraphController@calendar']);

//ДОЛЖНИКИ
Route::get('debtors/index', ['uses' => 'DebtorsController@index']);
Route::get('debtors/forgotten', ['uses' => 'DebtorsController@forgotten']);
Route::post('debtors/forgotten', ['uses' => 'DebtorsController@forgotten']);
Route::get('debtors/recommends', ['uses' => 'DebtorsController@recommends']);
Route::post('debtors/recommends', ['uses' => 'DebtorsController@recommends']);
Route::get('debtors/calendar', ['uses' => 'DebtorsController@calendar']);
Route::post('debtors/calendar', ['uses' => 'DebtorsController@calendar']);
Route::get('debtors/editSmsCount', ['uses' => 'DebtorsController@editSmsCount']);
Route::post('debtors/editSmsCount', ['uses' => 'DebtorsController@editSmsCount']);
Route::get('debtors/debtorcard/{debtor_id}', ['uses' => 'DebtorsController@debtorcard']);
Route::get('debtors/addevent', ['uses' => 'DebtorsController@addevent']);
Route::post('debtors/addevent', ['uses' => 'DebtorsController@addevent']);
Route::post('ajax/debtors/eventcomplete', ['as' => 'ajax.debtors.eventcomplete', 'uses' => 'DebtorsController@eventComplete']);
Route::post('ajax/debtors/sendsmstodebtor', ['as' => 'ajax.debtors.sendsmstodebtor', 'uses' => 'DebtorsController@sendSmsToDebtor']);
Route::get('ajax/debtors/sendsmstodebtor', ['as' => 'ajax.debtors.sendsmstodebtor', 'uses' => 'DebtorsController@sendSmsToDebtor']);
Route::post('ajax/debtors/loadphoto/{claim_id}', ['uses' => 'DebtorsController@loadPhoto']);
Route::get('ajax/debtors/list', ['as' => 'ajax.debtors.list', 'uses' => 'DebtorsController@ajaxList']);
Route::get('ajax/debtorevents/list', ['as' => 'ajax.debtorevents.list', 'uses' => 'DebtorsController@ajaxEventsList']);
Route::get('ajax/debtorforgotten/list', ['as' => 'ajax.debtorforgotten.list', 'uses' => 'DebtorsController@ajaxForgottenList']);
Route::get('ajax/debtorrecommends/list', ['as' => 'ajax.debtorrecommends.list', 'uses' => 'DebtorsController@ajaxRecommendsList']);
Route::get('ajax/debtors/event/data', ['uses' => 'DebtorsController@getDebtorEventData']);
Route::post('debtors/event/update', ['uses' => 'DebtorsController@updateDebtorEvent']);
Route::get('ajax/debtors/search/autocomplete', ['uses' => 'DebtorsController@ajaxColumnAutocomplete']);
Route::get('debtors/history/{debtor_id}', ['uses' => 'DebtorsController@debtorHistory']);
Route::get('debtors/debtorcard/createPdf/{doc_id}/{debtor_id}/{date}/{fact_address}', ['uses' => 'DebtorsController@createPdf']);
Route::get('debtors/debtorcard/createPdf/{doc_id}/{debtor_id}/{date}', ['uses' => 'DebtorsController@createPdf']);
Route::get('debtor/courtorder/{debtor_id}','PdfController@getCourtOrderPdf')->name('debtor.courtorder');
Route::get('debtors/contacts/{debtor_id}', ['uses' => 'DebtorsController@contacts']);
Route::get('debtors/logs/{debtor_id}', ['uses' => 'DebtorsController@debtorLogs']);
Route::get('ajax/debtors/userpayments', ['uses' => 'DebtorsReportsController@getPaymentsForUser']);
Route::post('ajax/debtors/changePlanDeparture/{debtor_id}/{action}', ['uses' => 'DebtorsController@changePlanDeparture']);
Route::post('ajax/debtors/changePersonalData/{debtor_id}/{action}', ['uses' => 'DebtorsController@changePersonalData']);
Route::post('ajax/debtors/changeRecommend', ['uses' => 'DebtorsController@changeRecommend']);
Route::get('debtors/departuremap', ['uses' => 'DebtorsController@departureMap']);
Route::get('debtors/departureprint', ['uses' => 'DebtorsController@departurePrint']);
Route::get('addressdoubles/index', ['uses' => 'AddressDoublesController@index']);
Route::get('ajax/addressdoubles/list', ['uses' => 'AddressDoublesController@ajaxList']);
Route::post('ajax/debtors/oldevents/upload', ['uses' => 'DebtorsController@uploadOldDebtorEvents']);
Route::post('ajax/debtors/orders/upload', ['uses' => 'DebtorsController@uploadOrdersFrom1c']);
Route::get('debtors/report/countcustomers', ['uses' => 'DebtorsReportsController@countDebtCustomersForRespUser']);
Route::post('debtors/peaceclaim/new', ['uses' => 'DebtorsController@addNewRepaymentOffer']);
Route::get('/ajax/debtors/total-planned',['uses'=>'DebtorsController@totalNumberPlaned']);
Route::post('ajax/debtors/calc/creditcard', ['uses' => 'DebtorsController@getCalcDataForCreditCard']);

Route::get('/debtors/export', 'DebtorExportController@exportInExcelDebtors');
Route::get('/debtors/export/events','DebtorExportController@exportEvents');
Route::get('/debtors/export/forgotten', 'DebtorExportController@exportForgotten');
Route::get('debtors/export/postregistry', ['uses' => 'DebtorsController@exportPostRegistry']);
Route::get('debtors/omicron/gettask', ['uses' => 'CronController@getOmicronTask']);

Route::get('debtors/courts/index', ['uses' => 'DebtorsNoticesController@courtNotices']);
Route::get('debtors/courts/start', ['uses' => 'DebtorsNoticesController@startCourtTask']);
Route::get('debtors/notices/getFile/{type}/{task_id}', ['uses' => 'DebtorsNoticesController@getFile']);
Route::get('debtors/courts/getFile/{type}/{task_id}', ['uses' => 'DebtorsNoticesController@getCourtFile']);

Route::get('debtors/notices/index', ['uses' => 'DebtorsNoticesController@index']);
Route::get('debtors/notices/start', ['uses' => 'DebtorsNoticesController@startTask']);
Route::get('debtors/notices/getFile/{type}/{task_id}', ['uses' => 'DebtorsNoticesController@getFile']);

Route::get('/debtor/recurrent/query', ['uses' => 'DebtorsController@sentRecurrentQuery']);
Route::get('/debtor/recurrent/massquery', ['uses' => 'DebtorsController@massRecurrentQuery']);
Route::post('/debtor/recurrent/massquery', ['uses' => 'DebtorsController@massRecurrentQuery']);
Route::get('/debtor/recurrent/massquerytask', ['uses' => 'DebtorsController@massRecurrentTask']);
Route::post('/debtor/recurrent/massquerytask', ['uses' => 'DebtorsController@massRecurrentTask']);
Route::post('/debtor/recurrent/getstatus', ['uses' => 'DebtorsController@getMassRecurrentStatus']);

Route::get('/debtor/sms/mass', ['uses' => 'DebtorMassSmsController@index']);
Route::get('ajax/debtormasssms/list', ['uses' => 'DebtorMassSmsController@ajaxList']);
Route::post('ajax/debtormasssms/send', ['uses' => 'DebtorMassSmsController@sendMassSms']);

Route::get('debtors/loans/summary/{loan_id}', ['uses' => 'DebtorsController@getLoanSummary']);
Route::get('ajax/debtors/changeloadstatus/{debtor_id}', ['uses' => 'DebtorsController@changeLoadStatus']);
Route::post('ajax/debtors/getallpayments/{debtor_id}', ['uses' => 'DebtorsController@getAllPayments']);
Route::post('debtors/getResponsibleUser', ['uses' => 'FromDebtorsController@respUserForSellingARM']);
Route::post('ajax/debtors/loans/upload', ['uses' => 'DebtorsController@uploadLoans']);
Route::post('ajax/debtors/loans/getmultisum', ['uses' => 'DebtorsController@getMultiSum']);
Route::post('/debtors/loans/summary/updateloan', ['uses' => 'DebtorsController@updateLoan']);

Route::post('debtors/photos/main', ['uses' => 'FromDebtorsController@setMainPhoto']);
Route::post('debtors/debt', ['uses' => 'FromDebtorsController@getDebt']);
Route::get('debtors/orders/upload', ['uses' => 'FromDebtorsController@uploadOrders']);
Route::get('debtors/loans/upload', ['uses' => 'FromDebtorsController@uploadLoans']);

Route::post('debtors/events/from1c', ['uses' => 'DebtorsFrom1cController@eventFrom1c']);
Route::post('debtors/loan/closing', ['uses' => 'DebtorsFrom1cController@loanClosing']);
Route::post('debtors/omicron/task', ['uses' => 'DebtorsFrom1cController@omicronTask']);

Route::post('ajax/debtors/totalEvents', ['uses' => 'DebtorsController@refreshTotalEventTable']);
Route::post('ajax/debtors/overallEvents', ['uses' => 'DebtorsController@refreshOverallTable']);

Route::get('debtors/msg/debtoronsubdivision', ['uses' => 'FromSellingARMController@alertDebtorOnSubdivision']);
Route::get('debtors/onsite', ['uses' => 'FromSellingARMController@isDebtorOnSite']);
Route::post('debtors/event/withoutAccept', ['uses' => 'FromSellingARMController@withoutAcceptEvent']);

Route::post('debtors/infinity/incomingCall', ['uses' => 'InfinityController@incomingCall']);
Route::post('debtors/infinity/closingModals', ['uses' => 'InfinityController@closingModals']);

Route::get('debtors/setSelfResponsible/{debtor_id}', ['uses' => 'DebtorsController@setSelfResponsible']);

Route::get('/debtors/temporary/cron/handle', ['uses' => 'DebtorsController@temporaryCronTasksHandling']);

//ДОЛЖНИКИ ОТЧЕТЫ
Route::get('debtors/reports/plancalend', ['uses' => 'DebtorsReportsController@planCalend']);
Route::get('debtors/reports/ovz', ['uses' => 'DebtorsReportsController@ovz']);
Route::get('debtors/reports/jobsdoneact', ['uses' => 'DebtorsReportsController@jobsDoneAct']);
Route::get('debtorsreports/dzcollect', ['uses' => 'DebtorsReportsController@dzcollect']);
Route::get('debtors/reports/loginlog', ['uses' => 'DebtorsReportsController@exportToExcelDebtorsLoginLog']);

//ДОЛЖНИКИ: ПЕРЕДАЧА ОТ ОТВЕТСТВЕННОМУ К ОТВЕТСТВЕННОМУ
Route::get('debtortransfer/index', ['uses' => 'DebtorTransferController@index']);
Route::get('debtortransfer/history', ['uses' => 'DebtorTransferController@transferHistory']);
Route::get('ajax/debtortransfer/list', ['uses' => 'DebtorTransferController@ajaxList']);
Route::post('ajax/debtortransfer/changeResponsibleUser', ['uses' => 'DebtorTransferController@changeResponsibleUser']);
Route::get('ajax/debtortransfer/printResponsibleUser', ['uses' => 'DebtorTransferController@getActPdf']);

//ФОРМЫ СМСОК ДЛЯ ВЗЫСКАНИЯ
Route::get('adminpanel/smsform/index', ['uses' => 'SmsFormController@index']);
Route::get('adminpanel/smsform/edit', ['uses' => 'SmsFormController@edit']);
Route::post('adminpanel/smsform/update', ['uses' => 'SmsFormController@update']);
Route::get('adminpanel/smsform/destroy', ['uses' => 'SmsFormController@destroy']);

//СТРАНИЦА ПЕЧАТИ ДОКУМЕНТОВ НА ТРУДОУСТРОЙСТВО
Route::get('employment/docs', ['uses' => 'EmploymentDocsController@index']);
Route::get('employment/tracknumber', ['uses' => 'EmploymentDocsController@addTrackNumber']);
Route::post('employment/tracknumber/update', ['uses' => 'EmploymentDocsController@updateTrackNumber']);
Route::post('employment/docs/signed', ['uses' => 'EmploymentDocsController@setEmploymentDocsSigned']);
Route::post('employment/user/update', ['uses' => 'EmploymentDocsController@setBirthDate']);
Route::get('employment/docs/pdf', ['uses' => 'EmploymentDocsController@createPdf']);
Route::get('employment/truddoprename', ['uses' => 'EmploymentDocsController@trudDopRename']);
Route::post('employment/truddoprename', ['uses' => 'EmploymentDocsController@trudDopRename']);
//СТРАНИЦА ОПРОСНИКА ПЕРЕД УВОЛЬНЕНИЕМ
Route::get('employment/fire/quiz', ['uses' => 'EmploymentDocsController@fireQuiz']);
Route::post('employment/fire/quiz/save', ['uses' => 'EmploymentDocsController@fireQuizSave']);
Route::get('employment/fire/quiz/report', ['uses' => 'EmploymentDocsController@fireQuizReport']);

//ТЕСТИРОВАНИЕ
Route::get('usertests/home', ['uses' => 'UserTestController@home']);
Route::get('usertests/index', ['uses' => 'UserTestController@index']);
Route::get('usertests/view/{id}', ['uses' => 'UserTestController@view']);
Route::get('usertests/stat/{id}', ['uses' => 'UserTestController@stat']);
Route::get('usertests/view/{test_id}/{question_id}', ['uses' => 'UserTestController@view']);
Route::post('usertests/answer', ['uses' => 'UserTestController@answer']);

//РЕДАКТОР ТЕСТОВ
Route::get('usertests/editor/index', ['uses' => 'UserTestEditorController@index']);
Route::get('usertests/editor/create', ['uses' => 'UserTestEditorController@create']);
Route::post('usertests/editor/update', ['uses' => 'UserTestEditorController@update']);
Route::get('usertests/editor/edit/{id}', ['uses' => 'UserTestEditorController@edit']);
Route::get('usertests/editor/remove/{id}', ['uses' => 'UserTestEditorController@remove']);

//ЗАЯВКИ НА ОРДЕРА НА ПОДОТЧЕТ
Route::get('ajax/orders/issueclaims/list', ['uses' => 'IssueClaimController@ajaxList']);
Route::get('ajax/orders/issueclaims/view/{id}', ['uses' => 'IssueClaimController@ajaxView']);
Route::post('ajax/orders/issueclaims/update', ['uses' => 'IssueClaimController@update']);
Route::get('orders/issueclaims/index', ['uses' => 'IssueClaimController@index']);
Route::get('orders/issueclaims/delete/{id}', ['uses' => 'IssueClaimController@delete', 'before' => 'admin_only']);
Route::get('orders/issueclaims/claimforremove/{id}', ['uses' => 'IssueClaimController@claimForRemove']);
Route::get('orders/issueclaims/pdf/{id}', ['uses' => 'IssueClaimController@createPdf']);

//АВАНСОВЫЕ ОТЧЕТЫ
Route::get('reports/advancereports/index', ['uses' => 'AdvanceReportController@index']);
Route::get('ajax/reports/advancereports/list', ['uses' => 'AdvanceReportController@ajaxList']);
Route::get('ajax/reports/advancereports/issueorders', ['uses' => 'AdvanceReportController@getIssueOrders']);
Route::get('reports/advancereports/create', ['uses' => 'AdvanceReportController@edit']);
Route::get('reports/advancereports/edit/{id}', ['uses' => 'AdvanceReportController@edit']);
Route::get('reports/advancereports/destroy/{id}', ['uses' => 'AdvanceReportController@destroy']);
Route::get('reports/advancereports/pdf/{id}', ['uses' => 'AdvanceReportController@pdf']);
Route::post('reports/advancereports/upload', ['uses' => 'AdvanceReportController@upload']);
Route::get('reports/advancereports/upload', ['uses' => 'AdvanceReportController@upload']);
Route::post('reports/advancereports/update', ['uses' => 'AdvanceReportController@update']);
Route::get('nomenclature/upload', ['uses' => 'NomenclatureController@upload', 'before' => 'admin_only']);

//ФОТО СПЕЦИАЛИСТОВ
Route::get('reports/userphotos/index', ['uses' => 'UserPhotoController@index']);
Route::get('ajax/userphotos/list', ['uses' => 'UserPhotoController@ajaxList']);
Route::get('ajax/userphotos/list2', ['uses' => 'UserPhotoController@ajaxBasicList']);

//ОТЧЕТЫ ПО РАБОТЕ ИТ
Route::get('reports/it/score',['uses'=>'ReportsController@getItScoreReport']);
//ОТЧЕТЫ ПО ТАБЕЛЯМ
Route::get('reports/worktimes',['uses'=>'ReportsController@getWorkTimesReport']);
Route::get('worktimes/tabel',['uses'=>'WorkTimeController@tabel']);
Route::get('worktimes/tabel/pdf',['uses'=>'WorkTimeController@createTabelPdf']);
//ОТЧЕТЫ ПО УДАЛЕНИЯМ
Route::get('reports/remreqs',['uses'=>'ReportsController@getRemoveRequestsReport']);
Route::get('reports/remreqs/user',['uses'=>'ReportsController@getRemoveRequestsForUser']);

//КЛАДР
//Route::get('kladr/list',['uses'=>'KladrController@getSuggestions']);

//ТИКЕТЫ
//Route::post('tickets/update', ['uses' => 'TicketsController@update']);
//Route::post('ajax/tickets/update', ['uses' => 'TicketsController@ajaxUpdate']);
//Route::get('tickets/index', ['uses' => 'TicketsController@index']);
//Route::get('tickets/show', ['uses' => 'TicketsController@show']);
//Route::get('ajax/tickets/list', ['uses' => 'TicketsController@ajaxList']);
//Route::get('ajax/tickets/recall/list', ['uses' => 'TicketsController@getRecallList']);

//КЦ
//Route::get('kc/customer/summary/{id}', ['uses' => 'CustomerSummaryController@summary']);
//Route::get('ajax/kc/sfp/summary', ['uses' => 'KcDashboardController@sfp']);
//Route::get('kc/teleport/index', ['uses' => 'KcDashboardController@teleportIndex']);
//Route::get('kc/claims', ['uses' => 'KcDashboardController@claims']);
//Route::get('ajax/kc/claims/list', ['uses' => 'KcDashboardController@ajaxClaimsList']);
//Route::get('ajax/kc/plan', ['uses' => 'KcDashboardController@getPlan']);
//Route::get('ajax/kc/pcsave', ['uses' => 'KcDashboardController@getPcSaveData']);
//Route::post('kc/debtors/events/add', ['uses' => 'CustomerSummaryController@addDebtorEvent']);
//Route::get('ajax/teleportmeetings/subdivision', ['uses' => 'TeleportMeetingsController@getSubdivisionData']);
//Route::post('teleportmeetings/update', ['uses' => 'TeleportMeetingsController@update']);
//Route::post('ajax/kc/sendsmssecret', ['uses' => 'KcDashboardController@sendSmsSecret']);
//Route::get('kc/claims/teleport/cancel', ['uses' => 'KcDashboardController@setTeleportCancel']);
//Route::get('kc/phonecalls/plan', ['uses' => 'KcDashboardController@phonecallsPlan']);
//Route::post('kc/claims/comment/add', ['uses' => 'KcDashboardController@addClaimComment']);
//Route::post('ajax/plannedcalls/store', ['uses' => 'PlannedCallController@ajaxStore']);
//Route::get('plannedcalls/index', ['uses' => 'PlannedCallController@index']);
//Route::get('ajax/planned_calls/list', ['uses' => 'PlannedCallController@getList']);
//Route::get('planned_calls/edit', ['uses' => 'PlannedCallController@editItem']);
//Route::post('planned_calls/update', ['uses' => 'PlannedCallController@updateItem']);
//Route::get('planned_calls/remove', ['uses' => 'PlannedCallController@removeItem']);

//СЭБ
//Route::get('seb/claims/get/new', ['uses' => 'SebDashboardController@getNewClaim']);
//Route::get('seb/claims/finish', ['uses' => 'SebDashboardController@setSebFinished']);

//СОХРАНЕНИЯ ПРОЦЕНТНОЙ СТАВКИ
//Route::post('pcsaves/update', ['uses' => 'PcsavesController@update']);
//Route::get('pcsaves/index', ['uses' => 'PcsavesController@index']);
//Route::get('pcsaves/remove', ['uses' => 'PcsavesController@remove']);
//Route::get('ajax/pcsaves/list', ['uses' => 'PcsavesController@ajaxList']);

//ИНФИНИТИ
Route::get('ajax/infinity', ['uses' => 'InfinityController@main']);
Route::get('infinity/income', ['uses' => 'InfinityController@incoming']);
Route::post('infinity/callbacks/{item}/{callback}', ['uses' => 'InfinityController@callbacks']);
Route::get('infinity/callbacks/{item}/{callback}', ['uses' => 'InfinityController@callbacks']);
Route::get('infinity/losscall', ['uses' => 'InfinityController@fromInfinityLossCalls']);
Route::get('infinity/is_debtor_time', ['uses' => 'InfinityController@getDebtorTimeByPhoneWithRequest']);
Route::get('infinity/is_debtor_time/{telephone}', ['uses' => 'InfinityController@getDebtorTimeByPhone']);
Route::get('infinity/is_debtor_operator/{telephone}', ['uses' => 'InfinityController@getUserInfinityIdByDebtorPhone']);

//ВХОДЯЩИЕ СМС
//Route::get('smsinbox/index', ['uses' => 'SmsInboxController@index']);
//Route::get('smsinbox/badsmslist', ['uses' => 'SmsInboxController@badSmsList']);
//Route::get('ajax/smsinbox/list', ['uses' => 'SmsInboxController@ajaxList']);
//Route::get('ajax/smssent/list', ['uses' => 'SmsSentController@getSmsSentList']);
//
////ИСХОДЯЩИЕ СМС
//Route::get('smssent/index', ['uses' => 'SmsSentController@index']);
//Route::get('ajax/smssent/list', ['uses' => 'SmsSentController@getSmsSentList']);
//Route::get('ajax/smssent/list/all', ['uses' => 'SmsSentController@ajaxList']);

//АПИ ФИНТЕРРА ДЛЯ МОБИЛЬНОГО ПРИЛОЖЕНИЯ
//Route::get('api/customer/data', ['uses' => 'ApiFinterraController@getCustomerId1c']);
//Route::get('api/cabinet/data', ['uses' => 'ApiFinterraController@getCabinetData']);
//Route::get('api/claim/create', ['uses' => 'ApiFinterraController@createClaim']);
//Route::post('api/claim/create', ['uses' => 'ApiFinterraController@createClaim']);
//Route::get('api/loan/create', ['uses' => 'ApiFinterraController@createLoanForCustomer']);
//Route::get('api/payture/update', ['uses' => 'ApiFinterraController@updatePayturePayment']);
//Route::get('api/sms/send', ['uses' => 'ApiFinterraController@sendSms']);
//Route::get('api/history/data', ['uses' => 'ApiFinterraController@getHistoryByCustomerId1c']);
//Route::get('api/debtor/onsite', ['uses' => 'ApiFinterraController@isDebtorOnSite']);

//ПРОВЕРКА ТЕЛЕФОНА
//Route::post('ajax/telephone/check/pin', ['uses' => 'TelephoneCheckController@checkPin']);
//Route::post('ajax/telephone/check/send/pin', ['uses' => 'TelephoneCheckController@sendPin']);

//НАСТРОЙКИ СКИДОК И АКЦИЙ ДЛЯ ПОДРАЗДЕЛЕНИЙ
//Route::get('subdivision/stock/settings/index', ['uses' => 'SubdivisionStockSettingsController@index']);
//Route::get('subdivision/stock/settings/logs', ['uses' => 'SubdivisionStockSettingsController@viewLogs']);
//Route::get('subdivision/stock/settings/log', ['uses' => 'SubdivisionStockSettingsController@viewLog']);
//Route::get('subdivision/stock/settings/edit', ['uses' => 'SubdivisionStockSettingsController@edit']);
//Route::post('subdivision/stock/settings/update', ['uses' => 'SubdivisionStockSettingsController@update']);

//роуты для отправки еmail
Route::get('/debtors/emails/list/{user_id}',['uses'=>'EmailController@index']);
Route::post('/debtors/email/send',['as'=>'email.send','uses' => 'EmailController@sendEmail']);

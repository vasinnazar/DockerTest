<?php

use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\AdvanceReportController;
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\CardsController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\DailyCashReportController;
use App\Http\Controllers\DebtorMassSendController;
use App\Http\Controllers\DebtorsController;
use App\Http\Controllers\DebtorsReportsController;
use App\Http\Controllers\DebtorTransferController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InfinityController;
use App\Http\Controllers\IssueClaimController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanTypeController;
use App\Http\Controllers\MaterialsClaimsController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\NpfController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\PeacePaysController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\RepaymentController;
use App\Http\Controllers\RepaymentViewerController;
use App\Http\Controllers\SpylogController;
use App\Http\Controllers\SubdivisionController;
use App\Http\Controllers\TerminalAdminController;
use App\Http\Controllers\TesterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPhotoController;
use App\Http\Controllers\UsersRequestsController;
use App\Http\Controllers\WorkTimeController;
use App\Http\Controllers\Ajax\DebtorEventController;
use Illuminate\Support\Facades\Route;

Route::prefix('/ajax')->group(function () {
    //заявка
    Route::prefix('/claims')->group(function () {
        Route::get('/list', [HomeController::class, 'claimsList'])->name('ajax.claims.list');
        Route::get('/filter', [HomeController::class, 'getCustomFilterData'])->name('ajax.claims.filter');
        Route::get('/hasloan/{claim_id}', [ClaimController::class, 'hasLoan'])->name('claims.hasloan');
        Route::get('/checkpassport/{claim_id}', [ClaimController::class, 'checkPassport'])
            ->name('/claims.checkpassport');

        Route::post('/customer', [ClaimController::class, 'getClaimFormDataByPassport'])->name('claims.customer');
        Route::post('/autoapprove', [ClaimController::class, 'sendToAutoApprove'])->name('claims.autoapprove');
        Route::post('/check/telephone', [ClaimController::class, 'checkTelephone']);
        Route::post('/comment', [HomeController::class, 'getComment'])->name('ajax.claims.comment');


    });
    //займы
    Route::prefix('/loans')->group(function () {

        Route::get('/get/{loan_id}', [LoanController::class, 'getLoan'])->name('ajax.loans.get');
        Route::get('/list', [LoanController::class, 'getLoansList'])->name('ajax.loans.list');
        Route::post('/get/debt', [LoanController::class, 'getDebt']);
        Route::post('/create', [LoanController::class, 'createPromocode'])
            ->middleware('admin_only')
            ->name('ajax.promocodes.create');


    });
    //отчеты
    Route::prefix('/reports')->group(function () {
        Route::get('/dailycashreportslist', [DailyCashReportController::class, 'getList'])
            ->name('ajax.reports.dailycashreportslist');
        Route::get('/matchWithCashbook/{report_id}', [DailyCashReportController::class, 'matchWithCashbookById'])
            ->name('ajax.reports.matchWithCashbook');
        Route::get('/advancereports/list', [AdvanceReportController::class, 'ajaxList']);
        Route::get('/advancereports/issueorders', [AdvanceReportController::class, 'getIssueOrders']);
    });
    //загрузка фотографий
    Route::prefix('/photos')->group(function () {
        Route::post('/upload', [PhotoController::class, 'ajaxUpload'])->name('photos.ajax.upload');
        Route::post('/upload2', [PhotoController::class, 'ajaxUpload2'])->name('photos.ajax.upload2');
        Route::post('/webcam/upload', [PhotoController::class, 'webcamUpload']);
        Route::post('/remove', [PhotoController::class, 'ajaxRemove'])->name('photos.ajax.remove');
        Route::post('/remove/all', [PhotoController::class, 'removeAllForClaim']);
        Route::post('/userphoto/upload', [UserPhotoController::class, 'userPhotoUpload']);

    });

    Route::prefix('/promocodes')->group(function () {
        Route::get('/{loan_id}', [HomeController::class, 'getPromocode'])->name('ajax.promocode');
        Route::post('/add', [ClaimController::class, 'addPromocode'])->name('ajax.promocodes.add');
        Route::post('/getdata', [HomeController::class, 'getPromocodeData'])
            ->name('ajax.promocodes.getdata');
        Route::post('/add/manual', [HomeController::class, 'manualAddPromocode'])
            ->middleware('admin_only')
            ->name('ajax.promocodes.add.manual');

    });

    Route::prefix('/customers')->group(function () {
        Route::post('/{customerId}/passports/{passportId}', [CustomersController::class, 'updatePassport'])
            ->name('customers.updatePassport');
        Route::post('/telephone', [CustomersController::class, 'getTelephone'])->name('ajax.customers.telephone');
        Route::get('/find', [CustomersController::class, 'findCustomers'])->name('ajax.customers.find');
        Route::get('/list', [CustomersController::class, 'getList'])->name('ajax.customers.list');
    });

    Route::prefix('/subdivisions')->group(function () {
        Route::get('/get', [SubdivisionController::class, 'getSubdivision']);
        Route::get('/autocomplete', [SubdivisionController::class, 'getAutocompleteList'])
            ->name('ajax.subdivisions.autocomplete');
    });

    Route::prefix('/passports')->group(function () {
        Route::get('/autocomplete', [AjaxController::class, 'passportsAutocomplete']);
        Route::get('/get', [AjaxController::class, 'getPassport']);
    });
    //логи
    Route::prefix('/spylog')->middleware('admin_only')->group(function () {
        Route::get('/list', [SpylogController::class, 'getList'])->name('ajax.spylog.list');
        Route::post('/view/{log_id}', [SpylogController::class, 'viewLog'])->name('ajax.spylog.view');
        Route::post('/repeat', [SpylogController::class, 'backupOneRequest'])->name('ajax.spylog.repeat');
    });

    Route::prefix('/adminpanel')->middleware('admin_only')->group(function () {
        Route::post('/users/changesubdivision', [AdminPanelController::class, 'changeSubdivision'])
            ->name('ajax.adminpanel.user.changesubdivision');
        Route::get('/userslist', [AdminPanelController::class, 'getUsersList'])
            ->name('ajax.adminpanel.userslist');
        Route::get('/users/userlog', [AdminPanelController::class, 'getUserLog'])
            ->name('ajax.adminpanel.user.userlog');
        Route::get('/users/{user_id}', [AdminPanelController::class, 'getUser'])
            ->name('ajax.adminpanel.user');
        Route::post('/users/update', [AdminPanelController::class, 'updateUser'])
            ->name('ajax.adminpanel.user.update');
        Route::post('/users/{id}/createcustomer', [AdminPanelController::class, 'createCustomer'])
            ->name('ajax.adminpanel.user.createcustomer');
        Route::post('/users/bantime', [AdminPanelController::class, 'updateUserBantime'])
            ->name('ajax.adminpanel.user.bantime');
        Route::post('/users/changepass', [AdminPanelController::class, 'changePassword'])
            ->name('ajax.adminpanel.user.changepass');
        Route::post('/users/add', [AdminPanelController::class, 'addUser'])->name('ajax.adminpanel.user.add');
        Route::post('/users/saveDebtorUserSlaves', [AdminPanelController::class, 'saveDebtorUserSlaves'])
            ->name('ajax.adminpanel.user.saveDebtorUserSlaves');
        Route::post('/refreshlastlogin/{user_id}', [AdminPanelController::class, 'refreshUserLastLogin'])
            ->name('ajax.adminpanel.refreshlastlogin');
        Route::post('/users/employment/{user_id}', [UserController::class, 'setEmploymentFields']);
        Route::get('/subdivisions/list', [AdminPanelController::class, 'getSubdivisionsList'])
            ->name('ajax.adminpanel.subdivisions.list');
        Route::post('/users/terminals/grant', [UserController::class, 'grantTerminals']);
        Route::post('/users/mail/add', [UserController::class, 'addEmailAjax']);
        Route::post('/permissions/get/{id}', [PermissionsController::class, 'getPermission']);
        Route::post('/subdivisions/list/1c', [SubdivisionController::class, 'getSubdivisionsFrom1c'])
            ->name('subdivisions.list.1c');

        Route::post('/tester/exchangearm', [TesterController::class, 'testExchangeArm']);
        Route::post('/tester/button', [TesterController::class, 'testButton']);
        Route::post('/tester/soaptest', [TesterController::class, 'ajaxSendSoapRequest']);
        Route::post('/tester/loadparamscsv', [TesterController::class, 'ajaxLoadTestParamsCsv']);

        Route::get('/terminals/list', [TerminalAdminController::class,'getTerminalsList'])->name('terminals.list');
        Route::get('/terminals/refreshstatus', [TerminalAdminController::class,'refreshStatus'])
            ->name('terminals.refreshstatus');
    });

    Route::prefix('/orders')->group(function () {
        Route::get('/order/{id}', [OrdersController::class,'getOrder'])->name('ajax.orders.order');
        Route::get('/list', [OrdersController::class,'getOrdersList'])->name('ajax.orders.list');
        Route::post('/claimforremove/{id}', [OrdersController::class,'claimForRemove'])
            ->name('ajax.orders.claimforremove');

        //ЗАЯВКИ НА ОРДЕРА НА ПОДОТЧЕТ
        Route::get('/issueclaims/list', [IssueClaimController::class, 'ajaxList']);
        Route::get('/issueclaims/view/{id}', [IssueClaimController::class, 'ajaxView']);
        Route::post('/issueclaims/update', [IssueClaimController::class, 'update']);
    });

    Route::prefix('/repayments')->group(function () {
        Route::get('/list', [RepaymentViewerController::class,'getList'])->name('repayments.list');
        Route::get('/get/{id}', [RepaymentController::class,'getRepayment'])->name('ajax.repayments.get');
        Route::get('/peacepays/get/{id}', [PeacePaysController::class,'getPeacePay'])->name('ajax.peacepays.get');
    });

    Route::prefix('/debtors')->group(function () {
        Route::post('/eventcomplete', [DebtorsController::class,'eventComplete'])->name('ajax.debtors.eventcomplete');
        Route::delete('/{debtorId}/events/{eventId}', [DebtorEventController::class,'destroyDebtorEvent']);
        Route::post('/sendsmstodebtor', [DebtorsController::class,'sendSmsToDebtor'])->name('ajax.debtors.sendsmstodebtor');
        Route::get('/sendsmstodebtor', [DebtorsController::class,'sendSmsToDebtor'])->name('ajax.debtors.sendsmstodebtor');
        Route::post('/loadphoto/{claim_id}', [DebtorsController::class,'loadPhoto']);
        Route::get('/list', [DebtorsController::class,'ajaxList'])->name('ajax.debtors.list');

        Route::get('/event/data', [DebtorsController::class,'getDebtorEventData']);
        Route::get('/search/autocomplete', [DebtorsController::class,'ajaxColumnAutocomplete']);
        Route::post('/changePersonalData/{debtor_id}/{action}', [DebtorsController::class,'changePersonalData']);
        Route::post('/changeRecommend', [DebtorsController::class,'changeRecommend']);
        Route::post('/userpayments', [DebtorsReportsController::class,'getPaymentsForUser']);
        Route::post('/oldevents/upload', [DebtorsController::class,'uploadOldDebtorEvents']);
        Route::post('/orders/upload', [DebtorsController::class,'uploadOrdersFrom1c']);
        Route::get('/total-planned',[DebtorsController::class,'totalNumberPlaned']);
        Route::post('/calc/creditcard', [DebtorsController::class,'getCalcDataForCreditCard']);
        Route::post('/massmessage/send', [DebtorMassSendController::class,'sendMassMessage']);
        Route::get('/changeloadstatus/{debtor_id}', [DebtorsController::class,'changeLoadStatus']);
        Route::post('/getallpayments/{debtor_id}', [DebtorsController::class,'getAllPayments']);
        Route::post('/loans/upload', [DebtorsController::class,'uploadLoans']);
        Route::post('/loans/getmultisum', [DebtorsController::class,'getMultiSum']);
        Route::post('/totalEvents', [DebtorsController::class,'refreshTotalEventTable']);
        Route::post('/overallEvents', [DebtorsController::class,'refreshOverallTable']);
        Route::post('/searchEqualContacts', [DebtorsController::class,'searchEqualContacts']);
        Route::post('/transfer/changeResponsibleUser', [DebtorTransferController::class,'changeResponsibleUser']);
        Route::get('/transfer/printResponsibleUser', [DebtorTransferController::class,'getActPdf']);
    });
    Route::get('/debtormasssms/list', [DebtorMassSendController::class,'ajaxList']);
    Route::get('/removeRequests/list', [UsersRequestsController::class,'removeRequestsList'])
        ->middleware('office_only')
        ->name('usersreqs.remove.list');
    Route::post('/removeRequests/claim', [UsersRequestsController::class,'claimForRemove'])
        ->name('ajax.removeRequests.claim');
    Route::get('/usersreqs/removeRequests/info/{id}', [UsersRequestsController::class,'getRemoveRequestInfo'])
        ->middleware('office_only')
        ->name('usersreqs.removeRequests.info');
    Route::post('/contracts/list/{claim_id}', [HomeController::class, 'contractslist'])->name('ajax.contracts.list');
    Route::get('/loantypes/list/{claim_id}', [LoanTypeController::class, 'getPossible'])->name('ajax.loantypes.list');
    Route::get('/carddata', [HomeController::class, 'getCardData'])->name('ajax.carddata');
    Route::get('/{table}/autocomplete', [AjaxController::class, 'autocomplete']);
    Route::get('/autocomplete/label', [AjaxController::class, 'getLabelById']);
    Route::get('/users/autocomplete', [UserController::class, 'getAutocompleteList'])->name('ajax.users.autocomplete');
    Route::post('/cards/check', [CardsController::class,'checkCard']);
    Route::get('/cardChanges/list', [CardsController::class,'getCardChangesList'])->name('ajax.cardchanges.list');
    Route::get('/npf/list', [NpfController::class,'getList'])->name('npf.list');
    Route::get('/matclaims/list', [MaterialsClaimsController::class,'getList'])->name('matclaims.list');
    Route::get('/work_times/list', [WorkTimeController::class,'getList'])->middleware('admin_only');
    Route::get('/messages/list', [MessagesController::class,'getList'])->name('messages.list');
    Route::get('/userphotos/list', [UserPhotoController::class,'ajaxList']);
    Route::get('/userphotos/list2', [UserPhotoController::class,'ajaxBasicList']);
    Route::get('/infinity', [InfinityController::class,'main']);
    Route::get('debtorevents/list', [DebtorsController::class,'ajaxEventsList'])->name('ajax.debtorevents.list');
    Route::get('debtorforgotten/list', [DebtorsController::class,'ajaxForgottenList'])
        ->name('ajax.debtorforgotten.list');
    Route::get('debtorrecommends/list', [DebtorsController::class,'ajaxRecommendsList'])
        ->name('ajax.debtorrecommends.list');
    Route::get('debtortransfer/list', [DebtorTransferController::class,'ajaxList']);


});

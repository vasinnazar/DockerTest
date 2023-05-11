<?php

use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\AdvanceReportController;
use App\Http\Controllers\BlanksController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CardsController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\ConditionsController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\ContractVersionsController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\DailyCashReportController;
use App\Http\Controllers\DataLoaderController;
use App\Http\Controllers\DebtorExportController;
use App\Http\Controllers\DebtorMassSmsController;
use App\Http\Controllers\DebtorsController;
use App\Http\Controllers\DebtorsFrom1cController;
use App\Http\Controllers\DebtorsNoticesController;
use App\Http\Controllers\DebtorsReportsController;
use App\Http\Controllers\DebtorTransferController;
use App\Http\Controllers\DocsRegisterController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EmploymentDocsController;
use App\Http\Controllers\FinterraController;
use App\Http\Controllers\From1cController;
use App\Http\Controllers\FromDebtorsController;
use App\Http\Controllers\FromSellingARMController;
use App\Http\Controllers\GraphController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InfinityController;
use App\Http\Controllers\IssueClaimController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanTypeController;
use App\Http\Controllers\MailLoaderController;
use App\Http\Controllers\MassiveChangeController;
use App\Http\Controllers\MaterialsClaimsController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\NomenclatureController;
use App\Http\Controllers\NpfController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\PaySheetController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\PeacePaysController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\PhoneCallController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProblemSolverController;
use App\Http\Controllers\QuizDepartmentController;
use App\Http\Controllers\RepaymentController;
use App\Http\Controllers\RepaymentsEditorController;
use App\Http\Controllers\RepaymentViewerController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RnkoController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SaldoController;
use App\Http\Controllers\SalesReportsController;
use App\Http\Controllers\SmsFormController;
use App\Http\Controllers\SpylogController;
use App\Http\Controllers\SubdivisionController;
use App\Http\Controllers\TerminalAdminController;
use App\Http\Controllers\TerminalController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\TesterController;
use App\Http\Controllers\UserPhotoController;
use App\Http\Controllers\UsersRequestsController;
use App\Http\Controllers\UserTestController;
use App\Http\Controllers\UserTestEditorController;
use App\Http\Controllers\WorkTimeController;
use Illuminate\Support\Facades\Route;

Route::get('maintenance', function () {
    if (config('admin.maintenance_mode')) {
        return view('maintenance');
    }
    return redirect('/');

});

//запросы с сайта Финтерра.рф
//TODO: переделать на апи
Route::get('fincustomer', [FinterraController::class, 'getCustomerIdForFinterra']);
Route::get('finloaninfo', [FinterraController::class, 'getLoanInfo']);
Route::get('finterra/claim/create', [FinterraController::class, 'createClaimForCustomer']);
Route::get('finterra/loan/create', [FinterraController::class, 'createLoanForCustomer']);
Route::get('finterra/asp/secret', [FinterraController::class, 'aspSecret']);
Route::get('finterra/asp/approve', [FinterraController::class, 'aspApprove']);
Route::get('finterra/asp/data', [FinterraController::class, 'aspData']);

Route::prefix('auth')->group(function () {
    Route::get('/login', ['uses' => 'Auth\AuthController@login']);
    Route::get('/logout', ['uses' => 'Auth\AuthController@logout']);
    Route::post('/login', ['uses' => 'Auth\AuthController@postLogin']);
});

Route::middleware('auth_only')->group(function () {
    Route::get('/', [HomeController::class, 'index']);
    Route::get('/phpinfo', [RnkoController::class, 'phpinfo']);
    Route::get('/videoplayer', [TestController::class, 'video']);
    Route::get('/home2', [HomeController::class, 'claimsList2'])->name('claims.list2');
    Route::get('/npf/pdf/{contract_id}/{npf_id}', [NpfController::class, 'createPdf'])->name('contracts.npf');
    Route::get('/startwork', [HomeController::class, 'startWork'])->name('startwork');
    Route::get('/endwork', [HomeController::class, 'endWork'])->name('endwork');
    Route::get('spylog/list', [SpylogController::class, 'index'])
        ->middleware('admin_only')
        ->name('spylog.list');

    //ГРАФИК ПРОДАЖ
    Route::get('graph/index', [GraphController::class,'index']);
    Route::post('graph/index/graphdata', [GraphController::class,'getGraphData']);

    Route::prefix('/test')->group(function () {
        Route::get('/', [TestController::class, 'test'])->name('test');
        Route::post('/', [TestController::class, 'test'])->name('test');
        Route::get('/claim', [TestController::class, 'testClaimPage']);
        Route::post('/claim/create', [TestController::class, 'createTestClaim']);
    });
    //админ.панель
    Route::middleware('admin_only')->prefix('/adminpanel')->group(function () {
        Route::get('/', [AdminPanelController::class, 'index'])->name('adminpanel.index');
        Route::get('/cashbook/{id}', [AdminPanelController::class, 'getCashbook'])->name('adminpanel.cashbook');
        Route::get('/orders/remove/{id}', [AdminPanelController::class, 'removeOrder'])
            ->name('adminpanel.orders.remove');
        Route::get('/terminals', [TerminalAdminController::class, 'index'])->name('adminpanel.terminals');
        Route::post('/printserver/update', [ConfigController::class, 'printServerUpdate']);

        Route::prefix('/users')->group(function () {
            Route::get('/', [AdminPanelController::class, 'getUsers'])->name('adminpanel.users');
            Route::get('/ssl/download', [AdminPanelController::class, 'createUserCertificate']);
        });
        Route::prefix('/subdivisions')->group(function () {
            Route::get('/', [AdminPanelController::class, 'subdivisionsList'])
                ->name('adminpanel.subdivisions');
            Route::get('/close/{id}', [AdminPanelController::class, 'closeSubdivision'])
                ->name('adminpanel.subdivisions.close');
            Route::get('/create', [SubdivisionController::class, 'editSubdivision'])
                ->name('adminpanel.subdivisions.create');
            Route::get('/edit/{id}', [SubdivisionController::class, 'editSubdivision'])
                ->name('adminpanel.subdivisions.edit');
            Route::post('/update', [SubdivisionController::class, 'updateSubdivision'])
                ->name('adminpanel.subdivisions.update');
            Route::get('/cities', [SubdivisionController::class, 'updateCities'])
                ->name('adminpanel.subdivisions.cities');
        });
        Route::prefix('/repaymenttypes')->group(function () {
            Route::get('/', [RepaymentsEditorController::class, 'index'])
                ->name('adminpanel.repaymenttypes');
            Route::get('/create', [RepaymentsEditorController::class, 'editor'])
                ->name('adminpanel.repaymenttypes.create');
            Route::get('/edit/{id}', [RepaymentsEditorController::class, 'editor'])
                ->name('adminpanel.repaymenttypes.edit');
            Route::post('/update', [RepaymentsEditorController::class, 'update'])
                ->name('adminpanel.repaymenttypes.update');
            Route::get('/remove/{id}', [RepaymentsEditorController::class, 'remove'])
                ->name('adminpanel.repaymenttypes.remove');
        });
        Route::prefix('/npffonds')->group(function () {
            Route::get('/', [AdminPanelController::class, 'getNpfFondsList'])->name('adminpanel.npffonds');
            Route::get('/create', [AdminPanelController::class, 'editNpfFond'])->name('adminpanel.npffonds.create');
            Route::get('/edit', [AdminPanelController::class, 'editNpfFond'])->name('adminpanel.npffonds.edit');
            Route::get('/remove', [AdminPanelController::class, 'removeNpfFond'])->name('adminpanel.npffonds.remove');
            Route::post('/update', [AdminPanelController::class, 'updateNpfFond'])->name('adminpanel.npffonds.update');

        });
        Route::prefix('/problemsolver')->group(function () {
            Route::get('/', [ProblemSolverController::class, 'index'])->name('adminpanel.problemsolver');
            Route::post('/loan/remove2', [ProblemSolverController::class, 'removeLoan'])
                ->name('adminpanel.problemsolver.loan.remove2');
            Route::post('/card/remove', [ProblemSolverController::class, 'removeCard'])
                ->name('adminpanel.problemsolver.card.remove');
            Route::post('/repayment/remove', [ProblemSolverController::class, 'removeRepayment'])
                ->name('adminpanel.problemsolver.repayment.remove');
            Route::post('/order/remove', [ProblemSolverController::class, 'removeOrder'])
                ->name('adminpanel.problemsolver.order.remove');
            Route::post('/getloan', [ProblemSolverController::class, 'getLoanByNumber'])
                ->name('adminpanel.problemsolver.loan.get');
            Route::post('/fakeloan', [ProblemSolverController::class, 'createFakeClaimAndLoan'])
                ->name('adminpanel.problemsolver.loan.fake');
            Route::post('/promocode/info', [ProblemSolverController::class, 'getPromocodeInfo'])
                ->name('adminpanel.problemsolver.promocode.info');
            Route::post('/photos/changefolder', [ProblemSolverController::class, 'changePhotoFolder'])
                ->name('adminpanel.problemsolver.photos.changefolder');
            Route::post('/phone/change', [ProblemSolverController::class, 'changePhone']);
            Route::post('/repayment/change', [ProblemSolverController::class, 'changeRepaymentUser']);
            Route::post('/claim/remove', [ProblemSolverController::class, 'removeClaim']);
        });
        Route::prefix('/dataloader')->group(function () {
            Route::get('/', [DataLoaderController::class, 'index']);
            Route::get('/customers', [DataLoaderController::class, 'loadCustomers']);
        });
        Route::prefix('/mailloader')->group(function () {
            Route::get('/', [MailLoaderController::class, 'index']);
            Route::get('/xml', [MailLoaderController::class, 'saveBigXML']);
            Route::get('/loadxml', [MailLoaderController::class, 'loadMailXML']);
            Route::post('/load', [MailLoaderController::class, 'loadMail']);

        });
        Route::prefix('/massivechange')->group(function () {
            Route::get('/', [MassiveChangeController::class, 'index']);
            Route::post('/execute', [MassiveChangeController::class, 'executeChange']);
        });
        Route::prefix('/tester')->group(function () {
            Route::get('/', [TesterController::class, 'index']);
            Route::get('/loadtest', [TesterController::class, 'soapLoadTesting']);
        });
        Route::prefix('/smser')->group(function () {
            Route::post('/update', [ConfigController::class, 'smserUpdate']);
            Route::post('/check', [ConfigController::class, 'smserCheck']);
        });
        Route::prefix('/config')->group(function () {
            Route::get('/', [ConfigController::class, 'index']);
            Route::get('/mysql/kill/{param}', [ConfigController::class, 'killAllMysql']);
            Route::post('/mysql/threads', [ConfigController::class, 'getMysqlThreadsCount']);
            Route::post('/mode/update', [ConfigController::class, 'modeUpdate']);
            Route::post('/1c/update', [ConfigController::class, 'server1cUpdate']);
            Route::post('/orders/sync', [ConfigController::class, 'syncOrders']);
            Route::get('/mysql/threads/chart', [ConfigController::class, 'mysqlThreadsChart']);
            Route::post('/mysql/threads/data', [ConfigController::class, 'getMysqlThreadsData']);
            Route::post('/debtors/upload', [ConfigController::class, 'uploadEmptyDebtorsToArm']);
            Route::post('/options/update', [ConfigController::class, 'updateOption']);
            Route::post('/action', [ConfigController::class, 'makeAction']);
        });
        Route::prefix('/roles')->group(function () {
            Route::get('/index', [RolesController::class, 'index']);
            Route::get('/create', [RolesController::class, 'create']);
            Route::get('/edit/{id}', [RolesController::class, 'edit']);
            Route::post('/update', [RolesController::class, 'update']);
            Route::post('/grant', [RolesController::class, 'grant']);
        });
        Route::prefix('/permissions')->group(function () {
            Route::post('/grant', [PermissionsController::class, 'grantToUser']);
            Route::post('/update', [PermissionsController::class, 'update']);
            Route::get('/destroy/{id}', [PermissionsController::class, 'destroy']);
        });
        //терминалы в админпанели
        Route::prefix('/terminals')->group(function () {
            Route::post('/addcash', [TerminalAdminController::class, 'addCash'])->name('terminals.addcash');
            Route::post('/incass', [TerminalAdminController::class, 'incass'])->name('terminals.incass');
            Route::post('/changelockstatus', [TerminalAdminController::class, 'changeLockStatus'])
                ->name('terminals.changelockstatus');
            Route::get('/create', [TerminalAdminController::class, 'editor'])->name('adminpanel.terminals.create');
            Route::get('/edit', [TerminalAdminController::class, 'editor'])->name('adminpanel.terminals.edit');
            Route::post('/update', [TerminalAdminController::class, 'update'])->name('adminpanel.terminals.update');
            Route::get('/remove', [TerminalAdminController::class, 'remove'])->name('adminpanel.terminals.remove');
            Route::post('/command/add', [TerminalAdminController::class, 'addCommand'])
                ->name('adminpanel.terminals.command.add');
            Route::get('/report', [TerminalAdminController::class, 'getReport']);
        });
    });

    //ФОРМЫ СМСОК ДЛЯ ВЗЫСКАНИЯ
    Route::prefix('/adminpanel/smsform')->group(function () {
        Route::get('/index', [SmsFormController::class,'index']);
        Route::get('/edit', [SmsFormController::class,'edit']);
        Route::post('/update', [SmsFormController::class,'update']);
        Route::get('/destroy', [SmsFormController::class,'destroy']);
    });
    //заявка
    Route::prefix('/claims')->group(function () {
        Route::get('/status/{claim_id}/{status}', [ClaimController::class, 'changeStatus'])
            ->name('claims.status');
        Route::get('/summary/{claim_id}', [ClaimController::class, 'summary'])->name('claims.summary');
        Route::get('/updatefrom1c/{claim_id}', [ClaimController::class, 'updateFrom1c']);
        Route::get('/setedit/{claim_id}', [ClaimController::class, 'setStatusToEdit']);
        Route::get('/create', [ClaimController::class, 'create'])->name('claims.create');
        Route::get('/edit/{claim_id}', [ClaimController::class, 'edit'])->name('claims.edit');
        Route::post('/update', [ClaimController::class, 'update'])->name('claims.edit');
        Route::post('/save', [ClaimController::class, 'store'])->name('claims.save');
        Route::post('/create', [ClaimController::class, 'create'])->name('claims.create');
        Route::post('/1c/update', [ClaimController::class, 'updateFrom1c'])->name('claims.update1c');
        Route::get('/uki/toggle', [HomeController::class, 'toggleUkiClaim']);
        Route::get('/cbupdate', [HomeController::class, 'toggleAboutClientCheckbox']);
        Route::middleware('admin_only')->group(function () {
            Route::get('/mark4remove/{claim_id}', [ClaimController::class, 'markForRemove'])
                ->name('claims.mark4remove');
            Route::get('/mark4remove2/{claim_id}', [ClaimController::class, 'markForRemove2'])
                ->name('claims.mark4remove2');
            Route::get('/remove/{claim_id}', [ClaimController::class, 'remove'])->name('claims.remove');
        });
    });
    //редактор договоров
    Route::prefix('/contracts')->group(function () {
        Route::get('/list', [ContractVersionsController::class, 'index'])->name('contracts.list');
        Route::get('/versions/remove', [ContractVersionsController::class, 'removeItem']);
        Route::get('/make/pdf', [ContractVersionsController::class, 'createPdfByRequest']);
        Route::get('/versions', [ContractVersionsController::class, 'versionsEditor']);
        Route::post('/versions/add', [ContractVersionsController::class, 'addVersion']);
        Route::post('/versions/update', [ContractVersionsController::class, 'updateVersion']);
        Route::prefix('/pdf')->group(function () {
            Route::get('/{contract_id}', [ContractVersionsController::class, 'createPdf'])
                ->name('contracts.delete');
            Route::get('/{contract_id}/{zaim_id}', [ContractVersionsController::class, 'createPdf'])
                ->name('contracts.empty');
            Route::get('/{contract_id}/{zaim_id}/{repayment_id}', [ContractVersionsController::class, 'createPdf'])
                ->name('contracts.filled');
            Route::get('/{contract_id}/{zaim_id}/{repayment_id}/{loan_id}',
                [ContractVersionsController::class, 'createPdf']
            );
        });
        Route::middleware('admin_only')->group(function () {
            Route::get('/create', [ContractVersionsController::class, 'openEditor'])
                ->name('contracts.create');
            Route::get('/edit/{contract_id}', [ContractVersionsController::class, 'openEditor'])
                ->name('contracts.edit');
            Route::get('/delete/{contract_id}', [ContractVersionsController::class, 'delete'])
                ->name('contracts.delete');
            Route::post('/update', [ContractVersionsController::class, 'update'])
                ->name('contracts.update');
        });
    });
    //редактор условий
    Route::prefix('/conditions')->middleware('admin_only')->group(function () {
        Route::get('/list', [ConditionsController::class, 'index'])->name('conditions.list');
        Route::get('/create', [ConditionsController::class, 'editor'])->name('conditions.create');
        Route::get('/edit/{condition_id}', [ConditionsController::class, 'editor'])->name('conditions.edit');
        Route::post('/update', [ConditionsController::class, 'update'])->name('conditions.update');
        Route::get('/delete/{condition_id}', [ConditionsController::class, 'delete'])->name('conditions.delete');
    });
    //виды займов
    Route::prefix('/loantypes')->middleware('admin_only')->group(function () {
        Route::get('/list', [LoanTypeController::class, 'index'])->name('loantypes.list');
        Route::get('/create', [LoanTypeController::class, 'editor'])->name('loantypes.create');
        Route::get('/edit/{condition_id}', [LoanTypeController::class, 'editor'])->name('loantypes.edit');
        Route::post('/update', [LoanTypeController::class, 'update'])->name('loantypes.update');
        Route::get('/delete/{condition_id}', [LoanTypeController::class, 'delete'])->name('loantypes.delete');
        Route::get('/clone/{loantype_id}', [LoanTypeController::class, 'cloneLoantype'])->name('loantypes.clone');
    });
    //займы
    Route::prefix('/loans')->group(function () {
        Route::get('/', [LoanController::class, 'getLoans'])->name('loans');
        Route::get('/summary/{loan_id}', [LoanController::class, 'getLoanSummary'])->name('loans');
        Route::get('/summary/{pseries}/{pnumber}', [LoanController::class, 'getLoanByPassport'])
            ->name('loans');
        Route::get('/enroll/{loan_id}', [LoanController::class, 'enroll'])->name('loans.enroll');
        Route::get('/close/{loan_id}', [LoanController::class, 'close'])->name('loans.close');
        Route::get('/makeandroll/{claim_id}', [LoanController::class, 'createAndEnroll'])
            ->name('loans.makeandroll');
        Route::get('/sendtobalance/{loan_id}', [LoanController::class, 'sendMoneyToBalance'])
            ->name('loans.sendtobalance');
        Route::post('/uki/orders/create', [LoanController::class, 'createUkiOrders']);
        Route::post('/create', [LoanController::class, 'create'])->name('loans.create');
        Route::post('/demo', [LoanController::class, 'demo'])->name('loans.demo');
        Route::get('/uki/toggle', [HomeController::class, 'toggleUki']);
        Route::middleware('admin_only')->group(function () {
            Route::get('/remove/{loan_id}', [LoanController::class, 'remove'])->name('loans.remove');
            Route::get('/incash/{loan_id}', [LoanController::class, 'inCash'])->name('loans.incash');
            Route::get('/clearenroll/{loan_id}', [LoanController::class, 'clearEnroll'])
                ->name('loans.clearenroll');
            Route::get('/remove2/{loan_id}', [LoanController::class, 'removeOnlyFromDB'])
                ->name('loans.remove2');
            Route::post('/update', [LoanController::class, 'update'])->name('loans.update');
            Route::get('/set/cccall', [HomeController::class, 'setCCcall'])->name('loans.set.cccall');
        });
    });
    //отчеты
    Route::prefix('/reports')->group(function () {
        Route::get('/', [ReportsController::class, 'reports'])->name('reports');
        Route::get('/dailycashreportslist', [DailyCashReportController::class, 'getListView'])
            ->name('reports.dailycashreportslist');
        Route::get('/dailycashreport', [DailyCashReportController::class, 'getView'])
            ->name('reports.dailycashreport');
        Route::get('/dailycashreport/{report_id}', [DailyCashReportController::class, 'getView'])
            ->name('reports.dailycashreport');
        Route::get('/pdf/dailycashreport/{report_id}', [DailyCashReportController::class, 'createPdf'])
            ->name('reports.dailycashreport');
        Route::get('/pdf/dailycashsummary', [DailyCashReportController::class, 'summaryReport'])
            ->name('reports.dailycashsummary');
        Route::post('/update', [DailyCashReportController::class, 'update'])->name('reports.update');
        Route::get('/cashbook', [DailyCashReportController::class, 'cashbook'])->name('reports.cashbook');
        Route::get('/pdf/cashbook', [DailyCashReportController::class, 'createCashbook'])->name('reports.pdf.cashbook');
        Route::post('/dailycashreport/1c/update', [DailyCashReportController::class, 'dailyCashReportUpdate'])
            ->name('reports.dailycashreport.1c.update');
        Route::get('/setmatch', [DailyCashReportController::class, 'setMatch'])->name('reports.setmatch');
        Route::get('/dailycashreport/editable/toggle', [DailyCashReportController::class, 'toggleReportEditable'])
            ->middleware('admin_only');
        Route::get('/remove', [DailyCashReportController::class, 'remove'])->name('reports.remove')
            ->middleware('admin_only');
        //отчет по отсутствующим подразделениям
        Route::get('/absentsubdivisions', [ReportsController::class, 'getAbsentSubdivisions']);
        //расчетный лист
        Route::get('/paysheet', [PaySheetController::class, 'index'])->name('paysheet');
        Route::get('/paysheet/pdf', [PaySheetController::class, 'createPdf'])->name('paysheet.pdf');
        //реестр документов
        Route::get('/docsregister', [DocsRegisterController::class, 'index'])->name('docsregister');
        Route::get('/docsregister/pdf', [DocsRegisterController::class, 'createPdf'])
            ->name('docsregister.pdf');
        Route::get('/docsregister/pdf2', [DocsRegisterController::class, 'createPdf2']);
        //отчет по продажам
        Route::get('/salesreports/1/pdf', [SalesReportsController::class, 'getSalesReport1']);
        Route::get('cashbook/sync', [DailyCashReportController::class, 'syncCashbook'])
            ->name('cashbook.sync');
        Route::get('cashbook/sync2', [DailyCashReportController::class, 'syncCashbookFromDailyCash'])
            ->name('cashbook.sync2');
        Route::get('/plan', [PlanController::class, 'getTableView'])->name('reports.plan');
        Route::get('/plan/loanslist', [PlanController::class, 'getLoansList'])
            ->name('reports.plan.loanslist');
        Route::post('/plan/loanslist', [PlanController::class, 'getLoansList'])
            ->name('reports.plan.loanslist');
        Route::get('/saldo', [SaldoController::class, 'getSaldoView'])->name('reports.saldo');
        //обзвон
        Route::get('/phonecall', [PhoneCallController::class, 'getView'])->name('reports.phonecall');
        Route::post('/phonecall/save', [PhoneCallController::class, 'saveAndShowNext'])->name('reports.phonecall.save');
        Route::middleware('auth_only')->group(function () {
            Route::get('/rnko', [RnkoController::class, 'index']);
            Route::post('/rnko/update', [RnkoController::class, 'update']);
            Route::get('/rnko/admin', [RnkoController::class, 'admin']);
            Route::get('/rnko/bydates', [RnkoController::class, 'getReportByDays']);
            Route::get('/rnko/number', [RnkoController::class, 'getRnkoByCardNumber']);
            Route::get('/rnko/check/get', [RnkoController::class, 'getUncheckedRnko']);
            Route::get('/rnko/photos', [RnkoController::class, 'openAllPhotos']);
            Route::get('/rnko/skip', [RnkoController::class, 'skipRnko']);
            //АВАНСОВЫЕ ОТЧЕТЫ
            Route::get('/nomenclature/upload', [NomenclatureController::class,'upload']);
        });

        //АВАНСОВЫЕ ОТЧЕТЫ
        Route::prefix('/advancereports')->group(function () {
            Route::get('/index', [AdvanceReportController::class, 'index']);
            Route::get('/create', [AdvanceReportController::class, 'edit']);
            Route::get('/edit/{id}', [AdvanceReportController::class, 'edit']);
            Route::get('/destroy/{id}', [AdvanceReportController::class, 'destroy']);
            Route::get('/pdf/{id}', [AdvanceReportController::class, 'pdf']);
            Route::post('/upload', [AdvanceReportController::class, 'upload']);
            Route::get('/upload', [AdvanceReportController::class, 'upload']);
            Route::post('/update', [AdvanceReportController::class, 'update']);
        });

        Route::get('/userphotos/index', [UserPhotoController::class,'index']);

    });
    //смена подразделения
    Route::prefix('/subdivisions')->group(function () {

        Route::middleware('change_subdiv_once')->group(function () {
            Route::post('/process', [SubdivisionController::class, 'process'])->name('subdivisions.process');
            Route::post('/update', [SubdivisionController::class, 'update'])->name('subdivisions.update');
            Route::get('/change', [SubdivisionController::class, 'index'])->name('subdivisions.change');
        });

        Route::get('/swapable/change', [SubdivisionController::class, 'swapableChangePage']);
        Route::post('/swapable/update', [SubdivisionController::class, 'swapableChangeSubdivision']);
        Route::post('/swapable/smspass', [SubdivisionController::class, 'getSmsPassword']);

    });
    //загрузка фотографий
    Route::prefix('photos')->group(function () {
        Route::get('/add/{claim_id}', [PhotoController::class, 'pload_photo'])->name('photos.add');
        Route::post('/upload', [PhotoController::class, 'uploads'])->name('photos.upload');
        Route::post('/update', [PhotoController::class, 'ditPhotos'])->name('photos.update');
        Route::post('/main', [PhotoController::class, 'setMain'])->name('photos.main');
        Route::get('/get/all', [PhotoController::class, 'getPhotos'])->name('photos.get.all');
        Route::get('/view', [PhotoController::class, 'viewPhoto'])->name('photos.view');
    });
    //контрагенты
    Route::prefix('/customers')->group(function () {
        Route::get('/', [CustomersController::class, 'getListView'])->name('customers');
        Route::get('/remove/{id}', [CustomersController::class, 'removeCustomer'])->name('customers.remove');
        Route::get('/remove2/{id}', [CustomersController::class, 'removeCustomer2'])
            ->middleware('admin_only')
            ->name('customers.remove');
        Route::get('/create', [CustomersController::class, 'createCustomer'])->name('customers.create');
        Route::post('/update', [CustomersController::class, 'update'])->name('customers.update');
        Route::get('/edit/{customer_id}/{passport_id}', [CustomersController::class, 'editCustomer'])
            ->name('customers.edit');
        Route::get('cards/{customer_id}', [CardsController::class, 'customerCards'])->name('customers.cards');


    });
    Route::prefix('/promocodes')->group(function () {
        Route::post('/add/manual', [HomeController::class, 'manualAddPromocode2'])
            ->name('promocodes.add.manual');
    });
    //бланки заявлений
    Route::prefix('/blanks')->group(function () {
        Route::get('/', [BlanksController::class, 'blanksList'])->name('blanks');
        Route::get('/customers', [BlanksController::class, 'customersBlanksList'])->name('blanks.customers');
        Route::get('/users', [BlanksController::class, 'usersBlanksList'])->name('blanks.users');
        Route::get('/pdf/{id}', [BlanksController::class, 'createPdf'])->name('blanks.pdf');
    });
    //запросы из 1с
    //TODO: поправить роуты в 1с
    Route::post('loans/promocode/get', [From1cController::class, 'getPromocode']);
    Route::get('loans/promocode/get', [From1cController::class, 'getPromocode']);
    Route::post('debtors/customers/upload', [From1cController::class, 'uploadDebtorsWithEmptyCustomers']);
    Route::prefix('/1c')->group(function () {
        Route::get('/docs/get', [From1cController::class, 'getOrdersAndDocs']);
        Route::post('/docs/get', [From1cController::class, 'getOrdersAndDocs']);
        Route::post('/sms/noloan', [From1cController::class, 'getSmsWithNoLoan']);
        Route::post('/orders/update', [From1cController::class, 'updateOrder']);

        Route::middleware('localCallOnly')->group(function () {
            Route::post('/users/add', [From1cController::class, 'addUserFrom1c'])->name('1c.user.add');
            Route::post('/claims/update', [From1cController::class, 'updateClaim'])->name('1c.claims.update');
            Route::post('/claims/update/status', [From1cController::class, 'updateClaimStatus']);
            Route::post('/seb', [From1cController::class, 'addSEBNumber'])->name('1c.seb');
        });
        Route::prefix('/debtors')->group(function () {
            Route::post('/upload', [From1cController::class, 'uploadSqlFile']);
            Route::post('/info/upload', [From1cController::class, 'uploadCsvFile']);
            Route::post('/upload/updateClientInfo', [From1cController::class, 'updateClientInfo']);
            Route::post('/events/update_id_1c', [From1cController::class, 'setId1cForEvents']);
            Route::post('/changeRespUser', [From1cController::class, 'changeResponsibleUserInDebtor']);
            Route::post('/addRecordToUploadSqlFilesType1', [From1cController::class, 'addRecordToUploadSqlFilesType1']);
            Route::post('/addRecordToUploadSqlFilesType2', [From1cController::class, 'addRecordToUploadSqlFilesType2']);
            Route::post('/upload/clearOtherPhones', [From1cController::class, 'clearOtherPhones']);
        });
    });
    //ордеры
    Route::prefix('/orders')->group(function () {
        Route::get('/', [OrdersController::class, 'getOrders'])->name('orders.list');
        Route::post('/store', [OrdersController::class, 'storeOrder'])->name('orders.store');
        Route::post('/update', [OrdersController::class, 'updateOrder'])->name('orders.update');
        Route::get('/{id}', [OrdersController::class, 'getOrders'])->name('orders.list');
        Route::get('/remove/{id}', [OrdersController::class, 'removeOrder'])
            ->middleware('admin_only')
            ->name('orders.remove');
        Route::get('/pdf/{id}', [OrdersController::class, 'createPDF'])->name('orders.pdf');
        Route::get('/claimforremove/{id}', [OrdersController::class, 'claimForRemove'])->name('orders.claimforremove');

        //ЗАЯВКИ НА ОРДЕРА НА ПОДОТЧЕТ
        Route::get('/issueclaims/index', [IssueClaimController::class, 'index']);
        Route::get('/issueclaims/delete/{id}', [IssueClaimController::class, 'delete'])->middleware('admin_only');
        Route::get('/issueclaims/claimforremove/{id}', [IssueClaimController::class, 'claimForRemove']);
        Route::get('/issueclaims/pdf/{id}', [IssueClaimController::class, 'createPdf']);

    });
    //карты
    Route::prefix('/cards')->group(function () {
        Route::post('/add', [CardsController::class, 'addCard']);
        Route::post('/enable/{card_id}', [CardsController::class, 'enableCard'])
            ->middleware('admin_only')
            ->name('cards.enable');
        Route::post('/disable/{card_id}', [CardsController::class, 'disableCard'])
            ->middleware('admin_only')
            ->name('cards.disable');
    });
    //замена карт
    Route::prefix('/cardchanges')->group(function () {
        Route::get('/', [CardsController::class, 'cardChangesList'])->name('cardchanges');
        Route::post('/add', [CardsController::class, 'addCardChange'])->name('cardchanges.add');
        Route::get('/remove/{id}', [CardsController::class, 'removeCardChange'])
            ->middleware('admin_only')
            ->name('cardchanges.remove');
        Route::get('/claimforremove/{id}', [CardsController::class, 'claimForRemoveCardChange'])
            ->name('cardchanges.claimforremove');
    });
    Route::prefix('/repayments')->group(function () {
        Route::get('/', [RepaymentViewerController::class, 'getTableView'])->name('repayments.table');

        Route::post('/create', [RepaymentController::class, 'create'])->name('repayments.create');
        Route::post('/update', [RepaymentController::class, 'update'])
            ->middleware('admin_only')
            ->name('repayments.update');
        Route::get('/remove/{id}', [RepaymentController::class, 'remove'])
            ->middleware('admin_only')
            ->name('repayments.remove');
        Route::post('peacepays/update', [PeacePaysController::class, 'update'])
            ->middleware('admin_only')
            ->name('peacepays.update');
        Route::post('/payment', [RepaymentController::class, 'addPayment'])->name('repayments.payment');
        Route::post('/claimforremove', [RepaymentController::class, 'claimForRemove'])
            ->name('repayments.claimforremove');
        Route::post('/suzschedule/add', [RepaymentController::class, 'addSuzSchedule']);
        Route::post('/dopcommission/calculate', [RepaymentController::class, 'calculateDopCommissionMoney']);
    });
    //помощь
    Route::prefix('/help')->group(function () {
        Route::get('/', [HelpController::class, 'menu'])->name('help');
        Route::get('/cert', [HelpController::class, 'cert'])->name('help.cert');
        Route::get('/docs', [HelpController::class, 'docs'])->name('help.docs');
        Route::get('/rules', [HelpController::class, 'rules'])->name('help.rules');
        Route::get('/addresses', [HelpController::class, 'addresses']);
        Route::get('/videos', [HelpController::class, 'videos']);
        Route::get('/videos/{id}', [HelpController::class, 'videos']);
        Route::get('/page/{page}', [HelpController::class, 'page']);
        Route::get('/instructions', [HelpController::class, 'instructions']);
    });
    //запросы
    Route::prefix('/usersreqs')->group(function () {
        Route::get('/remove', [UsersRequestsController::class, 'remove'])
            ->middleware('office_only')
            ->name('usersreqs.remove');
        Route::get('/remove/request', [UsersRequestsController::class, 'removeRequest'])
            ->middleware('office_only')
            ->name('usersreqs.remove.request');
        Route::get('/hide/{id}', [UsersRequestsController::class, 'hideRequest'])
            ->middleware('admin_only')
            ->name('usersreqs.hide');

    });
    //НПФ
    Route::prefix('/npf')->group(function () {
        Route::get('/', [NpfController::class, 'getTableView'])->name('npf.table');
        Route::get('/create', [NpfController::class, 'editItem'])->name('npf.create');
        Route::get('/edit', [NpfController::class, 'editItem'])->name('npf.edit');
        Route::get('/remove', [NpfController::class, 'removeItem'])->name('npf.remove');
        Route::get('/claimforremove', [NpfController::class, 'claimForRemoveItem'])->name('npf.claimforremove');
        Route::post('/update', [NpfController::class, 'updateItem'])->name('npf.create');

    });
    //Заявления на материалы
    Route::prefix('/matclaims')->group(function () {
        Route::get('/', [MaterialsClaimsController::class, 'getTableView'])->name('matclaims.table');
        Route::get('/create', [MaterialsClaimsController::class, 'editItem'])->name('matclaims.create');
        Route::get('/edit', [MaterialsClaimsController::class, 'editItem'])->name('matclaims.edit');
        Route::get('/remove', [MaterialsClaimsController::class, 'removeItem'])->name('matclaims.remove');
        Route::get('/claimforremove', [MaterialsClaimsController::class, 'claimForRemoveItem'])
            ->name('matclaims.claimforremove');
        Route::post('/update', [MaterialsClaimsController::class, 'updateItem'])->name('matclaims.create');
    });
    Route::prefix('/worktime')->group(function () {
        Route::get('/index', [WorkTimeController::class, 'getTableView'])->middleware('admin_only');
        Route::get('/edit', [WorkTimeController::class, 'editItem'])->middleware('admin_only');
        Route::get('/today/get', [WorkTimeController::class, 'getTodayItem'])->name('worktime.today.get');
        Route::post('/update2', [WorkTimeController::class, 'update'])->middleware('admin_only');
        Route::post('/update', [WorkTimeController::class, 'updateItem'])->name('worktime.update');
    });
    //сообщения
    Route::prefix('/messages')->group(function () {
        Route::get('/', [MessagesController::class, 'getTableView'])->name('messages.table');
        Route::get('/create', [MessagesController::class, 'editItem'])->name('messages.create');
        Route::get('/edit', [MessagesController::class, 'editItem'])->name('messages.edit');
        Route::get('/remove', [MessagesController::class, 'removeItem'])->name('messages.remove');
        Route::post('/update', [MessagesController::class, 'updateItem'])->name('messages.create');
    });
    //терминал
    Route::prefix('/terminal')->group(function () {
        Route::get('/', [TerminalController::class, 'index'])->name('terminal');
        Route::get('/file', [TerminalController::class, 'file'])->name('terminal.file1');
        Route::get('/fileinfo', [TerminalController::class, 'fileinfo'])->name('terminal.fileinfo1');
        Route::post('/preauth', [TerminalController::class, 'preauth'])->name('terminal.preauth');
        Route::post('/status', [TerminalController::class, 'status'])->name('terminal.status');
        Route::post('/auth', [TerminalController::class, 'auth'])->name('terminal.auth');
        Route::post('/pinauth', [TerminalController::class, 'pinauth'])->name('terminal.pinauth');
        Route::post('/tpitems', [TerminalController::class, 'TPitems'])->name('terminal.tpitems');
        Route::post('/order', [TerminalController::class, 'order'])->name('terminal.order');
        Route::post('/file', [TerminalController::class, 'file'])->name('terminal.file');
        Route::post('/fileinfo', [TerminalController::class, 'fileinfo'])->name('terminal.fileinfo');
        Route::post('/paypoints', [TerminalController::class, 'paypoints'])->name('terminal.paypoints');
        Route::post('/sendaction', [TerminalController::class, 'sendaction'])->name('terminal.sendaction');
        Route::post('/scmd', [TerminalController::class, 'scmd'])->name('terminal.scmd');
        Route::post('/promo', [TerminalController::class, 'promo'])->name('terminal.promo');
    });
    //ОПРОС
    Route::prefix('/quizdept')->group(function () {
        Route::get('/create', [QuizDepartmentController::class, 'create']);
        Route::post('/store', [QuizDepartmentController::class, 'store']);
        Route::get('/report', [QuizDepartmentController::class, 'getReport'])->middleware('admin_only');
    });
    //Кандидаты
    Route::prefix('/candidate')->group(function () {
        Route::get('/index', [CandidateController::class, 'index']);
        Route::get('/create', [CandidateController::class, 'create']);
        Route::post('/insert', [CandidateController::class, 'insertCandidate']);
        Route::get('/delete', [CandidateController::class, 'deleteCandidate']);
        Route::get('/update', [CandidateController::class, 'update']);
        Route::post('/update', [CandidateController::class, 'updateCandidate']);
        Route::get('/excel', [CandidateController::class, 'exportToExcel']);
        Route::post('/excel/report', [CandidateController::class, 'exportToExcelReport'])
            ->name('candidate.excel.report');
        Route::post('/excel/report/city', [CandidateController::class, 'exportToExcelReportCity'])
            ->name('candidate.excel.report.city');
    });
    //СТРАНИЦА ПЕЧАТИ ДОКУМЕНТОВ НА ТРУДОУСТРОЙСТВО
    Route::prefix('/employment')->group(function () {
        Route::get('/docs', [EmploymentDocsController::class,'index']);
        Route::get('/tracknumber', [EmploymentDocsController::class,'addTrackNumber']);
        Route::post('/tracknumber/update', [EmploymentDocsController::class,'updateTrackNumber']);
        Route::post('/docs/signed', [EmploymentDocsController::class,'setEmploymentDocsSigned']);
        Route::post('/user/update', [EmploymentDocsController::class,'setBirthDate']);
        Route::get('/docs/pdf', [EmploymentDocsController::class,'createPdf']);

    });
    //ТЕСТИРОВАНИЕ
    Route::prefix('/usertests')->group(function () {
        Route::get('/home', [UserTestController::class,'home']);
        Route::get('/index', [UserTestController::class,'index']);
        Route::get('/view/{id}', [UserTestController::class,'view']);
        Route::get('/stat/{id}', [UserTestController::class,'stat']);
        Route::get('/view/{test_id}/{question_id}', [UserTestController::class,'view']);
        Route::post('/answer', [UserTestController::class,'answer']);

        //РЕДАКТОР ТЕСТОВ
        Route::prefix('/editor')->group(function () {
            Route::get('/index', [UserTestEditorController::class,'index']);
            Route::get('/create', [UserTestEditorController::class,'create']);
            Route::post('/update', [UserTestEditorController::class,'update']);
            Route::get('/edit/{id}', [UserTestEditorController::class,'edit']);
            Route::get('/remove/{id}', [UserTestEditorController::class,'remove']);

        });


    });


    Route::prefix('/debtors')->group(function () {
        Route::get('/index', [DebtorsController::class,'index']);
        Route::get('/forgotten', [DebtorsController::class,'forgotten']);
        Route::post('/forgotten', [DebtorsController::class,'forgotten']);
        Route::get('/recommends', [DebtorsController::class,'recommends']);
        Route::post('/recommends', [DebtorsController::class,'recommends']);
        Route::get('/calendar', [DebtorsController::class,'calendar']);
        Route::post('/calendar', [DebtorsController::class,'calendar']);
        Route::get('/editSmsCount', [DebtorsController::class,'editSmsCount']);
        Route::post('/editSmsCount', [DebtorsController::class,'editSmsCount']);
        Route::get('/debtorcard/{debtor_id}', [DebtorsController::class,'debtorcard']);
        Route::get('/addevent', [DebtorsController::class,'addevent']);
        Route::post('/addevent', [DebtorsController::class,'addevent']);
        Route::post('/event/update', [DebtorsController::class,'updateDebtorEvent']);
        Route::get('/history/{debtor_id}', [DebtorsController::class,'debtorHistory']);
        Route::get('/debtorcard/createPdf/{doc_id}/{debtor_id}/{date}/{fact_address}', [DebtorsController::class,'createPdf']);
        Route::get('/debtorcard/createPdf/{doc_id}/{debtor_id}/{date}', [DebtorsController::class,'createPdf']);
        Route::get('/courtorder/{debtor_id}',[PdfController::class,'getCourtOrderPdf'])->name('debtor.courtorder');
        Route::get('/contacts/{debtor_id}', [DebtorsController::class,'contacts']);
        Route::get('/report/countcustomers', [DebtorsReportsController::class,'countDebtCustomersForRespUser']);
        Route::post('/peaceclaim/new', [DebtorsController::class,'addNewRepaymentOffer']);
        Route::get('/omicron/gettask', [CronController::class,'getOmicronTask']);
        Route::get('/sms/mass', [DebtorMassSmsController::class,'index']);
        Route::get('/loans/summary/{loan_id}', [DebtorsController::class,'getLoanSummary']);
        Route::post('/getResponsibleUser', [FromDebtorsController::class,'respUserForSellingARM']);
        Route::post('/loans/summary/updateloan', [DebtorsController::class,'updateLoan']);

        Route::post('/photos/main', [FromDebtorsController::class,'setMainPhoto']);
        Route::post('/debt', [FromDebtorsController::class,'getDebt']);
        Route::get('/orders/upload', [FromDebtorsController::class,'uploadOrders']);
        Route::get('/loans/upload', [FromDebtorsController::class,'uploadLoans']);

        Route::post('/events/from1c', [DebtorsFrom1cController::class,'eventFrom1c']);
        Route::post('/loan/closing', [DebtorsFrom1cController::class,'loanClosing']);
        Route::post('/omicron/task', [DebtorsFrom1cController::class,'omicronTask']);

        Route::get('/msg/debtoronsubdivision', [FromSellingARMController::class,'alertDebtorOnSubdivision']);
        Route::get('/onsite', [FromSellingARMController::class,'isDebtorOnSite']);
        Route::post('/event/withoutAccept', [FromSellingARMController::class,'withoutAcceptEvent']);

        Route::get('/setSelfResponsible/{debtor_id}', [DebtorsController::class,'setSelfResponsible']);
        Route::get('/temporary/cron/handle', [DebtorsController::class,'temporaryCronTasksHandling']);



        Route::get('/emails/list/{user_id}',[EmailController::class,'index']);
        Route::post('/email/send',[EmailController::class,'sendEmail'])->name('email.send');

        Route::prefix('/export')->group(function () {
            Route::get('/', [DebtorExportController::class,'exportInExcelDebtors']);
            Route::get('/events',[DebtorExportController::class,'exportEvents']);
            Route::get('/forgotten', [DebtorExportController::class,'exportForgotten']);
            Route::get('/postregistry', [DebtorsController::class,'exportPostRegistry']);
        });

        Route::prefix('/courts')->group(function () {
            Route::get('/index', [DebtorsNoticesController::class,'courtNotices']);
            Route::get('/start', [DebtorsNoticesController::class,'startCourtTask']);
            Route::get('getFile/{type}/{task_id}', [DebtorsNoticesController::class,'getCourtFile']);
        });

        Route::prefix('/notices')->group(function () {
            Route::get('/index', [DebtorsNoticesController::class,'index']);
            Route::get('/start', [DebtorsNoticesController::class,'startTask']);
            Route::get('/getFile/{type}/{task_id}', [DebtorsNoticesController::class,'getFile']);
            Route::get('/getFile/{type}/{task_id}', [DebtorsNoticesController::class,'getFile']);
        });
        Route::prefix('/recurrent')->group(function () {
            Route::get('/query', [DebtorsController::class,'sentRecurrentQuery']);
            Route::get('/massquery', [DebtorsController::class,'massRecurrentQuery']);
            Route::post('/massquery', [DebtorsController::class,'massRecurrentQuery']);
            Route::get('/massquerytask', [DebtorsController::class,'massRecurrentTask']);
            Route::post('/massquerytask', [DebtorsController::class,'massRecurrentTask']);
            Route::post('/getstatus', [DebtorsController::class,'getMassRecurrentStatus']);
        });
        Route::prefix('/reports')->group(function () {
            Route::get('/plancalend', [DebtorsReportsController::class,'planCalend']);
            Route::get('/ovz', [DebtorsReportsController::class,'ovz']);
            Route::get('/jobsdoneact', [DebtorsReportsController::class,'jobsDoneAct']);
            Route::get('/dzcollect', [DebtorsReportsController::class,'dzcollect']);
            Route::get('/loginlog', [DebtorsReportsController::class,'exportToExcelDebtorsLoginLog']);
        });
        Route::prefix('/transfer')->group(function () {
            Route::get('/index', [DebtorTransferController::class,'index']);
            Route::get('/history', [DebtorTransferController::class,'transferHistory']);
        });

    });

});

//ИНФИНИТИ
Route::post('/debtors/infinity/incomingCall', [InfinityController::class,'incomingCall']);
Route::post('/debtors/infinity/closingModals', [InfinityController::class,'closingModals']);
Route::prefix('/infinity')->group(function () {

    Route::get('infinity/income', [InfinityController::class,'incoming']);
    Route::post('/callbacks/{item}/{callback}', [InfinityController::class,'callbacks']);
    Route::get('/callbacks/{item}/{callback}', [InfinityController::class,'callbacks']);
    Route::get('/losscall', [InfinityController::class,'fromInfinityLossCalls']);
    Route::get('/is_debtor_time', [InfinityController::class,'getDebtorTimeByPhoneWithRequest']);
    Route::get('/is_debtor_time/{telephone}', [InfinityController::class,'getDebtorTimeByPhone']);
    Route::get('/is_debtor_operator/{telephone}', [InfinityController::class,'getUserInfinityIdByDebtorPhone']);
});

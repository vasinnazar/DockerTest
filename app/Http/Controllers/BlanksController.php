<?php

namespace App\Http\Controllers;

use \App\ContractForm,
    mikehaertl\wkhtmlto\Pdf,
    Storage;
use Config;

/**
 * контроллер для бланков заявлений
 */
class BlanksController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function blanksList() {
        $user_blanks = ContractForm::where('text_id', config('options.blank_user'))->select('name', 'text_id', 'id', 'description')->get();
        $customer_blanks = ContractForm::where('text_id', config('options.blank_customer'))->select('name', 'text_id', 'id', 'description')->get();
        return view('blanks.blanks')->with('user_blanks', $user_blanks)->with('customer_blanks', $customer_blanks);
    }

    /**
     * показывает список бланков заявлений для специалистов
     * @return type
     */
    public function usersBlanksList() {
        $blanks = ContractForm::where('text_id', config('options.blank_user'))->select('name', 'text_id', 'id', 'description')->get();
        return view('blanks.blanks')->with('blanks', $blanks)->with('header', 'Заявления для сотрудников');
    }

    /**
     * показывает список бланков заявлений для клиентов
     * @return type
     */
    public function customersBlanksList() {
        $blanks = ContractForm::where('text_id', config('options.blank_customer'))->select('name', 'text_id', 'id', 'description')->get();
        return view('blanks.blanks')->with('blanks', $blanks)->with('header', 'Заявления для клиентов');
    }

    /**
     * показывает пдф для бланка с переданным идентификатором
     * @param type $id
     * @return type
     */
    public function createPdf($id) {
        $contract = ContractForm::where('id', (int) $id)->firstOrFail();
        if (!is_null($contract->tplFileName) && mb_strlen($contract->tplFileName) > 0) {
            if (is_file(\App\Utils\FileToPdfUtil::getPathToTpl() . $contract->tplFileName)) {
                return \App\Utils\FileToPdfUtil::replaceKeysAndPrint($contract->tplFileName, Config::get('vars'));
            }
        }
        $html = $contract->template;
        $html = ContractEditorController::replaceConfigVars($html);
        $html = ContractEditorController::clearTags($html);
        
        return \App\Utils\PdfUtil::getPdf($html);
    }

}

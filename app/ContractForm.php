<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\ContractVersion;

class ContractForm extends Model {

    protected $table = 'contracts_forms';
    protected $fillable = ['name', 'template', 'text_id', 'tplFileName', 'description'];

    /**
     * 
     * @return \App\ContractForm
     */
    public function getLastVersion($date = null) {
        if (is_null($date)) {
            $version = ContractVersion::where('contract_form_id', $this->id)->orderBy('date', 'desc')->first();
        } else {
            $version = ContractVersion::where('contract_form_id', $this->id)->where('date', '<', $date)->orderBy('date', 'desc')->first();
        }
        if (is_null($version)) {
            return $this;
        } else {
            return $version->newContractForm;
        }
    }

    /**
     * 
     * @param type $id
     * @return \App\ContractForm
     */
    static function getLastVersionByIdAndDate($id, $date) {
        $version = ContractVersion::where('contract_form_id', $id)->where('date', '>', $date)->first();
        if (is_null($version)) {
            return ContractForm::find($id);
        } else {
            return $version->newContractForm;
        }
    }

    /**
     * Возвращает id печатной формы по текстовому идентификатору
     * @param string $text_id
     * @return string | boolean
     */
    static function getContractIdByTextId($text_id) {
        if (mb_strlen($text_id)) {
            $contractForm = ContractForm::where('text_id', $text_id)->first();
            if (!is_null($contractForm)) {
                return $contractForm->id;
            }
        }

        return false;
    }

}

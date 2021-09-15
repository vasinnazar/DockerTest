<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContractVersion extends BasicModel {

    protected $table = 'contract_versions';
    protected $fillable = ['contract_form_id', 'date', 'new_contract_form_id'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'date'];

    public function contractForm() {
        return $this->belongsTo('\App\ContractForm', 'contract_form_id');
    }

    public function newContractForm() {
        return $this->belongsTo('\App\ContractForm', 'new_contract_form_id');
    }

}

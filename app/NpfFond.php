<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NpfFond extends BasicModel {

    protected $table = 'npf_fonds';
    protected $fillable = ['name', 'contract_form_id', 'pd_agreement_id', 'claim_from_npf_id', 'claim_from_pfr_id', 'anketa_id', 'id_1c'];

}

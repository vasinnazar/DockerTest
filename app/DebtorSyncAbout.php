<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DebtorSyncAbout extends Model
{
    protected $table = 'debtor_sync_about';

    protected $fillable = [
        'debtor_id_1c',
        'customer_id_1c',
        'telephone',
        'telephonehome',
        'telephoneorganiz',
        'telephonerodstv',
        'anothertelephone',
        'zip',
        'address_region',
        'address_district',
        'address_city',
        'address_street',
        'address_house',
        'address_building',
        'address_apartment',
        'address_city1',
        'fact_zip',
        'fact_address_region',
        'fact_address_district',
        'fact_address_city',
        'fact_address_street',
        'fact_address_house',
        'fact_address_building',
        'fact_address_apartment',
        'fact_address_city1',
        'file_id',
        'deleted_at',
    ];
}

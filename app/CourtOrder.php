<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CourtOrder extends Model
{
    const TEXT_ID_FORM_ORDER = 'court_order';
    protected $connection='debtors1';
    protected $fillable=[
      'debtor_id',
      'is_printed',
    ];

    public function debtor()
    {
        return $this->belongsTo('App\Debtor');
    }
}

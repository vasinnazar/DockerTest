<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CardChange extends BasicModel {

    protected $table = 'card_changes';
    protected $fillable = ['old_card_number', 'new_card_number', 'customer_id'];

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BasicModel extends Model {
    use SoftDeletes;
    public function claimForRemove(){
        $this->claimed_for_remove = Carbon::now()->format('Y-m-d H:i:s');
        return $this->save();
    }

}

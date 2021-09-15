<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PermissionUser extends Model
{
    protected $fillable = [
        'user_id',
        'permission_id',
        'valid_until'
    ];

    public $timestamps = false;

    protected $table = 'permission_user';

    protected $with = [
        'permission'
    ];

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}

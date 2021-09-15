<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubdivisionStore extends Model
{
    protected $table = 'subdivision_stores';
    protected $fillable = ['name','id_1c','full_name'];
    
    static function uploadFrom1c() {
        $res = MySoap::sendExchangeArm(MySoap::createXML(['type' => 'GetStoreList']), false);
        if ((int) $res->result === 0) {
            return false;
        }
        foreach ($res->items->children() as $item) {
            $nom = SubdivisionStore::where('id_1c', (string) $item->id_1c)->first();
            if (is_null($nom)) {
                $nom = new SubdivisionStore();
            }
            $nom->name = (string) $item->name;
            $nom->full_name = (string) $item->full_name;
            $nom->id_1c = (string) $item->id_1c;
            $nom->save();
        }
        return true;
    }
}

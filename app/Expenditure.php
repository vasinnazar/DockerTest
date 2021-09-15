<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Expenditure extends Model {

    protected $table = 'expenditures';
    protected $fillable = ['id_1c', 'name', 'full_name'];

    static function uploadFrom1c() {
        $res = MySoap::sendExchangeArm(MySoap::createXML(['type' => 'GetExpenditureList']), false);
        if ((int) $res->result === 0) {
            return false;
        }
        foreach ($res->items->children() as $item) {
            $nom = Expenditure::where('id_1c', (string) $item->id_1c)->first();
            if (is_null($nom)) {
                $nom = new Expenditure();
            }
            $nom->name = (string) $item->name;
            $nom->full_name = (string) $item->full_name;
            $nom->id_1c = (string) $item->id_1c;
            $nom->save();
        }
        return true;
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class Nomenclature extends Model {
    
    const TYPE_GOODS = 0;
    const TYPE_OTHER = 1;
    
    protected $table = 'nomenclatures';
    protected $fillable = ['name', 'full_name', 'id_1c'];
    
    /**
     * загружает список номенклатуры из 1с
     * @return boolean
     */
    static function uploadFrom1c() {
        $res = MySoap::sendExchangeArm(MySoap::createXML(['type' => 'GetNomenclatureList']), false);
        if ((int) $res->result === 0) {
            return false;
        }
        foreach ($res->goods->children() as $item) {
            $nom = Nomenclature::where('id_1c', (string) $item->id_1c)->first();
            if (is_null($nom)) {
                $nom = new Nomenclature();
            }
            $nom->name = (string) $item->name;
            $nom->full_name = (string) $item->full_name;
            $nom->id_1c = (string) $item->id_1c;
            $nom->type = Nomenclature::TYPE_GOODS;
            $nom->save();
        }
        foreach ($res->other->children() as $item) {
            $nom = Nomenclature::where('id_1c', (string) $item->id_1c)->first();
            if (is_null($nom)) {
                $nom = new Nomenclature();
            }
            $nom->name = (string) $item->name;
            $nom->full_name = (string) $item->full_name;
            $nom->id_1c = (string) $item->id_1c;
            $nom->type = Nomenclature::TYPE_OTHER;
            $nom->save();
        }
        return true;
    }

    public function saveThrough1c() {
        $res1c = MySoap::sendExchangeArm(MySoap::createXML([
                            'type' => 'AddNomenclature',
                            'name' => $this->name,
                            'full_name' => $this->full_name
        ]));
        if(!isset($res1c->result) || (int)$res1c->result===0){
            return false;
        } else {
            $this->id_1c = (string)$res1c->result;
            return $this->save();
        }
    }

}

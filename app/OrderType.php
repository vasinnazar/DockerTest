<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderType extends Model {

    protected $fillable = ['text_id', 'name', 'plus', 'invoice'];
    protected $table = 'order_types';
    
    const RKO = 'RKO';
    const PKO = 'PKO';
    const CARD = 'CARD';
    /**
     * Оплата труда
     */
    const SALARY = 'SALARY';
    /**
     * Оплата труда по 91 счету (уже нельзя создавать)
     */
    const SALARY91 = 'SALARY91';
    /**
     * Выдача в подотчет
     */
    const PODOTCHET = 'PODOTCHET';
    /**
     * Расход (внутренние перемещения)
     */
    const RASHOD = 'RASHOD';
    /**
     * Канцтовары
     */
    const CANC = 'CANC';
    /**
     * Инкассация
     */
    const INCASS = 'INCASS';
    /**
     * Комиссия банка
     */
    const COMIS = 'COMIS';
    /**
     * Возврат подотчетных средст
     */
    const VOZVRAT = 'VOZVRAT';
    /**
     * Самоинкассация СБЕРБАНК
     */
    const SBERINCASS = 'SBERINCASS';
    /**
     * Самоинкасация УРАЛСИБ
     */
    const URALINCASS = 'URALINCASS';
    /**
     * Приход (внутренние перемещения)
     */
    const VPKO = 'VPKO';
    /**
     * Почтовые расходы
     */
    const POCHTA = 'POCHTA';
    /**
     * Возврат недостачи
     */
    const DEFICIT = 'DEFICIT';
    const DEFICITRKO = 'DEFICITRKO';
    /**
     * Излишки
     */
    const OVERAGE = 'OVERAGE';
    /**
     * Прочий приход
     */
    const OPKO = 'OPKO';
    const QIWI = 'QIWI';
    const TPKO = 'TPKO';
    const BANK1 = 'BANK1';
    const INTERNET = 'INTERNET';
    const HOZRASHOD = 'HOZRASHOD';
    const BUYEQUIP = 'BUYEQUIP';
    const COMRASHOD = 'COMRASHOD';

    static function getRKOid() {
        $type = OrderType::where('text_id', 'RKO')->first();
        return (!is_null($type) ? $type->id : null);
    }
    static function getPKOid() {
        $type = OrderType::where('text_id', 'PKO')->first();
        return (!is_null($type) ? $type->id : null);
    }
    static function getCARDid() {
        $type = OrderType::where('text_id', 'CARD')->first();
        return (!is_null($type) ? $type->id : null);
    }
    static function getSALARYid() {
        $type = OrderType::where('text_id', 'SALARY')->first();
        return (!is_null($type) ? $type->id : null);
    }
    static function getIdByTextId($text_id){
        $type = OrderType::where('text_id', $text_id)->first();
        return (!is_null($type) ? $type->id : null);
    }

}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AddressDouble extends Model {
    protected $connection = 'debtors215';
    protected $table = 'address_doubles';
    protected $fillable = ['debtor_fio', 'debtor_address', 'debtor_telephone',
        'debtor_overdue', 'customer_fio', 'customer_address', 'customer_telephone',
        'comment', 'date', 'responsible_user_id_1c', 'is_debtor', 'created_at', 'updated_at'];
    /**
     * Набор полей для поиска по дублям адресов и их настройки
     * @return array
     */
    public static function getSearchFields(){
        return [
            [
                'name' => 'address_doubles@debtor_fio',
                'input_type' => 'text',
                'label' => 'ФИО должника'
            ],
            [
                'name' => 'address_doubles@debtor_address',
                'input_type' => 'text',
                'label' => 'Адрес должника'
            ],
            [
                'name' => 'address_doubles@debtor_telephone',
                'input_type' => 'text',
                'label' => 'Телефон должника'
            ],
            [
                'name' => 'address_doubles@debtor_overdue',
                'input_type' => 'text',
                'label' => 'Дней просрочки'
            ],
            [
                'name' => 'address_doubles@customer_fio',
                'input_type' => 'text',
                'label' => 'ФИО контрагента'
            ],
            [
                'name' => 'address_doubles@customer_address',
                'input_type' => 'text',
                'label' => 'Адрес контрагента'
            ],
            [
                'name' => 'address_doubles@customer_telephone',
                'input_type' => 'text',
                'label' => 'Телефон контрагента'
            ],
            [
                'name' => 'address_doubles@comment',
                'input_type' => 'text',
                'label' => 'Комментарий'
            ],
            [
                'name' => 'address_doubles@date',
                'input_type' => 'date',
                'label' => 'Дата'
            ],
            [
                'name' => 'address_doubles@responsible_user_id_1c',
                'input_type' => 'text',
                'label' => 'Ответственный'
            ],
        ];
    }
}

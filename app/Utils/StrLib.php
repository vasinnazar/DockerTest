<?php
namespace App\Utils;

class StrLib {
    static $ERR = 'Ошибка!';
    const ERR = 'Ошибка!';
    static $ERR_1C = 'Ошибка связи с 1С!';
    const ERR_1C = 'Ошибка связи с 1С!';
    static $ERR_NULL = 'Ошибка! Объект не найден!';
    const ERR_NULL = 'Ошибка! Объект не найден!';
    static $ERR_NO_PARAMS = 'Ошибка! Переданы не все обязательные параметры!';
    const ERR_NO_PARAMS = 'Ошибка! Переданы не все обязательные параметры!';
    static $ERR_CLAIMED = 'Ошибка! Объект уже был помечен на удаление';
    const ERR_CLAIMED = 'Ошибка! Объект уже был помечен на удаление';
    static $ERR_CANT_DELETE = 'Ошибка! Не удалось удалить';
    const ERR_CANT_DELETE = 'Ошибка! Не удалось удалить';
    static $ERR_NO_CUSTOMER = ' Контрагент не найден.';
    const ERR_NO_CUSTOMER = ' Контрагент не найден.';
    static $ERR_NO_USER = ' Пользователь не найден.';
    const ERR_NO_USER = ' Пользователь не найден.';
    static $ERR_NO_SUBDIV = ' Подразделение не найдено.';
    const ERR_NO_SUBDIV = ' Подразделение не найдено.';
    static $ERR_DUPLICATE = ' Такой объект уже существует.';
    const ERR_DUPLICATE = ' Такой объект уже существует.';
    static $ERR_DUPLICATE_PASSPORT = ' Контрагент с такими паспортными данным уже существует.';
    const ERR_DUPLICATE_PASSPORT = ' Контрагент с такими паспортными данным уже существует.';
    const ERR_HAS_CLAIM = 'Ошибка! На контрагента есть заявка';
    const ERR_VALID_FORM = 'Ошибка! Форма заполнена неверно';
    const ERR_HAS_LOAN = 'У клиента есть невыплаченный займ';
    const ERR_HAS_CLAIM_TODAY = 'На сегодня уже была заявка для данного контрагента!';
    const ERR_CLAIM_HAS_LOAN = 'Займ для этой заявки уже был оформлен';
    const ERR_CARD_EXISTS = 'Ошибка! Карта уже активирована на другого клиента!';
    const ERR_CARD = 'Ошибка при оформлении карты!';
    const ERR_DUPLICATE_1C = 'Объект уже существует в 1С';
    const ERR_LOAN_HAS_REPS = 'Ошибка! На договоре уже есть дополнительные документы.';
    const ERR_NOT_ADMIN = 'Ошибка! Недостаточно прав!';
    const ERR_TIME_GT = 'Ошибка! Срок превышает допустимый!';
    const ERR_MONEY_GT = 'Ошибка! Сумма превышает допустимую!';
    const ERR_NO_PAY = 'Ошибка! Платеж не зачислен';


    static $SUC = 'Готово!';
    const SUC = 'Готово!';
    static $SUC_SAVED = 'Сохранено!';
    const SUC_SAVED = 'Сохранено!';
    
    const CLASS_ERR = 'alert-danger';
    const CLASS_SUC = 'alert-success';
}

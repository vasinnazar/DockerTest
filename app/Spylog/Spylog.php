<?php

namespace App\Spylog;

use App\Spylog\SpylogModel,
    Auth,
    Carbon\Carbon,
    App\Spylog\LogDataModel;

class Spylog {

    /**
     * идентификатор пользователя 1С в БД, вставляется в логи если запрос пришел от 1с
     */
    const USERS_ID_1C = 18;
    const ACTION_OPEN = 0;
    const ACTION_UPDATE = 1;
    const ACTION_DELETE = 2;
    const ACTION_CREATE = 3;
    const ACTION_MARK4REMOVE = 4;
    const ACTION_STATUS_CHANGE = 5;
    const ACTION_NEW = 6;
    const ACTION_LOGIN = 7;
    const ACTION_LOGOUT = 8;
    const ACTION_SUBDIV_CHANGE = 9;
    const ACTION_ID1C_CHANGE = 10;
    const ACTION_ENROLLED = 11;
    const ACTION_PAYMENT = 12;
    const ACTION_LASTLOGIN_REFRESH = 13;
    const ACTION_CLAIM_FOR_REMOVE = 14;
    const ACTION_CALL1C = 15;
    const ACTION_SYNC_CASHBOOK = 16;
    const ACTION_ERROR = 17;
    const ACTION_CALC_ERROR = 18;
    const ACTION_TERMINAL_AUTH = 19;
    const ACTION_TERMINAL_PROMO = 20;
    const ACTION_TERMINAL_FILEINFO = 21;
    const ACTION_TERMINAL_ORDER = 22;
    const ACTION_TERMINAL_REFILL = 23;
    const ACTION_TERMINAL_INCASS = 24;
    const ACTION_TERMINAL_CASHOUT = 25;
    const ACTION_TERMINAL_CASHIN = 26;
    const ACTION_ERROR_ARM = 27;
    const ACTION_GRANT_ROLE = 28;
    const ACTION_GRANT_PERMISSION = 29;
    const ACTION_PRINT = 30;
    const TABLE_CLAIMS = 0;
    const TABLE_LOANS = 2;
    const TABLE_CUSTOMERS = 3;
    const TABLE_USERS = 4;
    const TABLE_PASSPORTS = 5;
    const TABLE_ABOUT_CLIENTS = 6;
    const TABLE_NPF_FONDS = 7;
    const TABLE_NPF_CONTRACTS = 9;
    const TABLE_CARDS = 10;
    const TABLE_CONDITIONS = 11;
    const TABLE_CONTRACTS_FORMS = 12;
    const TABLE_DAILY_CASH_REPORTS = 13;
    const TABLE_LOANTYPES = 14;
    const TABLE_LOANTYPES_CONDITIONS = 15;
    const TABLE_ORDERS = 16;
    const TABLE_PEACE_PAYS = 17;
    const TABLE_PHOTOS = 18;
    const TABLE_PROMOCODES = 19;
    const TABLE_REMOVE_REQUESTS = 20;
    const TABLE_REPAYMENT_TYPES = 21;
    const TABLE_REPAYMENTS = 22;
    const TABLE_SUBDIVISIONS = 23;
    const TABLE_TERMINAL_ACTIONS = 24;
    const TABLE_TERMINALS = 25;
    const TABLE_TERMINAL_COMMANDS = 26;
    const TABLE_ROLES = 27;
    const TABLE_PERMISSIONS = 28;

    static $tablesNames = ['claims', '', 'loans', 'customers', 'users', 'passports', 'about_clients', 'npf_fonds', '',
        'npf_contracts', 'cards', 'conditions', 'contracts_forms', 'daily_cash_reports',
        'loantypes', 'loantypes_conditions', 'orders', 'peace_pays', 'photos', 'promocodes', 'remove_requests',
        'repayment_types', 'repayments', 'subdivisions', 'terminal_actions', 'terminals','terminal_commands','roles','permissions'];
    static $actionsNames = ['Открытие', 'Редактирование', 'Удаление', 'Создание',
        'Помечено на удаление', 'Смена статуса', 'Открыта форма', 'Вход', 'Выход',
        'Смена подразделения', 'Смена Кода в 1С', 'Зачисление', 'Платёж', 'Обнов. времени входа',
        'Запрос на удаление', 'Запрос в 1С', 'Синхр. кассовой книги', 'Ошибка 1С', 'Ошибка расчета',
        'Терминал-Авторизация', 'Терминал-Запрос промокода', 'Терминал-Файл', 'Терминал-Заявка', 'Терминал-Пополнение', 'Терминал-Инкассация', 'Терминал-Снятие', "Терминал-Внесение",
        'Ошибка АРМ','Добавление роли','Добавление права','Печать'];
    public $table;
    public $id;
    public $data = [];
    public $action;

    public function __construct() {
        
    }

    /**
     * сохраняет лог в базу
     * @param int $action действие
     * @param string $table таблица
     * @param int $id  идентификатор записи в таблице над которой совершено действие
     * @return type
     */
    public function save($action, $table = null, $id = null, $user_id=null) {
//        return true;
        return Spylog::log($action, $table, $id, json_encode($this->data), $user_id);
    }

    /**
     * добавляет лог
     * @param int $action действие
     * @param string $table таблица
     * @param int $id идентификатор записи в таблице над которой совершено действие
     * @param string $data доп.данные
     * @return type
     */
    static public function log($action, $table = null, $id = null, $data = null, $user_id=null) {
        if(config('app.version_type')=='debtors'){
            return;
        }
//        return true;
        $model = new SpylogModel();
        if (!is_null($table)) {
            if (!is_int($table)) {
                $model->table_id = array_search($table, Spylog::$tablesNames);
            } else {
                $model->table_id = $table;
            }
        }
        if (!is_null($id)) {
            $model->doc_id = $id;
        }
        if (!is_null($data)) {
            $logdata = new LogDataModel();
            $logdata->data = $data;
            if ($logdata->save()) {
                $model->data_id = $logdata->id;
            }
        }
        if(!is_null($user_id)){
            $model->user_id = $user_id;
        } else {
            $user = (!is_null(Auth::user())) ? Auth::user() : \App\User::find(Spylog::USERS_ID_1C);
            $model->user_id = (!is_null($user)) ? $user->id : NULL;
        }
        $model->action = $action;
        
        return $model->save();
    }

    /**
     * добавить данные для лога с редактированием модели. ипользуется при добавлении лога с многочиленными изменениями в разных таблицах
     * @param type $table
     * @param type $model
     * @param type $input
     */
    public function addModelChangeData($table, $model, $input) {
        $id = null;
        $action = Spylog::ACTION_UPDATE;
        $data = ['before' => '', 'after' => ''];
        if (!is_null($model) && !is_array($model)) {
            $model = $model->toArray();
        }
        if (!is_null($model) && array_key_exists('id', $model) && !is_null($model['id'])) {
            $id = $model['id'];
            $data['before'] = $model;
            $action = Spylog::ACTION_UPDATE;
            foreach ($input as $key => $value) {
                if (array_key_exists($key, $model)) {
                    $model[$key] = $value;
                }
            }
            $model['updated_at'] = with(Carbon::now())->format('Y-m-d H:i:s');
            $data['after'] = $model;
            if ($data['before'] != $data['after']) {
                if (!array_key_exists($table, $this->data)) {
                    $this->data[$table] = [];
                }
            }
        } else {
            $action = Spylog::ACTION_CREATE;
            $model = $input;
            if (!is_null($model)) {
                foreach ($input as $key => $value) {
                    if (array_key_exists($key, $model)) {
                        $model[$key] = $value;
                    }
                }
            }
            $data = $model;
            $this->data[$table] = [];
        }
        if (array_key_exists($table, $this->data)) {
            $this->data[$table]['action'] = $action;
            $this->data[$table]['data'] = $data;
        }
    }

    /**
     * добавляет данные для лога
     * @param type $table
     * @param type $model
     */
    public function addModelData($table, $model) {
        if (!array_key_exists($table, $this->data)) {
            $this->data[$table] = [];
        }
        $this->data[$table]['action'] = Spylog::ACTION_CREATE;
        $this->data[$table]['data'] = (is_array($model)) ? $model : $model->toArray();
    }

    /**
     * добавляет лог с редактированием модели
     * @param type $table
     * @param type $model
     * @param type $input
     */
    static public function logModelChange($table, $model, $input) {
        $spylog = new Spylog();
        $spylog->addModelChangeData($table, $model, $input);
        $id = null;
        $action = Spylog::ACTION_UPDATE;
        if (is_array($model)) {
            if (array_key_exists('id', $model)) {
                $id = $model['id'];
            }
        } else if (!is_null($model->id)) {
            $id = $model->id;
        } else if (is_array($input)) {
            if (array_key_exists('id', $input)) {
                $id = $input['id'];
                $action = Spylog::ACTION_CREATE;
            }
        } else if (!is_null($input->id)) {
            $id = $input->id;
            $action = Spylog::ACTION_CREATE;
        }
        $spylog->save($action, $table, $id);
    }

    /**
     * добавляет лог с действием над объектом типа удаления\создания  и тд
     * @param int $action действие
     * @param string $table таблица
     * @param Model $model модель
     */
    static public function logModelAction($action, $table, $model) {
        $spylog = new Spylog();
        $logdata = new LogDataModel();
        if ($action == Spylog::ACTION_CREATE) {
            return Spylog::logModelSave($table, $model);
            if (is_array($model)) {
                $data = $model;
                if (array_key_exists('id', $model)) {
                    $id = $model['id'];
                } else {
                    $id = null;
                }
            } else {
                $data = $model->toArray();
                $id = $model->id;
            }
        } else if ($action == Spylog::ACTION_DELETE) {
            return Spylog::logModelDelete($table, $model);
        } else {
            $data = [];
            if (!array_key_exists($table, $spylog->data)) {
                $data[$table] = [];
            }
            $data[$table]['action'] = $action;
            if (is_array($model)) {
                if (array_key_exists('id', $model)) {
                    $id = $model['id'];
                } else {
                    $id = null;
                }
                $data[$table]['data'] = $model;
            } else {
                $id = $model->id;
                $data[$table]['data'] = $model->toArray();
            }
        }
        $logdata->data = json_encode($data);
        if ($logdata->save()) {
            $spylog->data_id = $logdata->id;
        }
        $spylog->save($action, $table, $id);
    }

    /**
     * возвращает название действия в логе
     * @param type $id
     * @return type
     */
    static public function getActionName($id) {
        return ($id >= 0 && $id < count(Spylog::$actionsNames)) ? Spylog::$actionsNames[$id] : $id;
    }

    static public function getTableName($id) {
        return (is_int($id) && $id >= 0 && $id < count(Spylog::$tablesNames)) ? Spylog::$tablesNames[$id] : $id;
    }

    /**
     * возвращает массив названий действий
     * @return type
     */
    static public function getActionsList() {
        return Spylog::$actionsNames;
    }

    static public function logModelSave($table, $model) {
        $id = null;
        if (is_array($model)) {
            $data = $model;
            if (array_key_exists('id', $model)) {
                $id = $model['id'];
            } else {
                $id = null;
            }
        } else {
            if(is_object($model)){
                $data = $model->toArray();
                $id = $model->id;
            } else {
                $data = [];
                $id = null;
            }
        }
        Spylog::log(Spylog::ACTION_CREATE, $table, $id, json_encode($data));
    }

    static public function logModelDelete($table, $model) {
        $id = null;
        if (is_array($model)) {
            $data = $model;
            if (array_key_exists('id', $model)) {
                $id = $model['id'];
            } else {
                $id = null;
            }
        } else {
            $data = $model->toArray();
            $id = $model->id;
        }
        Spylog::log(Spylog::ACTION_DELETE, $table, $id, json_encode($data));
    }

    static public function logError($data, $armError = false) {
        Spylog::log((!$armError) ? Spylog::ACTION_ERROR : Spylog::ACTION_ERROR_ARM, null, null, $data);
    }

}

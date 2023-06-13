<?php
return [
    'limit_per_day' => [
        'id' => 1,
        'message' => 'Превышен лимит за день',
        'code' => 400
    ],
    'limit_per_week' => [
        'id' => 2,
        'message' => 'Превышен лимит за неделю',
        'code' => 400
    ],
    'limit_per_month' => [
        'id' => 3,
        'message' => 'Превышен лимит за месяц',
        'code' => 400
    ],
    'synchronize_exception'=> [
        'id' => 3,
        'message' => 'Ошибка синхронизации должника',
        'code' => 400
    ],
];

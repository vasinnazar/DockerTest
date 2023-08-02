<table class='table table-bordered table-condensed'>
    <thead>
    <tr>
        <th>ФИО</th>
        <th>Договор</th>
        <th>База</th>
        <th>Телефон</th>
        <th>Группа долга</th>
        <th>Ответственный</th>
        <th>Подразделение</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @php
        $valuesElementTable = [
            [
                'equal_telephone',
                'мобильного телефона'
            ],
            [
                'equal_telephonehome',
                'домашнего телефона'
            ],
            [
                'equal_telephoneorganiz',
                'телефона организации'
            ],
            [
                'equal_telephonerodstv',
                'телефона родственников'
            ],
            [
                'equal_anothertelephone',
                'другого телефона'
            ],
            [
                'equal_addresses_fact_to_register',
                'адреса проживания с адресом прописки'
            ],
            [
                'equal_addresses_fact_to_fact',
                'адресов проживания'
            ],
            [
                'equal_addresses_register_to_register',
                'адресов прописки'
            ],
            [
                'equal_addresses_register_to_fact',
                'адреса прописки с адресом проживания'
            ]
        ];
    @endphp
    @if (!$collectContacts->count())
        <tr>
            <td colspan="8" style="background: #5bc0de;">
                Совпадений не найдено.
            </td>
        </tr>
    @else
        @foreach ($valuesElementTable as $valueElementTable)
            @include('debtocard.search-contacts.element-table',
            [
                'type_contact' => array_shift($valueElementTable),
                'template_text' => end($valueElementTable)
            ])
        @endforeach
    @endif
    </tbody>
</table>
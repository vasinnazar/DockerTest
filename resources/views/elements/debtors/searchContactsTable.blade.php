<table class='table table-bordered table-condensed'>
    <thead>
    <tr>
        <th>ФИО</th>
        <th>Договор</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @if (!$collectContacts->count())
        <tr>
            <td>
                Совпадений не найдено.
            </td>
        </tr>
    @endif

    @if ($collectContacts->get('equal_phones', false))
        <tr>
            <td colspan="3" style="background: #5bc0de;">Совпадение телефонов</td>
        </tr>
        @if (!$collectContacts->get('equal_phones')->count())
            <tr>
                <td colspan="3">Совпадений по телефону не найдено.</td>
            </tr>
        @else
            @foreach($collectContacts->get('equal_phones') as $contactDebtor)
                <tr>
                    <td>{{ $contactDebtor->passport()->first()->fio }}</td>
                    <td>{{ $contactDebtor->loan_id_1c }}</td>
                    <td>
                        <a href="/debtors/debtorcard/{{ $contactDebtor->id }}?finded_by={{ $debtor->id }}" class="btn btn-default btn-xs" size="xs" target="_blank">
                            <span class="glyphicon glyphicon-eye-open"></span>
                        </a>
                    </td>
                </tr>
            @endforeach
        @endif
    @endif

    @if ($collectContacts->get('equal_addresses_register_to_register', false))
        <tr>
            <td colspan="3" style="background: #5bc0de;">Совпадение адресов прописки</td>
        </tr>
        @if (!$collectContacts->get('equal_addresses_register_to_register')->count())
            <tr>
                <td colspan="3">Совпадений по адресу прописки не найдено.</td>
            </tr>
        @else
            @foreach($collectContacts->get('equal_addresses_register_to_register') as $contactDebtor)
                <tr>
                    <td>{{ $contactDebtor->passport()->first()->fio }}</td>
                    <td>{{ $contactDebtor->loan_id_1c }}</td>
                    <td>
                        <a href="/debtors/debtorcard/{{ $contactDebtor->id }}?finded_by={{ $debtor->id }}" class="btn btn-default btn-xs" size="xs" target="_blank">
                            <span class="glyphicon glyphicon-eye-open"></span>
                        </a>
                    </td>
                </tr>
            @endforeach
        @endif
    @endif

    @if ($collectContacts->get('equal_addresses_register_to_fact', false))
        <tr>
            <td colspan="3" style="background: #5bc0de;">Совпадение адреса прописки с адресом проживания</td>
        </tr>
        @if (!$collectContacts->get('equal_addresses_register_to_fact')->count())
            <tr>
                <td colspan="3">Совпадений по адресу прописки с адресом проживания не найдено.</td>
            </tr>
        @else
            @foreach($collectContacts->get('equal_addresses_register_to_fact') as $contactDebtor)
                <tr>
                    <td>{{ $contactDebtor->passport()->first()->fio }}</td>
                    <td>{{ $contactDebtor->loan_id_1c }}</td>
                    <td>
                        <a href="/debtors/debtorcard/{{ $contactDebtor->id }}?finded_by={{ $debtor->id }}" class="btn btn-default btn-xs" size="xs" target="_blank">
                            <span class="glyphicon glyphicon-eye-open"></span>
                        </a>
                    </td>
                </tr>
            @endforeach
        @endif
    @endif

    @if ($collectContacts->get('equal_addresses_fact_to_register', false))
        <tr>
            <td colspan="3" style="background: #5bc0de;">Совпадение адреса проживания с адресом прописки</td>
        </tr>
        @if (!$collectContacts->get('equal_addresses_fact_to_register')->count())
            <tr>
                <td colspan="3">Совпадений по адресу проживания с адресом прописки не найдено.</td>
            </tr>
        @else
            @foreach($collectContacts->get('equal_addresses_fact_to_register') as $contactDebtor)
                <tr>
                    <td>{{ $contactDebtor->passport()->first()->fio }}</td>
                    <td>{{ $contactDebtor->loan_id_1c }}</td>
                    <td>
                        <a href="/debtors/debtorcard/{{ $contactDebtor->id }}?finded_by={{ $debtor->id }}" class="btn btn-default btn-xs" size="xs" target="_blank">
                            <span class="glyphicon glyphicon-eye-open"></span>
                        </a>
                    </td>
                </tr>
            @endforeach
        @endif
    @endif

    @if ($collectContacts->get('equal_addresses_fact_to_fact', false))
        <tr>
            <td colspan="3" style="background: #5bc0de;">Совпадение адресов проживания</td>
        </tr>
        @if (!$collectContacts->get('equal_addresses_fact_to_fact')->count())
            <tr>
                <td colspan="3">Совпадений по адресу проживания не найдено.</td>
            </tr>
        @else
            @foreach($collectContacts->get('equal_addresses_fact_to_fact') as $contactDebtor)
                <tr>
                    <td>{{ $contactDebtor->passport()->first()->fio }}</td>
                    <td>{{ $contactDebtor->loan_id_1c }}</td>
                    <td>
                        <a href="/debtors/debtorcard/{{ $contactDebtor->id }}?finded_by={{ $debtor->id }}" class="btn btn-default btn-xs" size="xs" target="_blank">
                            <span class="glyphicon glyphicon-eye-open"></span>
                        </a>
                    </td>
                </tr>
            @endforeach
        @endif
    @endif
    </tbody>
</table>
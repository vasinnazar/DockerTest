@if (!$collectContacts->has($type_contact) || $collectContacts->get($type_contact)->isEmpty())
<tr>
    <td colspan="8">Совпадений {{$template_text}} не найдено.</td>
</tr>
@else
    <tr>
        <td colspan="8" style="background: #5bc0de;">Совпадения {{$template_text}}</td>
    </tr>
    @foreach($collectContacts->get($type_contact) as $contactDebtor)
        <tr>
            <td>{{ $contactDebtor->passport ? $contactDebtor->passport->fio : ' ' }}</td>
            <td>{{ $contactDebtor->loan_id_1c }}</td>
            <td>{{ $contactDebtor->base }}</td>
            <td>{{ $contactDebtor->customer ? $contactDebtor->customer->telephone : ' ' }}</td>
            <td>{{ $contactDebtor->debtGroup ? $contactDebtor->debtGroup->name : ' '}}</td>
            <td>{{ $contactDebtor->responsible_user_id_1c }}</td>
            <td>{{ $contactDebtor->str_podr }}</td>
            <td>
                <a href="/debtors/debtorcard/{{ $contactDebtor->id }}?finded_by={{ $debtor->id }}" class="btn btn-default btn-xs" size="xs" target="_blank">
                    <span class="glyphicon glyphicon-eye-open"></span>
                </a>
            </td>
        </tr>
    @endforeach
@endif

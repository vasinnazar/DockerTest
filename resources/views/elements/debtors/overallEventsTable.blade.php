<thead>
    <tr>
        <th style="width: 100px;">База</th>
        <th style="width: 70px;">Кол-во</th>
        <th style="width: 150px;">Зад-сть</th>
        <th style="width: 150px;">Сумма по ОД</th>
    </tr>
</thead>
<tbody class='debtors-frame'>
    @foreach($debtorsOverall['items'] as $item)
    <tr>
        <td>{{$item->base}}</td>
        <td>{{$item->num}}</td>
        <td>{{$item->sum_debt}}</td>
        <td>{{$item->sum_od}}</td>
    </tr>
    @endforeach
    <tr class='bg-danger'>
        <td>{{$debtorsOverall['total']['base']}}</td>
        <td>{{$debtorsOverall['total']['num']}}</td>
        <td>{{$debtorsOverall['total']['sum_debt']}}</td>
        <td>{{$debtorsOverall['total']['sum_od']}}</td>
    </tr>
</tbody>
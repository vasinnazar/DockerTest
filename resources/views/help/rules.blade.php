@extends('help.helpmenu')
@section('title')Правила@stop
@section('subcontent')
<h2>Промокод действует если</h2>
Ставка договора >= 2.0 %<br>
Нет дополнительных договоров<br>
Нет просрочки<br>
Количество активированных промокодов <= {{config('options.promocode_activate_num')}}


<table class="table table-bordered">
    <thead>
        <tr>
            <th></th>
        </tr>
    </thead>
</table>
@stop
@stop
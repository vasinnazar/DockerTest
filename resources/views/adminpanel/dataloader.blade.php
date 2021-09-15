@extends('adminpanel')
@section('title') Загрузка данных @stop
@section('subcontent')
<a href='{{url('adminpanel/dataloader/customers')}}' class='btn btn-default'>Загрузить контрагентов</a>
@stop
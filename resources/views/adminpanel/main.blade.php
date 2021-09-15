@extends('adminpanel')
@section('title') Администрирование @stop
@section('subcontent')
@stop
@section('scripts')
<script src="{{ URL::asset('js/adminpanel/adminpanelController.js') }}"></script>
<script>
(function () {
    $.adminCtrl.init();
})(jQuery);
</script>
@stop
@extends('app')
@section('title') Карта запланированных выездов @stop
@section('content')
<input name='debtorsJson' value='{{$debtors}}' type='hidden'/>
<div class='row'>
    <div class="col-xs-12">
        <div id="YMap" style="width: 100%; height: 600px;">

        </div>
    </div>
</div>
@stop
@section('scripts')
<script src="{{asset('js/debtors/debtorsController.js')}}"></script>
<script src="{{asset('js/libs/bootstrap-datetimepicker/js/bootstrap-datetimepicker.js')}}"></script>
<script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
<script>
$(document).ready(function () {
    $.debtorsCtrl.init();
    $.debtorsCtrl.initDebtorsCard();
    var myMap;
    ymaps.ready(function () {
        myMap = new ymaps.Map("YMap", {
            center: [55.2115, 86.0523],
            zoom: 10
        });

        var debtors = $.parseJSON($('[name="debtorsJson"]').val());
        var allCoords = [];
        var coordsNum = 0;
        var debtorsNum = debtors.length;
        debtors.forEach(function (debtor) {
            var coords = debtor.geocode;
            allCoords.push(coords);
            addPlaceMark(debtor, coords);
            coordsNum++;
            if (coordsNum == debtorsNum) {
                //addRoute(allCoords);
            }
        });
    });
    function addPlaceMark(debtor, coords) {
        var arr = coords.split(',');

        var startPreset;
        var ajaxPlannedLink;
        var startStatus;
        if (debtor.planned === null) {
            startPreset = 'islands#blueIcon';
            ajaxPlannedLink = '/ajax/debtors/changePlanDeparture/' + debtor.debtor_id + '/add';
            startStatus = '';
        } else {
            if (debtor.execDeparture) {
                startPreset = 'islands#yellowIcon';
                ajaxPlannedLink = '/ajax/debtors/changePlanDeparture/' + debtor.debtor_id + '/remove';
                startStatus = '<br>был выезд по адресу';
            } else {
                startPreset = 'islands#redIcon';
                ajaxPlannedLink = '/ajax/debtors/changePlanDeparture/' + debtor.debtor_id + '/remove';
                startStatus = '<br>выезд запланирован';
            }
        }

        var myPlacemark = new ymaps.Placemark(arr, {}, {
            preset: startPreset,
            ajaxLink: ajaxPlannedLink
        });

        var curPreset = myPlacemark.options.get('preset');


        myPlacemark.properties.set({
            hintContent: '<p style="text-align: center;"><a href="/debtors/debtorcard/' + debtor.debtor_id + '" target="_blank">' + debtor.fio + '</a>' + startStatus + '</p>',
            bubbleContent: debtor.debtor_id_1c
        });
        myPlacemark.events.add(['mouseenter', 'mouseleave'], function (e) {
            var target = e.get('target'),
                    type = e.get('type');

            if (type == 'mouseenter') {
                target.options.set('preset', 'islands#greyIcon');
            } else {
                target.options.set('preset', curPreset);
            }
        });
        myPlacemark.events.add('click', function (e) {
            var strConfirm;
            var target = e.get('target');

            if (debtor.planned === null) {
                strConfirm = 'Вы хотите запланировать выезд?';
            } else {
                strConfirm = 'Вы хотите отменить выезд?';
            }

            if (confirm(debtor.fio + ': ' + strConfirm)) {
                $.post(target.options.get('ajaxLink')).done(function () {
                    if (debtor.planned === null) {
                        curPreset = 'islands#redIcon';
                        target.options.set('preset', curPreset);
                        debtor.planned = 1;
                        target.options.set({
                            ajaxLink: '/ajax/debtors/changePlanDeparture/' + debtor.debtor_id + '/remove'
                        });
                        target.properties.set({
                            hintContent: '<p style="text-align: center;"><a href="/debtors/debtorcard/' + debtor.debtor_id + '" target="_blank">' + debtor.fio + '</a><br>выезд запланирован</p>'
                        });
                    } else {
                        curPreset = 'islands#blueIcon';
                        target.options.set('preset', curPreset);
                        debtor.planned = null;
                        target.options.set({
                            ajaxLink: '/ajax/debtors/changePlanDeparture/' + debtor.debtor_id + '/add'
                        });
                        target.properties.set({
                            hintContent: '<p style="text-align: center;"><a href="/debtors/debtorcard/' + debtor.debtor_id + '" target="_blank">' + debtor.fio + '</a></p>'
                        });
                    }
                });
            }
        });
        myMap.geoObjects.add(myPlacemark);
    }
    function addRoute(coords) {
        console.log(coords.length, coords);
        var multiRoute = new ymaps.multiRouter.MultiRoute({
            referencePoints: coords,
            params: {
                results: 2
            }
        }, {
            boundsAutoApply: true
        });
        console.log(myMap);
        myMap.geoObjects.add(multiRoute);
    }
});
</script>

@stop

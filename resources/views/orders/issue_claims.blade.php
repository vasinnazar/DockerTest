@extends('app')
@section('title') Заявки на ордера на подотчет @stop
@section('content')
<div class='row'>
    <div class='col-xs-12'>
        <ol class="breadcrumb">
            <li><a href="{{url('orders')}}">Кассовые операции</a></li>
            <li class="active">Заявки на ордера на подотчет</li>
        </ol>
    </div>
</div>
<div>
    <a class="btn btn-default" href="#" data-toggle="modal" data-target="#searchIssueClaimModal"><span class="glyphicon glyphicon-search"></span> Поиск</a>
    <button class="btn btn-default" id="clearIssueClaimsFilterBtn" disabled>Очистить фильтр</button>
</div>
<div class='row'>
    <div class='col-xs-12'>
        <table class='table table-condensed' id='issueclaimsTable'>
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Тип</th>
                    <th>Номер</th>
                    <th>Сумма</th>
                    <th>Ответственный</th>
                    <th>Контрагент</th>
                    <th></th>
                </tr>
            </thead>
        </table>
    </div>
</div>
<div class="modal fade" id="viewIssueClaimModal" tabindex="-1" role="dialog" aria-labelledby="viewIssueClaimModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="viewIssueClaimModalLabel">Просмотр заявки</h4>
            </div>
            <div class="modal-body">
                <div class='alert alert-warning'>
                    <strong>Согласовано:</strong> <span class="label" id="viewIssueClaimModalAgreedLabel"></span><br>
                    <small id="viewIssueClaimModalCanPrint">Вы можете распечатать ордер в <a href="{{url('orders')}}" class="alert-link">кассовых операциях</a>, найдя его по дате.</small>
                </div>
                <div class='form-group form-inline'>
                    <label>Сумма:</label>
                    <input class="form-control" name="money" readonly/>
                </div>
                <div class='form-group'>
                    <label>Основание:</label>
                    <input class="form-control" name="reason" readonly/>
                </div>
                <div class='form-group'>
                    <label>Комментарий:</label>
                    <textarea class="form-control" name="comment" readonly></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="editIssueClaimModal" tabindex="-1" role="dialog" aria-labelledby="editIssueClaimModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="editIssueClaimModalLabel">Редактирование заявки</h4>
            </div>
            <div class="modal-body">
                {!! Form::open(['url'=>url('ajax/orders/issueclaims/update'),'method'=>'post','id'=>'editIssueClaimForm']) !!}
                    <input type='hidden' name='id'/>
                    <div class='form-group form-inline'>
                        <label>Сумма:</label>
                        <input class="form-control" name="money"/>
                    </div>
                    <div class='form-group'>
                        <label>Основание:</label>
                        <input class="form-control" name="reason"/>
                    </div>
                    <div class='form-group'>
                        <label>Комментарий:</label>
                        <textarea class="form-control" name="comment"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Подразделение</label>
                        <input type='hidden' name='subdivision_id' />
                        <input type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' />
                    </div>
                    <div class="form-group">
                        <label>Специалист</label>
                        <input type='hidden' name='user_id' />
                        <input type='text' name='user_id_autocomplete' class='form-control' data-autocomplete='users' />
                    </div>
                    <div class="form-group">
                        <label>Ответственный</label>
                        <input type='hidden' name='passport_id' />
                        <input type='text' name='passport_id_autocomplete' class='form-control' data-autocomplete='passports' />
                    </div>
                    <button class='btn btn-primary pull-right' type="submit">Сохранить</button>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="searchIssueClaimModal" tabindex="-1" role="dialog" aria-labelledby="searchIssueClaimModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="issueclaimsFilter">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="searchIssueClaimModalLabel">Поиск заявки на подотчет</h4>
            </div>
            <div class="modal-body">
                <form class="row">
                    <div class="form-group-sm col-xs-12">
                        <label>ФИО ответственного</label>
                        <input name="username" class="form-control"/>
                    </div>
                    <div class="form-group-sm col-xs-12">
                        <label>Номер заявки</label>
                        <input name="issue_claim_id_1c" class="form-control"/>
                    </div>
                    <div class="form-group-sm col-xs-12">
                        <label>Дата заявки с</label>
                        <input name="issue_claim_created_at_min" class="form-control" type="date"/>
                    </div>
                    <div class="form-group-sm col-xs-12">
                        <label>Дата заявки до</label>
                        <input name="issue_claim_created_at_max" class="form-control" type="date"/>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="issueclaimsFilterBtn" data-dismiss="modal">
                    <span class="glyphicon glyphicon-search"></span> Поиск
                </button>
            </div>
        </div>
    </div>
</div>
@section('scripts')
<script src="{{asset('js/common/TableController.js')}}"></script>
<script src="{{asset('js/orders/issueClaimController.js')}}"></script>
<script>
(function () {
    $.issueClaimCtrl.init();
})(jQuery);
</script>
@stop
@stop
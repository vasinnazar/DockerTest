<div class="modal fade" id="userPaymentsModal" tabindex="-1" role="dialog" aria-labelledby="userPaymentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="userPaymentsModalLabel">Зачет оплат</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        {!!Form::open(['class'=>'form-inline','id'=>'userPaymentsForm'])!!}
                        <div class='input-group'>
                            <span class='input-group-addon'>Начало периода</span>
                            <input class='form-control' type='date' name='start_date' value='{{\Carbon\Carbon::now()->format('Y-m-d')}}' />
                        </div>
                        <div class='input-group'>
                            <span class='input-group-addon'>Конец периода</span>
                            <input class='form-control' type='date' name='end_date' value='{{\Carbon\Carbon::now()->format('Y-m-d')}}' />
                        </div>
                        <button class="btn btn-default" type="button" data-toggle="collapse" data-target="#userPaymentsModalUsers" aria-expanded="false" aria-controls="userPaymentsModalUsers">
                            <span class='glyphicon glyphicon-user'></span> 
                            Указать специалистов
                        </button>
                        @if ($user->hasRole('debtors_remote') && $user->hasRole('debtors_chief'))
                        <button class="btn btn-default" type="button" id="check-all-resps">
                            <span class='glyphicon glyphicon-home'></span> 
                            По всему отделу
                        </button>
                        @endif
                        <br><br>
                        <div class="collapse" id="userPaymentsModalUsers">
                            <div class="list-group">
                                <li class='list-group-item'>
                                    <label>
                                        <input name='user_id[]'
                                               class="{{(auth()->user()->user_group_id == '1541') ? 'remote_user' : ''}}"
                                               type='checkbox'
                                               value="{{auth()->user()->id}}"
                                        />
                                        {{auth()->user()->name}}
                                    </label>
                                </li>
                                @foreach($user->subordinatedUsers->sortBy('login') as $subordinated)
                                    <li class='list-group-item'>
                                        <label>
                                            <input name='user_id[]'
                                                   class="{{($subordinated->user_group_id == '1541') ? 'remote_user' : ''}}"
                                                   type='checkbox'
                                                   value="{{$subordinated->id}}"
                                            />
                                            {{$subordinated->name}}
                                        </label>
                                    </li>
                                @endforeach
                            </div>
                        </div>
                        {!!Form::close()!!}
                    </div>
                </div>
                <div class='row'>
                    <div class='col-xs-12'>
                        <table class='table table-condensed' id='userPaymentsHolder'>
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>ФИО</th>
                                    <th>Документ</th>
                                    <th>Договор</th>
                                    <th>Сумма</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class='btn btn-primary' onclick="$.debtorsCtrl.getUserPayments();
                        return false;">
                    Показать
                </button>
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>
<script>
    $(document).ready(function(){
        $(document).on('click', '#check-all-resps', function() {
            $('.remote_user').prop('checked', true);
            $.debtorsCtrl.getUserPayments();
            return false;
        });
    });
</script>
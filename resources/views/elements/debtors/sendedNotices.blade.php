<div class="modal fade" id="debtorSendedNotices" tabindex="-1" role="dialog" aria-labelledby="debtorSendedNoticesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title">Отправленные уведомления</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        @if (count($notices))
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Тип адреса</th>
                                    <th>Номер</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($notices as $notice)
                                <tr>
                                    <td>{{date('d.m.Y', strtotime($notice->created_at))}}</td>
                                    <td>{{($notice->is_ur_address) ? 'По регистрации' : 'По проживанию'}}</td>
                                    <td>{{$notice->id}}/УВ</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        Уведомления не отправлялись
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
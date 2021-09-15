<div class="modal fade" id="createSchedule" tabindex="-1" role="dialog" aria-labelledby="createScheduleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        @if($create_schedule)
                        <table class='table table-bordered table-condensed'>
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Общая сумма</th>
                                    <th>Проценты</th>
                                    <th>ОД</th>
                                    <th>Остаток</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($create_schedule as $pay)
                                <tr>
                                    <td>{{with(new \Carbon\Carbon((is_array($pay['date']))?'':((string)$pay['date'])))->format('d.m.Y')}} г.</td>
                                    <td>{{(is_array($pay['total']))?'':((string)$pay['total'])}} руб.</td>
                                    <td>{{(is_array($pay['pc']))?'':((string)$pay['pc'])}} руб.</td>
                                    <td>{{(is_array($pay['od']))?'':((string)$pay['od'])}} руб.</td>
                                    <td>
                                        @if(array_key_exists('all_remain',$pay))
                                        {{ (is_array($pay['all_remain']))?'0':((string)$pay['all_remain']) }} руб.
                                        @else
                                        -
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
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
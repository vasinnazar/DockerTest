<div class="modal fade" id="debtorNoticePersonal" tabindex="-1" role="dialog" aria-labelledby="debtorNoticePersonalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <input type="hidden" name="address_type">
                        <table class="table table-condensed">
                            <tr>
                                <td>Дата требования внесения: </td>
                                <td><input type="date" class="form-control" name="date_demand"></td>
                            </tr>
                            <tr>
                                <td>Уведомление/требование: </td>
                                <td>
                                    <select name="doc_id" class="form-control">
                                        <!--option value="{{ $contractforms['notice_personal'] }}">Уведомление</option-->
                                        @if ($debtor->is_bigmoney == 1)
                                        <option value="{{ $contractforms['requirement_personal_big_money'] }}">Требование</option>
                                        @else
                                        @if (is_array($arDataCcCard))
                                        @if (time() > strtotime($arDataCcCard['data']['loan_end_at']))
                                        <option value="{{ $contractforms['trebovanie_personal_cc'] }}">Требование ККЗ срок договора истек</option>
                                        @else
                                        <option value="{{ $contractforms['trebovanie_personal_cc_60'] }}">Требование ККЗ срок договора не истек</option>
                                        @endif
                                        @else
                                        <option value="{{ $contractforms['requirement_personal'] }}">Требование</option>
                                        @endif
                                        @endif
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" id="closeNoticePersonal">Закрыть</button>
                <button type="button" class="btn btn-primary" id="sendNoticePersonal" disabled>Отправить</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="debtorsSiteLoginReportModal" tabindex="-1" role="dialog" aria-labelledby="debtorsSiteLoginReportLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formReportSiteLogin" action="/debtors/reports/loginlog">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xs-12">
                                <input type="hidden" name="mode" value="{{ ($user->hasRole('debtors_personal')) ? 'lv' : 'uv' }}">
                                <h4>Отчет по заходам на сайт</h4>
                                <table style="margin-bottom: 15px;">
                                    <tr>
                                        <td>Логин с:</td>
                                        <td style="padding-left: 15px;"><input type="date" name="dateStart" class="form-control" style="width: 200px;" min="{{date('Y-m-d', strtotime("-1 month", time()))}}" max="{{date('Y-m-d', time())}}"></td>
                                    </tr>
                                    <tr style="ma">
                                        <td>Логин по:</td>
                                        <td style="padding-left: 15px;"><input type="date" name="dateEnd" class="form-control" style="width: 200px; margin-top: 15px;" min="{{date('Y-m-d', strtotime("-1 month", time()))}}" max="{{date('Y-m-d', time())}}"></td>
                                    </tr>
                                </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal" id="closeDebtorsSiteLoginReport">Закрыть</button>
                    <button type="submit" class="btn btn-primary" id="makeDebtorsSiteLoginReport">Сформировать</button>
                </div>
            </form>
        </div>
    </div>
</div>
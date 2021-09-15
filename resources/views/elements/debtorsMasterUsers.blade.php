<div class="modal fade" id="masterUsers" tabindex="-1" role="dialog" aria-labelledby="masterUsersLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <form id="formDebtorUserSlaves">
                            <input type="hidden" name="master_user_id" value="">
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <td>ID</td>
                                        <td>ФИО</td>
                                        <td>
                                            <input type="checkbox" onchange="$.adminCtrl.toggleAllSlaves(this); return false;"/>
                                        </td>
                                    </tr>
                                </thead>
                                <tbody id="debtorUsersData">

                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" id="closeDebtorUserSlaves">Закрыть</button>
                <button type="button" class="btn btn-primary" id="saveDebtorUserSlaves">Сохранить</button>
            </div>
        </div>
    </div>
</div>
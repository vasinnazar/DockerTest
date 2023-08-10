<div class="modal fade" id="changeAddress" tabindex="-1" role="dialog" aria-labelledby="debtorSearchContactsLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <b>Изменение адресов</b>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body" style="overflow-y: auto;">
                <div class="row">
                    <table class='table table-bordered'>
                        <tbody>
                        <tr>
                            <td colspan="2" class="pt-1">
                                <div class="form-group row">
                                    <label for="zip" class="col-sm-4 col-form-label">Адрес регистрации</label>
                                    <div class="col-sm-8">
                                        <input list="address_reg" name="zip" type="text" class="form-control" id="zip" value="" autocomplete="off">
                                        <datalist id="address_reg">
                                            <option value="">
                                        </datalist>
                                    </div>
                                </div>
                                <div class="text-right mt-1">
                                    <button class="btn btn-outline-secondary" onclick="window.checkAddress()">
                                        Проверить
                                    </button>
                                </div>
                                <br>
                                <form action="" method="POST">
                                    <div class="form-group row">
                                        <label for="region" class="col-sm-4 col-form-label">Адрес проживания</label>
                                        <div class="col-sm-8">
                                            <input list="address_fact" name="address_region" type="text" class="form-control" id="region" value="" autocomplete="off">
                                            <datalist id="address_fact">
                                                <option value="">
                                            </datalist>
                                        </div>
                                    </div>
                                    <div class="text-right mt-1">
                                        <button class="btn btn-outline-secondary">
                                            Проверить
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary">Сохранить</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
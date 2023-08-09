<div class="modal fade" id="debtorSearchContacts1" tabindex="-1" role="dialog" aria-labelledby="debtorSearchContactsLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <b>Изменение адресов</b>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body" style="overflow-y: auto;">
                <div class="row">
                    <div class="photo-data">
                        <table class='table table-bordered'>
                            <tbody>
                            <tr>
                                <td colspan="2">
                                    <strong>Адрес регистрации</strong>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="pt-1">
                                    <form action="" method="POST">
                                        <div class="form-group row">
                                            <label for="zip" class="col-sm-4 col-form-label">Индекс</label>
                                            <div class="col-sm-8">
                                                <input name="zip" type="text" readonly class="form-control" id="zip" value="">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="region" class="col-sm-4 col-form-label">Регион</label>
                                            <div class="col-sm-8">
                                                <input name="address_region" type="text" class="form-control" id="region" value="" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="district" class="col-sm-4 col-form-label">Район</label>
                                            <div class="col-sm-8">
                                                <input name="address_district" type="text" class="form-control" id="district" value="" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="city" class="col-sm-4 col-form-label">Город</label>
                                            <div class="col-sm-8">
                                                <input name="address_city" type="text" class="form-control" id="city" value="" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="settlement" class="col-sm-4 col-form-label">Нас. пункт</label>
                                            <div class="col-sm-8">
                                                <input name="address_city1" type="text" class="form-control" id="settlement" value="" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="street" class="col-sm-4 col-form-label">Улица</label>
                                            <div class="col-sm-8">
                                                <input name="address_street" type="text" class="form-control" id="street" value="" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="house" class="col-sm-4 col-form-label">Дом</label>
                                            <div class="col-sm-8">
                                                <input name="address_house" type="text" class="form-control" id="house" value="" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="building" class="col-sm-4 col-form-label">Строение</label>
                                            <div class="col-sm-8">
                                                <input name="address_building" type="text" class="form-control" id="building" value="" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="apartment" class="col-sm-4 col-form-label">Квартира</label>
                                            <div class="col-sm-8">
                                                <input name="address_apartment" type="text" class="form-control" id="apartment" value="" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="text-right mt-1">
                                            <button class="btn btn-outline-secondary">
                                                Сохранить
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
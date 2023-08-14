<div class="modal fade" id="changeAddress" tabindex="-1" role="dialog" aria-labelledby="debtorSearchContactsLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <b>Изменение адресов</b>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body" style="overflow-y: auto;">
                <div class="row">
                    <table class='table without-border table-address-info'>
                        <tbody>
                        <tr>
                            <td colspan="4" class="pt-1">
                                <div class="form-group row">
                                    <label for="address_reg" class="col-sm-4 col-form-label">Адрес регистрации</label>
                                    <div class="col-sm-8 pos-relative">
                                        <textarea id="input_address_reg" name="address_reg" type="text" class="form-control" value="" autocomplete="off">{{$debtor->passport->full_address}}</textarea>
                                        <ul class="list-group ul-address" id="address_reg">
                                        </ul>
                                    </div>
                                </div>
                                <div class="text-right mt-1">
                                    <button class="btn btn-outline-secondary" id="checkAddressReg">
                                        Проверить
                                    </button>
                                </div>
                                <br>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Индекс</th>
                            <td id="zip">{{$debtor->passport->zip}}</td>
                            <th scope="row">Код ОКАТО</th>
                            <td id="okato"></td>
                        </tr>
                        <tr>
                            <th scope="row">Регион</th>
                            <td id="address_region">{{$debtor->passport->address_region}}</td>
                            <th scope="row">Код ОКТМО</th>
                            <td id="oktmo"></td>
                        </tr>
                        <tr>
                            <th scope="row">Район</th>
                            <td id="address_district">{{$debtor->passport->address_district}}</td>
                            <th scope="row">Код ФИАС</th>
                            <td id="fias_code"></td>
                        </tr>
                        <tr>
                            <th scope="row">Город</th>
                            <td id="address_city">{{$debtor->passport->address_city}}</td>
                            <th scope="row">ФИАС ИД</th>
                            <td id="fias_id"></td>
                        </tr>
                        <tr>
                            <th scope="row">Нас. пункт</th>
                            <td id="address_city1">{{$debtor->passport->address_city1}}</td>
                            <th scope="row">КЛАДР</th>
                            <td id="kladr_id"></td>
                        </tr>
                        <tr>
                            <th scope="row">Улица</th>
                            <td id="address_street">{{$debtor->passport->address_street}}</td>
                        </tr>
                        <tr>
                            <th scope="row">Дом</th>
                            <td id="address_house">{{$debtor->passport->address_house}}</td>
                        </tr>
                        <tr>
                            <th scope="row">Корпус</th>
                            <td id="address_building">{{$debtor->passport->address_building}}</td>
                        </tr>
                        <tr>
                            <th scope="row">Квартира</th>
                            <td id="address_apartment">{{$debtor->passport->address_apartment}}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="pt-1">
                                <hr>
                                <div class="form-group row">
                                    <label for="address_fact" class="col-sm-4 col-form-label">Адрес проживания</label>
                                    <div class="col-sm-8 pos-relative">
                                        <textarea id="input_address_fact" name="address_fact" type="text" class="form-control" value="" autocomplete="off">{{$debtor->passport->fact_full_address}}</textarea>
                                        <ul class="list-group ul-address" id="address_fact">
                                        </ul>
                                    </div>
                                </div>
                                <div class="text-right mt-1">
                                    <button class="btn btn-outline-secondary" id="checkAddressFact">
                                        Проверить
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Индекс</th>
                            <td>{{$debtor->passport->fact_zip}}</td>
                            <th scope="row">Код ОКАТО</th>
                            <td>{{$debtor->passport->fact_okato}}</td>
                        </tr>
                        <tr>
                            <th scope="row">Регион</th>
                            <td>{{$debtor->passport->fact_address_region}}</td>
                            <th scope="row">Код ОКТМО</th>
                            <td>{{$debtor->passport->fact_oktmo}}</td>
                        </tr>
                        <tr>
                            <th scope="row">Район</th>
                            <td>{{$debtor->passport->fact_address_district}}</td>
                            <th scope="row">Код ФИАС</th>
                            <td>{{$debtor->passport->fact_fias_id}}</td>
                        </tr>
                        <tr>
                            <th scope="row">Город</th>
                            <td>{{$debtor->passport->fact_address_city}}</td>
                            <th scope="row">ФИАС ИД</th>
                            <td>{{$debtor->passport->fact_fias_id}}</td>
                        </tr>
                        <tr>
                            <th scope="row">Нас. пункт</th>
                            <td>{{$debtor->passport->fact_address_city1}}</td>
                            <th scope="row">КЛАДР</th>
                            <td>{{$debtor->passport->fact_kladr_id}}</td>
                        </tr>
                        <tr>
                            <th scope="row">Улица</th>
                            <td>{{$debtor->passport->fact_address_street}}</td>
                        </tr>
                        <tr>
                            <th scope="row">Дом</th>
                            <td>{{$debtor->passport->fact_address_house}}</td>
                        </tr>
                        <tr>
                            <th scope="row">Корпус</th>
                            <td>{{$debtor->passport->fact_address_building}}</td>
                        </tr>
                        <tr>
                            <th scope="row">Квартира</th>
                            <td>{{$debtor->passport->fact_address_apartment}}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="categoryViewForm()">Сохранить</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
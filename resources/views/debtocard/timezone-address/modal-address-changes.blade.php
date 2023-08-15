<div class="modal fade" id="changeAddress" tabindex="-1" role="dialog" aria-labelledby="debtorSearchContactsLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <b>Изменение адресов</b>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body" style="overflow-y: auto;">
                <div class="row">
                    <div class="form-group row">
                        <label for="address_reg" class="col-sm-4 col-form-label">Адрес регистрации</label>
                        <div class="col-sm-8 pos-relative">
                                        <textarea id="input_address_reg" name="address_reg" type="text"
                                                  class="form-control" value=""
                                                  autocomplete="off">{{$debtor->passport->full_address}}</textarea>
                            <ul class="list-group ul-address" id="address_reg">
                            </ul>
                        </div>
                    </div>
                    <div class="text-right mt-1">
                        <button class="btn btn-outline-secondary" id="checkAddressReg">
                            Проверить
                        </button>
                    </div>

                    <div class="form-group row">
                        <label for="address_fact" class="col-sm-4 col-form-label">Адрес проживания</label>
                        <div class="col-sm-8 pos-relative">
                                        <textarea id="input_address_fact" name="address_fact" type="text"
                                                  class="form-control" value=""
                                                  autocomplete="off">{{$debtor->passport->fact_full_address}}</textarea>
                            <ul class="list-group ul-address" id="address_fact">
                            </ul>
                        </div>
                    </div>
                    <div class="text-right mt-1">
                        <button class="btn btn-outline-secondary" id="checkAddressFact">
                            Проверить
                        </button>
                    </div>
                    <form id="addressCustomer">
                        <div class="col-sm-6">
                            <p>Полный адрес регистрации</p>
                            <label>Индекс</label>
                            <input type="text" class="form-control" name="zip"
                                   value="{{$debtor->passport->zip}}" disabled>
                            <label>Регион</label>
                            <input type="text" class="form-control" name="address_region"
                                   value="{{$debtor->passport->address_region}}" disabled>
                            <label>Район</label>
                            <input type="text" class="form-control" name="address_district"
                                   value="{{$debtor->passport->address_district}}" disabled>
                            <label>Город</label>
                            <input type="text" class="form-control" name="address_city"
                                   value="{{$debtor->passport->address_city}}" disabled>
                            <label>Нас. пункт</label>
                            <input type="text" class="form-control" name="address_city1"
                                   value="{{$debtor->passport->address_city1}}" disabled>
                            <label>Улица</label>
                            <input type="text" class="form-control" name="address_street"
                                   value="{{$debtor->passport->address_street}}" disabled>
                            <label>Дом</label>
                            <input type="text" class="form-control" name="address_house"
                                   value="{{$debtor->passport->address_house}}" disabled>
                            <label>Корпус</label>
                            <input type="text" class="form-control" name="address_building"
                                   value="{{$debtor->passport->address_building}}" disabled>
                            <label>Квартира</label>
                            <input type="text" class="form-control" name="address_apartment"
                                   value="{{$debtor->passport->address_apartment}}" disabled>
                            <label>Код ОКАТО</label>
                            <input type="text" class="form-control" name="okato"
                                   value="{{$debtor->passport->zip}}" disabled>
                            <label>Код ОКТМО</label>
                            <input type="text" class="form-control" name="oktmo"
                                   value="{{$debtor->passport->oktmo}}" disabled>
                            <label>Код ФИАС</label>
                            <input type="text" class="form-control" name="fias_code"
                                   value="{{$debtor->passport->fias_code}}" disabled>
                            <label>ФИАС ИД</label>
                            <input type="text" class="form-control" name="fias_id"
                                   value="{{$debtor->passport->fias_id}}" disabled>
                            <label>КЛАДР</label>
                            <input type="text" class="form-control" name="kladr_id"
                                   value="{{$debtor->passport->kladr_id}}" disabled>
                        </div>
                        <div class="col-sm-6">
                            <p>Полный адрес проживания</p>
                            <label>Индекс</label>
                            <input type="text" class="form-control" name="fact_zip"
                                   value="{{$debtor->passport->fact_zip}}" disabled>
                            <label>Регион</label>
                            <input type="text" class="form-control" name="fact_address_region"
                                   value="{{$debtor->passport->fact_address_region}}" disabled>
                            <label>Район</label>
                            <input type="text" class="form-control" name="fact_address_district"
                                   value="{{$debtor->passport->fact_address_district}}" disabled>
                            <label>Город</label>
                            <input type="text" class="form-control" name="fact_address_city"
                                   value="{{$debtor->passport->fact_address_city}}" disabled>
                            <label>Нас. пункт</label>
                            <input type="text" class="form-control" name="fact_address_city1"
                                   value="{{$debtor->passport->fact_address_city1}}" disabled>
                            <label>Улица</label>
                            <input type="text" class="form-control" name="fact_address_street"
                                   value="{{$debtor->passport->fact_address_street}}" disabled>
                            <label>Дом</label>
                            <input type="text" class="form-control" name="fact_address_house"
                                   value="{{$debtor->passport->fact_address_house}}" disabled>
                            <label>Корпус</label>
                            <input type="text" class="form-control" name="fact_address_building"
                                   value="{{$debtor->passport->fact_address_building}}" disabled>
                            <label>Квартира</label>
                            <input type="text" class="form-control" name="fact_address_apartment"
                                   value="{{$debtor->passport->fact_address_apartment}}" disabled>
                            <label>Код ОКАТО</label>
                            <input type="text" class="form-control" name="fact_okato"
                                   value="{{$debtor->passport->fact_okato}}" disabled>
                            <label>Код ОКТМО</label>
                            <input type="text" class="form-control" name="fact_oktmo"
                                   value="{{$debtor->passport->fact_oktmo}}" disabled>
                            <label>Код ФИАС</label>
                            <input type="text" class="form-control" name="fact_fias_code"
                                   value="{{$debtor->passport->fact_fias_code}}" disabled>
                            <label>ФИАС ИД</label>
                            <input type="text" class="form-control" name="fact_fias_id"
                                   value="{{$debtor->passport->fact_fias_id}}" disabled>
                            <label>КЛАДР</label>
                            <input type="text" class="form-control" name="fact_kladr_id"
                                   value="{{$debtor->passport->fact_kladr_id}}" disabled>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal-footer">
                <button
                        type="button"
                        class="btn btn-primary"
                        onclick="updatePassport(
                            {{$dataArm['customerId'] ?? null}},
                            {{$dataArm['passportId']}}
                        )">
                    Сохранить
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
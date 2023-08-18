<div class="modal fade" id="changeAddress" tabindex="-1" role="dialog" aria-labelledby="debtorSearchContactsLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <b>Изменение адресов</b>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body" style="overflow-y: auto;">
                <div class="row">
                    <div class="input-address">
                        <div class="form-group row">
                            <label for="address_reg" class="col-sm-4 col-form-label">Адрес регистрации</label>
                            <div class="col-sm-8 pos-relative">
                                        <textarea id="address_reg" name="address_reg" type="text"
                                                  class="form-control" value=""
                                                  autocomplete="off">{{$debtor->passport->full_address}}</textarea>
                                <ul class="list-group ul-address" id="list_address_reg">
                                </ul>
                            </div>
                        </div>
                        <div class="text-right mtb-10">
                            <button class="btn btn-outline-secondary" id="checkAddressReg">
                                Проверить
                            </button>
                        </div>
                        <div class="form-group row">
                            <label for="address_fact" class="col-sm-4 col-form-label">Адрес проживания</label>
                            <div class="col-sm-8 pos-relative">
                                        <textarea id="address_fact" name="address_fact" type="text"
                                                  class="form-control" value=""
                                                  autocomplete="off">{{$debtor->passport->fact_full_address}}</textarea>
                                <ul class="list-group ul-address" id="list_address_fact">
                                </ul>
                            </div>
                        </div>
                        <div class="text-right mtb-10">
                            <button class="btn btn-outline-secondary" id="checkAddressFact">
                                Проверить
                            </button>
                        </div>
                    </div>
                    <form id="addressCustomer">
                        <div class="col-sm-6">
                            <p>Полный адрес регистрации</p>
                            <label>Индекс</label>
                            <input type="text" class="disabled form-control" id="zip" name="zip"
                                   value="{{$debtor->passport->zip}}">
                            <label>Регион</label>
                            <input type="text" class="disabled form-control" id="address_region" name="address_region"
                                   value="{{$debtor->passport->address_region}}">
                            <label>Район</label>
                            <input type="text" class="disabled form-control" id="address_district" name="address_district"
                                   value="{{$debtor->passport->address_district}}">
                            <label>Город</label>
                            <input type="text" class="disabled form-control" id="address_city" name="address_city"
                                   value="{{$debtor->passport->address_city}}">
                            <label>Нас. пункт</label>
                            <input type="text" class="disabled form-control" id="address_city1" name="address_city1"
                                   value="{{$debtor->passport->address_city1}}">
                            <label>Улица</label>
                            <input type="text" class="disabled form-control" id="address_street" name="address_street"
                                   value="{{$debtor->passport->address_street}}">
                            <label>Дом</label>
                            <input type="text" class="disabled form-control" id="address_house" name="address_house"
                                   value="{{$debtor->passport->address_house}}">
                            <label>Корпус</label>
                            <input type="text" class="disabled form-control" id="address_building" name="address_building"
                                   value="{{$debtor->passport->address_building}}">
                            <label>Квартира</label>
                            <input type="text" class="disabled form-control" id="address_apartment" name="address_apartment"
                                   value="{{$debtor->passport->address_apartment}}">
                            <label>Код ОКАТО</label>
                            <input type="text" class="disabled form-control" id="okato" name="okato"
                                   value="{{$debtor->passport->zip}}">
                            <label>Код ОКТМО</label>
                            <input type="text" class="disabled form-control" id="oktmo" name="oktmo"
                                   value="{{$debtor->passport->oktmo}}">
                            <label>Код ФИАС</label>
                            <input type="text" class="disabled form-control" id="fias_code" name="fias_code"
                                   value="{{$debtor->passport->fias_code}}">
                            <label>ФИАС ИД</label>
                            <input type="text" class="disabled form-control" id="fias_id" name="fias_id"
                                   value="{{$debtor->passport->fias_id}}">
                            <label>КЛАДР</label>
                            <input type="text" class="disabled form-control" id="kladr_id" name="kladr_id"
                                   value="{{$debtor->passport->kladr_id}}">
                        </div>
                        <div class="col-sm-6">
                            <p>Полный адрес проживания</p>
                            <label>Индекс</label>
                            <input type="text" class="disabled form-control" id="fact_zip" name="fact_zip"
                                   value="{{$debtor->passport->fact_zip}}">
                            <label>Регион</label>
                            <input type="text" class="disabled form-control" id="fact_address_region" name="fact_address_region"
                                   value="{{$debtor->passport->fact_address_region}}">
                            <label>Район</label>
                            <input type="text" class="disabled form-control" id="fact_address_district" name="fact_address_district"
                                   value="{{$debtor->passport->fact_address_district}}">
                            <label>Город</label>
                            <input type="text" class="disabled form-control" id="fact_address_city" name="fact_address_city"
                                   value="{{$debtor->passport->fact_address_city}}">
                            <label>Нас. пункт</label>
                            <input type="text" class="disabled form-control" id="fact_address_city1" name="fact_address_city1"
                                   value="{{$debtor->passport->fact_address_city1}}">
                            <label>Улица</label>
                            <input type="text" class="disabled form-control" id="fact_address_street" name="fact_address_street"
                                   value="{{$debtor->passport->fact_address_street}}">
                            <label>Дом</label>
                            <input type="text" class="disabled form-control" id="fact_address_house" name="fact_address_house"
                                   value="{{$debtor->passport->fact_address_house}}">
                            <label>Корпус</label>
                            <input type="text" class="disabled form-control" id="fact_address_building" name="fact_address_building"
                                   value="{{$debtor->passport->fact_address_building}}">
                            <label>Квартира</label>
                            <input type="text" class="disabled form-control" id="fact_address_apartment" name="fact_address_apartment"
                                   value="{{$debtor->passport->fact_address_apartment}}">
                            <label>Код ОКАТО</label>
                            <input type="text" class="disabled form-control" id="fact_okato" name="fact_okato"
                                   value="{{$debtor->passport->fact_okato}}">
                            <label>Код ОКТМО</label>
                            <input type="text" class="disabled form-control" id="fact_oktmo" name="fact_oktmo"
                                   value="{{$debtor->passport->fact_oktmo}}">
                            <label>Код ФИАС</label>
                            <input type="text" class="disabled form-control" id="fact_fias_code" name="fact_fias_code"
                                   value="{{$debtor->passport->fact_fias_code}}">
                            <label>ФИАС ИД</label>
                            <input type="text" class="disabled form-control" id="fact_fias_id" name="fact_fias_id"
                                   value="{{$debtor->passport->fact_fias_id}}">
                            <label>КЛАДР</label>
                            <input type="text" class="disabled form-control" id="fact_kladr_id" name="fact_kladr_id"
                                   value="{{$debtor->passport->fact_kladr_id}}">
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
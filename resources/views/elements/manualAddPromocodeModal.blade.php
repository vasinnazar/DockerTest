<?php
use Carbon\Carbon; ?>
<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="manualAddPromocodeModal" aria-hidden="true" id="manualAddPromocodeModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Добавить промокод вручную (ТОЛЬКО В АРМ)</h4>
            </div>
            <!--Form::open(['url' => 'promocodes/add/manual','id'=>'manualPromocodeForm'])-->
            <form id="manualPromocodeForm">
            <input type="hidden" name="claim_id"/>
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-xs-12 col-md-12">
                        <label class="control-label">Номер промокода в заявке:</label>
                        <input name="claim_promocode" class="form-control"  maxlength="6"/>
                        <label class="control-label">Номер промокода в кредитнике:</label>
                        <input name="loan_promocode" class="form-control"  maxlength="6"/>
                    </div>
<!--                    <div class="form-group col-xs-12 col-md-12">
                        <input type="checkbox" class="checkbox" name="to_claim" type="text" id="manualPromocodeToClaim"/>
                        <label for="manualPromocodeToClaim">К заявке</label>
                        
                    </div>
                    <div class="form-group col-xs-12 col-md-12">
                        <input type="checkbox" class="checkbox" name="to_loan" type="text" id="manualPromocodeToLoan"/>
                        <label for="manualPromocodeToLoan">К кредитному договору</label>
                    </div>-->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="$.dashboardCtrl.saveManualAddPromocode(); return false;">Сохранить</button>
            </div>
            </form>
            <!--Form::close()-->
        </div>
    </div>
</div>
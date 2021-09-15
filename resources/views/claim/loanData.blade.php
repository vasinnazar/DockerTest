<div class="loan-step">
    <div class="row">
        <div class="form-group col-xs-12 col-md-6">
            <label for="sum">Сумма займа<span class="required-mark">*</span></label>
            <?php
            $opts = [];
            for ($i = 0; $i < 31; $i++) {
                $opts[$i * 1000] = ((string) ($i * 1000)) . ' рублей';
            }
            echo Form::select('sum', $opts, null, ['class' => 'form-control', 'required' => 'required']);
            ?>
        </div>
        <div class="form-group col-xs-12 col-md-4">
            <label for="srok">Срок займа<span class="required-mark">*</span><span class="loan-end-date-holder"></span></label>
            <?php
            $opts = [];
            for ($i = 0; $i < 31; $i++) {
                $opts[$i] = $i . ' дней';
            }
            echo Form::select('srok', $opts, null, ['class' => 'form-control', 'required' => 'required']);
            ?>
        </div>
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('uki', 1, null, ['class' => 'checkbox', 'id' => 'uki_checkbox']); ?>
                <label for="uki_checkbox" > Улучшение Кредитной Истории</label>
        </div>
        <!--        <div class="form-group col-xs-4">
                    {!! Form::hidden('promocode_id',null,['class'=>'form-control']) !!}
                    <label for="promocodeInput">Промокод</label>
                    <div class="input-group">
                        <span class="input-group-btn">
                            <button class="btn btn-default" type="button" id="checkPromocodeBtn">Проверить</button>
                        </span>
                        {!! Form::text('promocode_number',null,['class'=>'form-control','id'=>'promocodeInput']) !!}
                    </div>
                </div>-->
    </div>
</div>
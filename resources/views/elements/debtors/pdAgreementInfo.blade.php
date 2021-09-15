<div class="modal fade" id="debtorPdAgreementInfo" tabindex="-1" role="dialog" aria-labelledby="debtorPdAgreementInfoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title">Согласие на обработку персональных данных</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        @if (count($arPdAgreement))
                        {{$arPdAgreement['user_name']}}, {{$arPdAgreement['subdivision_name']}}
                        <br>
                        {{$arPdAgreement['signed_time']}}
                        @else
                        Соглашение не подписано.
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="debtorTherdPeopleAgreementInfo" tabindex="-1" role="dialog" aria-labelledby="debtorTherdPeopleAgreementInfoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title">Согласие на взаимодействие с 3-ми лицами</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <?php
                        $user_signed_agreement = DB::Table('armf.users')->where('id', $third_people_agreement->user_id)->first();
                        $subdivision_signed_agreement = DB::Table('armf.subdivisions')->where('id', $third_people_agreement->subdivision_id)->first();
                        ?>
                        {{($user_signed_agreement) ? $user_signed_agreement->login : 'Пользователь неизвестен'}}, {{($subdivision_signed_agreement) ? $subdivision_signed_agreement->name : 'Подразделение неизвестно'}}
                        @if (!is_null($third_people_agreement->loan_id_1c))
                        , номер договора: {{$third_people_agreement->loan_id_1c}}
                        @endif
                        <br>
                        {{date('d.m.Y H:i', strtotime($third_people_agreement->updated_at))}}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
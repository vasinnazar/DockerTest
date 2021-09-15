<div class="modal fade" id="debtorRecommendEdit" tabindex="-1" role="dialog" aria-labelledby="debtorRecommendEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title">Редактирование рекомендации</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <textarea id="recommend_text_edit" name="recommend_text_edit" style="width: 100%" rows="5" class="form-control">{{$data[0]['recommend_text']}}</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" id="closeDebtorRecommend">Закрыть</button>
                <button type="button" class="btn btn-primary" id="editDebtorRecommend" data-action="edit" disabled>Сохранить</button>
            </div>
        </div>
    </div>
</div>
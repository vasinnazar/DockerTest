<?php
$quizDeptModel = new \App\QuizDepartment();
$quizDeptModelYesNoFields = $quizDeptModel->getYesNoCommentFields();
?>
<div class="modal fade bs-example-modal-lg open-on-load" tabindex="-1" role="dialog" aria-labelledby="quizDeptModal" aria-hidden="true" id="quizDeptModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Опрос</h4>
            </div>
            <div class="modal-body">
                <div class='row'>
                    <div class='col-xs-12'>
                        <a href='{{url("quizdept/create")}}' class='btn btn-success'>Ответить на опрос</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
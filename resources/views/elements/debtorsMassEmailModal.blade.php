<div class="modal fade" id="debtorMassEmail" tabindex="-1" role="dialog" aria-labelledby="debtorMassEmailLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <form id="formSendEmail">
                            <input type="hidden" name="debtor_ids" value="">
                            <h4>{{$nameGroup}}</h4>
                            <table class="table table-bordered table-condensed">
                                <thead>
                                <tr>
                                    <td></td>
                                    <td><b>Текст Email</b></td>
                                </tr>
                                </thead>
                                @foreach ($emailCollect as $email)
                                    <tr>
                                        <td><input type="radio" name="email_id" value="{{$email->id}}"></td>
                                        <td class="email_text" style="text-align: left">{{$email->template_message}}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

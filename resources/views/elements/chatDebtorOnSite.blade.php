<?php
if(!is_null(Auth::user())){
    $currentUser = Auth::user();
    $pfx_loan = ($currentUser->hasRole('debtors_personal')) ? 'vn' : 'sn';
    $tmsToShow = \Carbon\Carbon::now()->subMinutes(15);
    $msgss = DB::connection('debtors')->table('messages')->limit(50)->where('message_type', $pfx_loan)->where('created_at', '>=', $tmsToShow)->where('deleted_at', null);
    /*if (!$currentUser->hasRole('debtors_chief')) {
        $msgss->where('recepient_id', $currentUser->id);
    }*/
    
    $msgs = $msgss->get();
}
?>
<div class="panel panel-default debtoronsite-window closed">
    <div class="panel-heading">
        &nbsp;
        <button class="close">&times;</button>
    </div>
    <div class="panel-body">
        @if(isset($msgs))
        @foreach($msgs as $msg)
        <?php
        $respUser = \App\User::find($msg->recepient_id);
        if (!is_null($respUser)) {
            $respUserName = $respUser->name;
        } else {
            $respUserName = 'не определен';
        }
        
        if (strpos($msg->type, 'ln') !== false || strpos($msg->type, 'pn') !== false || strpos($msg->type, 'sn') !== false || strpos($msg->type, 'vn') !== false) {
            $alert_css = 'success';
        } else {
            $alert_css = $msg->type;
        }
        ?>
        <div class="chat-msg alert alert-{{$alert_css}}">
            <?php echo html_entity_decode($msg->text); ?>
            <br>
            <br>
            <p><small class='pull-right'><span class='msg-datetime' data-msg-datetime="{{$msg->created_at}}">{{with(new Carbon\Carbon($msg->created_at))->format('d.m.y H:i')}}</span>, отв: {{$respUserName}}</small></p>
        </div>
        @endforeach
        @endif
    </div>
    
</div>
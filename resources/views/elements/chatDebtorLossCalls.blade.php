<?php
if(!is_null(Auth::user())){
    $currentUser = Auth::user();
    $msglc = DB::connection('debtors')->table('debtors_loss_calls');
    /*if (!$currentUser->hasRole('debtors_chief')) {
        $msglc->where('responsible_user_id', $currentUser->id);
    }*/
    
    $msglc->groupBy('debtor_id_1c');
    
    $msgs_lc = $msglc->get();
}
?>
<div class="panel panel-default losscalls-window closed">
    <div class="panel-heading">
        &nbsp;
        <button class="close">&times;</button>
    </div>
    <div class="panel-body">
        @if(isset($msgs_lc))
        @foreach($msgs_lc as $msg)
        <?php
        
        $respUserLC = \App\User::find($msg->responsible_user_id);
        if (!is_null($respUserLC)) {
            $respUserNameLC = $respUserLC->name;
        } else {
            $respUserNameLC = 'не определен';
        }
        ?>
        <div class="chat-msg alert alert-success">
            <?php echo html_entity_decode($msg->text); ?>
            <br>
            <br>
            <p><small class='pull-right'><span class='msg-datetime' data-msg-datetime="{{$msg->created_at}}">{{with(new Carbon\Carbon($msg->created_at))->format('d.m.y H:i')}}</span>, отв: {{$respUserNameLC}}</small></p>
        </div>
        @endforeach
        @endif
    </div>
    
</div>
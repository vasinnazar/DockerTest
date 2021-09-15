<?php
if(!is_null(Auth::user())){
    if(config('app.version_type')=='debtors') {
        $currentUserId = Auth::user()->id;
        $msgs = DB::connection('debtors')->table('messages')->limit(50)->where('recepient_id', $currentUserId)->whereIn('type', ['success', 'danger', 'warning'])->get();
    } else {
        $msgs = DB::connection('debtors')->table('messages')->limit(50)->whereNull('recepient_id')->orWhere('recepient_id',Auth::user()->id)->orderBy('created_at', 'desc')->get();
    }
}
?>
<div class="panel panel-default chat-window closed">
    <div class="panel-heading">
        &nbsp;
        <button class="close">&times;</button>
    </div>
    <div class="panel-body">
        @if(isset($msgs))
        @foreach($msgs as $msg)
        <div class="chat-msg alert alert-{{$msg->type}}">
            <?php echo html_entity_decode($msg->text); ?>
            <br>
            <br>
            <p><small class='pull-right'><span class='msg-datetime' data-msg-datetime="{{$msg->created_at}}">{{with(new Carbon\Carbon($msg->created_at))->format('d.m.y H:i')}}</span> от {{$msg->caption}}</small></p>
        </div>
        @endforeach
        @endif
    </div>
    
</div>
<?php

namespace App;

use Illuminate\Database\Eloquent\Model,
    Illuminate\Database\Eloquent\SoftDeletes,
    Illuminate\Support\Facades\Log;

class RemoveRequest extends Model {

    use SoftDeletes;

    protected $table = 'remove_requests';
    protected $fillable = ['status', 'comment', 'doc_id', 'doc_type', 'requester_id', 'user_id'];

    const STATUS_CLAIMED = 0;
    const STATUS_DONE = 1;

    public function requester() {
        return $this->belongsTo('App\User', 'requester_id');
    }

    public function user() {
        return $this->belongsTo('App\User', 'user_id');
    }

    static function setDone($doc_id, $doc_type) {
        $remreq = RemoveRequest::where('doc_id', $doc_id)->where('doc_type', $doc_type)->first();
        if (is_null($remreq)) {
            Log::error('RemoveRequest.setDone 3: remreq is null', ['doc_id' => $doc_id, 'doc_type' => $doc_type]);
            return false;
        }
        if ($remreq->update(['status' => RemoveRequest::STATUS_DONE])) {
            Log::info('RemoveRequest.setDone 1');
            return true;
        } else {
            Log::error('RemoveRequest.setDone 2', ['doc_id' => $doc_id, 'doc_type' => $doc_type]);
            return false;
        }
    }

}

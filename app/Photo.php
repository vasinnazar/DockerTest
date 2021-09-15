<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Spylog\Spylog;
use Log;

class Photo extends Model {
    protected $table = 'photos';
    protected $fillable = ['customer_id','path','is_main','description'];
    
    public function setMain(){
        Log::info('Photo.setMain',['photo'=>$this]);
        $photos = Photo::where('claim_id',$this->claim_id)->get();
        DB::beginTransaction();
        foreach ($photos as $photo) {
            $photo->is_main = ($photo->id == $this->id) ? 1 : 0;
            if ($photo->save()) {
                Spylog::logModelAction(Spylog::ACTION_UPDATE, 'photos', $photo);
            } else {
                DB::rollback();
                return false;
            }
        }
        DB::commit();
        return true;
    }
}

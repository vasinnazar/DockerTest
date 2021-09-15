<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\MySoap;
use DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Auth;
use App\Spylog\Spylog;

class IssueClaim extends Model {

    protected $table = 'issue_claims';
    protected $fillable = ['user_id', 'subdivision_id', 'reason', 'comment', 'money', 'passport_id', 'order_type_id'];

    public function passport() {
        return $this->belongsTo('App\Passport', 'passport_id');
    }

    public function subdivision() {
        return $this->belongsTo('App\Subdivision', 'subdivision_id');
    }

    public function user() {
        return $this->belongsTo('App\User', 'user_id');
    }

    static function getIssueOrderTypesTextIds() {
        return[
//            OrderType::PODOTCHET,
            OrderType::CANC,
            OrderType::POCHTA,
            OrderType::BUYEQUIP,
            OrderType::COMRASHOD,
            OrderType::INTERNET,
            OrderType::HOZRASHOD
        ];
    }
    /**
     * Возвращает принадлежит ли переданный тип ордера к ордерам на подотчет
     * @param int $orderTypeId
     * @return boolean
     */
    static function isOrderTypeForIssueClaim($orderTypeId) {
        return in_array($orderTypeId, OrderType::whereIn('text_id', IssueClaim::getIssueOrderTypesTextIds())->lists('id')->toArray());
    }
    /**
     * Сохранить заявку и в арм и в 1с
     * @return boolean
     */
    public function saveThrough1c($myres=false) {
        $data = [
            'money' => $this->money,
            'reason' => $this->reason,
            'comment' => $this->comment,
            'passport_series' => $this->passport->series,
            'passport_number' => $this->passport->number,
            'customer_id_1c' => $this->passport->customer->id_1c,
            'user_id_1c' => $this->user->id_1c,
            'subdivision_id_1c' => $this->subdivision->name_id,
            'order_Type' => $this->order_type_id,
            'type' => 'CreateClaimForIssue'
        ];
        if (!is_null($this->data)) {
            $items = json_decode($this->data);
            \PC::debug($items);
            $data['items'] = [];
            foreach ($items as $item) {
                $data['items'][] = [
                    'goal' => $item->ic_goal,
                    'money' => StrUtils::rubToKop($item->ic_money)
                ];
            }
        }
        $res1c = MySoap::sendExchangeArm(MySoap::createXML($data));

        if (!isset($res1c->result) || $res1c->result == 0) {
            if($myres){
                return new MyResult(false,(isset($res1c->error))?$res1c->error:Utils\StrLib::ERR);
            } else {
                return false;
            }
        }
        if (isset($res1c->value)) {
            $this->id_1c = (string) $res1c->value;
        }
        if($myres){
            return new MyResult($this->save());
        } else {
            return $this->save();
        }
    }
    /**
     * Удалить заявку и в арм и в 1с
     * @return \App\MyResult
     */
    public function deleteThrough1c() {
        $res1c = MySoap::sendExchangeArm(MySoap::createXML(['Number' => $this->id_1c, 'type' => 'Delete', 'doc_type' => MySoap::ITEM_ISSUE_CLAIM]));
        if ((int) $res1c->result == 0) {
            return new MyResult(false,(string) $res1c->error);
        }
        if ($this->delete()) {
            $remreq = RemoveRequest::where('doc_id', $this->id)->where('doc_type', MySoap::ITEM_ISSUE_CLAIM)->first();
            if (!is_null($remreq)) {
                $remreq->update(['status' => RemoveRequest::STATUS_DONE, 'user_id' => Auth::user()->id]);
            }
            Spylog::logModelAction(Spylog::ACTION_DELETE, $this->table, $this);
            return new MyResult(true);
        } else {
            return new MyResult(false);
        }
    }
    /**
     * Подать заявку на удаление
     * @return \App\MyResult
     */
    public function claimForRemove() {
        if (!is_null($this->claimed_for_remove) && $this->claimed_for_remove != '0000-00-00 00:00:00') {
            return new MyResult(false,'Уже помечен на удаление');
        } else {
            DB::beginTransaction();
            $this->claimed_for_remove = Carbon::now();
            $remreq = RemoveRequest::create(['doc_type' => MySoap::ITEM_ISSUE_CLAIM, 'doc_id' => $this->id, 'requester_id' => Auth::user()->id, 'comment' => '', 'status' => RemoveRequest::STATUS_CLAIMED]);
            if (is_null($remreq) || is_null($remreq->id)) {
                DB::rollback();
                return new MyResult(false,'Не удалось создать заявку на удаление');
            }
            if (!$this->save()) {
                DB::rollback();
                return new MyResult(false,'Не удалось создать заявку на удаление');
            }
            DB::commit();
            return new MyResult(true);
        }
    }

}

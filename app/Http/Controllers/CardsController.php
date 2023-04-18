<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Carbon\Carbon,
    App\Customer,
    App\CardChange,
    Yajra\DataTables\Facades\DataTables,
    Illuminate\Support\Facades\Validator,
    Illuminate\Support\Facades\DB,
    Auth,
    App\Utils\HtmlHelper,
    App\Utils\StrLib,
    App\Card;
use App\MySoap;

class CardsController extends Controller {

    public function __construct() {
        
    }

    public function customerCards($customer_id) {
        return Card::where('customer_id', $customer_id)->get();
    }

    public function disableCard($card_id) {
        $card = Card::find($card_id);
        if (!is_null($card)) {
            $card->status = Card::STATUS_CLOSED;
            return ($card->save()) ? 1 : 0;
        } else {
            return 0;
        }
    }

    public function enableCard($card_id) {
        $card = Card::find($card_id);
        if (!is_null($card)) {
            $card->status = Card::STATUS_ACTIVE;
            return ($card->save()) ? 1 : 0;
        } else {
            return 0;
        }
    }

    public function addCard(Request $req) {
        if ($req->has('card_number') && $req->has('customer_id') && $req->has('secret_word')) {
            return (Card::createCard($req->card_number, $req->secret_word, $req->customer_id)) ? 1 : 0;
        } else {
            return 0;
        }
    }

    public function cardChangesList() {
        return view('cards.list');
    }

    public function getCardChangesList(Request $req) {
        $items = CardChange::select('card_changes.id as ccid', 'card_changes.created_at as ccdate', 'card_changes.old_card_number as ccold', 'card_changes.new_card_number as ccnew', 'users.name as username', 'passports.fio', 'card_changes.claimed_for_remove as ccclaim')
                ->leftJoin('cards', 'cards.card_number', '=', 'card_changes.new_card_number')
                ->leftJoin('customers', 'customers.id', '=', 'cards.customer_id')
                ->leftJoin('passports', 'customers.id', '=', 'passports.customer_id')
                ->leftJoin('users', 'users.id', '=', 'card_changes.subdivision_id')
                ->groupBy('card_changes.id');
        if (!is_null(Auth::user()) && !Auth::user()->isAdmin()) {
            $items->where('card_changes.subdivision_id', Auth::user()->subdivision_id);
        }
        $collection = Datatables::of($items)
            ->editColumn('ccdate', function ($item) {
                return with(new Carbon($item->ccdate))->format('d.m.Y H:i:s');
            })
            ->addColumn('actions', function ($item) {
                $html = '<div class="btn-group btn-group-sm">';
                if (!is_null(Auth::user()) && Auth::user()->isAdmin()) {
                    $html .= HtmlHelper::Buttton(url('cardchanges/remove/' . $item->ccid),
                        ['class' => 'btn btn-default btn-sm', 'glyph' => 'remove']);
                }
                if (is_null($item->ccclaim)) {
                    $html .= HtmlHelper::Buttton(url('cardchanges/claimforremove/' . $item->ccid),
                        ['class' => 'btn btn-default btn-sm', 'glyph' => 'exclamation-sign']);
                } else {
                    $html .= HtmlHelper::Buttton(null,
                        ['disabled' => true, 'class' => 'btn btn-danger btn-sm', 'glyph' => 'exclamation-sign']);
                }
                $html .= '</div>';
                return $html;
            })
            ->removeColumn('ccclaim')
            ->filter(function ($query) use ($req) {
                if ($req->has('fio')) {
                    $query->where('passports.fio', 'like', "%" . $req->get('fio') . "%");
                }
                if ($req->has('old_card_number')) {
                    $query->where('card_changes.old_card_number', '=', $req->get('old_card_number'));
                }
                if ($req->has('new_card_number')) {
                    $query->where('card_changes.new_card_number', '=', $req->get('new_card_number'));
                }
            })
            ->rawColumn(['actions'])
            ->toJson();
        return $collection;
    }

    public function addCardChange(Request $req) {
        $validator = Validator::make($req->all(), ['old_card_number' => 'required|numeric', 'new_card_number' => 'required|numeric', 'secret_word' => 'required']);
        if ($validator->fails()) {
            return redirect()->back()->with('msg', 'Переданы не все необходимые параметры')->with('class', 'alert-danger');
        }
        if (Card::where('card_number', $req->new_card_number)->count() > 0) {
            return redirect()->back()->with('msg', 'Карта с таким номером уже есть')->with('class', 'alert-danger');
        }
        DB::beginTransaction();
        $cardchange = new CardChange($req->all());
        $cardchange->user_id = Auth::user()->id;
        $cardchange->subdivision_id = Auth::user()->subdivision_id;
//        $oldCard = Card::where('card_number', $req->old_card_number)->first();
//        if (!is_null($oldCard)) {
//            $oldCard->status = Card::STATUS_CLOSED;
//            if (!$oldCard->save()) {
//                DB::rollback();
//                return redirect()->back()->with('msg', 'Не удалось обновить старую карту')->with('class', 'alert-danger');
//            }
//        }
//        $newCard = new Card();
//        $newCard->customer_id = $oldCard->customer_id;
//        $newCard->secret_word = $req->secret_word;
//
//        if (!$newCard->save()) {
//            DB::rollback();
//            return redirect()->back()->with('msg', 'Не удалось сохранить карту')->with('class', 'alert-danger');
//        }

        if ($cardchange->save()) {
            DB::commit();
            return redirect()->back()->with('msg', 'Сохранено')->with('class', 'alert-success');
        } else {
            DB::rollback();
            return redirect()->back()->with('msg', 'Ошибка при сохранении')->with('class', 'alert-danger');
        }
    }

    public function removeCardChange($id) {
        $change = CardChange::find($id);
        if (is_null($change)) {
            return redirect()->back()->with('msg', 'Замена не найдена')->with('class', 'alert-danger');
        }
        if (!$change->delete()) {
            return redirect()->back()->with('msg', 'Ошибка на удалении')->with('class', 'alert-danger');
        }
        return redirect()->back()->with('msg', 'Удалено')->with('class', 'alert-success');
    }

    public function claimForRemoveCardChange($id) {
        $change = CardChange::find($id);
        if (is_null($change)) {
            return redirect()->back()->with('msg', 'Замена не найдена')->with('class', 'alert-danger');
        }
        $change->claimed_for_remove = Carbon::now()->format('Y-m-d H:i:s');
        if (!$change->save()) {
            return redirect()->back()->with('msg', 'Ошибка на сохранении')->with('class', 'alert-danger');
        }
        return redirect()->back()->with('msg', 'Сохранено')->with('class', 'alert-success');
    }

    /**
     * Проверяем есть ли карта на подразделение, 
     * а заодно если нужно проверяем есть ли в наличии старые карты
     * @param Request $req
     * @return type
     */
    public function checkCard(Request $req) {
        $result = ['result' => 0, 'has_old_cards' => 0, 'allow_use_new_cards' => Auth::user()->subdivision->allow_use_new_cards];
        if (!$req->has('card_number')) {
            $result['msg_err'] = StrLib::ERR_NO_PARAMS;
            return $result;
        } else {
            $xml = \App\MySoap::createXML(['card_barcode' => $req->card_number, 'date' => Carbon::now()->format('YmdHis'), 'subdivision_id_1c' => Auth::user()->subdivision->name_id, 'type' => 'CheckCard']);
            $res1c = \App\MySoap::sendExchangeArm($xml);
            if (isset($res1c->result)) {
                if ((int) $res1c->result == 1) {
                    if ($req->has('check_for_old') && $this->subdivisionHasOldCards()) {
                        $result['has_old_cards'] = 1;
                    }
                    if (isset($res1c->answer) && (int) $res1c->answer == 1) {
                        $result['result'] = 1;
                        $result['comment'] = (isset($res1c->comment)) ? (string) $res1c->comment : 'Карта на подразделении';
                        return $result;
                    } else {
                        $result['comment'] = (isset($res1c->comment)) ? (string) $res1c->comment : 'Карты нет на подразделении';
                        return $result;
                    }
                } else {
                    $result['msg_err'] = (isset($res1c->error)) ? (string) $res1c->error : StrLib::ERR;
                    return $result;
                }
            } else {
                $result['msg_err'] = StrLib::ERR_1C;
                return $result;
            }
        }
    }

    public function subdivisionHasOldCards() {
        $today = Carbon::now()->format('Ymd');
        $user_id_1c = Auth::user()->id_1c;
        $subdivision_id_1c = Auth::user()->subdivision->name_id;
        $res1c = MySoap::getDocsRegister(
                        [
                            'date_start' => $today,
                            'date_finish' => $today,
                            'subdivision_id_1c' => $subdivision_id_1c,
                            'user_id_1c' => $user_id_1c,
                            'type' => \App\MySoap::ITEM_SFP
                        ]
        );
        if (array_key_exists('cards', $res1c)) {
            foreach ($res1c['cards'] as $card) {
                if (substr($card['card_number'], 0, 4) == '2700') {
                    return true;
                }
            }
        }
        return false;
    }

}

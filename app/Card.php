<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Заявка на займ
 */
class Card extends Model {

    protected $table = 'cards';
    protected $fillable = ['card_number', 'secret_word', 'status', 'customer_id'];

    const STATUS_ACTIVE = 0;
    const STATUS_CLOSED = 1;

    public function customer() {
        return $this->belongsTo('App\Customer');
    }

    public static function createCard($card_number, $secret_word, $customer_id) {
        if ($card_number == 0 || $card_number == '') {
            return 0;
        }
        if (is_null(Card::where('card_number', $card_number)->first())) {
            $cards = Card::where('customer_id', $customer_id)->get();
            foreach ($cards as $item) {
                $item->status = Card::STATUS_CLOSED;
                $item->save();
            }
            /**
             * добавить новую карту на клиента
             */
            $card = new Card();
            $card->card_number = $card_number;
            $card->secret_word = $secret_word;
            $card->customer_id = $customer_id;
            return $card->save();
        } else {
            return 0;
        }
    }

}

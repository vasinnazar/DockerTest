<?php

namespace App\Events\Infinity;

use App\Events\Event;
use App\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IncomingCallEvent extends Event implements ShouldBroadcast
{
    private $number;
    private $callId;
    private $userExtension;

    /**
     * IncomingCallEvent constructor.
     * @param $number
     * @param $callId
     * @param $userExtension
     */
    public function __construct($number, $callId, $userExtension)
    {
        $this->number = $number;
        $this->callId = $callId;
        $this->userExtension = $userExtension;
    }

    /**
     * @return array
     */
    public function broadcastWith()
    {
        $res = [
            'call_id' => $this->callId,
            'number' => $this->number,
        ] + $this->getData($this->number);

        $user = User::where('infinity_extension', $this->userExtension)->first();

        if ($user) {
            Cache::put('last_call_user_id' . $user->id, json_encode([
                'call_id' => $this->callId,
                'number' => $this->number,
                'extension' => $this->userExtension,
            ]),
                60
            );
        }
        Log::info("InfinityController.incomingCall ", ['arr' => $res]);
        return $res;
    }
    
    private function getData($number)
    {
        $fio = 'Не удалось определить ФИО';
        $debtorid = 0;
        $customer = \App\Customer::where('telephone', $number)->first();
        $username = 'Ответственный не определен';
        
        if (!is_null($customer)) {
            $passport = $customer->getLastPassport();
            
            if (!is_null($passport)) {
                $fio = $passport->fio;
            }
            
            $debtor = \App\Debtor::where('customer_id_1c', $customer->id_1c)->where('is_debtor', 1)->first();
            
            if (!is_null($debtor)) {
                $debtorid = $debtor->id;
                $user = User::where('id_1c', $debtor->responsible_user_id_1c)->first();
                
                if (!is_null($user)) {
                    $username = $user->name;
                }
            }
        }
        
        return [
            'fio' => $fio,
            'debtorid' => $debtorid,
            'username' => $username
        ];
    }

    /**
     * @return string
     */
    public function broadcastAs()
    {
        return 'incoming_call';
    }

    /**
     * @return string[]
     */
    public function broadcastOn()
    {
        return [
            'infinity_calls_' . $this->userExtension,
        ];
    }
}

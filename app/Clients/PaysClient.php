<?php

namespace App\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;

class PaysClient
{
    private $url;
    private $client;

    public function __construct()
    {
        $this->url = config('services.pays.url');
        $this->client = new Client(
            [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
            ]
        );
    }

    public function getInfoByCustomerId1c(string $customerId1c): Collection
    {
        $response = $this->client->get($this->url . '/api/v1/customers?external_id=' . $customerId1c);
        return collect(\GuzzleHttp\json_decode($response->getBody()->getContents()));
    }

    public function createPayment(
        string $customer1cId,
        int $money,
        string $loan1cId,
        int $purposeId,
        int $isRecurrent = 0,
        string $cardExternalId = null,
        string $details = null
    )
    {
        $response = $this->client->request(
            'POST',
            $this->url . '/api/v1/payments',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                RequestOptions::JSON => [
                    'amount' => $money,
                    'customer_external_id' => $customer1cId,
                    'loan_external_id' => $loan1cId,
                    'details' => $details ? json_encode($details) : null,
                    'purpose_id' => $purposeId,
                    'is_recurrent' => $isRecurrent,
                    'card_external_id' => $cardExternalId
                ]
            ]
        );
        return json_decode($response->getBody()->getContents(), true);
    }
}
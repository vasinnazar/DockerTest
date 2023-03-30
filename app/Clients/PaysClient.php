<?php

namespace App\Clients;

use GuzzleHttp\Client;
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
}


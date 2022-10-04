<?php

namespace App\Clients;

use GuzzleHttp\Client;

class ArmClient
{
    private $url;
    private $client;

    public function __construct()
    {
        $this->url = config('services.arm.url');
        $this->client = new Client(
            [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'auth' => [
                    config('services.arm.arm_login'),
                    config('services.arm.arm_password'),
                ]
            ]
        );
    }

    /**
     * @param string $userId1C
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUserById1c(string $userId1C)
    {
        $response = $this->client->request(
            'GET',
            $this->url . '/api/v1/users?id_1c=' . $userId1C
        );
        return json_decode($response->getBody()->getContents(), true);
    }
}

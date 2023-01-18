<?php

namespace App\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

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

    /**
     * @param string $loanId1c
     * @return \Illuminate\Support\Collection
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrdersByLoanId1c(string $loanId1c)
    {
        $response = $this->client->request(
            'GET',
            $this->url . '/api/v1/loans?loan_id_1c=' . $loanId1c
        );

        $loanArm = collect(\GuzzleHttp\json_decode($response->getBody()->getContents()));
        if (!empty($loanArm->first())) {
            $responseOrders = $this->client->request(
                'GET',
                $this->url . '/api/v1/loans/' . $loanArm->first()->id . '/orders'
            );
            return collect(\GuzzleHttp\json_decode($responseOrders->getBody()->getContents()));
        }

        return collect();
    }


    public function sendOffer($options)
    {
        $response = $this->client->post(
            $this->url . '/api/repayments/offers',
            [RequestOptions::JSON => $options]
        );
        return json_decode($response->getBody()->getContents());
    }
}

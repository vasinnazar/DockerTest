<?php

namespace App\Clients;

use App\Debtor;
use Carbon\Carbon;
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
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUserById1c(string $userId1C)
    {
        $response = $this->client->get($this->url . '/api/v1/users?id_1c=' . $userId1C);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function getUserById(int $userId)
    {
        $response = $this->client->get($this->url . '/api/v1/users/' . $userId);
        return collect(\GuzzleHttp\json_decode($response->getBody()->getContents()));
    }
    public function getSubdivisions()
    {
        $response = $this->client->get($this->url . '/api/v1/subdivisions');
        return collect(\GuzzleHttp\json_decode($response->getBody()->getContents()));
    }

    public function getLoanById1c(string $loanId1c)
    {
        $response = $this->client->get($this->url . '/api/v1/loans?loan_id_1c=' . $loanId1c);
        return collect(\GuzzleHttp\json_decode($response->getBody()->getContents()));
    }
    public function getHasPeaceClaim(string $loanId1c)
    {
        $response = $this->client->get($this->url . '/api/repayments/offers/status?loan_id_1c=' . $loanId1c);
        return json_decode($response->getBody()->getContents(), true);
    }
    public function getCustomerById(int $customerId)
    {
        $response = $this->client->get($this->url . "/api/v1/customers/{$customerId}?with_all=1");
        return collect(\GuzzleHttp\json_decode($response->getBody()->getContents()));
    }

    /**
     * @param string $loanId1c
     * @return \Illuminate\Support\Collection
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrdersById(int $id)
    {
        $response = $this->client->get($this->url . '/api/v1/loans/' . $id . '/orders');
        return collect(\GuzzleHttp\json_decode($response->getBody()->getContents()));
    }

    public function getOffers(string $loanId1c)
    {
        $response = $this->client->get($this->url . '/api/repayments/offers?loan_id_1c=' . $loanId1c);
        return collect(\GuzzleHttp\json_decode($response->getBody()->getContents()));
    }

    public function sendRepaymentOffer(
        string $repaymentTypeId,
        int $times,
        int $amount,
        string $loanId1c,
        Carbon $endAt,
        Carbon $startAt = null,
        bool $prepaid = false,
        bool $multiple = true
    ) {
        $response = $this->client->post(
            $this->url . '/api/repayments/offers',
            [
                RequestOptions::JSON => [
                    'repayment_type_id' => $repaymentTypeId,
                    'times' => $times,
                    'amount' => $amount,
                    'start_at' => $startAt ? $startAt->format('Y-m-d') : Carbon::now()->format('Y-m-d'),
                    'end_at' => $endAt->format('Y-m-d'),
                    'loan_id_1c' => $loanId1c,
                    'prepaid' => $prepaid,
                    'multiple' => $multiple
                ]
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    public function updateOffer(int $id, $options)
    {
        $response = $this->client->post(
            $this->url . '/api/repayments/offers/' . $id,
            [RequestOptions::JSON => $options]
        );
        return json_decode($response->getBody()->getContents());
    }

    public function getTinkoffLink(Debtor $debtor, float $amount, string $phone)
    {
        $response = $this->client->get($this->url . '/api/tinkoff/init?amount=' . $amount
            . '&loan_1c_id=' . $debtor->loan_id_1c
            . '&order_id=&customer_id=' . $debtor->customer_id_1c
            . '&phone=' . $phone
            . '&token=' . crypt('gfhjkmhfplsdfnhb12332hfp.', 'shitokudosai')
            . '&version=2&details=null&order_type_id=&payment_type_id=&paysystem_type_id=&'
            . 'notification_url=https://xn--j1ab.xn--80ajiuqaln.xn--p1ai/api/payments/notification&success_url='
        );
        return json_decode($response->getBody()->getContents());
    }
}

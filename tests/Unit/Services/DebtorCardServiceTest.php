<?php

namespace Tests\Unit\Services;

use AdsourcesSeeder;
use App\about_client;
use App\Claim;
use App\Customer;
use App\Debtor;
use App\Loan;
use App\LoanType;
use App\Passport;
use App\Repositories\CustomerRepository;
use App\Repositories\AboutClientRepository;
use App\Repositories\PassportRepository;
use App\Services\DebtorCardService;
use App\Subdivision;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DebtorCardServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DebtorCardService $debtorCardService;
    private $user;
    private $debtors;
    private $fakePhone = '123456789';
    private CustomerRepository $customerRepository;
    private AboutClientRepository $aboutClientRepository;
    private PassportRepository $passportRepository;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->customerRepository = app()->make(CustomerRepository::class);
        $this->aboutClientRepository = app()->make(AboutClientRepository::class);
        $this->passportRepository = app()->make(PassportRepository::class);
    }
    public function setUp(): void
    {
        parent::setUp();
        $this->debtorCardService = app()->make(DebtorCardService::class);
        factory(Subdivision::class)->create();
        $this->user = factory(User::class)->create();
        factory(LoanType::class)->create();
        $this->debtors = factory(Debtor::class, 'debtor', 2)->create();
        foreach ($this->debtors as $debtor) {
            $customer = factory(Customer::class)->create([
                'id_1c' => $debtor->customer_id_1c
            ]);
            $passport = factory(Passport::class)->create([
                'series' => $debtor->passport_series,
                'number' => $debtor->passport_number,
                'customer_id' => $customer->id
            ]);
            $claim = factory(Claim::class)->create([
                'customer_id' => $customer->id,
                'passport_id' => $passport->id
            ]);
            factory(Loan::class)->create([
                'id_1c' => $debtor->loan_id_1c,
                'claim_id' => $claim->id
            ]);
            factory(about_client::class)->create([
                'customer_id' => $customer->id
            ]);
        }
    }
    public function testGetEqualContactsDebtorsWithoutCustomer()
    {
        $debtor = $this->debtors->first();
        $debtor->customer = null;
        $debtorsEqualPhonesAndAddress = $this->debtorCardService->getEqualContactsDebtors($debtor);
        $this->assertTrue($debtorsEqualPhonesAndAddress->isEmpty());
    }
    public function testGetEqualContactsDebtorsWithoutPassport()
    {
        $debtor = $this->debtors->first();
        $debtor->passport = null;
        $debtorsEqualPhonesAndAddress = $this->debtorCardService->getEqualContactsDebtors($debtor);
        $this->assertTrue($debtorsEqualPhonesAndAddress->isEmpty());
    }
    public function testGetEqualContactsDebtorsWithoutAbout()
    {
        $debtor = $this->debtors->first();
        $debtor->customer->about_clients = null;
        $debtorsEqualPhonesAndAddress = $this->debtorCardService->getEqualContactsDebtors($debtor);
        $this->assertTrue($debtorsEqualPhonesAndAddress->isEmpty());
    }
    public function testGetEqualContactsDebtorsNoMatchesPhones()
    {
        $debtor = $this->debtors->first();
        $debtorsEqualPhonesAndAddress = $this->debtorCardService->getEqualContactsDebtors($debtor);
        $keysPhonesSearch = [
            'equal_telephone',
            'equal_telephonehome',
            'equal_telephoneorganiz',
            'equal_telephonerodstv',
            'equal_anothertelephone'
        ];
        foreach ($keysPhonesSearch as $key) {
            $this->assertTrue($debtorsEqualPhonesAndAddress->get($key)->isEmpty());
        }
    }
    public function testGetEqualContactsDebtorsNoTelephone()
    {
        $debtor = $this->debtors->first();
        $keysPhonesSearch = [
            'equal_telephone',
            'equal_telephonehome',
            'equal_telephoneorganiz',
            'equal_telephonerodstv',
            'equal_anothertelephone'
        ];
        $emptyPhones = [null, 'нет', ''];
        foreach ($emptyPhones as $phone) {
            $this->customerRepository->update($debtor->customer->id, ['telephone' => $phone]);
            $this->aboutClientRepository->update(
                $debtor->customer->about_clients->last()->id, ['telephonehome' => $phone]
            );
            $this->aboutClientRepository->update(
                $debtor->customer->about_clients->last()->id, ['telephoneorganiz' => $phone])
            ;
            $this->aboutClientRepository->update(
                $debtor->customer->about_clients->last()->id, ['telephonerodstv' => $phone]
            );
            $this->aboutClientRepository->update(
                $debtor->customer->about_clients->last()->id, ['anothertelephone' => $phone]
            );
            $debtorsEqualPhonesAndAddress = $this->debtorCardService->getEqualContactsDebtors($debtor);
            foreach ($keysPhonesSearch as $key) {
                $this->assertTrue($debtorsEqualPhonesAndAddress->get($key)->isEmpty());
            }
        }
    }
    public function testGetEqualContactsDebtorsPhoneMatch()
    {
        $debtorSearch = $this->debtors->first();
        $lastCustomerid = $this->debtors->last()->customer->id;
        $this->customerRepository->update($lastCustomerid, [
            'telephone' => $debtorSearch->customer->telephone,
        ]);
        $debtorsEqualPhones = $this->debtorCardService->getEqualContactsDebtors($debtorSearch);
        $this->assertEquals($debtorsEqualPhones->get('equal_telephone')->first()->id, $lastCustomerid);
    }
//    public function testGetEqualContactsDebtorsTelephoneMatch()
//    {
//        $debtorSearch = $this->debtors->first();
//        $aboutClientEqualPhone = $this->debtors->last()->customer->about_clients->last();
//        $typesPhone = ['telephonehome', 'telephoneorganiz', 'telephonerodstv', 'anothertelephone'];
//        foreach ($typesPhone as $typePhone) {
//            $debtorSearch->customer->about_clients->last()->$typePhone = $this->fakePhone;
//            $this->aboutClientRepository->update($aboutClientEqualPhone->id, [$typePhone => $this->fakePhone]);
//            $debtorsEqualPhones = $this->debtorCardService->getEqualContactsDebtors($debtorSearch);
//            $this->assertEquals($debtorsEqualPhones->get('equal_'.$typePhone)->first()->id, $aboutClientEqualPhone->id);
//        }
//    }
//    public function testGetEqualContactsDebtorsAllTelephonehomeMatch()
//    {
//        $aboutClientIdAll = $this->aboutClientRepository->getAll()->pluck('id')->toArray();
//        asort($aboutClientIdAll);
//        $typesPhone = ['telephonehome'];
//        foreach ($typesPhone as $typePhone) {
//            about_client::whereIn('id', $aboutClientIdAll)->update([$typePhone => $this->fakePhone]);
//            $aboutClientSearchPhone = $this->debtors->first()->customer->about_clients->first();
//            $debtorsEqualPhones = $this->debtorCardService->getEqualContactsDebtors($this->debtors->first());
//            $aboutClientIdResult = $debtorsEqualPhones->get('equal_'.$typePhone)->pluck('id')->toArray();
//            $aboutClientIdResult = array_merge($aboutClientIdResult, [$aboutClientSearchPhone->id]);
//            asort($aboutClientIdResult);
//            $this->assertEquals(array_values($aboutClientIdResult), array_values($aboutClientIdAll));
//        }
//    }
//    public function testGetEqualContactsDebtorsAllTelephoneorganizMatch()
//    {
//        $aboutClientIdAll = $this->aboutClientRepository->getAll()->pluck('id')->toArray();
//        asort($aboutClientIdAll);
//        $typesPhone = ['telephoneorganiz'];
//        foreach ($typesPhone as $typePhone) {
//            about_client::whereIn('id', $aboutClientIdAll)->update([$typePhone => $this->fakePhone]);
//            $aboutClientSearchPhone = $this->debtors->first()->customer->about_clients->first();
//            $debtorsEqualPhones = $this->debtorCardService->getEqualContactsDebtors($this->debtors->first());
//            $aboutClientIdResult = $debtorsEqualPhones->get('equal_'.$typePhone)->pluck('id')->toArray();
//            $aboutClientIdResult = array_merge($aboutClientIdResult, [$aboutClientSearchPhone->id]);
//            asort($aboutClientIdResult);
//            $this->assertEquals(array_values($aboutClientIdResult), array_values($aboutClientIdAll));
//        }
//    }
//    public function testGetEqualContactsDebtorsAllTelephonerodstvMatch()
//    {
//        $aboutClientIdAll = $this->aboutClientRepository->getAll()->pluck('id')->toArray();
//        asort($aboutClientIdAll);
//        $typesPhone = ['telephonerodstv'];
//        foreach ($typesPhone as $typePhone) {
//            about_client::whereIn('id', $aboutClientIdAll)->update([$typePhone => $this->fakePhone]);
//            $aboutClientSearchPhone = $this->debtors->first()->customer->about_clients->first();
//            $debtorsEqualPhones = $this->debtorCardService->getEqualContactsDebtors($this->debtors->first());
//            $aboutClientIdResult = $debtorsEqualPhones->get('equal_'.$typePhone)->pluck('id')->toArray();
//            $aboutClientIdResult = array_merge($aboutClientIdResult, [$aboutClientSearchPhone->id]);
//            asort($aboutClientIdResult);
//            $this->assertEquals(array_values($aboutClientIdResult), array_values($aboutClientIdAll));
//        }
//    }
//    public function testGetEqualContactsDebtorsAllAnothertelephoneMatch()
//    {
//        $aboutClientIdAll = $this->aboutClientRepository->getAll()->pluck('id')->toArray();
//        asort($aboutClientIdAll);
//        $typesPhone = ['anothertelephone'];
//        foreach ($typesPhone as $typePhone) {
//            about_client::whereIn('id', $aboutClientIdAll)->update([$typePhone => $this->fakePhone]);
//            $aboutClientSearchPhone = $this->debtors->first()->customer->about_clients->first();
//            $debtorsEqualPhones = $this->debtorCardService->getEqualContactsDebtors($this->debtors->first());
//            $aboutClientIdResult = $debtorsEqualPhones->get('equal_'.$typePhone)->pluck('id')->toArray();
//            $aboutClientIdResult = array_merge($aboutClientIdResult, [$aboutClientSearchPhone->id]);
//            asort($aboutClientIdResult);
//            $this->assertEquals(array_values($aboutClientIdResult), array_values($aboutClientIdAll));
//        }
//    }
//    public function testGetEqualContactsDebtorsAddressMatch()
//    {
//        $typesEqualAddress = [
//            'equal_addresses_fact_to_register',
//            'equal_addresses_fact_to_fact',
//            'equal_addresses_register_to_register',
//            'equal_addresses_register_to_fact'
//        ];
//        foreach ($typesEqualAddress as $typeEqualAddress) {
//            $debtorSearch = $this->debtors->first();
//            $debtorsEqualPhones = $this->debtorCardService->getEqualContactsDebtors($debtorSearch);
//            $passportIdAll = $this->passportRepository->getAll()->pluck('id')->toArray();
//            asort($passportIdAll);
//            $passportIdResult = $debtorsEqualPhones->get($typeEqualAddress)->pluck('id')->toArray();
//            $passportIdResult = array_merge($passportIdResult, [$debtorSearch->passport->id]);
//            asort($passportIdResult);
//            $this->assertEquals(array_values($passportIdResult), array_values($passportIdAll));
//        }
//    }
}

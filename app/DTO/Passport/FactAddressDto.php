<?php

namespace App\DTO\Passport;

use App\Passport;
use Spatie\DataTransferObject\DataTransferObject;

class FactAddressDto extends DataTransferObject
{
    public ?string $zip;
    public ?string $address_region;
    public ?string $address_district;
    public ?string $address_city;
    public ?string $address_street;
    public ?string $address_house;
    public ?string $address_building;
    public ?string $address_apartment;
    public ?string $address_city1;
    public int $id;

    public static function fromModel(Passport $passport): self
    {
        return new self([
            'zip' => $passport->fact_zip,
            'address_region' => $passport->fact_address_region,
            'address_district' => $passport->fact_address_district,
            'address_city' => $passport->fact_address_city,
            'address_street' => $passport->fact_address_street,
            'address_house' => $passport->fact_address_house,
            'address_building' => $passport->fact_address_building,
            'address_apartment' => $passport->fact_address_apartment,
            'address_city1' => $passport->fact_address_city1,
            'id' => $passport->id,
        ]);
    }

}

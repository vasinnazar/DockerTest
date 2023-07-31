<?php

namespace App\DTO\Passport;

use App\Passport;
use Spatie\DataTransferObject\DataTransferObject;

class RegAddressDto extends DataTransferObject
{
    public ?string $zip;
    public string $address_region;
    public ?string $address_district;
    public string $address_city;
    public string $address_street;
    public string $address_house;
    public ?string $address_building;
    public ?string $address_apartment;
    public ?string $address_city1;
    public int $id;

    public static function fromModel(Passport $passport): self
    {
        return new self([
            'zip' => $passport->zip,
            'address_region' => $passport->address_region,
            'address_district' => $passport->address_district,
            'address_city' => $passport->address_city,
            'address_street' => $passport->address_street,
            'address_house' => $passport->address_house,
            'address_building' => $passport->address_building,
            'address_apartment' => $passport->address_apartment,
            'address_city1' => $passport->address_city1,
            'id' => $passport->id,
        ]);
    }

}

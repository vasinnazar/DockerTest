<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static clean(string $name, string $value)
 * @method static suggest(string $name, string $value)
 * @see \Dadata\DadataClient
 */
class DadataFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dadata';
    }
}

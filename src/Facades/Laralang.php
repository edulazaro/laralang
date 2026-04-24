<?php

namespace EduLazaro\Laralang\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array alternates(array $params = [], bool $absolute = true)
 *
 * @see \EduLazaro\Laralang\Laralang
 */
class Laralang extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \EduLazaro\Laralang\Laralang::class;
    }
}

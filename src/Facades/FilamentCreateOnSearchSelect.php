<?php

namespace Xoshbin\FilamentCreateOnSearchSelect\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Xoshbin\FilamentCreateOnSearchSelect\FilamentCreateOnSearchSelect
 */
class FilamentCreateOnSearchSelect extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Xoshbin\FilamentCreateOnSearchSelect\FilamentCreateOnSearchSelect::class;
    }
}

<?php

namespace Xoshbin\FilamentCreateOnSearchSelect;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentCreateOnSearchSelectServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-create-on-search-select';

    public static string $viewNamespace = 'filament-create-on-search-select';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews(static::$viewNamespace);
    }
}

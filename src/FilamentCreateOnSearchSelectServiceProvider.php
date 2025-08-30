<?php

namespace Xoshbin\FilamentCreateOnSearchSelect;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
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

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('filament-create-on-search-select-styles', __DIR__ . '/../resources/dist/filament-create-on-search-select.css')->loadedOnRequest(),
            Js::make('filament-create-on-search-select-scripts', __DIR__ . '/../resources/dist/filament-create-on-search-select.js')->loadedOnRequest(),
        ], 'xoshbin/filament-create-on-search-select');
    }
}

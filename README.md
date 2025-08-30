# Filament Create On Search Select

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xoshbin/filament-create-on-search-select.svg?style=flat-square)](https://packagist.org/packages/xoshbin/filament-create-on-search-select)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/xoshbin/filament-create-on-search-select/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/xoshbin/filament-create-on-search-select/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/xoshbin/filament-create-on-search-select/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/xoshbin/filament-create-on-search-select/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/xoshbin/filament-create-on-search-select.svg?style=flat-square)](https://packagist.org/packages/xoshbin/filament-create-on-search-select)

A Filament form field component that extends the Select field with the ability to create new options on-the-fly. When users can't find the option they're looking for, they can click a "+" button to open a modal and create a new option directly from the select field.

## Installation

You can install the package via composer:

```bash
composer require xoshbin/filament-create-on-search-select
```

## Usage

### Basic Usage

Use the `CreateOnSearchSelect` component in your Filament forms:

```php
use Xoshbin\FilamentCreateOnSearchSelect\CreateOnSearchSelect;

CreateOnSearchSelect::make('category_id')
    ->label('Category')
    ->options(Category::pluck('name', 'id'))
    ->canCreateOption()
    ->createOptionAction(function (array $data) {
        return Category::create([
            'name' => $data['name'],
        ]);
    })
```

### Advanced Usage

#### Custom Create Form

You can customize the form fields shown in the create modal:

```php
CreateOnSearchSelect::make('category_id')
    ->label('Category')
    ->options(Category::pluck('name', 'id'))
    ->canCreateOption()
    ->createOptionForm('
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Category Name
            </label>
            <input
                type="text"
                x-model="createOptionData.name"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                placeholder="Enter category name"
                required
            />
        </div>
    ')
    ->createOptionAction(function (array $data) {
        return Category::create([
            'name' => $data['name'],
        ]);
    })
```

#### Custom Modal Labels

Customize the modal heading and button labels:

```php
CreateOnSearchSelect::make('category_id')
    ->label('Category')
    ->options(Category::pluck('name', 'id'))
    ->canCreateOption()
    ->createOptionModalHeading('Create New Category')
    ->createOptionModalSubmitActionLabel('Create Category')
    ->createOptionModalCancelActionLabel('Cancel')
    ->createOptionAction(function (array $data) {
        return Category::create([
            'name' => $data['name'],
        ]);
    })
```

### Available Methods

| Method | Description |
|--------|-------------|
| `canCreateOption(bool $condition = true)` | Enable/disable the create option functionality |
| `createOptionAction(Closure $action)` | Define the action to create a new option |
| `createOptionForm(string $form)` | Custom HTML form for the create modal |
| `createOptionModalHeading(string $heading)` | Set the modal heading text |
| `createOptionModalSubmitActionLabel(string $label)` | Set the submit button text |
| `createOptionModalCancelActionLabel(string $label)` | Set the cancel button text |

### Features

- ✅ Extends Filament's native Select component
- ✅ Modal-based option creation
- ✅ Customizable form fields
- ✅ Dark mode support
- ✅ Accessible keyboard navigation
- ✅ Custom styling support
- ✅ Works with relationships

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Khoshbin](https://github.com/Xoshbin)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

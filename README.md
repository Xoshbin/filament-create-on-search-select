# Filament Create On Search Select

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xoshbin/filament-create-on-search-select.svg?style=flat-square)](https://packagist.org/packages/xoshbin/filament-create-on-search-select)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/xoshbin/filament-create-on-search-select/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/xoshbin/filament-create-on-search-select/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/xoshbin/filament-create-on-search-select/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/xoshbin/filament-create-on-search-select/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/xoshbin/filament-create-on-search-select.svg?style=flat-square)](https://packagist.org/packages/xoshbin/filament-create-on-search-select)

A space-efficient Filament form field that solves the interface clutter problem of the original Select field's creation functionality. While Filament's native Select field adds a suffix button and icon for creating new records (which takes up valuable interface space), this component provides a cleaner, more intuitive solution.

## The Problem This Solves

Filament's default Select field with `createOptionForm()` adds visual clutter:
- ➕ Suffix button takes up horizontal space
- 🎯 Icon makes the field wider and less clean
- 📱 Poor mobile experience due to cramped interface
- 👁️ Breaks the visual flow of forms

## The Solution

This component provides the same searchable Select functionality as the original, but with a smarter approach to record creation:

✅ **Clean Interface**: No suffix buttons or icons cluttering your forms
✅ **Smart Suggestions**: When searching for a record that doesn't exist, shows "Create [search term]" suggestion
✅ **Seamless UX**: Click the suggestion to open the same modal creation experience
✅ **Space Efficient**: Maintains the original Select field's compact design
✅ **Fully Customizable**: All the same customization options as the original field

## Installation

You can install the package via composer:

```bash
composer require xoshbin/filament-create-on-search-select
```

## How It Works

1. **Search Experience**: Users type to search through existing records (same as original Select)
2. **Smart Detection**: When no matching records are found, shows "Create [search term]" suggestion
3. **Modal Creation**: Clicking the suggestion opens the familiar Filament modal for creating new records
4. **Seamless Integration**: New record is automatically selected and form continues normally

## Usage

### Basic Usage

Replace your existing Select field with `CreateOnSearchSelect`:

```php
use Xoshbin\FilamentCreateOnSearchSelect\CreateOnSearchSelect;

// Instead of this cluttered approach:
// Select::make('category_id')
//     ->options(Category::pluck('name', 'id'))
//     ->searchable()
//     ->createOptionForm([...]) // Adds suffix button + icon

// Use this clean approach:
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

### Key Features

**Interface Benefits:**
- ✅ **No visual clutter** - No suffix buttons or icons taking up space
- ✅ **Clean design** - Maintains the original Select field appearance
- ✅ **Mobile-friendly** - Better responsive experience without cramped buttons
- ✅ **Intuitive UX** - "Create [search term]" suggestions feel natural

**Technical Features:**
- ✅ Extends Filament's native Select component
- ✅ Modal-based option creation (same as original)
- ✅ Fully searchable with real-time filtering
- ✅ Customizable form fields and validation
- ✅ Dark mode support
- ✅ Accessible keyboard navigation (Arrow keys, Enter, Escape)
- ✅ Works with relationships and custom models
- ✅ Supports all original Select field options

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

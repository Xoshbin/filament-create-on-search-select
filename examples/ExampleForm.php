<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Models\Partner;
use App\Enums\Partners\PartnerType;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Xoshbin\FilamentCreateOnSearchSelect\CreateOnSearchSelect;

class CreateInvoice extends CreateRecord
{
    public static string $resource = InvoiceResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Example 1: Basic usage with Partner model
                CreateOnSearchSelect::make('customer_id')
                    ->label(__('invoice.customer'))
                    ->options(Partner::where('type', PartnerType::Customer)->pluck('name', 'id'))
                    ->canCreateOption()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label(__('partner.name'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->createOptionAction(function (array $data) {
                        return Partner::create([
                            'name' => $data['name'],
                            'type' => PartnerType::Customer,
                            'company_id' => \Filament\Facades\Filament::getTenant()->id,
                        ]);
                    })
                    ->required()
                    ->columnSpan(2),

                // Example 2: More complex form with multiple fields
                CreateOnSearchSelect::make('supplier_id')
                    ->label(__('invoice.supplier'))
                    ->options(Partner::where('type', PartnerType::Supplier)->pluck('name', 'id'))
                    ->canCreateOption()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label(__('partner.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('partner.email'))
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label(__('partner.phone'))
                            ->tel()
                            ->maxLength(20),
                        Textarea::make('address')
                            ->label(__('partner.address'))
                            ->rows(3),
                    ])
                    ->createOptionAction(function (array $data) {
                        return Partner::create([
                            'name' => $data['name'],
                            'email' => $data['email'] ?? null,
                            'phone' => $data['phone'] ?? null,
                            'address' => $data['address'] ?? null,
                            'type' => PartnerType::Supplier,
                            'company_id' => \Filament\Facades\Filament::getTenant()->id,
                        ]);
                    })
                    ->createOptionModalHeading(__('partner.create_supplier'))
                    ->createOptionModalSubmitActionLabel(__('partner.create'))
                    ->createOptionModalCancelActionLabel(__('common.cancel'))
                    ->columnSpan(2),

                // Example 3: Using with different label attribute
                CreateOnSearchSelect::make('category_id')
                    ->label(__('product.category'))
                    ->options(\App\Models\Category::pluck('title', 'id'))
                    ->canCreateOption()
                    ->createOptionForm([
                        TextInput::make('title')
                            ->label(__('category.title'))
                            ->required()
                            ->maxLength(100),
                        Textarea::make('description')
                            ->label(__('category.description'))
                            ->rows(2),
                    ])
                    ->createOptionAction(function (array $data) {
                        return \App\Models\Category::create([
                            'title' => $data['title'],
                            'description' => $data['description'] ?? null,
                            'company_id' => \Filament\Facades\Filament::getTenant()->id,
                        ]);
                    })
                    ->createOptionLabelAttribute('title') // Use 'title' instead of default 'name'
                    ->columnSpan(1),
            ]);
    }

    /**
     * Required Livewire method for create option functionality
     * This method must be added to any Livewire component that uses CreateOnSearchSelect
     */
    public function createNewOption(string $statePath, array $data)
    {
        $field = $this->getFormComponent($statePath);
        return $field->createNewOptionWithValidation($data);
    }
}

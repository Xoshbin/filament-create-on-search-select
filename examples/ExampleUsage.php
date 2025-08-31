<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Models\Customer;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Xoshbin\FilamentCreateOnSearchSelect\CreateOnSearchSelect;

class CreateInvoice extends CreateRecord
{

    protected static string $resource = InvoiceResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Example 1: Basic usage with customer selection
                CreateOnSearchSelect::make('customer_id')
                    ->label('Customer')
                    ->options(Customer::pluck('name', 'id'))
                    ->canCreateOption()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Customer Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20),
                    ])
                    ->createOptionAction(function (array $data) {
                        return Customer::create([
                            'name' => $data['name'],
                            'email' => $data['email'] ?? null,
                            'phone' => $data['phone'] ?? null,
                            'company_id' => auth()->user()->company_id,
                        ]);
                    })
                    ->required(),

                // Example 2: Custom modal labels
                CreateOnSearchSelect::make('category_id')
                    ->label('Category')
                    ->options(\App\Models\Category::pluck('name', 'id'))
                    ->canCreateOption()
                    ->createOptionModalHeading('Create New Category')
                    ->createOptionModalSubmitActionLabel('Create Category')
                    ->createOptionModalCancelActionLabel('Cancel')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->createOptionAction(function (array $data) {
                        return \App\Models\Category::create([
                            'name' => $data['name'],
                            'company_id' => auth()->user()->company_id,
                        ]);
                    }),

                // Other form fields...
                TextInput::make('invoice_number')
                    ->label('Invoice Number')
                    ->required(),

                // ... more fields
            ]);
    }
}

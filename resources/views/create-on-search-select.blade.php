@php
    $canCreateOption = $getCanCreateOption();
    $createOptionModalHeading = $getCreateOptionModalHeading();
    $createOptionModalSubmitActionLabel = $getCreateOptionModalSubmitActionLabel();
    $createOptionModalCancelActionLabel = $getCreateOptionModalCancelActionLabel();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            createOptionModalOpen: false,
            createOptionData: {},
            searchTerm: '',
            
            openCreateOptionModal() {
                this.createOptionModalOpen = true;
                this.createOptionData = {};
            },
            
            closeCreateOptionModal() {
                this.createOptionModalOpen = false;
                this.createOptionData = {};
            },
            
            async createOption() {
                try {
                    const response = await $wire.call('createOption', this.createOptionData);
                    if (response) {
                        // Add the new option to the select
                        const select = this.$refs.select;
                        const newOption = document.createElement('option');
                        newOption.value = response.id;
                        newOption.textContent = response.name || response.title || response.label;
                        newOption.selected = true;
                        select.appendChild(newOption);
                        
                        // Trigger change event
                        select.dispatchEvent(new Event('change'));
                        
                        this.closeCreateOptionModal();
                    }
                } catch (error) {
                    console.error('Error creating option:', error);
                }
            }
        }"
        x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-create-on-search-select-styles', package: 'xoshbin/filament-create-on-search-select'))]"
        x-load-js="[@js(\Filament\Support\Facades\FilamentAsset::getScriptSrc('filament-create-on-search-select-scripts', package: 'xoshbin/filament-create-on-search-select'))]"
    >
        <div class="relative">
            <select
                x-ref="select"
                {{
                    $attributes
                        ->merge($getExtraAttributes(), escape: false)
                        ->merge($getExtraInputAttributes(), escape: false)
                        ->class([
                            'fi-select-input block w-full border-none bg-transparent py-1.5 pe-8 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6',
                        ])
                }}
                @if ($isAutofocused()) autofocus @endif
                @if ($isDisabled()) disabled @endif
                @if ($isMultiple()) multiple @endif
                @if ($isRequired()) required @endif
                @if ($getPlaceholder()) placeholder="{{ $getPlaceholder() }}" @endif
                dusk="filament.forms.{{ $getStatePath() }}"
                id="{{ $getId() }}"
                wire:loading.attr="disabled"
                {{ $getExtraAlpineAttributeBag() }}
            >
                @if ($getPlaceholder())
                    <option value="">{{ $getPlaceholder() }}</option>
                @endif

                @foreach ($getOptions() as $value => $label)
                    <option
                        value="{{ $value }}"
                        @if ($isSelected($value)) selected @endif
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            @if ($canCreateOption)
                <button
                    type="button"
                    x-on:click="openCreateOptionModal()"
                    class="absolute inset-y-0 right-8 flex items-center px-2 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                    title="Create new option"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </button>
            @endif
        </div>

        @if ($canCreateOption)
            <!-- Create Option Modal -->
            <div
                x-show="createOptionModalOpen"
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                style="display: none;"
            >
                <div
                    x-show="createOptionModalOpen"
                    x-transition
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4"
                    x-on:click.away="closeCreateOptionModal()"
                >
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            {{ $createOptionModalHeading }}
                        </h3>

                        <div class="space-y-4">
                            @if ($getCreateOptionForm())
                                {!! $getCreateOptionForm() !!}
                            @else
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Name
                                    </label>
                                    <input
                                        type="text"
                                        x-model="createOptionData.name"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        placeholder="Enter option name"
                                    />
                                </div>
                            @endif
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <button
                                type="button"
                                x-on:click="closeCreateOptionModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                            >
                                {{ $createOptionModalCancelActionLabel }}
                            </button>
                            <button
                                type="button"
                                x-on:click="createOption()"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                {{ $createOptionModalSubmitActionLabel }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-dynamic-component>

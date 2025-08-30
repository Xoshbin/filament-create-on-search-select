@php
    $canCreateOption = $getCanCreateOption();
    $createOptionModalHeading = $getCreateOptionModalHeading();
    $createOptionModalSubmitActionLabel = $getCreateOptionModalSubmitActionLabel();
    $createOptionModalCancelActionLabel = $getCreateOptionModalCancelActionLabel();
    $createOptionLabelAttribute = $getCreateOptionLabelAttribute();
    $statePath = $getStatePath();
@endphp

<div
    {{
        $attributes
            ->merge($getExtraAttributes(), escape: false)
            ->class([
                'fi-fo-select relative',
            ])
    }}
    x-data="{
        createOptionModalOpen: false,
        createOptionData: {},
        searchTerm: '',
        isOpen: false,
        selectedIndex: -1,
        filteredOptions: [],
        showCreateOption: false,

        init() {
            this.updateFilteredOptions();
        },

        updateFilteredOptions() {
            const options = Array.from(this.$refs.select.options);
            this.filteredOptions = options.filter(option => {
                if (!option.value) return false; // Skip placeholder
                return option.textContent.toLowerCase().includes(this.searchTerm.toLowerCase());
            });

            // Show create option if search term doesn't match any existing options and canCreateOption is true
            this.showCreateOption = {{ $canCreateOption ? 'true' : 'false' }} &&
                                   this.searchTerm.length > 0 &&
                                   this.filteredOptions.length === 0;
            this.selectedIndex = -1;
        },

        openCreateOptionModal() {
            this.createOptionModalOpen = true;
            this.createOptionData = {};
            this.createOptionData['{{ $createOptionLabelAttribute }}'] = this.searchTerm;
            this.isOpen = false;
        },

        closeCreateOptionModal() {
            this.createOptionModalOpen = false;
            this.createOptionData = {};
        },

        async createOption() {
            try {
                // Call the Livewire component method to create the option
                const response = await $wire.call('createNewOption', '{{ $statePath }}', this.createOptionData);
                if (response) {
                    // Add the new option to the select
                    const select = this.$refs.select;
                    const newOption = document.createElement('option');
                    newOption.value = response.id;
                    newOption.textContent = response.{{ $createOptionLabelAttribute }} || response.name || response.title || response.label;
                    newOption.selected = true;
                    select.appendChild(newOption);

                    // Update the wire model
                    $wire.set('{{ $statePath }}', response.id);

                    this.closeCreateOptionModal();
                    this.searchTerm = '';
                    this.updateFilteredOptions();
                }
            } catch (error) {
                console.error('Error creating option:', error);
            }
        },

        selectOption(value) {
            this.$refs.select.value = value;
            $wire.set('{{ $statePath }}', value);
            this.isOpen = false;
            this.searchTerm = '';
        }
    }"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-create-on-search-select-styles', package: 'xoshbin/filament-create-on-search-select'))]"
    x-load-js="[@js(\Filament\Support\Facades\FilamentAsset::getScriptSrc('filament-create-on-search-select-scripts', package: 'xoshbin/filament-create-on-search-select'))]"
>
            <!-- Hidden select for form submission -->
            <select
                x-ref="select"
                {{
                    $attributes
                        ->merge($getExtraAttributes(), escape: false)
                        ->merge($getExtraInputAttributes(), escape: false)
                        ->class(['sr-only'])
                }}
                @if ($isAutofocused()) autofocus @endif
                @if ($isDisabled()) disabled @endif
                @if ($isMultiple()) multiple @endif
                @if ($isRequired()) required @endif
                dusk="filament.forms.{{ $getStatePath() }}"
                id="{{ $getId() }}"
                wire:loading.attr="disabled"
                {{ $getExtraAlpineAttributeBag() }}
                tabindex="-1"
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

            <!-- Custom search input that looks like Filament select -->
            <div class="relative">
                <input
                    type="text"
                    x-model="searchTerm"
                    x-on:input="updateFilteredOptions()"
                    x-on:focus="isOpen = true"
                    x-on:blur="setTimeout(() => isOpen = false, 200)"
                    x-on:keydown.arrow-down.prevent="selectedIndex = Math.min(selectedIndex + 1, filteredOptions.length + (showCreateOption ? 0 : -1))"
                    x-on:keydown.arrow-up.prevent="selectedIndex = Math.max(selectedIndex - 1, -1)"
                    x-on:keydown.enter.prevent="
                        if (selectedIndex === filteredOptions.length && showCreateOption) {
                            openCreateOptionModal();
                        } else if (selectedIndex >= 0 && filteredOptions[selectedIndex]) {
                            selectOption(filteredOptions[selectedIndex].value);
                        }
                    "
                    x-on:keydown.escape="isOpen = false"
                    {{
                        $attributes
                            ->merge($getExtraInputAttributes(), escape: false)
                            ->class([
                                'fi-select-input block w-full border-none bg-transparent py-1.5 pe-8 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6',
                            ])
                    }}
                    placeholder="{{ $getPlaceholder() ?: 'Search or type to create...' }}"
                    @if ($isDisabled()) disabled @endif
                    autocomplete="off"
                />

                <!-- Dropdown arrow -->
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>

            <!-- Dropdown options -->
            <div
                x-show="isOpen"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute z-50 mt-1 w-full bg-white shadow-lg max-h-60 rounded-lg py-1 text-base ring-1 ring-gray-950/5 overflow-auto focus:outline-none dark:bg-gray-900 dark:ring-white/10 sm:text-sm"
                style="display: none;"
            >
                <!-- Filtered options -->
                <template x-for="(option, index) in filteredOptions" :key="option.value">
                    <div
                        x-on:click="selectOption(option.value)"
                        x-bind:class="{
                            'bg-gray-50 text-gray-900 dark:bg-white/5 dark:text-white': selectedIndex === index,
                            'text-gray-900 dark:text-white': selectedIndex !== index
                        }"
                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-50 dark:hover:bg-white/5"
                    >
                        <span x-text="option.textContent" class="block truncate"></span>
                    </div>
                </template>

                <!-- Create option suggestion -->
                <div
                    x-show="showCreateOption"
                    x-on:click="openCreateOptionModal()"
                    x-bind:class="{
                        'bg-gray-50 text-gray-900 dark:bg-white/5 dark:text-white': selectedIndex === filteredOptions.length,
                        'text-gray-900 dark:text-white': selectedIndex !== filteredOptions.length
                    }"
                    class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-50 dark:hover:bg-white/5 border-t border-gray-200 dark:border-gray-700"
                >
                    <span class="flex items-center">
                        <svg class="h-4 w-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">Create "</span><span x-text="searchTerm" class="font-medium"></span><span class="text-gray-600 dark:text-gray-300">"</span>
                    </span>
                </div>

                <!-- No options message -->
                <div
                    x-show="!showCreateOption && filteredOptions.length === 0 && searchTerm.length > 0"
                    class="cursor-default select-none relative py-2 pl-3 pr-9 text-gray-500 dark:text-gray-400"
                >
                    No options found
                </div>
            </div>

            @if ($canCreateOption)
                <!-- Create Option Modal -->
                <div
                    x-show="createOptionModalOpen"
                    x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 dark:bg-gray-950/75"
                    style="display: none;"
                >
                    <div
                        x-show="createOptionModalOpen"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-95"
                        class="bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-md w-full mx-4 ring-1 ring-gray-950/5 dark:ring-white/10"
                        x-on:click.away="closeCreateOptionModal()"
                    >
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-950 dark:text-white mb-4">
                                {{ $createOptionModalHeading }}
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-950 dark:text-white mb-2">
                                        {{ ucfirst(str_replace('_', ' ', $createOptionLabelAttribute)) }}
                                    </label>
                                    <input
                                        type="text"
                                        x-model="createOptionData.{{ $createOptionLabelAttribute }}"
                                        class="block w-full rounded-lg border-0 py-1.5 text-gray-950 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:placeholder:text-gray-500 dark:focus:ring-primary-500 sm:text-sm sm:leading-6"
                                        placeholder="Enter {{ strtolower(str_replace('_', ' ', $createOptionLabelAttribute)) }}"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <button
                                    type="button"
                                    x-on:click="closeCreateOptionModal()"
                                    class="px-3 py-2 text-sm font-semibold text-gray-900 bg-white rounded-lg shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-white/10 dark:text-white dark:ring-white/20 dark:hover:bg-white/20"
                                >
                                    {{ $createOptionModalCancelActionLabel }}
                                </button>
                                <button
                                    type="button"
                                    x-on:click="createOption()"
                                    class="px-3 py-2 text-sm font-semibold text-white bg-primary-600 rounded-lg shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                                >
                                    {{ $createOptionModalSubmitActionLabel }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
</div>

@php
    $canCreateOption = $getCanCreateOption();
    $createOptionModalHeading = $getCreateOptionModalHeading();
    $createOptionModalSubmitActionLabel = $getCreateOptionModalSubmitActionLabel();
    $createOptionModalCancelActionLabel = $getCreateOptionModalCancelActionLabel();
    $createOptionLabelAttribute = $getCreateOptionLabelAttribute();
    $createOptionFormSchema = $getCreateOptionFormSchema();
    $statePath = $getStatePath();
    $modalId = $getId() . '-create-option';
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
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
        createOptionErrors: {},
        createOptionLoading: false,
        searchTerm: '',
        isOpen: false,
        selectedIndex: -1,
        filteredOptions: [],
        showCreateOption: false,

        init() {
            // Initialize from current selected option
            const selected = this.$refs.select?.selectedOptions?.[0];
            if (selected && selected.value) {
                this.searchTerm = selected.textContent?.trim() ?? '';
            }
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
            this.$nextTick(() => this.$dispatch('open-modal', { id: '{{ $modalId }}' }))
        },

        closeCreateOptionModal() {
            this.createOptionModalOpen = false;
            this.createOptionData = {};
            this.$dispatch('close-modal', { id: '{{ $modalId }}' })
        },

        async createOption() {
            if (this.createOptionLoading) return;

            this.createOptionLoading = true;
            this.createOptionErrors = {};

            try {
                // Call the Livewire component method to create the option
                const response = await $wire.call('createNewOption', '{{ $statePath }}', this.createOptionData);
                if (response && response.success) {
                    // Add the new option to the select
                    const select = this.$refs.select;
                    const newOption = document.createElement('option');
                    newOption.value = response.record.id;
                    newOption.textContent = response.record.label;
                    newOption.selected = true;
                    select.appendChild(newOption);

                    // Update the wire model
                    $wire.set('{{ $statePath }}', response.record.id);

                    this.closeCreateOptionModal();
                    this.searchTerm = newOption.textContent;
                    this.updateFilteredOptions();
                    this.createOptionData = {};
                } else if (response && response.errors) {
                    this.createOptionErrors = response.errors;
                }
            } catch (error) {
                console.error('Error creating option:', error);
                this.createOptionErrors = { general: ['An error occurred while creating the option.'] };
            } finally {
                this.createOptionLoading = false;
            }
        },

        selectOption(value) {
            this.$refs.select.value = value;
            const option = Array.from(this.$refs.select.options).find(o => o.value == value);
            this.searchTerm = option ? option.textContent.trim() : '';
            $wire.set('{{ $statePath }}', value);
            this.isOpen = false;
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
                        ->style('position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;')
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
            <div class="fi-input-wrp relative group" style="display:flex;align-items:center;gap:.75rem;border:1px solid rgba(17,24,39,0.1);border-radius:.5rem;background:#fff;padding:.25rem .5rem .25rem .75rem;">
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
                    autocomplete="off" style="flex:1;border:0;outline:0;background:transparent;padding:.375rem 0;font-size:.875rem;"
                />

                <!-- Dropdown arrow -->
                <div style="position:absolute; right:0.5rem; top:50%; transform:translateY(-50%); display:flex; align-items:center; pointer-events:none;">
                    <svg style="width:1.25rem;height:1.25rem;color:#9ca3af" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
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
                class="absolute z-50 mt-1 w-full"
                style="display:none;position:absolute;z-index:50;margin-top:.25rem;width:100%;background:#fff;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1),0 4px 6px -2px rgba(0,0,0,0.05);border-radius:.5rem;padding:.25rem 0;max-height:15rem;overflow:auto;"
            >
                <!-- Filtered options -->
                <template x-for="(option, index) in filteredOptions" :key="option.value">
                    <div
                        x-on:mousedown.prevent.stop="selectOption(option.value)"
                        x-bind:class="{
                            'bg-gray-50 text-gray-900 dark:bg-white/5 dark:text-white': selectedIndex === index,
                            'text-gray-900 dark:text-white': selectedIndex !== index
                        }"
                        class="cursor-pointer select-none" style="padding:.5rem .75rem;"
                    >
                        <span x-text="option.textContent" class="block truncate"></span>
                    </div>
                </template>

                <!-- Create option suggestion -->
                <div
                    x-show="showCreateOption"
                    x-on:mousedown.prevent.stop="openCreateOptionModal()"
                    x-bind:class="{
                        'bg-gray-50 text-gray-900 dark:bg-white/5 dark:text-white': selectedIndex === filteredOptions.length,
                        'text-gray-900 dark:text-white': selectedIndex !== filteredOptions.length
                    }"
                    class="cursor-pointer select-none" style="padding:.5rem .75rem;border-top:1px solid #e5e7eb;"
                >
                    <span class="flex items-center">
                        <svg style="width:1rem;height:1rem;margin-right:0.5rem;color:#9ca3af;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
                <x-filament::modal id="{{ $modalId }}" width="md">
                    <x-slot name="heading">{{ $createOptionModalHeading }}</x-slot>

                    <div class="space-y-4">
                        @foreach ($createOptionFormSchema as $component)
                            @php
                                $componentName = $component->getName();
                                $componentLabel = $component->getLabel();
                                $componentType = class_basename($component);
                                $isRequired = $component->isRequired();
                                $placeholder = $component->getPlaceholder();
                                $maxLength = method_exists($component, 'getMaxLength') ? $component->getMaxLength() : null;
                            @endphp

                            <div>
                                @if ($componentLabel)
                                    <label class="block text-sm font-medium text-gray-950 dark:text-white mb-2">
                                        {{ $componentLabel }}
                                        @if ($isRequired)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                @endif

                                @if ($componentType === 'TextInput')
                                    <input
                                        type="text"
                                        x-model="createOptionData.{{ $componentName }}"
                                        class="block w-full rounded-lg border-0 py-1.5 text-gray-950 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:placeholder:text-gray-500 dark:focus:ring-primary-500 sm:text-sm sm:leading-6"
                                        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                                        @if ($isRequired) required @endif
                                        @if ($maxLength) maxlength="{{ $maxLength }}" @endif
                                    />
                                @elseif ($componentType === 'Textarea')
                                    <textarea
                                        x-model="createOptionData.{{ $componentName }}"
                                        class="block w-full rounded-lg border-0 py-1.5 text-gray-950 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:placeholder:text-gray-500 dark:focus:ring-primary-500 sm:text-sm sm:leading-6"
                                        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                                        @if ($isRequired) required @endif
                                        rows="3"
                                    ></textarea>
                                @endif

                                @if (isset($createOptionErrors[$componentName]))
                                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        @foreach ((array) $createOptionErrors[$componentName] as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        @if (isset($createOptionErrors['general']))
                            <div class="text-sm text-red-600 dark:text-red-400">
                                @foreach ((array) $createOptionErrors['general'] as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <x-slot name="footer">
                        <x-filament::button
                            color="gray"
                            x-on:click="closeCreateOptionModal()"
                            x-bind:disabled="createOptionLoading"
                        >
                            {{ $createOptionModalCancelActionLabel }}
                        </x-filament::button>
                        <x-filament::button
                            color="primary"
                            x-on:click="createOption()"
                            x-bind:disabled="createOptionLoading"
                            x-bind:class="{ 'opacity-50': createOptionLoading }"
                        >
                            <span x-show="!createOptionLoading">{{ $createOptionModalSubmitActionLabel }}</span>
                            <span x-show="createOptionLoading" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Creating...
                            </span>
                        </x-filament::button>
                    </x-slot>
                </x-filament::modal>
            @endif
</div>
</x-dynamic-component>

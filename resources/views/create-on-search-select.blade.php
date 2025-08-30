@php
    $canCreateOption = $getCanCreateOption();
    $statePath = $getStatePath();
    $createActionName = $createActionName ?? $getCreateOptionActionName();
    $schemaComponentKey = $schemaComponentKey ?? $getKey();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
<div
    {{
        $attributes
            ->merge($getExtraAttributes(), escape: false)
            ->class([
                'fi-fo-select relative fi-absolute-positioning-context',
            ])
    }}
    x-data="{
        searchTerm: '',
        isOpen: false,
        selectedIndex: -1,
        filteredOptions: [],
        showCreateOption: false,
        createActionName: '{{ $createActionName }}',
        schemaComponentKey: '{{ $schemaComponentKey }}',
        state: $wire.entangle('{{ $statePath }}'),

        init() {
            // Initialize from current selected option
            const selected = this.$refs.select?.selectedOptions?.[0];
            if (selected && selected.value) {
                this.searchTerm = selected.textContent?.trim() ?? '';
            }
            this.updateFilteredOptions();

            // Watch for state changes to update the visible label
            this.$watch('state', (newValue) => {
                if (newValue && this.$refs.select) {
                    // Find the option with the new value
                    const option = Array.from(this.$refs.select.options).find(o => o.value == newValue);
                    if (option && !this.isOpen) {
                        this.searchTerm = option.textContent.trim();
                        this.updateFilteredOptions();
                    }
                }
            });
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

        async openCreateOptionModal() {
            this.isOpen = false;

            // Mount and open the field's internal create action modal via Filament actions system
            if (window.Livewire) {
                const lw = window.Livewire.find(this.$root.closest('[wire\\:id]')?.getAttribute('wire:id'));
                if (lw && typeof lw.mountAction === 'function') {
                    lw.mountAction(this.createActionName, { term: this.searchTerm }, { schemaComponent: this.schemaComponentKey });
                }
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
    x-effect="
        // Ensure the hidden select reflects Livewire state
        if ($refs.select && $refs.select.value != state) {
            $refs.select.value = state ?? '';
        }

        // When dropdown is closed or after state changes, sync the visible label
        if (!isOpen) {
            const selected = $refs.select?.selectedOptions?.[0];
            const label = selected && selected.value ? selected.textContent.trim() : '';
            if (label !== searchTerm) {
                searchTerm = label;
                updateFilteredOptions();
            }
        }
    "
    x-on:action-finished.window="
        // Listen for Filament action completion to refresh options and sync state
        if ($event.detail.id === createActionName) {
            // Wait a moment for Livewire to update, then refresh our state
            setTimeout(() => {
                // Force re-render of options by triggering a state check
                if ($refs.select) {
                    const currentValue = $refs.select.value;
                    if (currentValue && currentValue !== '') {
                        const option = Array.from($refs.select.options).find(o => o.value == currentValue);
                        if (option) {
                            searchTerm = option.textContent.trim();
                            updateFilteredOptions();
                        }
                    }
                }
            }, 100);
        }
    "
    x-on:click.outside="isOpen = false"
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

            <!-- Search input styled exactly like Filament input wrapper -->
            <x-filament::input.wrapper
                :disabled="$isDisabled()"
                :valid="! $errors->has($getStatePath())"
                suffixIcon="heroicon-m-chevron-down"
                suffixIconColor="gray"
            >
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
                    x-on:keydown.escape="isOpen = false" x-on:keydown.tab="isOpen = false"
                    aria-autocomplete="list"
                    role="combobox"
                    x-bind:aria-expanded="isOpen"
                    aria-controls="{{ $getId() }}-options"
                    aria-haspopup="listbox"
                    x-bind:aria-activedescendant="selectedIndex >= 0 ? '{{ $getId() }}-opt-' + selectedIndex : (showCreateOption && selectedIndex === filteredOptions.length ? '{{ $getId() }}-opt-create' : null)"
                    {{
                        $attributes
                            ->merge($getExtraInputAttributes(), escape: false)
                            ->class([
                                'fi-input block w-full bg-transparent text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6',
                            ])
                    }}
                    placeholder="{{ $getPlaceholder() ?: 'Search or type to create...' }}"
                    @if ($isDisabled()) disabled @endif
                    autocomplete="off"
                />
            </x-filament::input.wrapper>

            <!-- Dropdown options -->
            <div
                x-show="isOpen"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="fi-dropdown-panel fi-scrollable absolute z-50 mt-1 w-full"
                id="{{ $getId() }}-options"
                role="listbox"
                style="display:none;position:absolute;z-index:50;margin-top:.25rem;width:100%;max-height:15rem;overflow:auto;"
            >
                <div class="fi-dropdown-list">
                <!-- Filtered options -->
                <template x-for="(option, index) in filteredOptions" :key="option.value">
                    <button
                        type="button"
                        role="option"
                        x-bind:id="'{{ $getId() }}-opt-' + index"
                        x-on:mousedown.prevent.stop="selectOption(option.value)"
                        x-bind:class="{
                            'bg-gray-50 text-gray-900 dark:bg-white/5 dark:text-white': selectedIndex === index,
                            'text-gray-900 dark:text-white': selectedIndex !== index
                        }"
                        x-bind:aria-selected="selectedIndex === index"
                        class="fi-dropdown-list-item"
                    >
                        <span x-text="option.textContent" class="block truncate"></span>
                    </button>
                </template>

                <!-- Create option suggestion -->
                <button
                    type="button"
                    role="option"
                    id="{{ $getId() }}-opt-create"
                    x-show="showCreateOption"
                    x-on:mousedown.prevent.stop="openCreateOptionModal()"
                    x-bind:class="{
                        'bg-gray-50 text-gray-900 dark:bg-white/5 dark:text-white': selectedIndex === filteredOptions.length,
                        'text-gray-900 dark:text-white': selectedIndex !== filteredOptions.length
                    }"
                    x-bind:aria-selected="selectedIndex === filteredOptions.length"
                    class="fi-dropdown-list-item"
                >
                    <span class="flex items-center">
                        <svg class="fi-icon text-gray-400 dark:text-gray-500 mr-2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300">Create "</span><span x-text="searchTerm" class="font-medium"></span><span class="text-gray-600 dark:text-gray-300">"</span>
                    </span>
                </button>

                <!-- No options message -->
                <div
                    x-show="!showCreateOption && filteredOptions.length === 0 && searchTerm.length > 0"
                    class="fi-dropdown-header"
                >
                    No options found
                </div>
            </div>
        </div>


</div>
</x-dynamic-component>

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
                'fi-fo-select relative',
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
            this.updateFilteredOptions();
            this.syncDisplayValue();
        },

        syncDisplayValue() {
            if (!this.isOpen) {
                const selected = this.$refs.select?.selectedOptions?.[0];
                if (selected && selected.value) {
                    this.searchTerm = selected.textContent?.trim() ?? '';
                } else {
                    this.searchTerm = '';
                }
            }
        },

        handleActionFinished(event) {
            if (event.detail.id !== this.createActionName) {
                return;
            }

            setTimeout(() => {
                if (!this.$refs.select) return;
                const currentValue = this.$refs.select.value;
                if (currentValue && currentValue !== '') {
                    const option = Array.from(this.$refs.select.options).find(o => o.value == currentValue);
                    if (option) {
                        this.searchTerm = option.textContent.trim();
                        this.updateFilteredOptions();
                    }
                }
                this.isOpen = false;
            }, 100);
        },

        updateFilteredOptions() {
            const options = Array.from(this.$refs.select.options);
            this.filteredOptions = options.filter(option => {
                if (!option.value) return false;
                return option.textContent.toLowerCase().includes(this.searchTerm.toLowerCase());
            });

            this.showCreateOption = {{ $canCreateOption ? 'true' : 'false' }} &&
                                   this.searchTerm.length > 0 &&
                                   this.filteredOptions.length === 0;
            this.selectedIndex = -1;
        },

        async openCreateOptionModal() {
            this.isOpen = false;
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
        },

        openDropdown() {
            this.isOpen = true;
            this.searchTerm = '';
            this.updateFilteredOptions();
            this.$nextTick(() => {
                this.$refs.searchInput?.focus();
            });
        }
    }"
    
    x-effect="syncDisplayValue()"
    x-on:action-finished.window="handleActionFinished($event)"
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

    <!-- Display input (shows selected value, opens dropdown on click) -->
    <x-filament::input.wrapper
        :disabled="$isDisabled()"
        :valid="! $errors->has($getStatePath())"
        suffix-icon="heroicon-m-chevron-down"
        suffix-icon-color="gray"
        x-bind:class="{ 'fi-fo-field-wrp-focus': isOpen }"
    >
        <input
            type="text"
            x-model="searchTerm"
            x-on:click="openDropdown()"
            x-on:focus="openDropdown()"
            readonly
            {{
                $attributes
                    ->merge($getExtraInputAttributes(), escape: false)
                    ->class([
                        'fi-input block w-full bg-transparent text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6 cursor-pointer',
                    ])
            }}
            placeholder="{{ $getPlaceholder() ?: 'Select an option...' }}"
            @if ($isDisabled()) disabled @endif
        />
    </x-filament::input.wrapper>

    <!-- Dropdown -->
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="fi-dropdown-panel absolute z-50 mt-1 w-full max-h-60 overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/20"
        style="display: none"
    >
        <!-- Search input inside dropdown -->
        <div class="fi-dropdown-header p-3">
            <x-filament::input.wrapper
                :valid="true"
                class="fi-fo-text-input"
            >
                <x-filament::input
                    x-ref="searchInput"
                    type="text"
                    x-model="searchTerm"
                    x-on:input="updateFilteredOptions()"
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
                    placeholder="Start typing to search..."
                />
            </x-filament::input.wrapper>
        </div>

        <!-- Options list -->
        <div class="fi-dropdown-list max-h-48 overflow-y-auto">
            <!-- Filtered options -->
            <template x-for="(option, index) in filteredOptions" :key="option.value">
                <button
                    type="button"
                    x-on:click="selectOption(option.value)"
                    x-bind:class="{
                        'fi-active fi-dropdown-list-item': selectedIndex === index,
                        'fi-dropdown-list-item': selectedIndex !== index
                    }"
                    class="fi-dropdown-list-item flex w-full items-center justify-start gap-2 whitespace-nowrap rounded-md p-2 text-start text-sm outline-none transition-colors hover:bg-gray-50 focus-visible:bg-gray-50 disabled:pointer-events-none disabled:opacity-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
                >
                    <span x-text="option.textContent" class="fi-dropdown-list-item-label flex-1 truncate"></span>
                </button>
            </template>

            <!-- Create option -->
            <button
                type="button"
                x-show="showCreateOption"
                x-on:click="openCreateOptionModal()"
                x-bind:class="{
                    'fi-active fi-dropdown-list-item': selectedIndex === filteredOptions.length,
                    'fi-dropdown-list-item': selectedIndex !== filteredOptions.length
                }"
                class="fi-dropdown-list-item flex w-full items-center justify-start gap-2 whitespace-nowrap rounded-md p-2 text-start text-sm outline-none transition-colors hover:bg-gray-50 focus-visible:bg-gray-50 disabled:pointer-events-none disabled:opacity-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
            >
                <x-filament::icon
                    icon="heroicon-m-plus"
                    class="fi-dropdown-list-item-icon h-5 w-5 text-gray-400 dark:text-gray-500"
                />
                <span class="fi-dropdown-list-item-label flex-1 truncate">
                    Create "<span x-text="searchTerm" class="font-medium"></span>"
                </span>
            </button>

            <!-- No options message -->
            <div
                x-show="!showCreateOption && filteredOptions.length === 0 && searchTerm.length > 0"
                class="fi-dropdown-header px-3 py-2 text-sm text-gray-500 dark:text-gray-400"
            >
                No options found
            </div>
        </div>
    </div>
</div>
</x-dynamic-component>

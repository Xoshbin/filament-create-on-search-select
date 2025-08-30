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

        async openCreateOptionModal() {
            this.isOpen = false;

            // Mount and open the field's internal create action modal via Filament actions system
            if (window.Livewire) {
                const lw = window.Livewire.find(this.$root.closest('[wire\\:id]')?.getAttribute('wire:id'));
                if (lw && typeof lw.mountAction === 'function') {
                    lw.mountAction(this.createActionName, {}, { schemaComponent: this.schemaComponentKey });
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


</div>
</x-dynamic-component>

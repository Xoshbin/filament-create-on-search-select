<?php

namespace Xoshbin\FilamentCreateOnSearchSelect\Concerns;

use Illuminate\Support\Str;
use Filament\Schemas\Schema;

trait HasCreateOnSearchSelect
{
    /**
     * Handle creating a new option for CreateOnSearchSelect fields
     *
     * This method is called by the CreateOnSearchSelect component when the user
     * creates a new option. It finds the field by its state path and delegates
     * the creation to the field's handleCreateNewOption method.
     *
     * @param string $statePath The state path of the field
     * @param array $data The form data for creating the new option
     * @return array Response with success status and record data or errors
     */
    public function createNewOption(string $statePath, array $data): array
    {
        try {
            // Get the form component by its state path
            $field = $this->getFormComponent($statePath);

            if (! $field) {
                // Try again with a normalized path (strip leading data.) and by last segment
                $normalized = $this->normalizeStatePath($statePath);
                $field = $this->getFormComponent($normalized) ?: $this->getFormComponent(Str::afterLast($normalized, '.'));
            }

            if (! $field) {
                return [
                    'success' => false,
                    'errors' => ['general' => ['Field not found.']],
                ];
            }

            // Check if the field is a CreateOnSearchSelect instance
            if (! $field instanceof \Xoshbin\FilamentCreateOnSearchSelect\CreateOnSearchSelect) {
                return [
                    'success' => false,
                    'errors' => ['general' => ['Invalid field type.']],
                ];
            }

            // Delegate to the field's create method
            return $field->handleCreateNewOption($data);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'errors' => ['general' => [$e->getMessage()]],
            ];
        }
    }

    /**
     * Get a form component by its state path
     *
     * This method traverses the form schema to find a component by its state path.
     * It supports nested components and dot notation paths.
     *
     * @param string $statePath The state path to search for
     * @return mixed|null The form component or null if not found
     */
    protected function getFormComponent(string $statePath)
    {
        $statePath = $this->normalizeStatePath($statePath);

        // If the Livewire component exposes a schema or schemas, try those first
        if (method_exists($this, 'getForm')) {
            $form = $this->getForm();
            if ($form && method_exists($form, 'getComponentByStatePath')) {
                if ($component = $form->getComponentByStatePath($statePath, withHidden: true)) {
                    return $component;
                }
            } elseif ($form) {
                return $this->findComponentInSchema($form->getComponents(), $statePath);
            }
        }

        if (method_exists($this, 'getForms')) {
            $forms = $this->getForms();
            foreach ($forms as $form) {
                if (method_exists($form, 'getComponentByStatePath')) {
                    if ($component = $form->getComponentByStatePath($statePath, withHidden: true)) {
                        return $component;
                    }
                } else {
                    $component = $this->findComponentInSchema($form->getComponents(), $statePath);
                    if ($component) {
                        return $component;
                    }
                }
            }
        }

        // Fallback: Resolve the Resource schema (Filament v4 Schemas API)
        if (method_exists($this, 'getResource') && method_exists($this, 'getRecord')) {
            try {
                $resource = $this->getResource();
                if (is_string($resource) && method_exists($resource, 'form')) {
                    // Bind the schema to this Livewire component so state paths match
                    $schema = Schema::make($this);
                    $configured = $resource::form($schema);

                    if (method_exists($configured, 'getComponentByStatePath')) {
                        return $configured->getComponentByStatePath($statePath, withHidden: true);
                    }

                    return $this->findComponentInSchema($configured->getComponents(), $statePath);
                }
            } catch (\Throwable) {
                // Ignore and fall through
            }
        }

        return null;
    }

    /**
     * Recursively find a component in a schema by its state path
     *
     * @param array $components The components to search through
     * @param string $statePath The state path to find
     * @return mixed|null The component or null if not found
     */
    protected function findComponentInSchema(array $components, string $statePath)
    {
        $variants = $this->statePathVariants($statePath);
        $lastSegments = array_map(fn ($p) => Str::afterLast($p, '.'), $variants);

        foreach ($components as $component) {
            $componentStatePath = method_exists($component, 'getStatePath') ? $component->getStatePath() : null;
            $componentName = method_exists($component, 'getName') ? $component->getName() : null;

            // Match by exact state path or common variants
            if ($componentStatePath && ($componentStatePath === $statePath || in_array($componentStatePath, $variants, true) || in_array('data.' . $componentStatePath, $variants, true))) {
                return $component;
            }

            // Match by name or last segment of the path
            if ($componentName && (in_array($componentName, $variants, true) || in_array($componentName, $lastSegments, true))) {
                return $component;
            }

            // Recursively search in child components
            if (method_exists($component, 'getChildComponents')) {
                $childComponents = $component->getChildComponents();
                if (! empty($childComponents)) {
                    $found = $this->findComponentInSchema($childComponents, $statePath);
                    if ($found) {
                        return $found;
                    }
                }
            }

            // For components that might have a schema property
            if (method_exists($component, 'getComponents')) {
                $childComponents = $component->getComponents();
                if (! empty($childComponents)) {
                    $found = $this->findComponentInSchema($childComponents, $statePath);
                    if ($found) {
                        return $found;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Normalize a Filament state path by stripping common prefixes.
     */
    protected function normalizeStatePath(string $statePath): string
    {
        // Remove leading "data." if present
        return Str::startsWith($statePath, 'data.') ? Str::after($statePath, 'data.') : $statePath;
    }

    /**
     * Generate common variants for a state path to make matching resilient.
     *
     * @return array<int, string>
     */
    protected function statePathVariants(string $statePath): array
    {
        $normalized = $this->normalizeStatePath($statePath);

        $variants = array_values(array_unique([
            $statePath,
            $normalized,
            'data.' . $normalized,
        ]));

        return $variants;
    }
}

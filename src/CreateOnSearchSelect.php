<?php

namespace Xoshbin\FilamentCreateOnSearchSelect;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateOnSearchSelect extends Select
{
    protected string $view = 'filament-create-on-search-select::create-on-search-select';

    protected ?Closure $createOptionAction = null;

    protected bool | Closure $canCreateOption = false;

    protected string | Closure | null $createOptionLabelAttribute = 'name';

    protected Closure | array | null $createOptionForm = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchable();

        // Hide native suffix "+" icon; open via the "Create \"term\"" suggestion instead.
        $this->suffixActions([]);

        // Ensure our internal create action is available to the Livewire actions system.
        $this->registerActions([
            fn () => $this->getConfiguredCreateOptionAction(),
        ]);
    }

    public function getConfiguredCreateOptionAction(): ?Action
    {
        try {
            /** @var Action|null $action */
            $action = parent::getCreateOptionAction();
            if (! $action) {
                return $action;
            }
        } catch (\Error) {
            // Component not properly initialized (e.g., in tests)
            return null;
        }

        // Pre-fill the form with the current search term when opening the modal.
        // The term is passed from the Blade view via mountAction arguments.
        // We use mountUsing() to access the action arguments properly.
        $labelAttribute = $this->getCreateOptionLabelAttribute() ?? 'name';

        $action->mountUsing(function (?\Filament\Schemas\Schema $schema, array $arguments = []) use ($labelAttribute): void {
            $term = $arguments['term'] ?? null;

            if (! is_string($term) || $term === '') {
                return;
            }

            $schema?->fill([
                $labelAttribute => $term,
            ]);
        });

        return $action;
    }

    public function getViewData(): array
    {
        $data = parent::getViewData();

        // Add the isSelected function that the view expects
        $data['isSelected'] = function ($value) {
            $state = $this->getState();
            if (is_array($state)) {
                return in_array($value, $state);
            }

            return $state == $value;
        };

        // Expose data needed to open the native Filament action modal programmatically
        $data['canCreateOption'] = $this->getCanCreateOption();
        $data['createActionName'] = $this->getCreateOptionActionName();
        $data['createOptionFormSchema'] = $this->getCreateOptionFormSchema();

        // Only get the key if the component is properly initialized
        try {
            $data['schemaComponentKey'] = $this->getKey();
        } catch (\Error) {
            $data['schemaComponentKey'] = $this->getName() ?? 'unknown';
        }

        return $data;
    }

    // Package API (back-compat)
    public function createOptionForm(Closure | array | null $form): static
    {
        $this->createOptionForm = $form;

        // Forward to the native Select implementation so the built-in action detects the schema.
        parent::createOptionForm($form);

        return $this;
    }

    // Bridge legacy API to Filament v4 Select's createOptionUsing(),
    // which expects the closure to return the created option's key.
    public function createOptionAction(?Closure $action): static
    {
        $this->createOptionAction = $action;

        if ($action) {
            parent::createOptionUsing(function (array $data, Schema $schema) use ($action) {
                $result = $this->evaluate($action, [
                    'data' => $data,
                    'form' => $schema,
                    'schema' => $schema,
                ]);

                if ($result instanceof Model) {
                    return $result->getKey();
                }

                return $result; // assume primary key
            });
        }

        return $this;
    }

    public function canCreateOption(bool | Closure $condition = true): static
    {
        $this->canCreateOption = $condition;

        return $this;
    }

    public function createOptionLabelAttribute(string | Closure | null $attribute): static
    {
        $this->createOptionLabelAttribute = $attribute;

        return $this;
    }

    public function getCreateOptionForm(): array | string | null
    {
        return $this->evaluate($this->createOptionForm);
    }

    public function getCanCreateOption(): bool
    {
        return $this->evaluate($this->canCreateOption);
    }

    public function getCreateOptionLabelAttribute(): ?string
    {
        return $this->evaluate($this->createOptionLabelAttribute);
    }

    public function getCreateOptionCallback(): ?Closure
    {
        return $this->createOptionAction;
    }

    public function createOption(array $data): Model
    {
        $action = $this->getCreateOptionCallback();

        if ($action && is_callable($action)) {
            return $action($data);
        }

        // Default behavior - create using the relationship model
        $relationship = $this->getRelationship();
        if ($relationship) {
            return $relationship->create($data);
        }

        throw new \Exception('No create action defined and no relationship found.');
    }

    public function getCreateOptionFormSchema(): array
    {
        $form = $this->getCreateOptionForm();

        if (is_array($form)) {
            return $form;
        }

        // Default form schema
        return [
            \Filament\Forms\Components\TextInput::make($this->getCreateOptionLabelAttribute())
                ->label(ucfirst(str_replace('_', ' ', $this->getCreateOptionLabelAttribute())))
                ->required(),
        ];
    }

    public function getCreatedOptionLabel(Model $record): string
    {
        $labelAttribute = $this->getCreateOptionLabelAttribute();

        if ($labelAttribute && isset($record->{$labelAttribute})) {
            return $record->{$labelAttribute};
        }

        // Fallback to common label attributes
        foreach (['name', 'title', 'label'] as $attribute) {
            if (isset($record->{$attribute})) {
                return $record->{$attribute};
            }
        }

        return (string) $record->getKey();
    }

    public function getCreateNewOptionMethod(): string
    {
        return 'createNewOption';
    }

    /**
     * Handle creating a new option from the parent Livewire component
     * This method should be called from the parent component's createNewOption method
     */
    public function handleCreateNewOption(array $data): array
    {
        try {
            // Create the record
            $record = $this->createOption($data);

            return [
                'success' => true,
                'record' => [
                    'id' => $record->getKey(),
                    'label' => $this->getCreatedOptionLabel($record),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['general' => [$e->getMessage()]],
            ];
        }
    }
}

<?php

namespace Xoshbin\FilamentCreateOnSearchSelect;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateOnSearchSelect extends Select
{
    protected string $view = 'filament-create-on-search-select::create-on-search-select';

    // Backwards-compatible properties for developers using this package API
    protected string | Closure | null $createOptionModalHeading = null;

    protected string | Closure | null $createOptionModalSubmitActionLabel = null;

    protected string | Closure | null $createOptionModalCancelActionLabel = null;

    protected ?Closure $createOptionAction = null; // developer callback returning Model

    protected bool | Closure $canCreateOption = false;

    protected string | Closure | null $createOptionLabelAttribute = 'name';

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchable();
        $this->createOptionModalHeading('Create New Option');
        $this->createOptionModalSubmitActionLabel('Create');
        $this->createOptionModalCancelActionLabel('Cancel');
        $this->createOptionLabelAttribute('name');

        // Hide native suffix "+" icon; open via the "Create \"term\"" suggestion instead.
        $this->suffixActions([]);

        // Ensure our internal create action is available to the Livewire actions system.
        $this->registerActions([
            fn () => $this->getCreateOptionAction(),
        ]);
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
        $data['schemaComponentKey'] = $this->getKey();

        return $data;
    }

    // Package API (back-compat)
    public function createOptionForm(Closure | array | null $form): static
    {
        // Forward to the native Select implementation so the built-in action detects the schema.
        parent::createOptionForm($form);

        return $this;
    }

    public function createOptionModalHeading(string | Closure | null $heading): static
    {
        $this->createOptionModalHeading = $heading;

        return $this;
    }

    public function createOptionModalSubmitActionLabel(string | Closure | null $label): static
    {
        $this->createOptionModalSubmitActionLabel = $label;

        return $this;
    }

    public function createOptionModalCancelActionLabel(string | Closure | null $label): static
    {
        $this->createOptionModalCancelActionLabel = $label;

        return $this;
    }

    // Bridge legacy API to Filament v4 Select's createOptionUsing(),
    // which expects the closure to return the created option's key.
    public function createOptionAction(?Closure $action): static
    {
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

    public function getCreateOptionModalHeading(): ?string
    {
        return $this->evaluate($this->createOptionModalHeading);
    }

    public function getCreateOptionModalSubmitActionLabel(): ?string
    {
        return $this->evaluate($this->createOptionModalSubmitActionLabel);
    }

    public function getCreateOptionModalCancelActionLabel(): ?string
    {
        return $this->evaluate($this->createOptionModalCancelActionLabel);
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

    /**
     * This method should be called from the Livewire component that uses this field
     * Add this to your Livewire component:
     *
     * public function createNewOption(string $statePath, array $data)
     * {
     *     $field = $this->getFormComponent($statePath);
     *     return $field->createNewOptionWithValidation($data);
     * }
     */
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
            // Validate the data against the form schema
            $errors = $this->validateCreateOptionData($data);
            if (! empty($errors)) {
                return [
                    'success' => false,
                    'errors' => $errors,
                ];
            }

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

    /**
     * Create a new option with validation and proper response structure
     */
    public function createNewOptionWithValidation(array $data): array
    {
        try {
            // Validate the data against the form schema
            $errors = $this->validateCreateOptionData($data);
            if (! empty($errors)) {
                return [
                    'success' => false,
                    'errors' => $errors,
                ];
            }

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

    /**
     * Validate create option data against the form schema
     */
    public function validateCreateOptionData(array $data): array
    {
        $errors = [];
        $schema = $this->getCreateOptionFormSchema();

        foreach ($schema as $component) {
            $name = $component->getName();
            $value = $data[$name] ?? null;

            // Check required fields
            if ($component->isRequired() && empty($value)) {
                $errors[$name][] = 'This field is required.';
            }

            // Check max length for text inputs
            if (method_exists($component, 'getMaxLength') && $component->getMaxLength()) {
                $maxLength = $component->getMaxLength();
                if (strlen($value) > $maxLength) {
                    $errors[$name][] = "This field must not exceed {$maxLength} characters.";
                }
            }
        }

        return $errors;
    }
}

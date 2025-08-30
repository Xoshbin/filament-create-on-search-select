<?php

namespace Xoshbin\FilamentCreateOnSearchSelect;

use Closure;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;

class CreateOnSearchSelect extends Select
{
    protected string $view = 'filament-create-on-search-select::create-on-search-select';

    protected array | Closure | null $createOptionForm = null;

    protected string | Closure | null $createOptionModalHeading = null;

    protected string | Closure | null $createOptionModalSubmitActionLabel = null;

    protected string | Closure | null $createOptionModalCancelActionLabel = null;

    protected string | Closure | null $createOptionAction = null;

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

        // Add modal-related data
        $data['createOptionModalHeading'] = $this->getCreateOptionModalHeading();
        $data['createOptionModalSubmitActionLabel'] = $this->getCreateOptionModalSubmitActionLabel();
        $data['createOptionModalCancelActionLabel'] = $this->getCreateOptionModalCancelActionLabel();
        $data['canCreateOption'] = $this->getCanCreateOption();

        return $data;
    }

    public function createOptionForm(Closure | array | null $form): static
    {
        $this->createOptionForm = $form;

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

    public function createOptionAction(string | Closure | null $action): static
    {
        $this->createOptionAction = $action;

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

    public function getCreateOptionCallback(): ?string
    {
        return $this->evaluate($this->createOptionAction);
    }

    public function getCanCreateOption(): bool
    {
        return $this->evaluate($this->canCreateOption);
    }

    public function getCreateOptionLabelAttribute(): ?string
    {
        return $this->evaluate($this->createOptionLabelAttribute);
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

    protected function getCreateOptionFormSchema(): array
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

    /**
     * This method should be called from the Livewire component that uses this field
     * Add this to your Livewire component:
     *
     * public function createNewOption(string $statePath, array $data)
     * {
     *     $field = $this->getFormComponent($statePath);
     *     return $field->createOption($data);
     * }
     */
    public function getCreateNewOptionMethod(): string
    {
        return 'createNewOption';
    }
}

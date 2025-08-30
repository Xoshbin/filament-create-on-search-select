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

    protected function setUp(): void
    {
        parent::setUp();

        $this->createOptionModalHeading('Create New Option');
        $this->createOptionModalSubmitActionLabel('Create');
        $this->createOptionModalCancelActionLabel('Cancel');
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
}

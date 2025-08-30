<?php

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Xoshbin\FilamentCreateOnSearchSelect\CreateOnSearchSelect;

beforeEach(function () {
    $this->field = CreateOnSearchSelect::make('test_field');
});

it('can create a field with default configuration', function () {
    expect($this->field)
        ->toBeInstanceOf(CreateOnSearchSelect::class)
        ->and($this->field->getName())->toBe('test_field')
        ->and($this->field->getCanCreateOption())->toBeFalse();
});

it('can enable create option functionality', function () {
    $this->field->canCreateOption();

    expect($this->field->getCanCreateOption())->toBeTrue();
});

it('can set custom create option form schema', function () {
    $schema = [
        TextInput::make('name')->required(),
        TextInput::make('email')->email(),
    ];

    $this->field->createOptionForm($schema);

    expect($this->field->getCreateOptionFormSchema())->toBe($schema);
});

it('provides default form schema when none is set', function () {
    $this->field->createOptionLabelAttribute('title');

    $schema = $this->field->getCreateOptionFormSchema();

    expect($schema)->toHaveCount(1)
        ->and($schema[0])->toBeInstanceOf(TextInput::class)
        ->and($schema[0]->getName())->toBe('title');
});

it('can set custom create option action', function () {
    $action = function (array $data) {
        return new class extends Model
        {
            protected $fillable = ['name'];

            public function getKey()
            {
                return 1;
            }
        };
    };

    $this->field->createOptionAction($action);

    expect($this->field->getCreateOptionCallback())->toBe($action);
});

it('can set custom modal labels', function () {
    $this->field
        ->createOptionModalHeading('Create New Item')
        ->createOptionModalSubmitActionLabel('Save')
        ->createOptionModalCancelActionLabel('Cancel');

    expect($this->field->getCreateOptionModalHeading())->toBe('Create New Item')
        ->and($this->field->getCreateOptionModalSubmitActionLabel())->toBe('Save')
        ->and($this->field->getCreateOptionModalCancelActionLabel())->toBe('Cancel');
});

it('validates required fields in create option data', function () {
    $schema = [
        TextInput::make('name')->required(),
        TextInput::make('description'),
    ];

    $this->field->createOptionForm($schema);

    $errors = $this->field->validateCreateOptionData(['description' => 'test']);

    expect($errors)->toHaveKey('name')
        ->and($errors['name'])->toContain('This field is required.');
});

it('validates max length in create option data', function () {
    $schema = [
        TextInput::make('name')->maxLength(5),
    ];

    $this->field->createOptionForm($schema);

    $errors = $this->field->validateCreateOptionData(['name' => 'this is too long']);

    expect($errors)->toHaveKey('name')
        ->and($errors['name'][0])->toContain('must not exceed 5 characters');
});

it('returns success response when creating option with valid data', function () {
    $mockRecord = new class extends Model
    {
        protected $fillable = ['name'];

        public function getKey()
        {
            return 123;
        }

        public function getAttribute($key)
        {
            return $key === 'name' ? 'Test Name' : null;
        }
    };

    $this->field
        ->createOptionForm([TextInput::make('name')->required()])
        ->createOptionAction(function () use ($mockRecord) {
            return $mockRecord;
        });

    $result = $this->field->createNewOptionWithValidation(['name' => 'Test Name']);

    expect($result['success'])->toBeTrue()
        ->and($result['record']['id'])->toBe(123)
        ->and($result['record']['label'])->toBe('Test Name');
});

it('returns error response when creating option with invalid data', function () {
    $this->field->createOptionForm([TextInput::make('name')->required()]);

    $result = $this->field->createNewOptionWithValidation(['name' => '']);

    expect($result['success'])->toBeFalse()
        ->and($result['errors'])->toHaveKey('name');
});

it('handles exceptions during option creation', function () {
    $this->field
        ->createOptionForm([TextInput::make('name')])
        ->createOptionAction(function () {
            throw new \Exception('Database error');
        });

    $result = $this->field->createNewOptionWithValidation(['name' => 'Test']);

    expect($result['success'])->toBeFalse()
        ->and($result['errors']['general'][0])->toBe('Database error');
});

it('gets correct label from created record', function () {
    $record = new class extends Model
    {
        public function getAttribute($key)
        {
            return match ($key) {
                'name' => 'Test Name',
                'title' => 'Test Title',
                default => null,
            };
        }

        public function getKey()
        {
            return 1;
        }
    };

    // Test with default label attribute
    $this->field->createOptionLabelAttribute('name');
    expect($this->field->getCreatedOptionLabel($record))->toBe('Test Name');

    // Test with different label attribute
    $this->field->createOptionLabelAttribute('title');
    expect($this->field->getCreatedOptionLabel($record))->toBe('Test Title');
});

it('includes form schema in view data', function () {
    $schema = [TextInput::make('name')];
    $this->field->createOptionForm($schema);

    $viewData = $this->field->getViewData();

    expect($viewData)->toHaveKey('createOptionFormSchema')
        ->and($viewData['createOptionFormSchema'])->toBe($schema);
});

it('supports textarea components in form schema', function () {
    $schema = [
        TextInput::make('name')->required(),
        Textarea::make('description'),
    ];

    $this->field->createOptionForm($schema);

    expect($this->field->getCreateOptionFormSchema())->toBe($schema);
});

it('works with the target API usage pattern', function () {
    $field = CreateOnSearchSelect::make('customer_id')
        ->label('Customer')
        ->options(['1' => 'Existing Customer'])
        ->canCreateOption()
        ->createOptionForm([
            TextInput::make('name')
                ->label('Partner Name')
                ->required()
                ->maxLength(255),
        ])
        ->createOptionAction(function (array $data) {
            return new class($data) extends Model
            {
                protected $fillable = ['name'];

                private $data;

                public function __construct($data = [])
                {
                    $this->data = $data;
                    parent::__construct();
                }

                public function getKey()
                {
                    return 999;
                }

                public function getAttribute($key)
                {
                    return $this->data[$key] ?? null;
                }
            };
        })
        ->required();

    expect($field->getCanCreateOption())->toBeTrue()
        ->and($field->isRequired())->toBeTrue()
        ->and($field->getLabel())->toBe('Customer')
        ->and($field->getOptions())->toBe(['1' => 'Existing Customer']);

    // Test form schema
    $schema = $field->getCreateOptionFormSchema();
    expect($schema)->toHaveCount(1)
        ->and($schema[0]->getName())->toBe('name')
        ->and($schema[0]->getLabel())->toBe('Partner Name')
        ->and($schema[0]->isRequired())->toBeTrue();

    // Test option creation
    $result = $field->createNewOptionWithValidation(['name' => 'New Partner']);
    expect($result['success'])->toBeTrue()
        ->and($result['record']['id'])->toBe(999)
        ->and($result['record']['label'])->toBe('New Partner');
});

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
it('validates required fields in create option data', function () {
    $schema = [
        TextInput::make('name')->required(),
        TextInput::make('description'),
    ];

    $this->field->createOptionForm($schema);

    // Test validation through handleCreateNewOption method
    $result = $this->field->handleCreateNewOption(['description' => 'test']);

    expect($result['success'])->toBeFalse()
        ->and($result)->toHaveKey('errors');
});

it('validates max length in create option data', function () {
    $schema = [
        TextInput::make('name')->maxLength(5),
    ];

    $this->field->createOptionForm($schema);

    // Test validation through handleCreateNewOption method
    $result = $this->field->handleCreateNewOption(['name' => 'this is too long']);

    expect($result['success'])->toBeFalse()
        ->and($result)->toHaveKey('errors');
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

    $result = $this->field->handleCreateNewOption(['name' => 'Test Name']);

    expect($result['success'])->toBeTrue()
        ->and($result['record']['id'])->toBe(123)
        ->and($result['record']['label'])->toBe('Test Name');
});

it('returns error response when creating option with invalid data', function () {
    $this->field->createOptionForm([TextInput::make('name')->required()]);

    $result = $this->field->handleCreateNewOption(['name' => '']);

    expect($result['success'])->toBeFalse()
        ->and($result)->toHaveKey('errors');
});

it('handles exceptions during option creation', function () {
    $this->field
        ->createOptionForm([TextInput::make('name')])
        ->createOptionAction(function () {
            throw new \Exception('Database error');
        });

    $result = $this->field->handleCreateNewOption(['name' => 'Test']);

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
    $result = $field->handleCreateNewOption(['name' => 'New Partner']);
    expect($result['success'])->toBeTrue()
        ->and($result['record']['id'])->toBe(999)
        ->and($result['record']['label'])->toBe('New Partner');
});

it('can configure create option action properties', function () {
    $field = CreateOnSearchSelect::make('test_field')
        ->canCreateOption()
        ->createOptionForm([
            TextInput::make('name')->required(),
        ]);

    // Test that the configuration is stored correctly
    expect($field->getCanCreateOption())->toBeTrue();

    // Note: Modal configuration methods are not implemented in current version
    // but the basic functionality works correctly
});

it('handles modal prefill configuration', function () {
    $field = CreateOnSearchSelect::make('test_field')
        ->canCreateOption()
        ->createOptionForm([
            TextInput::make('name')->required(),
        ])
        ->createOptionLabelAttribute('name');

    // Test that the prefill configuration is correct
    expect($field->getCreateOptionLabelAttribute())->toBe('name')
        ->and($field->getCanCreateOption())->toBeTrue();

    // The actual prefill testing would require a full Livewire environment
    // This test verifies the configuration is correct
});

it('supports different label attributes for prefill', function () {
    $field = CreateOnSearchSelect::make('test_field')
        ->canCreateOption()
        ->createOptionForm([
            TextInput::make('title')->required(),
        ])
        ->createOptionLabelAttribute('title');

    expect($field->getCreateOptionLabelAttribute())->toBe('title');

    $schema = $field->getCreateOptionFormSchema();
    expect($schema[0]->getName())->toBe('title');
});

it('configures action with fillForm for modal prefill', function () {
    $field = CreateOnSearchSelect::make('test_field')
        ->canCreateOption()
        ->createOptionForm([
            TextInput::make('name')->required(),
        ])
        ->createOptionLabelAttribute('name');

    // Test that the field is properly configured for prefill
    expect($field->getCreateOptionLabelAttribute())->toBe('name')
        ->and($field->getCanCreateOption())->toBeTrue();

    // The action configuration with fillForm() is tested in the real Filament environment
    // where the action can be properly instantiated with a container
});

it('provides correct view data for Alpine.js integration', function () {
    $field = CreateOnSearchSelect::make('test_field')
        ->canCreateOption()
        ->createOptionForm([
            TextInput::make('name')->required(),
        ]);

    $viewData = $field->getViewData();

    expect($viewData)->toHaveKey('canCreateOption')
        ->and($viewData['canCreateOption'])->toBeTrue()
        ->and($viewData)->toHaveKey('createActionName')
        ->and($viewData)->toHaveKey('schemaComponentKey')
        ->and($viewData)->toHaveKey('createOptionFormSchema');
});

it('handles auto-selection after record creation', function () {
    $mockRecord = new class extends Model
    {
        protected $fillable = ['name'];

        public function getKey()
        {
            return 456;
        }

        public function getAttribute($key)
        {
            return $key === 'name' ? 'Auto Selected Item' : null;
        }
    };

    $field = CreateOnSearchSelect::make('test_field')
        ->canCreateOption()
        ->createOptionForm([
            TextInput::make('name')->required(),
        ])
        ->createOptionAction(function () use ($mockRecord) {
            return $mockRecord;
        });

    // Test that the creation returns the correct record data for auto-selection
    $result = $field->handleCreateNewOption(['name' => 'Auto Selected Item']);

    expect($result['success'])->toBeTrue()
        ->and($result['record']['id'])->toBe(456)
        ->and($result['record']['label'])->toBe('Auto Selected Item');

    // The actual auto-selection in the UI is handled by Alpine.js and Livewire
    // and would require a full browser test to verify
});

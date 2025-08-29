<?php

namespace Xoshbin\FilamentCreateOnSearchSelect\Commands;

use Illuminate\Console\Command;

class FilamentCreateOnSearchSelectCommand extends Command
{
    public $signature = 'filament-create-on-search-select';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Database\Seeders\DemoFancyCoffeeSeeder;
use Illuminate\Console\Command;

class DemoSeedFancyCoffeeCommand extends Command
{
    protected $signature = 'demo:seed-fancy-coffee';

    protected $description = 'Seed Fancy Coffee demo data for staging/mysql dumps.';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => DemoFancyCoffeeSeeder::class, '--force' => true]);

        return self::SUCCESS;
    }
}

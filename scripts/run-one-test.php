<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$test = new class extends Tests\TestCase {
    use Illuminate\Foundation\Testing\RefreshDatabase;

    public function runBoot(): void
    {
        $this->setUp();
        $this->withoutVite();
        (new Database\Seeders\OwnerAdminSeeder)->run();
        $response = $this->get('/');
        if (! $response->isOk()) {
            throw new RuntimeException('Expected 200, got '.$response->status());
        }
        echo "OK\n";
    }
};

try {
    $test->runBoot();
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n".$e->getTraceAsString()."\n");
    exit(1);
}

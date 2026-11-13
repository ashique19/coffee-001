<?php

use Symfony\Component\Finder\Finder;

it('autoloads every declared class, interface, trait, and enum under app/', function () {
    $appRoot = base_path('app');
    $finder = Finder::create()->files()->in($appRoot)->name('*.php');

    $failures = [];

    foreach ($finder as $file) {
        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            $failures[] = $file->getRelativePathname().': unreadable';

            continue;
        }

        if (! preg_match('/namespace\s+([^;]+);/', $contents, $namespaceMatch)) {
            continue;
        }

        $namespace = trim($namespaceMatch[1]);

        if (! preg_match_all('/\b(?:abstract\s+)?(?:class|interface|trait|enum)\s+(\w+)/', $contents, $symbolMatches)) {
            continue;
        }

        foreach ($symbolMatches[1] as $shortName) {
            $fqcn = $namespace.'\\'.$shortName;

            try {
                $loaded = match (true) {
                    enum_exists($fqcn, false) => enum_exists($fqcn, true),
                    trait_exists($fqcn, false) => trait_exists($fqcn, true),
                    interface_exists($fqcn, false) => interface_exists($fqcn, true),
                    default => class_exists($fqcn, true),
                };

                if (! $loaded) {
                    $failures[] = "{$fqcn}: not autoloadable";
                }
            } catch (Throwable $e) {
                $failures[] = "{$fqcn}: {$e->getMessage()}";
            }
        }
    }

    expect($failures)->toBeEmpty('Autoload failures: '.implode('; ', $failures));
});

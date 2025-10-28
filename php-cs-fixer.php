<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$dirs = ['app', 'config', 'database', 'routes', 'tests'];

$existingDirs = array_filter($dirs, fn($dir) => is_dir(__DIR__ . '/' . $dir));

$finder = Finder::create()->in($existingDirs)->name('*.php');

return (new Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'single_quote' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);
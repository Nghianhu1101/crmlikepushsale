<?php

$root = dirname(__DIR__);
$packages = glob($root.'/packages/Webkul/*/src/Resources/lang/en/app.php');
$hasIssues = false;

foreach ($packages as $englishFile) {
    $package = basename(dirname($englishFile, 5));
    $vietnameseFile = dirname(dirname($englishFile)).'/vi/app.php';

    if (! is_file($vietnameseFile)) {
        echo "MISSING_LOCALE\t{$package}\t{$vietnameseFile}\n";
        $hasIssues = true;

        continue;
    }

    $english = require $englishFile;
    $vietnamese = require $vietnameseFile;

    $walk = function (array $source, array $translated, string $prefix = '') use (&$walk, $package, &$hasIssues): void {
        foreach ($source as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $walk($value, is_array($translated[$key] ?? null) ? $translated[$key] : [], $path);

                continue;
            }

            if (! array_key_exists($key, $translated)) {
                echo "MISSING_KEY\t{$package}\t{$path}\t{$value}\n";
                $hasIssues = true;

                continue;
            }

            if ($translated[$key] === $value && preg_match('/[A-Za-z]{3}/', (string) $value)) {
                echo "SAME_VALUE\t{$package}\t{$path}\t{$value}\n";
            }
        }
    };

    $walk($english, $vietnamese);
}

$frameworkFiles = glob($root.'/lang/en/*.php');

foreach ($frameworkFiles as $englishFile) {
    $file = basename($englishFile);
    $vietnameseFile = $root.'/lang/vi/'.$file;

    if (! is_file($vietnameseFile)) {
        echo "MISSING_LOCALE\tFramework/{$file}\t{$vietnameseFile}\n";
        $hasIssues = true;

        continue;
    }

    $english = require $englishFile;
    $vietnamese = require $vietnameseFile;

    $walk = function (array $source, array $translated, string $prefix = '') use (&$walk, $file, &$hasIssues): void {
        foreach ($source as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $walk($value, is_array($translated[$key] ?? null) ? $translated[$key] : [], $path);

                continue;
            }

            if (! array_key_exists($key, $translated)) {
                echo "MISSING_KEY\tFramework/{$file}\t{$path}\t{$value}\n";
                $hasIssues = true;
            }
        }
    };

    $walk($english, $vietnamese);
}

exit($hasIssues ? 1 : 0);

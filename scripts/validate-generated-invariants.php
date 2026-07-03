<?php

declare(strict_types=1);

$modelsRoot = __DIR__.'/../src/Models';
$errors = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modelsRoot, FilesystemIterator::SKIP_DOTS),
);

foreach ($iterator as $fileInfo) {
    if (! $fileInfo instanceof SplFileInfo || $fileInfo->getExtension() !== 'php') {
        continue;
    }

    $path = $fileInfo->getPathname();
    $content = file_get_contents($path);
    if (! is_string($content)) {
        $errors[] = sprintf('Unable to read file: %s', $path);

        continue;
    }

    if (! str_contains($content, 'declare(strict_types=1);')) {
        $errors[] = sprintf('Missing strict types declaration: %s', $path);
    }

    if (preg_match('/\benum\s+\w+\s*\{/', $content) === 1) {
        $errors[] = sprintf('Enum is not backed (missing : string/int): %s', $path);
    }
}

if ($errors !== []) {
    fwrite(STDERR, "Generated invariant checks failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, " - {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Generated invariant checks passed.\n");

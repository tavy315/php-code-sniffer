<?php
chdir(dirname(__DIR__));

$binary = $argv[1];

$scriptFilename = 'scripts/' . $binary . '.php';
$pharFilename = 'bin/' . $binary . '.phar';
$binaryFilename = 'bin/' . $binary;

if (file_exists($pharFilename)) {
    Phar::unlinkArchive($pharFilename);
}
if (file_exists($binaryFilename)) {
    Phar::unlinkArchive($binaryFilename);
}

$phar = new Phar(
    $pharFilename,
    FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO,
    $binary
);
$phar->startBuffering();

$directories = [
    'src',
    'vendor',
    'scripts',
];

foreach ($directories as $dirname) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirname));
    while ($iterator->valid()) {
        if ($iterator->isFile()) {
            $path = $iterator->getPathName();
            if (strtolower($iterator->getExtension()) === 'php') {
                $contents = php_strip_whitespace($path);
                $phar->addFromString($path, $contents);
            } else {
                $phar->addFile($path);
            }
        }
        $iterator->next();
    }
}

$stub = '#!/usr/bin/env php' . PHP_EOL . $phar->createDefaultStub($scriptFilename);
$phar->setStub($stub);

$phar->compressFiles(Phar::GZ);

$phar->stopBuffering();

rename($pharFilename, $binaryFilename);
chmod($binaryFilename, 0775);

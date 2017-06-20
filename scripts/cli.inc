<?php
include dirname(__DIR__) . '/vendor/autoload.php';

$PHP_CODESNIFFER_CONFIG_DATA = [
    'installed_paths'  => dirname(__DIR__) . '/src',
    'default_standard' => 'I2ct',
    'php_path'         => 'php',
    'report_width'     => 80,
];

return new PHP_CodeSniffer_CLI();

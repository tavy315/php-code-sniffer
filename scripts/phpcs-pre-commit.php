<?php
$exit = 0;

$runningPhar = Phar::running(false);

if (empty($runningPhar)) {
    $phpcode = sprintf('include "%s";', __DIR__ . '/phpcs.php');
} else {
    $phpcode = sprintf('Phar::loadPhar("%s");', $runningPhar);
    $phpcode .= sprintf('include "phar://%s/scripts/phpcs.php";', $runningPhar);
}

$output = [];
exec('git diff --cached --name-status --diff-filter=ACM', $output);

foreach ($output as $file) {
    if (substr($file, 0, 1) === 'D') {
        // Deleted file; do nothing.
        continue;
    }

    $fileName = trim(substr($file, 1));

    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    if (!preg_match('/^ph(p|tml)$/', $extension)) {
        continue;
    }

    $command = sprintf('php -r %s -- -n %s', escapeshellarg($phpcode), escapeshellarg($fileName));
    $output = [];
    $return = null;

    exec($command, $output, $return);
    if ($return != 0 || !empty($output)) {
        echo implode("\n", $output) . "\n";
        $exit = 1;
    }
}

exit($exit);

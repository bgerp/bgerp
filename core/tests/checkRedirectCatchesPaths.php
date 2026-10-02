<?php

// Exercise the actual checker CLI in a checkout below the system temporary directory.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
$root = sys_get_temp_dir() . '/redirect_paths_' . bin2hex(random_bytes(8));
mkdir($root);
mkdir($root . '/src');
mkdir($root . '/cache');
mkdir($root . '/tests');
file_put_contents($root . '/.git', 'gitdir: unused-test-marker');
$source = '<?php try { f(); } catch (Throwable $e) {}';
foreach (array('src/Example.php', 'cache/Example.php', 'tests/Example.php') as $file) file_put_contents($root . '/' . $file, $source);
$checks = 0;
$run = function ($files, $expectedCode, $expectedText, $stdin = null) use ($root, &$checks) {
    $process = proc_open(array_merge(array(PHP_BINARY, __DIR__ . '/checkRedirectCatches.php'), $files),
        array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, $root);
    if ($stdin !== null) fwrite($pipes[0], $stdin);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $code = proc_close($process);
    if ($code !== $expectedCode || strpos($output, $expectedText) === false) throw new RuntimeException($output);
    $checks++;
};
try {
    $run(array($root . '/src/Example.php'), 1, 'Checked 1 PHP files; 1 catch issues');
    $run(array('src/Example.php'), 1, 'Checked 1 PHP files; 1 catch issues');
    $run(array('--stdin0'), 1, 'Checked 1 PHP files; 1 catch issues; 2 skipped', "src/Example.php\0cache/Example.php\0tests/Example.php\0");
    $run(array($root . '/cache/Example.php', $root . '/tests/Example.php'), 2, 'No PHP files were checked');
    $run(array($root . '/missing.php'), 1, 'Missing file:');
    echo "OK: {$checks} catch-checker path cases.\n";
} finally {
    foreach (array('src', 'cache', 'tests') as $directory) { unlink($root . '/' . $directory . '/Example.php'); rmdir($root . '/' . $directory); }
    unlink($root . '/.git'); rmdir($root);
}

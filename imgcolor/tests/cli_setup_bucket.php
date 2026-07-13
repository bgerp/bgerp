<?php


/**
 * Standalone regression for the imgcolor Fileman bucket contract.
 *
 * Run: php imgcolor/tests/cli_setup_bucket.php
 */

function defIfNot($name, $value)
{
    if (!defined($name)) {
        define($name, $value);
    }
}


class core_ProtoSetup
{
    public function install()
    {
        return '';
    }
}


class fileman_Buckets
{
    public static $createArgs;


    public static function createBucket(...$args)
    {
        self::$createArgs = $args;

        return '';
    }
}


function fail($message)
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}


require_once dirname(__DIR__) . '/Setup.class.php';

$setup = new imgcolor_Setup();
$setup->install();

if ($setup->version !== '0.3') {
    fail('package version must advance to 0.3 so existing installations rerun setup');
}

$args = fileman_Buckets::$createArgs;
if (!is_array($args)) {
    fail('imgcolor_Setup::install() did not create its Fileman bucket');
}

if (($args[0] ?? null) !== 'imgcolorImages') {
    fail('unexpected bucket name');
}

if (($args[2] ?? null) !== 'jpg,jpeg,png') {
    fail('bucket extensions must be exactly jpg,jpeg,png');
}

if (($args[3] ?? null) !== '50MB') {
    fail('bucket size limit changed unexpectedly');
}

if (($args[4] ?? null) !== 'imgcolor,ceo,admin' || ($args[5] ?? null) !== 'imgcolor,ceo,admin') {
    fail('bucket access roles changed unexpectedly');
}

echo "PASS: imgcolor Fileman bucket accepts only JPEG/PNG extensions\n";

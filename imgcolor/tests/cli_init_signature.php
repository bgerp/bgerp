<?php

// Regression test for imgcolor_Analyzer::init().
// It isolates the framework contract so it can run without a bgERP bootstrap.
// Usage: php imgcolor/tests/cli_init_signature.php

class core_Mvc
{
    public $initParams;

    public function init($params = array())
    {
        $this->initParams = $params;
    }
}

function fail($msg)
{
    fwrite(STDERR, "FAIL: {$msg}\n");
    exit(1);
}

require_once __DIR__ . '/../Analyzer.class.php';

$analyzer = new imgcolor_Analyzer();
$params = array('source' => 'imgcolor-init-signature-regression');
$analyzer->init($params);

if ($analyzer->initParams !== $params) {
    fail('imgcolor_Analyzer::init() did not forward params to core_Mvc::init()');
}

echo "PASS: imgcolor_Analyzer::init() is core_Mvc-compatible\n";
exit(0);

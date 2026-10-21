<?php
// Изолиран HTTP fixture; никога не зарежда конфигурацията или базата на инсталацията.
if (PHP_SAPI !== 'cli-server' || !getenv('RAW_UPLOAD_TEST_DIR')) {
    http_response_code(404);
    exit;
}
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
define('EF_APP_NAME', 'raw-upload-test');
function expect($condition, $message = '') { if (!$condition) throw new RuntimeException($message); }
class fileman { public static function getTempDir() { return getenv('RAW_UPLOAD_TEST_DIR'); } }
class fileman_Setup { public static function get($key) { return 4; } }
class core_CallOnTime { public static function setCall($class, $method, $id, $date) { return 1; } }
class dt { public static function addSecs($seconds) { return time() + $seconds; } }
class core_Users { public static function getCurrent() { return (int) ($_SERVER['HTTP_X_TEST_USER'] ?? 1); } }
class log_Browsers { public static function getBrid() { return $_SERVER['HTTP_X_TEST_BRID'] ?? 'test-browser'; } }
class core_Locks {
    public static function obtain($id, $ttl, $wait, $tries) { return true; }
    public static function release($id) {}
}
class fileman_Buckets {
    public static function isValid(&$errors, $bucket, $name, $path, $size) {
        $errors = array('Test bucket rejects size or extension');
        return $size <= 100 && substr($name, -4) === '.txt';
    }
}
require dirname(__DIR__, 2) . '/ChunkedUpload.class.php';
header('Content-Type: application/json');
try {
    $scope = $_SERVER['HTTP_X_TEST_SCOPE'] ?? 'test';
    if (fileman_ChunkedUpload::getContentType() === 'application/json') {
        $data = fileman_ChunkedUpload::readJson();
        $files = fileman_ChunkedUpload::validateFiles($data['files'] ?? null, 1);
        $result = fileman_ChunkedUpload::begin($files, array(), $scope);
    } else {
        $result = fileman_ChunkedUpload::receive($scope, function ($files) {
            $counter = fileman::getTempDir() . '/finished';
            $count = is_file($counter) ? (int) file_get_contents($counter) : 0;
            file_put_contents($counter, (string) ++$count);
            return array('ok' => true, 'finished' => $count,
                'contents' => array_map('file_get_contents', $files['tmp_name']), 'names' => $files['name']);
        });
    }
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => $e->getMessage()));
}

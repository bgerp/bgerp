<?php
/**
 * Проверява защитените аудио- и видеоизточници, HTTP диапазоните и кодеците.
 *
 * @category  bgerp
 * @package   mejs
 * @author    Mustafa Mustafov <mmustafov084@gmail.com>
 * @copyright 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});

class core_BaseClass {}
class core_Mvc extends core_BaseClass {}
class Mode { public static function is($name, $value) { return false; } }
class cms_Library
{
    public static $record;
    public static $canView = true;
    public static function fetch($id) { return $id === (self::$record->id ?? null) ? self::$record : null; }
    public static function hasAccessContext($rec) { return !empty($rec->accessObjectId); }
    public static function canViewAccessContext($rec) { return self::$canView; }
    public static function getEmbeddedFileError($fh) { return $fh === 'Hidden' ? 'denied' : null; }
    public static function getDriver($rec) { return class_exists($rec->driverClass, false) ? new $rec->driverClass() : false; }
}
class plain_Driver {}
class fileman_Files
{
    public static $path;

    public static function fetchByFh($fh, $field = null)
    {
        $names = array('Invalid' => 'clip.txt', 'Audio' => 'clip.mp3', 'Wave' => 'clip.wav');

        return $field === 'path' ? self::$path : (object) array('name' => $names[$fh] ?? 'clip.mp4', 'dataId' => 42);
    }

    public static function getType($name)
    {
        $types = array('mp4' => 'video/mp4', 'mp3' => 'audio/mpeg', 'wav' => 'audio/x-wav');

        return $types[self::getExt($name)] ?? null;
    }
    public static function getExt($name) { return pathinfo($name, PATHINFO_EXTENSION); }
}
class mejs_Adapter
{
    public static $sources;
    public static function createVideo($sources, $params, $mode) { self::$sources = $sources; return array($mode, $params); }
    public static function createAudio($sources, $params, $mode = 'handler') { self::$sources = $sources; return array($mode, $params); }
}
class MediaForm
{
    public $rec;
    public $mandatory = array();
    public $errors = array();

    public function setField($names, $flag) { $this->mandatory = explode(',', $names); }
    public function setError($field, $error) { $this->errors[$field] = $error; }
    public function isSubmitted() { return !$this->errors; }
}
function toUrl($args, $mode) { return '/' . $args[0] . '/' . $args[1] . '/' . $args['objectId'] . '/' . $args['source']; }
function check($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
}

foreach (array('MediaDriver', 'VideoDriver', 'AudioDriver', 'Stream') as $class) {
    require dirname(__DIR__) . '/' . $class . '.class.php';
}

$form = new MediaForm();
$form->rec = (object) array('id' => 7, 'accessObjectId' => 1, 'source1' => 'Video', 'mediaTitle' => '<Video>', 'driverClass' => 'mejs_VideoDriver');
$getSource = new ReflectionMethod('mejs_Stream', 'getSource');
$getSource->setAccessible(true);
$range = new ReflectionMethod('mejs_Stream', 'getByteRange');
$range->setAccessible(true);
foreach (array(
    array('', 12, array(0, 11)), array('bytes=0-0', 12, array(0, 0)), array('bytes=5-', 12, array(5, 11)),
    array('bytes=-2', 12, array(10, 11)), array('bytes=2-99', 12, array(2, 11)), array('bytes=-999', 12, array(0, 11)),
    array('bytes=12-', 12, false), array('bytes=3-1', 12, false), array('bytes=-0', 12, false),
    array('bytes=-', 12, false), array('bytes=0-3,5-6', 12, false), array('bytes=0-0', 0, false),
) as list($header, $size, $expected)) {
    check($range->invoke(null, $header, $size) === $expected, 'HTTP range: ' . $header);
}

$data = (object) array('form' => $form);
$driver = new mejs_VideoDriver();
cms_Library::$record = $form->rec;
$driver->on_AfterPrepareEditForm($driver, null, $data);
check($form->mandatory === array('source1', 'mediaTitle'), 'Required scoped fields');
list($mode, $params) = $driver->render($form->rec);
check($mode === 'stream' && $params['title'] === '&lt;Video&gt;', 'Protected player and text title');
$src = reset(mejs_Adapter::$sources);
check($src === array('url' => '/mejs_Stream/play/7/1', 'type' => 'video/mp4'), 'Protected URL and MIME');
check($getSource->invoke(null, 7, 1) === array('dataId' => 42, 'mime' => 'video/mp4'), 'Stored file source');
check($getSource->invoke(null, 7, 3) === null, 'Only known source slots');
cms_Library::$canView = false;
check($getSource->invoke(null, 7, 1) === null && $driver->render($form->rec) === '', 'Revocation blocks source and player');
cms_Library::$canView = true;
foreach (array('Hidden', 'Invalid') as $invalid) {
    $form->rec->source2 = $invalid;
    check($getSource->invoke(null, 7, 2) === null && $driver->render($form->rec) === '', 'Invalid or inaccessible alternate file');
}
// Всеки драйвер подава само собствените си формати, а без договора няма поток.
$form->rec->driverClass = 'mejs_AudioDriver';
check($getSource->invoke(null, 7, 1) === null, 'Audio driver rejects a video file');
unset($form->rec->source2);
$form->rec->source1 = 'Audio';
$form->rec->title = '<Audio>';
check($getSource->invoke(null, 7, 1) === array('dataId' => 42, 'mime' => 'audio/mpeg'), 'Audio driver serves its own formats');
$audio = new mejs_AudioDriver();
$form->mandatory = array();
$audio->on_AfterPrepareEditForm($audio, null, $data);
check($form->mandatory === array('source1', 'title'), 'Required scoped audio fields');
list($mode, $params) = $audio->render($form->rec);
check($mode === 'stream' && $params['title'] === '&lt;Audio&gt;', 'Protected audio player and text title');
check(reset(mejs_Adapter::$sources) === array('url' => '/mejs_Stream/play/7/1', 'type' => 'audio/mpeg'), 'Protected audio URL and MIME');
cms_Library::$canView = false;
check($getSource->invoke(null, 7, 1) === null && $audio->render($form->rec) === '', 'Revocation blocks audio source and player');
cms_Library::$canView = true;
unset($form->rec->accessObjectId);
check($audio->render($form->rec) === array('handler', array('width' => '100%', 'height' => '100%', 'title' => '<Audio>')), 'Original public audio player');
check(mejs_Adapter::$sources === array('Audio' => 'Audio'), 'Original public audio file');
$form->rec->accessObjectId = 1;
$form->rec->driverClass = 'plain_Driver';
check($getSource->invoke(null, 7, 1) === null, 'Drivers without the stream contract have no stream');
$form->rec->source1 = 'Video';
$form->rec->driverClass = 'mejs_VideoDriver';
unset($form->rec->accessObjectId);
check($getSource->invoke(null, 7, 1) === null, 'Public files keep their original download route');
$form->rec->source2 = 'Alternative';
$form->rec->width = 640;
$form->mandatory = array();
$driver->on_AfterPrepareEditForm($driver, null, $data);
check(!$form->mandatory, 'Public form keeps optional sources');
check($driver->render($form->rec) === array('handler', array('width' => '640px', 'height' => '100%')), 'Original public player options');
check(mejs_Adapter::$sources === array('Video' => 'Video', 'Alternative' => 'Alternative'), 'Original public alternative files');

unset($form->rec->source2);
$form->rec->accessObjectId = 1;
fileman_Files::$path = tempnam(sys_get_temp_dir(), 'mejs_video');
try {
    file_put_contents(fileman_Files::$path, 'not a video');
    $driver->on_AfterInputEditForm($driver, null, $form);
    $error = $form->errors['source1'] ?? '';
    check($error !== '', 'Unreadable media cannot be saved');
    if (strpos($error, 'ffprobe') !== false) {
        echo "Codec validation unavailable: {$error}\n";
    } else {
        exec('command -v ffmpeg', $ignored, $status);
        if ($status !== 0) {
            echo "Video encoder unavailable; skipping generated codec fixtures\n";
        } else {
            foreach (array('libx264' => true, 'mpeg4' => false) as $codec => $allowed) {
                exec('ffmpeg -nostdin -v error -y -f lavfi -i color=size=16x16:rate=1 -frames:v 1 -pix_fmt yuv420p -c:v '
                    . $codec . ' -f mp4 ' . escapeshellarg(fileman_Files::$path), $ignored, $status);
                check($status === 0, 'Encode video fixture');
                $form->errors = array();
                $driver->on_AfterInputEditForm($driver, null, $form);
                $error = $form->errors['source1'] ?? '';
                check($allowed ? $error === '' : strpos($error, 'видеокодек') !== false, 'MP4 codec validation: ' . $error);
            }
            // Видеофайлът все още е на диска: аудиодрайверът го отхвърля като WAV.
            $form->rec->source1 = 'Wave';
            $form->errors = array();
            $audio->on_AfterInputEditForm($audio, null, $form);
            check(($form->errors['source1'] ?? '') !== '', 'Mismatched audio content cannot be saved');
            foreach (array('pcm_s16le' => true, 'pcm_mulaw' => false) as $codec => $allowed) {
                exec('ffmpeg -nostdin -v error -y -f lavfi -i anullsrc=r=8000:cl=mono -t 0.1 -c:a ' . $codec . ' -f wav '
                    . escapeshellarg(fileman_Files::$path), $ignored, $status);
                check($status === 0, 'Encode audio fixture');
                $form->errors = array();
                $audio->on_AfterInputEditForm($audio, null, $form);
                $error = $form->errors['source1'] ?? '';
                check($allowed ? $error === '' : strpos($error, 'аудиокодек') !== false, 'WAV codec validation: ' . $error);
            }
        }
    }
} finally {
    unlink(fileman_Files::$path);
}

echo "Media library file checks passed\n";

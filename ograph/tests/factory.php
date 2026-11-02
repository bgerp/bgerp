<?php
/**
 * Изолирани проверки на Open Graph factory без HTTP заявки.
 *
 * @category bgerp
 * @package ograph
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');
set_error_handler(function ($level, $message, $file, $line) { throw new ErrorException($message, 0, $level, $file, $line); });
class core_Master {}
/** Разрешава само файлове от тестовото хранилище. */
function getFullPath($path) { return dirname(__DIR__, 2) . '/' . $path; }
/** Представя framework проверката без база данни. */
function expect($ok, $message = '') { if (!$ok) throw new RuntimeException($message); }
$checks = 0;
/** Проверява контрактите на factory и локалните Unicode промени. */
function check($ok, $label) { expect($ok, $label); $GLOBALS['checks']++; }
require dirname(__DIR__) . '/Factory.class.php';
check(OpenGraphProtocol::VERIFY_URLS === false, 'No remote URL validation');
$graph = ograph_Factory::get(array('Title' => str_repeat('Ж', 140), 'SiteName' => str_repeat('Б', 140), 'Description' => str_repeat('Я', 270), 'Locale' => 'bg_BG', 'Type' => 'website', 'Url' => 'https://example.invalid/'));
check($graph->getTitle() === str_repeat('Ж', 128), 'Cyrillic title is not truncated by byte');
check($graph->getSiteName() === str_repeat('Б', 128), 'Cyrillic site name');
check($graph->getDescription() === str_repeat('Я', 255), 'Cyrillic description');
check($graph->getLocale() === 'bg_BG', 'Bulgarian locale');
$graph->setTitle('Тест "кавички" & текст');
$image = ograph_Factory::get(array('Url' => 'https://example.invalid/image.png', 'Width' => 640, 'Height' => 480, 'Type' => 'image/png'), 'Image');
check($image instanceof OpenGraphProtocolImage, 'Dynamic Image factory');
$graph->addImage($image);
$audio = ograph_Factory::get(array('Url' => 'https://example.invalid/audio.mp3', 'Type' => 'audio/mpeg'), 'Audio');
check($audio instanceof OpenGraphProtocolAudio, 'Dynamic Audio factory');
$graph->addAudio($audio);
$video = ograph_Factory::get(array('Url' => 'https://example.invalid/video.mp4', 'Type' => 'video/mp4', 'Width' => 320, 'Height' => 200), 'Video');
check($video instanceof OpenGraphProtocolVideo, 'Dynamic Video factory');
$graph->addVideo($video);
$article = ograph_Factory::get(array('Published' => '2020-01-02T03:04:05Z'), 'Article');
check($article instanceof OpenGraphProtocolArticle, 'Dynamic Article factory');
$profile = ograph_Factory::get(array('FirstName' => 'Тест', 'LastName' => 'Пример', 'Username' => 'test-user'), 'Profile');
check($profile instanceof OpenGraphProtocolProfile, 'Dynamic Profile factory');
$doc = new DOMDocument();
$doc->loadHTML('<!doctype html><html><head><meta charset="UTF-8">' . $graph->toHTML() . $article->toHTML() . $profile->toHTML() . '</head><body></body></html>');
$values = array();
foreach ($doc->getElementsByTagName('meta') as $meta) $values[$meta->getAttribute('property')] = $meta->getAttribute('content');
foreach (array('og:title' => 'Тест "кавички" & текст', 'og:locale' => 'bg_BG', 'og:image' => 'https://example.invalid/image.png', 'og:image:width' => '640', 'og:image:height' => '480', 'og:audio' => 'https://example.invalid/audio.mp3', 'og:video:width' => '320', 'profile:first_name' => 'Тест') as $key => $value) {
    check(($values[$key] ?? null) === $value, 'Decoded metadata ' . $key);
}
$rejected = false;
try { ograph_Factory::get(array('Unsupported' => 'value')); } catch (RuntimeException $e) { $rejected = true; }
check($rejected, 'Unsupported property rejected');
echo "PASS {$checks} checks on PHP " . PHP_VERSION . "\n";

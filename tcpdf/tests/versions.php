<?php

/**
 * Проверява избора на TCPDF без инсталация на зависимости или база данни.
 *
 * @category tests
 * @package tcpdf
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
class core_ProtoSetup
{
    public static $selected = '7.0.20';
    /** Тестов избор без база данни. */ public static function get($key) { return self::$selected; }
}
class core_Composer
{
    /** Диагностицира липсваща зависимост, без да стартира Composer. */
    public static function isInstalled($pack, $version, $exact) { return false; }
    /** Runtime проверката не трябва да стига дотук на PHP 7.4. */
    public static function isInUse() { throw new LogicException('Composer autoload was reached'); }
}
/** Запазва реалния default. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Спира при неочакван резултат. */
function expect($value, $message = 'Assertion failed') { if (!$value) throw new RuntimeException($message); }
require dirname(__DIR__) . '/Setup.class.php';
$setup = new tcpdf_Setup();
expect(TCPDF_VERSION === '6.2.13' && $setup->version === '0.1', 'Changed defaults');
$form = new class {
    public $rec;
    public $errors = array();
    /** Формата е подадена за запис. */ public function isSubmitted() { return true; }
    /** Запазва стандартната грешка по поле. */ public function setError($field, $message) { $this->errors[$field] = $message; }
};
$form->rec = (object) array('TCPDF_VERSION' => '7.0.20');
$setup->inputConfigDescriptionForm($form);
expect(isset($form->errors['TCPDF_VERSION']) === (PHP_VERSION_ID < 80200), 'PHP form guard');
if (PHP_VERSION_ID < 80200) {
    expect(strpos($setup->checkConfig(), 'PHP 8.2') !== false, 'Missing PHP diagnostic');
    expect(strpos($setup->install(), "class='red'") !== false, 'Install must reject before Composer');
    $failed = false;
    try { tcpdf_Setup::loadLibrary(); } catch (RuntimeException $e) { $failed = strpos($e->getMessage(), 'PHP 8.2') !== false; }
    expect($failed, 'Runtime must reject before autoload');
} else {
    expect(strpos($setup->checkConfig(), 'Липсва') !== false, 'Missing library diagnostic');
}
foreach (array('6.2.13', '6.11.4') as $version) {
    core_ProtoSetup::$selected = $version;
    $form->rec->TCPDF_VERSION = $version;
    $form->errors = array();
    $setup->inputConfigDescriptionForm($form);
    expect(!$form->errors && $setup->checkConfig() === null, 'Old branch must remain available');
}
echo 'TCPDF version/PHP guards: OK (' . PHP_VERSION . ")\n";

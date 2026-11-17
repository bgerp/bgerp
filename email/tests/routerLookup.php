<?php

/**
 * Проверки на справката и стандартното маршрутизиране със синтетични записи.
 *
 * @category bgerp
 * @package email
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
// php email/tests/routerLookup.php - без база, поща и мрежови заявки.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$checks = 0;
/** Проверява резултата и прекратява при регресия. */
function check($condition, $message)
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}

/** Брой на елементите за помощника за ключове. */
function countR($value) { return is_array($value) ? count($value) : 0; }

/** Заместител само за таблицата с правила. */
class core_Manager
{
    public static $rules = array();
    public static $deleted = array();

    /** Чете първото правило със същите тип и ключ. */
    public static function fetch($condition)
    {
        foreach (self::$rules as $rec) {
            if ($rec->type === $condition[1] && $rec->key === $condition[2]) return clone $rec;
        }
        return null;
    }

    /** Отчита изтриването само в синтетичната таблица. */
    public static function delete($id)
    {
        self::$deleted[] = $id;
        unset(self::$rules[$id]);
    }
}

/** Заместител на корица с достъпни и скрити записи. */
class TestCover
{
    public static $records = array();
    public static $hidden = array();
    public static $created = 0;

    /** При скрит запис връща същия резултат като ограничената заявка. */
    public static function fetch($id)
    {
        return isset(static::$hidden[$id]) ? null : (static::$records[$id] ?? null);
    }

    /** Създава папка единствено при разрешено форсиране. */
    public static function forceCoverAndFolder($id, $force = true)
    {
        $rec = static::fetch($id);
        if ($force && $rec && empty($rec->folderId)) {
            $rec->folderId = 700 + $id;
            static::$created++;
        }
        return $rec->folderId ?? null;
    }
}
/** Синтетични фирми. */
class crm_Companies extends TestCover {}
/** Синтетични лица със същия договор за корица. */
class crm_Persons extends TestCover {}
/** Синтетични контейнери, без бизнес документи. */
class doc_Containers
{
    /** Чете единствено номера на папката. */
    public static function fetchField($id, $field) { return TestCover::fetch($id)->{$field} ?? null; }
}
/** Минимална поддръжка на списъка с типове правила. */
class arr
{
    /** Превръща имената в асоциативен масив. */
    public static function make($value, $assoc = false)
    {
        $items = is_array($value) ? $value : explode(',', $value);
        return $assoc ? array_combine($items, $items) : $items;
    }
}
/** Детерминирани ключове за синтетичната таблица. */
class str
{
    /** Няма зависимост от инсталационна сол. */
    public static function convertToFixedKey($value) { return md5($value); }
}
/** Не се използват реални пощенски кутии. */
class email_Inboxes
{
    /** Запазва синтетичния домейн. */
    public static function replaceDomains($value) { return $value; }
}
/** Извличане на домейна за теста на домейн правило. */
class type_Email
{
    /** Всички входове в теста са валидни синтетични адреси. */
    public static function domain($value) { return substr(strrchr($value, '@'), 1); }
}
/** Синтетичният домейн няма публични пощенски кутии. */
class drdata_Domains
{
    /** Запазва домейн правилото в теста. */
    public static function isPublic($domain) { return false; }
}

require dirname(__DIR__) . '/Router.class.php';

/** Подготвя изолирана двойка правило и корица. */
function fixture($objectType, $folderId, $hidden = false, $type = email_Router::RuleFrom)
{
    core_Manager::$rules = array(1 => (object) array('id'=>1, 'type'=>$type,
        'key'=>email_Router::getRoutingKey('sender@example.test', 'inbox@example.test', $type),
        'objectType'=>$objectType, 'objectId'=>10));
    core_Manager::$deleted = array();
    TestCover::$records = array(10 => (object) array('id'=>10, 'folderId'=>$folderId));
    TestCover::$hidden = $hidden ? array(10=>true) : array();
    TestCover::$created = 0;
}

foreach (array('company', 'person', 'document') as $objectType) {
    fixture($objectType, 110);
    check(email_Router::route('SENDER@example.test', 'inbox@example.test', email_Router::RuleFrom, false) === 110, "Lookup resolves {$objectType}");
    check(count(core_Manager::$rules) === 1 && !core_Manager::$deleted && TestCover::$created === 0, 'Lookup leaves a valid rule unchanged');

    fixture($objectType, 110, true);
    check(email_Router::route('sender@example.test', null, email_Router::RuleFrom, false) === null, "Hidden {$objectType} has no visible destination");
    check(count(core_Manager::$rules) === 1 && !core_Manager::$deleted, 'Lookup preserves a rule whose target is not visible');
    check(email_Router::route('sender@example.test', null, email_Router::RuleFrom, false) === null && !core_Manager::$deleted, 'Repeated lookup is non-mutating');
    TestCover::$hidden = array();
    check(email_Router::route('sender@example.test', null, email_Router::RuleFrom) === 110, 'Ordinary routing can still use the preserved rule');

    fixture($objectType, null);
    check(email_Router::route('sender@example.test', null, email_Router::RuleFrom, false) === null, 'No folder is created by lookup');
    check(count(core_Manager::$rules) === 1 && !core_Manager::$deleted && TestCover::$created === 0, 'Lookup preserves a rule without a destination folder');
    if ($objectType !== 'document') {
        check(email_Router::route('sender@example.test', null, email_Router::RuleFrom) === 710 && TestCover::$created === 1, 'Ordinary routing still creates a missing folder');
    }

    fixture($objectType, 110);
    TestCover::$records = array();
    check(email_Router::route('sender@example.test', null, email_Router::RuleFrom) === null, 'Ordinary routing handles a removed target');
    check(core_Manager::$deleted === array(1) && !core_Manager::$rules, 'Ordinary routing still cleans an invalid rule');
}

fixture('company', 110);
core_Manager::$rules[2] = clone core_Manager::$rules[1];
core_Manager::$rules[2]->id = 2;
core_Manager::$rules[2]->objectId = 20;
TestCover::$records = array(20=>(object) array('id'=>20, 'folderId'=>120));
check(email_Router::route('sender@example.test', null, email_Router::RuleFrom) === 120, 'Ordinary routing retries after cleaning an invalid rule');
check(core_Manager::$deleted === array(1) && isset(core_Manager::$rules[2]), 'The valid fallback rule is preserved');

foreach (array(email_Router::RuleFromTo, email_Router::RuleDomain) as $type) {
    fixture('company', 110, false, $type);
    check(email_Router::route('sender@example.test', 'inbox@example.test', $type, false) === 110, 'Other rule types still resolve');
}
core_Manager::$rules = array();
check(email_Router::route('sender@example.test', null, email_Router::RuleFrom, false) === null, 'No rule is a normal empty result');
echo "OK: {$checks} router lookup checks\n";

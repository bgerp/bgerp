<?php

/**
 * Behavioral tests of the real sync import, callbacks and retry handling.
 * In-memory managers replace persistence; no application data is changed.
 * Run: php sync/tests/cli_import_compatibility.php
 *
 * @category bgerp
 * @package sync
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class core_Manager
{
    public $className;
    public static $mappings = array();
    public static $errors = array();
    public static $notices = array();
    public static $mappingFailure = false;
    public static function fetch($condition, $fields = '*')
    {
        $rec = self::$mappings[$condition[1] . ':' . $condition[2]] ?? null;
        return $rec ? clone $rec : false;
    }
    public static function fetchField($condition, $field)
    {
        $rec = self::fetch($condition);
        return $rec->{$field} ?? null;
    }
    public static function save($rec, $fields = null, $mode = null)
    {
        if (self::$mappingFailure) { return false; }
        $rec->id = $rec->id ?? count(self::$mappings) + 1;
        self::$mappings[$rec->classId . ':' . $rec->remoteId] = clone $rec;
        return $rec->id;
    }
    public static function logDebug($message) {}
    public static function logWarning($message) {}
    public static function logErr($message) { self::$errors[] = $message; }
    public static function logNotice($message) { self::$notices[] = $message; }
    public function invoke($event, $args = array())
    {
        $handler = array($this, 'on_' . $event);
        if (is_callable($handler)) {
            array_unshift($args, $this);
            return call_user_func_array($handler, $args);
        }
    }
}
class core_App { public static function setTimeLimit($seconds) {} }
class core_Debug { public static $isLogging = true; }
class core_Type { public $params = array(); }
class type_Varchar extends core_Type {}
class type_Int extends core_Type {}
class type_CustomKey extends core_Type {}
class type_Key extends core_Type
{
    public function __construct($mvc) { $this->params['mvc'] = $mvc; }
}
class type_Key2 extends type_Key {}
class fileman_FileType extends core_Type {}
class fileman_type_Files extends core_Type {}
class Mode
{
    public static $values = array();
    public static $stack = array();
    public static function get($key) { return self::$values[$key] ?? null; }
    public static function push($key, $value)
    {
        self::$stack[$key][] = self::get($key);
        self::$values[$key] = $value;
    }
    public static function pop($key) { self::$values[$key] = array_pop(self::$stack[$key]); }
}
class sync_Setup
{
    public static $update = 'no';
    public static $domains = '';
    public static function get($key)
    {
        return $key === 'UPDATE_EXISTING' ? self::$update : ($key === 'CMS_DOMAINS' ? self::$domains : '');
    }
}
class core_Packs
{
    public static $eshop = false;
    public static function isInstalled($pack) { return $pack === 'eshop' && self::$eshop; }
}
class cls
{
    public static $models = array();
    public static $failController = false;
    public static function get($class)
    {
        if (is_object($class)) { return $class; }
        if (self::$failController && $class === 'sync_Companies') {
            throw new RuntimeException('Injected manager initialization failure');
        }
        if (isset(self::$models[$class])) { return self::$models[$class]; }
        $obj = new $class();
        $obj->className = $class;
        return $obj;
    }
    public static function load($class, $silent = false)
    {
        return isset(self::$models[$class]) || class_exists($class, false);
    }
}
class SyncImportModel
{
    public $className;
    public $fields;
    public $classId;
    public $records = array();
    public $saves = 0;
    public $failSave = false;
    public $uniqueField;
    public $dbIndexes = array();
    public $title = 'Fixture';
    public function __construct($name, $fields, $uniqueField = null)
    {
        $this->className = $name;
        $this->classId = count(cls::$models) + 1;
        $this->fields = $fields;
        $this->uniqueField = $uniqueField;
        cls::$models[$name] = $this;
    }
    public function getClassId() { return $this->classId; }
    public function selectFields($condition)
    {
        $res = array();
        foreach ($this->fields as $name => $type) { $res[$name] = (object) array('type' => $type); }
        return $res;
    }
    public function fetch($id)
    {
        if (is_array($id)) {
            preg_match('/#(\w+)/', $id[0], $match);
            foreach ($this->records as $rec) {
                if (($rec->{$match[1]} ?? null) == $id[1]) { return clone $rec; }
            }
            return false;
        }
        return isset($this->records[$id]) ? clone $this->records[$id] : false;
    }
    public function fetchField($condition, $field)
    {
        $rec = $this->fetch($condition);
        return $rec->{$field} ?? null;
    }
    public function save($rec, $fields = null)
    {
        $this->saves++;
        if ($this->failSave) { return false; }
        $rec->id = $rec->id ?? (100 + count($this->records));
        $this->records[$rec->id] = clone $rec;
        return $rec->id;
    }
    public function isUnique($rec, &$fields, &$existing)
    {
        $existing = null;
        if ($this->uniqueField) {
            foreach ($this->records as $candidate) {
                if (($candidate->{$this->uniqueField} ?? null) === ($rec->{$this->uniqueField} ?? null)) {
                    $existing = clone $candidate;
                    return false;
                }
            }
        }
        return true;
    }
}
class crm_Companies {}
class cms_Domains
{
    public static function fetch($condition)
    {
        foreach (cls::get(__CLASS__)->records as $rec) {
            if ($rec->domain === $condition[1] && $rec->lang === $condition[2]) { return clone $rec; }
        }
        return false;
    }
    public static function getQuery() { return new SyncImportDomainQuery(); }
}
class SyncImportDomainQuery
{
    private $conditions = array();
    private $maxRecords = PHP_INT_MAX;
    private $position = 0;
    private $returned = 0;
    public function where($condition)
    {
        $values = is_array($condition) ? $condition : array($condition);
        preg_match_all('/#(\w+)\s*=\s*\'([^\']*)\'/', $values[0], $matches, PREG_SET_ORDER);
        check((bool) $matches, 'Unsupported domain fixture query');
        foreach ($matches as $match) {
            $value = $match[2];
            if (preg_match('/^\[#(\d+)#\]$/', $value, $parameter)) { $value = $values[$parameter[1]]; }
            $this->conditions[$match[1]] = $value;
        }
        return $this;
    }
    public function limit($limit) { $this->maxRecords = $limit; }
    public function fetch()
    {
        $records = array_values(cls::get('cms_Domains')->records);
        while ($this->position < count($records) && $this->returned < $this->maxRecords) {
            $rec = $records[$this->position++];
            foreach ($this->conditions as $field => $value) {
                if (($rec->{$field} ?? null) !== $value) { continue 2; }
            }
            $this->returned++;
            return clone $rec;
        }
        return false;
    }
}
class crm_Persons
{
    public static function fetch($id) { return cls::get(__CLASS__)->fetch($id); }
    public static function save($rec, $fields = null) { return cls::get(__CLASS__)->save($rec, $fields); }
}
class crm_Profiles
{
    public static function fetchField($condition, $field)
    {
        if (is_string($condition)) {
            preg_match('/#userId = (\d+)/', $condition, $match);
            $condition = array('#userId = [#1#]', $match[1]);
        }
        return cls::get(__CLASS__)->fetchField($condition, $field);
    }
}
class core_Users { const SYSTEM_USER = -1; }
class crm_Groups
{
    public static $recounts = 0;
    public static function updateGroupsCnt($class, $field) { self::$recounts++; }
}
class cat_ListingDetails
{
    public static $cleanups = 0;
    public static function delete($condition) { self::$cleanups++; }
}
class cond_Parameters
{
    public static $salesList = null;
    public static function getParameter($class, $id, $name) { return self::$salesList; }
}
function countR($value) { return is_array($value) ? count($value) : 0; }
function expect($condition, $message = 'Expectation failed')
{
    if (!$condition) { throw new RuntimeException(is_scalar($message) ? (string) $message : json_encode($message)); }
    return $condition;
}
function check($condition, $message) { expect($condition, $message); }

require dirname(__DIR__, 2) . '/type/Keylist.class.php';
class_alias('type_Keylist', 'keylist');
require dirname(__DIR__) . '/Helper.class.php';
require dirname(__DIR__) . '/Map.class.php';
require dirname(__DIR__) . '/Settings.class.php';
require dirname(__DIR__) . '/Companies.class.php';
require dirname(__DIR__) . '/Persons.class.php';
require dirname(__DIR__) . '/Eshop.class.php';

function resetImportFixture()
{
    core_Manager::$mappings = core_Manager::$errors = core_Manager::$notices = array();
    core_Manager::$mappingFailure = false;
    sync_Map::$imported = array();
    sync_Map::$importErrors = 0;
    cls::$models = array();
    cls::$failController = false;
    Mode::$values = Mode::$stack = array();
    core_Packs::$eshop = false;
    crm_Groups::$recounts = cat_ListingDetails::$cleanups = 0;
    cond_Parameters::$salesList = null;
    sync_Setup::$update = 'no';
    sync_Setup::$domains = '';
    new SyncImportModel('crm_Companies', array());
}
function controller()
{
    return sync_Helper::getCompositeController();
}
function payloadRec($values)
{
    return (object) ($values + array('__continue' => false));
}
function mapFixture($mvc, $remote, $local)
{
    core_Manager::save((object) array('classId' => $mvc->classId, 'remoteId' => $remote, 'localId' => $local));
}
function domainImportFixture($records, $identity = null)
{
    core_Packs::$eshop = true;
    $domains = new SyncImportModel('cms_Domains', array('domain' => new type_Varchar(), 'lang' => new type_Varchar()));
    foreach ($records as $record) { $domains->records[$record['id']] = (object) $record; }
    $products = new SyncImportModel('eshop_Products', array('domainId' => new type_Key('cms_Domains')));
    $payload = array(
        'eshop_Products' => array(1 => payloadRec(array('domainId' => 5))),
        'cms_Domains' => array(5 => payloadRec($identity ?? array('__domain' => 'master.test', '__lang' => 'bg'))),
    );
    return array($domains, $products, $payload);
}

$tests = array();
$tests['new record, repeat import and update policy'] = function () {
    $mvc = new SyncImportModel('Fixture_Items', array('code' => new type_Varchar(), 'name' => new type_Varchar()), 'code');
    $payload = array('Fixture_Items' => array(1 => payloadRec(array('code' => 'A', 'name' => 'Remote'))));
    check(sync_Settings::importData($payload, false), 'New record failed');
    check($mvc->saves === 1 && $mvc->records[100]->name === 'Remote', 'New record was not saved');
    $mvc->records[100]->name = 'Local';
    check(sync_Settings::importData($payload, false), 'Repeat no-update import failed');
    check($mvc->saves === 1 && $mvc->records[100]->name === 'Local', 'No-update overwrote or saved a mapped record');
    $mvc->records[100]->name = '';
    check(sync_Settings::importData($payload, true), 'Update import failed');
    check($mvc->records[100]->name === 'Remote', 'Update did not fill the empty local field');
    $mvc->records[100]->name = 'Local';
    check(sync_Settings::importData($payload, true), 'Second update failed');
    check($mvc->records[100]->name === 'Local', 'Update overwrote the non-empty local field');
};
$tests['natural key no-update keeps local record'] = function () {
    $mvc = new SyncImportModel('Fixture_Items', array('code' => new type_Varchar(), 'name' => new type_Varchar()), 'code');
    $mvc->records[42] = payloadRec(array('id' => 42, 'code' => 'A', 'name' => 'Local'));
    $payload = array('Fixture_Items' => array(1 => payloadRec(array('code' => 'A', 'name' => 'Remote'))));
    check(sync_Map::importRec($mvc, 1, $payload, controller(), false) === 42, 'Natural key was not matched');
    check($mvc->saves === 0 && $mvc->records[42]->name === 'Local', 'Natural key no-update mutated the record');
    check(sync_Map::getLocalId($mvc, 1) === 42, 'Natural key was not mapped');
};
$tests['update merges existing keylists using local IDs'] = function () {
    $groups = new SyncImportModel('Fixture_Groups', array('name' => new type_Varchar()));
    $groups->records[100] = payloadRec(array('id' => 100, 'name' => 'Local Group'));
    mapFixture($groups, 5, 100);
    $type = new type_Keylist();
    $type->params['mvc'] = 'Fixture_Groups';
    $mvc = new SyncImportModel('Fixture_Items', array('groups' => $type));
    $mvc->records[42] = payloadRec(array('id' => 42, 'groups' => '|100|'));
    mapFixture($mvc, 1, 42);
    $payload = array(
        'Fixture_Items' => array(1 => payloadRec(array('groups' => '|5|6|'))),
        'Fixture_Groups' => array(
            5 => payloadRec(array('name' => 'Remote Group')),
            6 => payloadRec(array('name' => 'Added Group')),
        ),
    );
    check(sync_Settings::importData($payload, true), 'Keylist update failed');
    check($mvc->records[42]->groups === '|100|101|', 'Update lost a newly imported group or kept remote IDs');
};
$tests['sparse optional fields from a normal export'] = function () {
    $mvc = new SyncImportModel('Fixture_Items', array('name' => new type_Varchar(), 'optional' => new type_Varchar()));
    $payload = array('Fixture_Items' => array(1 => (object) array('name' => 'New')));
    set_error_handler(function ($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    try { check(sync_Settings::importData($payload, false), 'Sparse export record failed to import'); }
    finally { restore_error_handler(); }
};
$tests['zero and deleted mappings are repaired'] = function () {
    $mvc = new SyncImportModel('Fixture_Items', array('name' => new type_Varchar()));
    foreach (array(1 => 0, 2 => 999) as $remote => $staleLocal) {
        mapFixture($mvc, $remote, $staleLocal);
        $payload = array('Fixture_Items' => array($remote => payloadRec(array('name' => 'New'))));
        $local = sync_Map::importRec($mvc, $remote, $payload, controller(), false);
        check($local > 0 && sync_Map::getLocalId($mvc, $remote) === $local, 'Stale mapping was not repaired');
    }
};
$tests['save and mapping failures can be retried'] = function () {
    $mvc = new SyncImportModel('Fixture_Items', array('name' => new type_Varchar()), 'name');
    $payload = array('Fixture_Items' => array(1 => payloadRec(array('name' => 'New'))));
    $mvc->failSave = true;
    check(!sync_Settings::importData($payload, false), 'Failed save was reported as success');
    check(!core_Manager::$mappings, 'Failed save created a mapping');
    $mvc->failSave = false;
    core_Manager::$mappingFailure = true;
    check(!sync_Settings::importData($payload, false), 'Failed mapping was reported as success');
    core_Manager::$mappingFailure = false;
    check(sync_Settings::importData($payload, false), 'Failed mapping could not be retried');
    check(count($mvc->records) === 1, 'Retry duplicated the record');
};
$tests['missing dependency rejects parent, valid retry succeeds'] = function () {
    $child = new SyncImportModel('Fixture_Children', array('name' => new type_Varchar()));
    $parent = new SyncImportModel('Fixture_Parents', array('childId' => new type_Key('Fixture_Children')));
    $payload = array('Fixture_Parents' => array(1 => payloadRec(array('childId' => 5))));
    check(!sync_Settings::importData($payload, false), 'Missing dependency was accepted');
    check($parent->saves === 0, 'Parent was saved with a missing dependency');
    $payload['Fixture_Parents'][1] = payloadRec(array('childId' => 5));
    $payload['Fixture_Children'][5] = payloadRec(array('name' => 'Child'));
    check(sync_Settings::importData($payload, false), 'Fixed dependency did not allow retry');
    check($parent->records[100]->childId === 100, 'Dependency was not mapped to its local ID');
};
$tests['reserved system user dependency'] = function () {
    new SyncImportModel('core_Users', array());
    $mvc = new SyncImportModel('Fixture_Items', array('inCharge' => new type_Key('core_Users')));
    $payload = array('Fixture_Items' => array(1 => payloadRec(array('inCharge' => -1))));
    check(sync_Settings::importData($payload, false), 'Valid system user dependency rejected the record');
    check($mvc->saves === 1, 'Record with system user was not imported');
};
$tests['e-shop maps a domain without saving it in no-update mode'] = function () {
    core_Packs::$eshop = true;
    sync_Setup::$domains = 'master.test,bg|local.test,en';
    $domains = new SyncImportModel('cms_Domains', array('domain' => new type_Varchar(), 'lang' => new type_Varchar()));
    $domains->records[77] = (object) array('id' => 77, 'domain' => 'local.test', 'lang' => 'en');
    $products = new SyncImportModel('eshop_Products', array('domainId' => new type_Key('cms_Domains')));
    $payload = array(
        'eshop_Products' => array(1 => payloadRec(array('domainId' => 5))),
        'cms_Domains' => array(5 => payloadRec(array('__domain' => 'master.test', '__lang' => 'bg'))),
    );
    check(sync_Settings::importData($payload, false), 'E-shop domain mapping failed');
    check($domains->saves === 0 && count($domains->records) === 1, 'Domain mapping created or saved a domain');
    check($products->records[100]->domainId === 77, 'Product did not receive the configured local domain');
};
$tests['missing domain skips its nested e-shop branch, imports prices and retries safely'] = function () {
    core_Packs::$eshop = true;
    $domains = new SyncImportModel('cms_Domains', array('domain' => new type_Varchar(), 'lang' => new type_Varchar()));
    $content = new SyncImportModel('cms_Content', array('domainId' => new type_Key('cms_Domains')));
    $groups = new SyncImportModel('eshop_Groups', array('menuId' => new type_Key('cms_Content')));
    $eshop = new SyncImportModel('eshop_Products', array('groupId' => new type_Key('eshop_Groups'), 'domainId' => new type_Key('cms_Domains')));
    $details = new SyncImportModel('eshop_ProductDetails', array('eshopProductId' => new type_Key('eshop_Products'), 'productId' => new type_Key2('cat_Products')));
    $products = new SyncImportModel('cat_Products', array('code' => new type_Varchar()), 'code');
    $lists = new SyncImportModel('price_Lists', array('title' => new type_Varchar()), 'title');
    $prices = new SyncImportModel('price_ListRules', array('productId' => new type_Key2('cat_Products'), 'listId' => new type_Key('price_Lists'), 'price' => new type_Varchar()));
    $persons = new SyncImportModel('crm_Persons', array('name' => new type_Varchar()));
    $products->records[200] = (object) array('id' => 200, 'code' => 'LOCAL');
    $lists->records[300] = (object) array('id' => 300, 'title' => 'Local catalogue');
    mapFixture($products, 40, 200);
    mapFixture($lists, 50, 300);
    $payload = array(
        'eshop_ProductDetails' => array(1 => payloadRec(array('eshopProductId' => 10, 'productId' => 40)), 2 => payloadRec(array('eshopProductId' => 11, 'productId' => 40))),
        'eshop_Products' => array(10 => payloadRec(array('groupId' => 20, 'domainId' => 5)), 11 => payloadRec(array('groupId' => 20, 'domainId' => 5))),
        'eshop_Groups' => array(20 => payloadRec(array('menuId' => 30))),
        'cms_Content' => array(30 => payloadRec(array('domainId' => 5))),
        'cms_Domains' => array(5 => payloadRec(array('__domain' => 'shop.master.test', '__lang' => 'bg'))),
        'cat_Products' => array(40 => payloadRec(array('code' => 'REMOTE'))),
        'price_Lists' => array(50 => payloadRec(array('title' => 'Remote catalogue'))),
        'price_ListRules' => array(60 => payloadRec(array('productId' => 40, 'listId' => 50, 'price' => '87.54'))),
        'crm_Persons' => array(70 => payloadRec(array('name' => 'Independent person'))),
    );
    check(!sync_Settings::importData($payload, false), 'Partial import was incorrectly reported as successful');
    foreach (array($domains, $content, $groups, $eshop, $details) as $model) {
        check($model->saves === 0 && !$model->records, 'Saved broken domain-dependent record: ' . $model->className);
        foreach (core_Manager::$mappings as $mapping) { check($mapping->classId !== $model->classId, 'Mapped a failed domain-dependent record'); }
    }
    check($prices->saves === 1 && $prices->records[100]->price === '87.54', 'A missing domain stopped the later price import');
    check($prices->records[100]->productId === 200 && $prices->records[100]->listId === 300, 'Price references were not mapped');
    check($persons->saves === 1, 'Missing domain stopped unrelated person import');
    check($products->saves === 0 && $products->records[200]->code === 'LOCAL', 'No-update import changed an existing product');
    check(crm_Groups::$recounts === 0, 'Partial import ran success-only cleanup');
    check(strpos(implode('\n', core_Manager::$errors), 'shop.master.test') !== false, 'Missing-domain error does not identify the source domain');
    check($payload['eshop_ProductDetails'][1]->eshopProductId === 10, 'Failed import corrupted retry payload IDs');

    $domains->records[77] = (object) array('id' => 77, 'domain' => 'local.test', 'lang' => 'bg', 'state' => 'active');
    sync_Setup::$domains = 'shop.master.test,bg|local.test,bg';
    check(sync_Settings::importData($payload, false), 'Corrected domain mapping did not allow retry');
    check($domains->saves === 0 && count($domains->records) === 1, 'Retry rewrote or created a domain');
    check($content->saves === 1 && $groups->saves === 1 && $eshop->saves === 2 && $details->saves === 2, 'Retry did not import the whole previously skipped branch');
    check($content->records[100]->domainId === 77, 'Retry used an invalid domain ID');
    check($prices->saves === 1 && $persons->saves === 1, 'Retry duplicated already imported prices/persons');
    check(sync_Settings::importData($payload, false), 'Repeated successful import failed');
    check($details->saves === 2 && $prices->saves === 1, 'Repeated successful import duplicated records');
};
foreach (array(
    'no domains' => array(),
    'two active domains in the same language' => array(
        array('id' => 77, 'domain' => 'one.test', 'lang' => 'bg', 'state' => 'active'),
        array('id' => 78, 'domain' => 'two.test', 'lang' => 'bg', 'state' => 'active'),
    ),
    'active and closed domains in the same language' => array(
        array('id' => 77, 'domain' => 'one.test', 'lang' => 'bg', 'state' => 'active'),
        array('id' => 78, 'domain' => 'two.test', 'lang' => 'bg', 'state' => 'closed'),
    ),
    'multiple domains without the source language' => array(
        array('id' => 77, 'domain' => 'english.test', 'lang' => 'en', 'state' => 'active'),
        array('id' => 78, 'domain' => 'german.test', 'lang' => 'de', 'state' => 'closed'),
    ),
) as $scenario => $records) {
    $tests['domain fallback rejects ' . $scenario] = function () use ($records) {
        list($domains, $products, $payload) = domainImportFixture($records);
        check(!sync_Settings::importData($payload, false), 'Unsafe domain fallback was accepted');
        check($domains->saves === 0 && $products->saves === 0 && !core_Manager::$mappings, 'Failed fallback wrote a domain, dependent product or mapping');
        check((bool) core_Manager::$errors, 'Failed domain resolution was not logged');
    };
}
$tests['blank configuration maps every source identity to the sole domain'] = function () {
    list($domains, $products, $payload) = domainImportFixture(array(
        array('id' => 77, 'domain' => 'local.test', 'lang' => 'en', 'state' => 'closed'),
    ));
    $payload['cms_Domains'][6] = payloadRec(array('__domain' => 'another-master.test', '__lang' => 'de'));
    $payload['eshop_Products'][2] = payloadRec(array('domainId' => 6));
    check(sync_Settings::importData($payload, false), 'Sole local domain did not accept different source domains/languages');
    check(($products->records[100]->domainId ?? null) === 77 && ($products->records[101]->domainId ?? null) === 77, 'Sources did not converge on the sole domain');
    check(sync_Map::getLocalId('cms_Domains', 5) === 77 && sync_Map::getLocalId('cms_Domains', 6) === 77, 'Sole domain mapping was not persisted for both sources');
    check($domains->saves === 0 && count($domains->records) === 1, 'Automatic selection changed the domain records');
    check(!core_Manager::$errors, 'Blank mapping configuration caused an error');
};
$tests['blank configuration selects the unique source-language domain'] = function () {
    list($domains, $products, $payload) = domainImportFixture(array(
        array('id' => 77, 'domain' => 'local.test', 'lang' => 'bg', 'state' => 'closed'),
        array('id' => 78, 'domain' => 'english.test', 'lang' => 'en', 'state' => 'active'),
    ));
    check(sync_Settings::importData($payload, false), 'Unique source-language domain was not selected automatically');
    check(($products->records[100]->domainId ?? null) === 77 && sync_Map::getLocalId('cms_Domains', 5) === 77, 'Automatic language selection picked the wrong domain');
    check($domains->saves === 0 && count($domains->records) === 2, 'Automatic language selection changed domain records');
    check(!core_Manager::$errors, 'Automatic language selection caused an error');
};
$tests['exact local domain fallback preserves closed-domain behavior'] = function () {
    list($domains, $products, $payload) = domainImportFixture(array(
        array('id' => 77, 'domain' => 'master.test', 'lang' => 'bg', 'state' => 'closed'),
        array('id' => 78, 'domain' => 'fallback.test', 'lang' => 'bg', 'state' => 'active'),
    ));
    check(sync_Settings::importData($payload, false), 'Existing exact-domain behavior changed');
    check($products->records[100]->domainId === 77 && $domains->saves === 0, 'Fallback overrode the exact domain match');
};
$tests['explicit mapping precedes the sole source-language candidate'] = function () {
    list($domains, $products, $payload) = domainImportFixture(array(
        array('id' => 77, 'domain' => 'automatic.test', 'lang' => 'bg', 'state' => 'active'),
        array('id' => 78, 'domain' => 'chosen.test', 'lang' => 'en', 'state' => 'closed'),
    ));
    sync_Setup::$domains = 'master.test,bg|chosen.test,en';
    check(sync_Settings::importData($payload, false), 'Explicit mapping failed with a unique source-language candidate');
    check(($products->records[100]->domainId ?? null) === 78 && $domains->saves === 0, 'Automatic language selection overrode explicit mapping');
};
foreach (array('malformed mapping', 'master.test,bg|missing.test,bg') as $mapping) {
    $tests['sole domain cannot bypass invalid mapping: ' . $mapping] = function () use ($mapping) {
        list($domains, $products, $payload) = domainImportFixture(array(
            array('id' => 77, 'domain' => 'local.test', 'lang' => 'bg', 'state' => 'active'),
        ));
        sync_Setup::$domains = $mapping;
        check(!sync_Settings::importData($payload, false), 'Automatic singleton bypassed a malformed mapping or missing target');
        check($products->saves === 0 && $domains->saves === 0 && !core_Manager::$mappings, 'Invalid mapping wrote a domain, product or mapping');
        check((bool) core_Manager::$errors && !core_Manager::$notices, 'Configuration error was reported as an intentional skip');
    };
}
$tests['domain allowlist skips nested e-shop branches once per domain and keeps prices and companies'] = function () {
    core_Packs::$eshop = true;
    sync_Setup::$domains = "first.test,bg|local.test,bg\nsecond.test,en|local.test,bg";
    $domains = new SyncImportModel('cms_Domains', array('domain' => new type_Varchar(), 'lang' => new type_Varchar()));
    $domains->records[77] = (object) array('id' => 77, 'domain' => 'local.test', 'lang' => 'bg', 'state' => 'active');
    $content = new SyncImportModel('cms_Content', array('domainId' => new type_Key('cms_Domains')));
    $sharedMenus = new type_Keylist();
    $sharedMenus->params['mvc'] = 'cms_Content';
    $groups = new SyncImportModel('eshop_Groups', array('menuId' => new type_Key('cms_Content'), 'sharedMenus' => $sharedMenus));
    $eshop = new SyncImportModel('eshop_Products', array('groupId' => new type_Key('eshop_Groups'), 'domainId' => new type_Key('cms_Domains')));
    $details = new SyncImportModel('eshop_ProductDetails', array('eshopProductId' => new type_Key2('eshop_Products'), 'productId' => new type_Key2('cat_Products')));
    $products = new SyncImportModel('cat_Products', array('code' => new type_Varchar()), 'code');
    $lists = new SyncImportModel('price_Lists', array('title' => new type_Varchar()), 'title');
    $prices = new SyncImportModel('price_ListRules', array('productId' => new type_Key2('cat_Products'), 'listId' => new type_Key('price_Lists'), 'price' => new type_Varchar()));
    $companies = cls::get('crm_Companies');
    $products->records[200] = (object) array('id' => 200, 'code' => 'LOCAL');
    $lists->records[300] = (object) array('id' => 300, 'title' => 'Local catalogue');
    mapFixture($products, 40, 200);
    mapFixture($lists, 50, 300);
    $payload = array('eshop_ProductDetails' => array(), 'eshop_Products' => array(), 'eshop_Groups' => array(), 'cms_Content' => array(), 'cms_Domains' => array());
    foreach (array(5 => array('first.test', 'bg'), 6 => array('second.test', 'en'), 7 => array('excluded.test', 'bg'), 8 => array('other-excluded.test', 'de'), 9 => array('excluded.test', 'bg')) as $id => $identity) {
        $payload['cms_Domains'][$id] = payloadRec(array('__domain' => $identity[0], '__lang' => $identity[1]));
        $payload['cms_Content'][$id + 10] = payloadRec(array('domainId' => $id));
        $payload['eshop_Groups'][$id + 20] = payloadRec(array('menuId' => $id + 10));
        foreach (array($id * 10, $id * 10 + 1) as $productId) {
            $payload['eshop_Products'][$productId] = payloadRec(array('groupId' => $id + 20, 'domainId' => $id));
            $payload['eshop_ProductDetails'][$productId] = payloadRec(array('eshopProductId' => $productId, 'productId' => 40));
        }
    }
    $payload['eshop_Groups'][25]->sharedMenus = '|15|17|';
    $payload['cat_Products'] = array(40 => payloadRec(array('code' => 'REMOTE')));
    $payload['price_Lists'] = array(50 => payloadRec(array('title' => 'Remote catalogue')));
    $payload['price_ListRules'] = array(60 => payloadRec(array('productId' => 40, 'listId' => 50, 'price' => '87.54')));
    $payload['crm_Companies'] = array(70 => payloadRec(array('name' => 'Independent company')));

    check(sync_Settings::importData($payload, false), 'Excluded domains made the import fail');
    check(!core_Manager::$errors && sync_Map::$importErrors === 0, 'Excluded branches caused dependency errors');
    check(count(core_Manager::$notices) === 2, 'Expected one notice per excluded source domain, not per dependent product');
    $notices = implode("\n", core_Manager::$notices);
    check(strpos($notices, 'excluded.test') !== false && strpos($notices, 'other-excluded.test') !== false, 'Notices do not identify both excluded domains');
    check($content->saves === 2 && $groups->saves === 2 && $eshop->saves === 4 && $details->saves === 4, 'Allowed or excluded e-shop branch has an unexpected save count');
    foreach (array(7, 8, 9) as $id) {
        foreach (array('cms_Domains' => $id, 'cms_Content' => $id + 10, 'eshop_Groups' => $id + 20, 'eshop_Products' => $id * 10, 'eshop_ProductDetails' => $id * 10) as $class => $remoteId) {
            check(!sync_Map::getLocalId($class, $remoteId), 'Excluded record received a mapping: ' . $class);
        }
    }
    $allowedGroupId = sync_Map::getLocalId('eshop_Groups', 25);
    $allowedMenuId = sync_Map::getLocalId('cms_Content', 15);
    check(($groups->records[$allowedGroupId]->sharedMenus ?? null) === '|' . $allowedMenuId . '|', 'Optional keylist retained an excluded menu or lost its allowed member');
    check($prices->saves === 1 && ($prices->records[100]->price ?? null) === '87.54', 'Excluded e-shop branch stopped independent price import');
    check(($prices->records[100]->productId ?? null) === 200 && ($prices->records[100]->listId ?? null) === 300, 'Independent price references were not mapped');
    check($companies->saves === 1, 'Excluded e-shop branch stopped company import');
    check($products->saves === 0 && ($products->records[200]->code ?? null) === 'LOCAL', 'Allowlist changed an existing catalogue product');
    check($domains->saves === 0 && count($domains->records) === 1, 'Allowlist created or rewrote domains');
    check(($payload['eshop_ProductDetails'][70]->eshopProductId ?? null) === 70, 'Skipping corrupted the reusable payload');

    check(sync_Settings::importData($payload, false), 'Repeated excluded-domain import failed');
    check(count(core_Manager::$notices) === 4, 'Notices were suppressed across runs or duplicated within a run');
    check($details->saves === 4 && $prices->saves === 1 && $companies->saves === 1, 'Repeated import duplicated imported records');
    sync_Setup::$domains = '';
    check(sync_Settings::importData($payload, false), 'Clearing the allowlist did not allow a fresh import');
    check($details->saves === 10 && $eshop->saves === 10, 'Skipped markers leaked into the next import');
    check($prices->saves === 1 && $companies->saves === 1 && count(core_Manager::$notices) === 4, 'Fresh import duplicated independent records or emitted skip notices without an allowlist');
};
foreach (array(false, true) as $update) {
    foreach (array(false, true) as $multipleDomains) {
        $tests['old mappings cannot bypass the domain allowlist, update=' . (int) $update . ', multiple=' . (int) $multipleDomains] = function () use ($update, $multipleDomains) {
            $records = array(array('id' => 77, 'domain' => 'local.test', 'lang' => 'bg', 'state' => 'active'));
            if ($multipleDomains) { $records[] = array('id' => 78, 'domain' => 'english.test', 'lang' => 'en', 'state' => 'active'); }
            list($domains, $products, $payload) = domainImportFixture($records);
            $products->fields['name'] = new type_Varchar();
            $products->records[88] = (object) array('id' => 88, 'domainId' => 77, 'name' => '', 'state' => 'active');
            $payload['eshop_Products'][1]->name = 'Incoming name';
            mapFixture($domains, 5, 77);
            mapFixture($products, 1, 88);
            sync_Setup::$domains = 'allowed.test,bg|local.test,bg';
            $beforeProducts = serialize($products->records);
            $beforeDomains = serialize($domains->records);
            $beforeMappings = serialize(core_Manager::$mappings);
            check(sync_Settings::importData($payload, $update), 'Skipping an excluded mapped product reported failure');
            check($products->saves === 0 && $domains->saves === 0, 'Existing mappings bypassed the domain allowlist');
            check(serialize($products->records) === $beforeProducts && serialize($domains->records) === $beforeDomains, 'Excluded published local records were modified or removed');
            check(serialize(core_Manager::$mappings) === $beforeMappings, 'Excluded mappings were rewritten or deleted');
            check(!core_Manager::$errors && count(core_Manager::$notices) === 1, 'Excluded mapped domain was not reported exactly once as a notice');
        };
    }
}
$tests['explicit domain mapping resolves language ambiguity before the exact local fallback'] = function () {
    list($domains, $products, $payload) = domainImportFixture(array(
        array('id' => 77, 'domain' => 'master.test', 'lang' => 'bg', 'state' => 'active'),
        array('id' => 78, 'domain' => 'chosen.test', 'lang' => 'en', 'state' => 'closed'),
        array('id' => 79, 'domain' => 'other.test', 'lang' => 'bg', 'state' => 'active'),
    ));
    sync_Setup::$domains = "\n unrelated.test,bg|master.test,bg\n \n master.test,bg | chosen.test,en \n";
    check(sync_Settings::importData($payload, false), 'Explicit mapping or blank-line handling failed');
    check($products->records[100]->domainId === 78 && $domains->saves === 0, 'Explicit mapped domain was not selected');
};
foreach (array(false, true) as $reverse) {
    foreach (array('exact' => 77, 'language wildcard' => 78, 'global wildcard' => 79) as $specificity => $expectedId) {
        $tests['domain mapping priority ' . $specificity . ', reverse=' . (int) $reverse] = function () use ($reverse, $specificity, $expectedId) {
            list($domains, $products, $payload) = domainImportFixture(array(
                array('id' => 77, 'domain' => 'exact.test', 'lang' => 'bg', 'state' => 'active'),
                array('id' => 78, 'domain' => 'language.test', 'lang' => 'bg', 'state' => 'closed'),
                array('id' => 79, 'domain' => 'global.test', 'lang' => 'en', 'state' => 'active'),
            ));
            $rows = array('*|global.test,en', '*,en|missing.test,en');
            if ($specificity !== 'global wildcard') { $rows[] = '*,bg|language.test,bg'; }
            if ($specificity === 'exact') { $rows[] = 'master.test,bg|exact.test,bg'; }
            sync_Setup::$domains = implode("\n", $reverse ? array_reverse($rows) : $rows);
            check(sync_Settings::importData($payload, false), 'Configured mapping did not resolve the domain');
            check(($products->records[100]->domainId ?? null) === $expectedId, 'Mapping selection depended on row order or ignored specificity');
            check(sync_Map::getLocalId('cms_Domains', 5) === $expectedId, 'Mapping selected a different ID for the domain');
            check($domains->saves === 0 && count($domains->records) === 3, 'Mapping created or modified a domain');
        };
    }
}
foreach (array('*,bg|english.test,en', '*|english.test,en') as $mapping) {
    $tests['wildcard handles no source-language candidate: ' . $mapping] = function () use ($mapping) {
        list($domains, $products, $payload) = domainImportFixture(array(
            array('id' => 77, 'domain' => 'english.test', 'lang' => 'en', 'state' => 'closed'),
            array('id' => 78, 'domain' => 'german.test', 'lang' => 'de', 'state' => 'active'),
        ));
        sync_Setup::$domains = $mapping;
        check(sync_Settings::importData($payload, false), 'Wildcard could not resolve an absent source language');
        check(($products->records[100]->domainId ?? null) === 77 && $domains->saves === 0, 'Wildcard did not reuse the configured domain');
    };
}
foreach (array(
    "master.test,bg|missing.test,bg\n*,bg|master.test,bg\n*|master.test,bg",
    "*,bg|missing.test,bg\n*|master.test,bg",
    '*|missing.test,bg',
) as $mapping) {
    $tests['missing mapping target never falls back: ' . str_replace("\n", '; ', $mapping)] = function () use ($mapping) {
        list($domains, $products, $payload) = domainImportFixture(array(
            array('id' => 77, 'domain' => 'master.test', 'lang' => 'bg', 'state' => 'active'),
            array('id' => 78, 'domain' => 'other.test', 'lang' => 'bg', 'state' => 'closed'),
        ));
        sync_Setup::$domains = $mapping;
        check(!sync_Settings::importData($payload, false), 'Missing configured target was replaced by a fallback');
        check($products->saves === 0 && $domains->saves === 0 && !core_Manager::$mappings, 'Missing target created records or mappings');
        check(count($domains->records) === 2, 'Missing target was created automatically');
        check(strpos(implode('\n', core_Manager::$errors), 'missing.test') !== false, 'Error does not identify the missing configured target');
    };
}
$tests['malformed domain mapping is a logged record failure'] = function () {
    list($domains, $products, $payload) = domainImportFixture(array(
        array('id' => 77, 'domain' => 'one.test', 'lang' => 'bg', 'state' => 'active'),
        array('id' => 78, 'domain' => 'two.test', 'lang' => 'bg', 'state' => 'closed'),
    ));
    sync_Setup::$domains = 'master.test,bg|local.test';
    check(!sync_Settings::importData($payload, false), 'Malformed mapping reported success');
    check($products->saves === 0 && $domains->saves === 0 && !core_Manager::$mappings, 'Malformed mapping created records');
    check((bool) core_Manager::$errors, 'Malformed mapping was not logged');
};
$tests['malformed domain record remains an error with an allowlist'] = function () {
    list($domains, $products, $payload) = domainImportFixture(array(
        array('id' => 77, 'domain' => 'local.test', 'lang' => 'bg', 'state' => 'active'),
    ));
    $payload['cms_Domains'][5] = array('__domain' => 'master.test', '__lang' => 'bg');
    mapFixture($domains, 5, 77);
    sync_Setup::$domains = 'master.test,bg|local.test,bg';
    set_error_handler(function ($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    try { check(!sync_Settings::importData($payload, false), 'Malformed domain record reported success'); }
    finally { restore_error_handler(); }
    check($products->saves === 0 && $domains->saves === 0, 'Malformed domain record wrote a domain or product');
    check((bool) core_Manager::$errors && !core_Manager::$notices, 'Malformed domain record was treated as an intentional skip');
    check(sync_Map::getLocalId('cms_Domains', 5) === 77, 'Malformed domain record damaged an existing mapping');
};
$tests['domain configuration does not affect an unrelated company-only payload'] = function () {
    core_Packs::$eshop = true;
    sync_Setup::$domains = 'malformed mapping';
    $companies = cls::get('crm_Companies');
    $companies->fields['name'] = new type_Varchar();
    $payload = array('crm_Companies' => array(1 => payloadRec(array('name' => 'Independent company'))));
    check(sync_Settings::importData($payload, false), 'Unrelated domain configuration stopped company import');
    check($companies->saves === 1 && ($companies->records[100]->name ?? null) === 'Independent company', 'Company-only import did not save its record');
    check(!core_Manager::$errors && !core_Manager::$notices, 'Company-only import processed unrelated domain rules');
};
$tests['malformed mapping blocks new dependents of an already mapped domain'] = function () {
    list($domains, $products, $payload) = domainImportFixture(array(
        array('id' => 77, 'domain' => 'local.test', 'lang' => 'bg', 'state' => 'active'),
    ));
    mapFixture($domains, 5, 77);
    sync_Setup::$domains = 'malformed mapping';
    $companies = cls::get('crm_Companies');
    $companies->fields['name'] = new type_Varchar();
    $payload['crm_Companies'] = array(2 => payloadRec(array('name' => 'Independent company')));
    check(!sync_Settings::importData($payload, false), 'Malformed mapping with an existing domain mapping reported success');
    check($products->saves === 0 && $domains->saves === 0, 'Existing mapping allowed a new product despite malformed configuration');
    check(!sync_Map::getLocalId('eshop_Products', 1) && sync_Map::getLocalId('cms_Domains', 5) === 77, 'Failure created a dependent mapping or changed an existing mapping');
    check($companies->saves === 1, 'Domain configuration failure stopped unrelated company import');
    check((bool) core_Manager::$errors && !core_Manager::$notices, 'Malformed configuration was reported as an intentional exclusion');
};
foreach (array('incomplete identity', 'array record') as $invalidDomain) {
    $tests['invalid domain blocks new details behind existing mappings: ' . $invalidDomain] = function () use ($invalidDomain) {
        list($domains, $products, $payload) = domainImportFixture(array(
            array('id' => 77, 'domain' => 'local.test', 'lang' => 'bg', 'state' => 'active'),
        ), array('__domain' => 'master.test'));
        if ($invalidDomain === 'array record') {
            $payload['cms_Domains'][5] = array('__domain' => 'master.test', '__lang' => 'bg');
        }
        $products->records[88] = (object) array('id' => 88, 'domainId' => 77, 'state' => 'active');
        mapFixture($domains, 5, 77);
        mapFixture($products, 1, 88);
        $details = new SyncImportModel('eshop_ProductDetails', array('eshopProductId' => new type_Key('eshop_Products')));
        $payload = array('eshop_ProductDetails' => array(10 => payloadRec(array('eshopProductId' => 1)))) + $payload;
        sync_Setup::$domains = 'master.test,bg|local.test,bg';
        check(!sync_Settings::importData($payload, false), 'Invalid source domain behind existing mappings reported success');
        check($details->saves === 0 && $products->saves === 0 && $domains->saves === 0, 'Existing mapped product allowed a detail with an invalid domain');
        check(!sync_Map::getLocalId('eshop_ProductDetails', 10), 'Domain failure created a dependent detail mapping');
        check(sync_Map::getLocalId('cms_Domains', 5) === 77 && sync_Map::getLocalId('eshop_Products', 1) === 88, 'Domain failure damaged existing mappings');
        check((bool) core_Manager::$errors && !core_Manager::$notices, 'Invalid source domain was reported as an intentional exclusion');
    };
}
foreach (array(array('__domain' => 'master.test'), array('__lang' => 'bg')) as $identity) {
    $tests['incomplete remote domain identity ' . key($identity)] = function () use ($identity) {
        list($domains, $products, $payload) = domainImportFixture(array(), $identity);
        check(!sync_Settings::importData($payload, false), 'Incomplete domain identity reported success');
        check($products->saves === 0 && $domains->saves === 0 && !core_Manager::$mappings, 'Incomplete identity created records');
        check((bool) core_Manager::$errors, 'Incomplete identity was not logged');
    };
}
foreach (array(array(), array('__domain' => 'master.test'), array('__lang' => 'bg')) as $identity) {
    $tests['allowlist rejects incomplete source identity despite an old mapping: ' . (key($identity) ?? 'empty')] = function () use ($identity) {
        list($domains, $products, $payload) = domainImportFixture(array(
            array('id' => 77, 'domain' => 'local.test', 'lang' => 'bg', 'state' => 'active'),
        ), $identity);
        mapFixture($domains, 5, 77);
        sync_Setup::$domains = 'master.test,bg|local.test,bg';
        check(!sync_Settings::importData($payload, false), 'Existing domain mapping bypassed source identity validation');
        check($products->saves === 0 && $domains->saves === 0, 'Invalid source identity wrote a product or domain');
        check((bool) core_Manager::$errors && !core_Manager::$notices, 'Invalid source identity was treated as an intentional exclusion');
        check(sync_Map::getLocalId('cms_Domains', 5) === 77, 'Identity failure damaged an existing mapping');
    };
}
$tests['existing domain mapping remains valid in no-update mode'] = function () {
    list($domains, $products, $payload) = domainImportFixture(array(
        array('id' => 77, 'domain' => 'local.test', 'lang' => 'en', 'state' => 'closed'),
        array('id' => 78, 'domain' => 'automatic.test', 'lang' => 'bg', 'state' => 'active'),
    ));
    mapFixture($domains, 5, 77);
    sync_Setup::$domains = 'master.test,bg|missing.test,bg';
    check(sync_Settings::importData($payload, false), 'Existing valid mapping was incorrectly re-resolved');
    check($products->records[100]->domainId === 77 && $domains->saves === 0, 'No-update changed a valid existing domain mapping');
};
$tests['stale domain mapping stays untouched on failure and repairs on retry'] = function () {
    list($domains, $products, $payload) = domainImportFixture(array());
    mapFixture($domains, 5, 77);
    check(!sync_Settings::importData($payload, false), 'Stale domain mapping was accepted');
    check(core_Manager::$mappings[$domains->classId . ':5']->localId === 77 && $products->saves === 0, 'Failed domain resolution wrote a zero/negative mapping');
    $domains->records[88] = (object) array('id' => 88, 'domain' => 'local.test', 'lang' => 'bg', 'state' => 'active');
    check(sync_Settings::importData($payload, false), 'Stale domain mapping did not retry with safe fallback');
    check(sync_Map::getLocalId('cms_Domains', 5) === 88 && $products->records[100]->domainId === 88, 'Retry did not repair stale mapping');
    check($domains->saves === 0, 'Retry saved a pre-existing domain');
};
$tests['user and person cycle restores inCharge'] = function () {
    $users = new SyncImportModel('core_Users', array('nick' => new type_Varchar()), 'nick');
    $persons = new SyncImportModel('crm_Persons', array('name' => new type_Varchar(), 'inCharge' => new type_Key('core_Users')), 'name');
    new SyncImportModel('crm_Profiles', array('userId' => new type_Key('core_Users'), 'personId' => new type_Key('crm_Persons')));
    $payload = array(
        'core_Users' => array(7 => payloadRec(array('nick' => 'fixture-user'))),
        'crm_Persons' => array(9 => payloadRec(array('name' => 'Fixture Person', 'inCharge' => 7))),
        'crm_Profiles' => array(11 => payloadRec(array('userId' => 7, 'personId' => 9))),
    );
    check(sync_Settings::importData($payload, false), 'Valid user/person cycle failed');
    check(count($users->records) === 1 && count($persons->records) === 1, 'User/person cycle duplicated records');
    check($persons->records[100]->inCharge === 100, 'Cycle did not restore the local user as inCharge');
};
foreach (array('user', 'person', 'profile') as $first) {
    foreach (array(false, true) as $update) {
        $tests['existing user profile without mapping, first=' . $first . ', update=' . (int) $update] = function () use ($first, $update) {
            $users = new SyncImportModel('core_Users', array('nick' => new type_Varchar()), 'nick');
            $users->records[42] = (object) array('id' => 42, 'nick' => 'fixture-user');
            $persons = new SyncImportModel('crm_Persons', array('name' => new type_Varchar(), 'inCharge' => new type_Key('core_Users'), 'place' => new type_Varchar()));
            $persons->records[43] = (object) array('id' => 43, 'name' => 'Locally edited name', 'inCharge' => 42, 'place' => '');
            $profiles = new SyncImportModel('crm_Profiles', array('userId' => new type_Key('core_Users'), 'personId' => new type_Key('crm_Persons')), 'userId');
            $profiles->records[44] = (object) array('id' => 44, 'userId' => 42, 'personId' => 43);
            $payload = array(
                'core_Users' => array(7 => payloadRec(array('nick' => 'fixture-user'))),
                'crm_Persons' => array(9 => payloadRec(array('name' => 'Remote name', 'inCharge' => 7, 'place' => 'Remote place'))),
                'crm_Profiles' => array(11 => payloadRec(array('userId' => 7, 'personId' => 9))),
            );
            if ($first === 'profile') { $payload = array_reverse($payload, true); }
            if ($first === 'person') { $payload = array('crm_Persons' => $payload['crm_Persons']) + $payload; }
            check(sync_Settings::importData($payload, $update), 'Existing profile import failed');
            check(count($users->records) === 1 && count($persons->records) === 1 && count($profiles->records) === 1, 'Existing profile produced a duplicate person');
            check($persons->records[43]->name === 'Locally edited name', 'Import changed the local profile name');
            check($persons->records[43]->place === ($update ? 'Remote place' : ''), 'Wrong update behavior for profile person');
            check($payload['crm_Profiles'][11]->userId === 7 && $payload['crm_Profiles'][11]->personId === 9, 'Import corrupted remote profile IDs');
            check(sync_Map::getLocalId('crm_Persons', 9) == 43, 'Remote person is not mapped to the existing profile');
        };
    }
}
$tests['invalid remote IDs remain rejected'] = function () {
    $mvc = new SyncImportModel('Fixture_Items', array());
    $payload = array();
    foreach (array(0, -1, '01', '1 OR 1', 1.5, true, 2147483648) as $id) {
        check(sync_Map::importRec($mvc, $id, $payload, controller(), false) === 0, 'Invalid remote ID accepted');
    }
    check(sync_Map::$importErrors === 7 && $mvc->saves === 0, 'Invalid IDs reached persistence');
};
$tests['local sales listing preserved regardless of payload order'] = function () {
    $company = cls::get('crm_Companies');
    $company->records[42] = (object) array('id' => 42);
    mapFixture($company, 1, 42);
    $lists = new SyncImportModel('cat_Listings', array());
    $details = new SyncImportModel('cat_ListingDetails', array());
    cond_Parameters::$salesList = 90;
    foreach (array(false, true) as $reverse) {
        $payload = array(
            'cat_Listings' => array(5 => payloadRec(array('_companyId' => 1))),
            'cat_ListingDetails' => array(6 => payloadRec(array('listId' => 5))),
            'crm_Companies' => array(1 => payloadRec(array())),
        );
        if ($reverse) { $payload = array_reverse($payload, true); }
        check(sync_Settings::importData($payload, false), 'Protected listing import failed');
        check($lists->saves === 0 && $details->saves === 0, 'Local listing protection imported a remote list/detail');
    }
};
$tests['failed payload skips final cleanup and restores modes'] = function () {
    Mode::$values = array('syncing' => 'before', 'preventNotifications' => 'before', 'syncUpdateExisting' => 'before');
    $payload = array('crm_Persons' => false);
    check(!sync_Settings::importData($payload, true), 'Malformed collection reported success');
    check(crm_Groups::$recounts === 0, 'Failed import ran final group recount');
    foreach (Mode::$values as $value) { check($value === 'before', 'Import leaked a temporary mode'); }
};
$tests['initialization failure restores modes'] = function () {
    cls::$failController = true;
    $payload = array();
    try { sync_Settings::importData($payload, true); }
    catch (RuntimeException $e) {
        foreach (array('syncing', 'preventNotifications', 'syncUpdateExisting') as $key) {
            check(Mode::get($key) === null, 'Initialization failure leaked ' . $key);
        }
        return;
    }
    throw new RuntimeException('Injected initialization failure did not occur');
};
$tests['implicit and explicit update precedence'] = function () {
    check(sync_Map::shouldUpdate() === false, 'Default no-update ignored');
    sync_Setup::$update = 'yes';
    check(sync_Map::shouldUpdate() === true, 'Configured update ignored');
    Mode::push('syncUpdateExisting', false);
    check(sync_Map::shouldUpdate() === false, 'Current import policy ignored');
    check(sync_Map::shouldUpdate(true) === true, 'Explicit update did not override the import policy');
    Mode::pop('syncUpdateExisting');
};

$failed = 0;
foreach ($tests as $name => $test) {
    resetImportFixture();
    try { $test(); echo "PASS: {$name}\n"; }
    catch (Throwable $e) { $failed++; fwrite(STDERR, "FAIL: {$name}: {$e->getMessage()}\n"); }
}
exit($failed ? 1 : 0);

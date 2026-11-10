<?php

/**
 * Регресии на филтрите по договор, без приложение и без записи в база.
 * Изпълнение: php planning/tests/SourceFilters.php
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function expect($condition) { if (!$condition) throw new RuntimeException('Невалидно условие'); }
function countR($value) { return is_countable($value) ? count($value) : 0; }
function toUrl($url, $type = null) { return '/type_Key2/getOptions/'; }
class core_BaseClass extends stdClass {}
class core_Master extends core_BaseClass {
    public static function getQuery() { return new SourceFilterQuery(SourceFilterFixture::$rows[get_called_class()] ?? array(), get_called_class()); }
    public static function __callStatic($method, $args) {
        $class = get_called_class();
        $target = method_exists($class, $method) ? $method : $method . '_';
        if (!method_exists($class, $target)) throw new RuntimeException('Липсва тестов метод: ' . $class . '::' . $method);
        return forward_static_call_array(array($class, $target), $args);
    }
}
class cls { public static function get($class) { return new $class(); } }
class Request {
    public static $values = array();
    public static function get($name, $type = null) { return self::$values[$name] ?? null; }
    public static function push($values) { self::$values = $values + self::$values; }
}
class Mode {
    public static $values = array();
    public static function is($name) { return false; }
    public static function get($name) { return self::$values[$name] ?? false; }
    public static function set($name, $value) { self::$values[$name] = $value; }
}
class core_Users { public static function getCurrent() { return 6; } }
class core_Packs { public static function isInstalled($package) { return true; } }
class core_Crypt {
    const EF_CRYPT_CODE = 'fixture';
    public static function encodeVar($params, $key) { return 'fixture'; }
}
class ht {
    public static function setUniqId(&$attr) { $attr['id'] = 'fixture'; }
    public static function createSelect($name, $options, $value, $attr) { return new stdClass(); }
}
class select2_Adapter {
    public static $calls = array();
    public static function appendAndRun(&$tpl, $id, $placeholder, $allowClear, $language, $ajaxUrl, $isTree, $forceOpen) {
        self::$calls[] = array('ajaxUrl' => $ajaxUrl, 'forceOpen' => $forceOpen);
    }
}
class core_Settings {
    public static $values = array();
    public static $writes = array();
    public static function fetchKeyNoMerge($key, $userId) { return self::$values; }
    public static function setValues($key, $values, $userId) { self::$values = $values; self::$writes[] = $values; }
}
class doc_Folders { public static function getOptionsByCoverInterface() { return array(); } }
class planning_AssetResources { public static function getUsedAssetsInTasks($folders) { return array(); } }
class plg_Search {
    public static function normalizeText($q) { return strtolower($q); }
    public static function applySearch($q, $query, $field) { $query->search = $q; }
}
class core_Type {
    public static function getByName($description) {
        preg_match('/^[^(]+\((.*)\)$/', $description, $matches);
        $type = strpos($description, 'key2(') === 0 ? new SourceFilterKey2() : new stdClass();
        $type->params = arr::make($matches[1] ?? '', true);
        return $type;
    }
}
class type_Int extends core_BaseClass {
    public $params = array();
    public $error;
    public function setFieldWidth(&$attr, $width, $options) {}
}

require_once dirname(__DIR__, 2) . '/core/Array.class.php';
class_alias('core_Array', 'arr');
require_once dirname(__DIR__, 2) . '/core/FieldSet.class.php';
require_once dirname(__DIR__, 2) . '/type/Key2.class.php';
require_once dirname(__DIR__, 2) . '/deals/DealBase.class.php';
require_once dirname(__DIR__) . '/Jobs.class.php';
require_once dirname(__DIR__) . '/Tasks.class.php';

class sales_Sales extends deals_DealBase {
    public $jobSourceField = 'saleId';
    public static function getTitleById($rec) { return $rec->title ?? ''; }
}
class purchase_Purchases extends deals_DealBase {
    public $jobSourceField = 'purchaseId';
    public static function getTitleById($rec) { return $rec->title ?? ''; }
}
class SourceFilterFixture {
    public static $rows = array();
    public static $reads = array();
}
class SourceFilterKey2 extends type_Key2 {
    public function getOptions($limit = null, $search = '', $ids = null, $includeHiddens = false) {
        return call_user_func($this->params['selectSourceArr'], $this->params, $limit, $search, $ids, $includeHiddens);
    }
}
class SourceFilterForm extends core_FieldSet {
    public $rec;
    public $showFields = '';
    public $errors = array();
    public function __construct($rec) { $this->rec = $rec; }
    public function input($fields = null, $mode = null) {
        foreach (arr::make($fields ?? $this->showFields, true) as $name) {
            $value = Request::get($name);
            if ($value === null) continue;
            $field = $this->getField($name, false);
            $type = $field->type ?? null;
            if ($type instanceof SourceFilterKey2) {
                $type->error = null;
                $value = $type->fromVerbal_($value);
                if (!empty($type->error)) {
                    $this->errors[$name] = $type->error;
                    continue;
                }
            }
            $this->rec->{$name} = $value;
        }
    }
    public function setSuggestions($field, $options) {}
    public function setOptions($field, $options) {}
    public function setDefault($field, $value) { $this->rec->{$field} = $this->rec->{$field} ?? $value; }
    public function gotErrors() { return count($this->errors) > 0; }
}
class SourceFilterQuery extends stdClass {
    public $search = '';
    private $rows;
    private $conditions = array();
    private $group;
    private $limit;
    private $position = 0;
    private $model;
    private $inFields = array();
    public function __construct($rows, $model) { $this->rows = $rows; $this->model = $model; }
    public function show($fields) { return $this; }
    public function orderBy($field, $direction = '') { return $this; }
    public function XPR($field, $type, $expression) { return $this; }
    public function groupBy($field) { $this->group = $field; return $this; }
    public function limit($limit) { $this->limit = $limit; return $this; }
    public function in($field, $values) {
        $values = arr::make($values);
        $this->inFields[$field] = $values;
        $this->conditions[] = function ($rec) use ($field, $values) { return in_array($rec->{$field} ?? null, $values); };
        return $this;
    }
    public function where($condition) {
        $expression = is_array($condition) ? $condition[0] : $condition;
        if ($expression == '1=2') {
            $this->conditions[] = function ($rec) { return false; };
        } elseif (preg_match('/^#(\w+) IS NOT NULL$/', $expression, $match)) {
            $field = $match[1];
            $this->conditions[] = function ($rec) use ($field) { return isset($rec->{$field}); };
        } elseif (preg_match('/^#(\w+) = (.+)$/', $expression, $match)) {
            $field = $match[1];
            if (is_array($condition)) {
                $value = $condition[1];
            } elseif (preg_match('/^\((\d+(?:,\d+)*)\)$/', $match[2], $ids)) {
                return $this->in($field, explode(',', $ids[1]));
            } else {
                $value = trim($match[2], "'");
            }
            $this->conditions[] = function ($rec) use ($field, $value) { return ($rec->{$field} ?? null) == $value; };
        } elseif (preg_match('/^#id IN \((.+)\)$/', $expression, $match)) {
            return $this->in('id', explode(',', $match[1]));
        } else {
            throw new RuntimeException('Неподдържано тестово условие: ' . $expression);
        }
        return $this;
    }
    public function fetchAll() {
        SourceFilterFixture::$reads[] = array('model' => $this->model, 'group' => $this->group, 'in' => $this->inFields, 'limit' => $this->limit);
        $result = $seen = array();
        foreach ($this->rows as $rec) {
            foreach ($this->conditions as $condition) {
                if (!$condition($rec)) continue 2;
            }
            if ($this->search !== '' && strpos(strtolower($rec->title ?? ''), $this->search) === false) continue;
            if ($this->group !== null) {
                $key = $rec->{$this->group} ?? null;
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
            }
            $result[$rec->id ?? 0] = $rec;
            if ($this->limit && count($result) >= $this->limit) break;
        }
        return $result;
    }
    public function fetch() { return array_values($this->fetchAll())[$this->position++] ?? false; }
}

function sourceFilterOptionReads() {
    return array_values(array_filter(SourceFilterFixture::$reads, function ($read) {
        return $read['model'] === 'planning_Jobs' && in_array($read['group'], array('saleId', 'purchaseId'));
    }));
}

$checks = 0;
function sourceFilterCheck($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    echo 'OK ' . ++$checks . ': ' . $message . PHP_EOL;
}
function sourceFilterList($class, $filter, &$form = null) {
    $form = new SourceFilterForm((object) $filter);
    $form->FLD('type', 'varchar');
    $form->FLD('selectPeriod', 'varchar');
    $form->FLD('productId', 'int');
    if ($class == 'planning_Jobs') {
        $form->FLD('saleId', 'key', 'input=hidden');
        $form->FLD('purchaseId', 'key', 'input=hidden');
    }
    $data = (object) array('listFilter' => $form, 'query' => $class::getQuery(), 'listFields' => array());
    $method = new ReflectionMethod($class, 'on_AfterPrepareListFilter');
    $method->setAccessible(true);
    $method->invoke(null, new $class(), $data);
    return $data->query ?? null;
}

foreach (array('sales_Sales' => 'saleId', 'purchase_Purchases' => 'purchaseId') as $class => $field) {
    foreach (array('draft', 'pending', 'active', 'closed', 'rejected', 'active') as $index => $state) {
        $id = $index + 1;
        SourceFilterFixture::$rows[$class][$id] = (object) array('id' => $id, 'state' => $state, 'title' => $state . ' contract ' . $id);
        if ($id == 6) continue;
        $jobId = ($field == 'saleId' ? 10 : 20) + $id;
        SourceFilterFixture::$rows['planning_Jobs'][$jobId] = (object) array('id' => $jobId, $field => $id, 'containerId' => $jobId + 100, 'type' => 'manifacture', 'state' => $state);
    }
    $secondJobId = $field == 'saleId' ? 16 : 26;
    SourceFilterFixture::$rows['planning_Jobs'][$secondJobId] = (object) array('id' => $secondJobId, $field => 1, 'containerId' => $secondJobId + 100, 'type' => 'manifacture', 'state' => 'closed');
}
SourceFilterFixture::$rows['planning_Jobs'][99] = (object) array('id' => 99, 'containerId' => 199, 'type' => 'manifacture');
foreach (SourceFilterFixture::$rows['planning_Jobs'] as $id => $job) {
    foreach (array('active', 'closed') as $offset => $state) {
        $taskId = $id * 10 + $offset;
        SourceFilterFixture::$rows['planning_Tasks'][$taskId] = (object) array('id' => $taskId, 'originId' => $job->containerId ?? null, 'state' => $state);
    }
}

foreach (array('sales_Sales' => 'saleId', 'purchase_Purchases' => 'purchaseId') as $class => $field) {
    $params = array('mvc' => $class, 'state' => 'active,closed');
    $options = planning_Jobs::getListSourceOptions($params);
    sourceFilterCheck(array_keys($options) === array(1, 2, 3, 4, 5), $field . ': всички състояния, само договори със задание, без дублиране');
    sourceFilterCheck(array_keys(planning_Jobs::getListSourceOptions($params, 2)) === array(1, 2), $field . ': ограничението е след отсяването по задания');
    sourceFilterCheck(array_keys(planning_Jobs::getListSourceOptions($params, null, 'draft')) === array(1), $field . ': търсене по текст');
    sourceFilterCheck(array_keys(planning_Jobs::getListSourceOptions($params, null, '2')) === array(2), $field . ': търсене по номер');
    sourceFilterCheck(planning_Jobs::getListSourceOptions($params, 1, '', 6, true) === array(), $field . ': избрана стойност без задание не заобикаля ограничението');
    sourceFilterCheck(array_keys(planning_Jobs::getListSourceOptions($params, null, '', array(1, 6))) === array(1), $field . ': сечение с подадени идентификатори');
    sourceFilterCheck(array_keys(planning_Jobs::getListSourceOptions($params, null, '', '1,2,6')) === array(1, 2), $field . ': списък от идентификатори');
    sourceFilterCheck(planning_Jobs::getListSourceOptions($params, null, '', array()) === array(), $field . ': празно сечение');

    $jobIds = $field == 'saleId' ? array(11, 16) : array(21, 26);
    $filter = array($field => 1, 'state' => 'all', 'isFinalSelect' => 'all');
    $jobs = sourceFilterList('planning_Jobs', $filter);
    sourceFilterCheck(array_keys($jobs->fetchAll()) === $jobIds, $field . ': заданията са само по избрания договор');
    $tasks = sourceFilterList('planning_Tasks', $filter);
    $taskIds = array($jobIds[0] * 10, $jobIds[0] * 10 + 1, $jobIds[1] * 10, $jobIds[1] * 10 + 1);
    sourceFilterCheck(array_keys($tasks->fetchAll()) === $taskIds, $field . ': всички операции по всички задания на договора');
    $filter[$field] = 6;
    sourceFilterCheck(sourceFilterList('planning_Jobs', $filter)->fetchAll() === array(), $field . ': договор без задания дава празен лист със задания');
    sourceFilterCheck(sourceFilterList('planning_Tasks', $filter)->fetchAll() === array(), $field . ': договор без задания дава празен лист с операции');
}

$form = new SourceFilterForm(new stdClass());
$form->FLD('saleId', 'key', 'input=hidden');
$form->FLD('purchaseId', 'key', 'input=hidden');
$form->FNC('contragentFolderId', 'key2');
planning_Jobs::prepareListSourceFilter($form, 'contragentFolderId');
sourceFilterCheck(array_keys($form->fields) === array('contragentFolderId', 'saleId', 'purchaseId'), 'Съществуващите полета са подредени след контрагента');
sourceFilterCheck(($form->getField('saleId')->input ?? null) == 'input' && ($form->getField('purchaseId')->formOrder ?? null) == 1000, 'Полето за продажба е видимо, покупката е последна');
sourceFilterCheck((new planning_Jobs())->toggableFieldsInVerticalListFilter == 'purchaseId' && (new planning_Tasks())->toggableFieldsInVerticalListFilter == 'purchaseId', 'Покупката е допълнителен филтър и в двата листа');
sourceFilterCheck(($form->getFieldType('saleId')->params['savePrevSearch'] ?? null) == 'no', 'Ново задание се отразява без остарели опции от предишно търсене');
$savedJobs = SourceFilterFixture::$rows['planning_Jobs'];
SourceFilterFixture::$rows['planning_Jobs'] = array();
sourceFilterCheck(planning_Jobs::getListSourceOptions(array('mvc' => 'sales_Sales')) === array(), 'При липса на задания няма опции за продажби');
sourceFilterCheck(planning_Jobs::getListSourceOptions(array('mvc' => 'purchase_Purchases')) === array(), 'При липса на задания няма опции за покупки');
SourceFilterFixture::$rows['planning_Jobs'] = $savedJobs;
sourceFilterCheck(count(sourceFilterList('planning_Tasks', array('state' => 'all', 'isFinalSelect' => 'all'))->fetchAll()) === count(SourceFilterFixture::$rows['planning_Tasks']), 'Празните договорни филтри запазват всички операции');

$restore = new ReflectionMethod('planning_Tasks', 'restorePersistentListFilter');
$restore->setAccessible(true);
foreach (array('saleId', 'purchaseId') as $field) {
    Request::$values = Mode::$values = core_Settings::$writes = array();
    core_Settings::$values = array($field => 6, 'search' => 'needle');
    $restore->invoke(null);
    $restoredForm = null;
    sourceFilterList('planning_Tasks', array('state' => 'all', 'isFinalSelect' => 'all'), $restoredForm);
    sourceFilterCheck(!$restoredForm->gotErrors(), $field . ': стар запомнен договор без задание не причинява грешка във формата');
    sourceFilterCheck(Request::get($field) === '' && core_Settings::$values[$field] === '', $field . ': невалидният запомнен избор се изчиства и от настройките');
    sourceFilterCheck((core_Settings::$values['search'] ?? null) === 'needle', $field . ': останалите запомнени филтри се запазват');

    Request::$values = Mode::$values = core_Settings::$writes = array();
    core_Settings::$values = array($field => 1);
    $restore->invoke(null);
    sourceFilterList('planning_Tasks', array('state' => 'all', 'isFinalSelect' => 'all'), $restoredForm);
    sourceFilterCheck(!$restoredForm->gotErrors() && Request::get($field) === 1 && !core_Settings::$writes, $field . ': валидният запомнен договор в чернова се запазва');

    Request::$values = array('Cmd' => array('refresh' => 1));
    Mode::$values = core_Settings::$writes = array();
    core_Settings::$values = array($field => 6);
    $restore->invoke(null);
    sourceFilterList('planning_Tasks', array('state' => 'all', 'isFinalSelect' => 'all'), $restoredForm);
    sourceFilterCheck(!$restoredForm->gotErrors() && Request::get($field) === '' && Request::get('Cmd') === array('default' => 1), $field . ': рефрешът на целия лист изчиства стария избор преди въвеждането');

    Request::$values = array($field => '');
    Mode::$values = core_Settings::$writes = array();
    core_Settings::$values = array($field => 6);
    $restore->invoke(null);
    sourceFilterList('planning_Tasks', array('state' => 'all', 'isFinalSelect' => 'all'), $restoredForm);
    sourceFilterCheck(!$restoredForm->gotErrors() && Request::get($field) === '', $field . ': празен избор не се замества със стария договор');

    Request::$values = array($field => 6);
    Mode::$values = core_Settings::$writes = array();
    core_Settings::$values = array($field => 1);
    $restore->invoke(null);
    sourceFilterList('planning_Tasks', array('state' => 'all', 'isFinalSelect' => 'all'), $restoredForm);
    sourceFilterCheck(($restoredForm->errors[$field] ?? null) === 'Несъществуващ обект' && Request::get($field) === 6 && !core_Settings::$writes, $field . ': изрично невалиден избор остава грешка и не отменя филтъра мълчаливо');
}

Request::$values = Mode::$values = core_Settings::$values = array();
foreach (array('planning_Jobs', 'planning_Tasks') as $class) {
    SourceFilterFixture::$reads = select2_Adapter::$calls = array();
    $restore->invoke(null);
    $emptyForm = null;
    sourceFilterList($class, array('state' => 'all', 'isFinalSelect' => 'all'), $emptyForm);
    foreach (array('saleId', 'purchaseId') as $field) {
        $emptyForm->getFieldType($field)->renderInput_($field, '');
    }
    sourceFilterCheck(sourceFilterOptionReads() === array(), $class . ': зареждането и рендирането на празните полета не извличат договори със задания');
    sourceFilterCheck(count(select2_Adapter::$calls) === 2 && !empty(select2_Adapter::$calls[0]['ajaxUrl']) && empty(select2_Adapter::$calls[0]['forceOpen']) && !empty(select2_Adapter::$calls[1]['ajaxUrl']) && empty(select2_Adapter::$calls[1]['forceOpen']), $class . ': полетата използват AJAX без автоматично отваряне');
}
foreach (array('sales_Sales' => 'saleId', 'purchase_Purchases' => 'purchaseId') as $class => $field) {
    SourceFilterFixture::$reads = array();
    planning_Jobs::getListSourceOptions(array('mvc' => $class), 1, '', 1, true);
    $reads = sourceFilterOptionReads();
    sourceFilterCheck(count($reads) === 1 && ($reads[0]['in'][$field] ?? null) === array('1') && ($reads[0]['limit'] ?? null) === 1, $field . ': проверката на избран договор е само по неговия номер с лимит едно задание');
}
echo $checks . ' проверки преминаха успешно.' . PHP_EOL;

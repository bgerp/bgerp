<?php

// Run with PHP CLI and mbstring. Uses the real search plugin and manager hooks, without a database.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });

$checks = 0;
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
class core_Plugin {}
class core_Manager {}
class arr { public static function make($value) { return is_array($value) ? $value : array(); } }

require dirname(__DIR__, 2) . '/plg/Search.class.php';
require dirname(__DIR__) . '/Numbers.class.php';
require dirname(__DIR__) . '/ListOperationsPlg.class.php';

class NumberSearchMvc extends callcenter_Numbers
{
    public function invoke($event, $args)
    {
        check($event === 'AfterParseSearchQuery', 'Use the standard search extension point');
        static::on_AfterParseSearchQuery($this, $args[0]);
    }
}
class NumberSearchQuery
{
    public $mvc;
    public $where = array();
    public function __construct($mvc) { $this->mvc = $mvc; }
    public function where($condition) { $this->where[] = $condition; }
}
class NumberSearchToolbar { public function addSbBtn($title, $action, $params, $attr) {} }
class NumberSearchForm
{
    public $rec;
    public $fields;
    public $toolbar;
    public $view;
    public $showFields;
    public function __construct($search, $type)
    {
        $this->rec = (object) array('search' => $search, 'type' => $type);
        $this->fields = array('type' => (object) array('type' => (object) array('options' => array('mobile' => 'Mobile'))));
        $this->toolbar = new NumberSearchToolbar();
    }
    public function setField($field, $params) {}
    public function input($field, $mode) {}
}
function filterNumbers($search, $type = null)
{
    $mvc = new NumberSearchMvc();
    $data = (object) array('query' => new NumberSearchQuery($mvc), 'listFilter' => new NumberSearchForm($search, $type));
    plg_Search::applySearch($search, $data->query);
    check(!$data->query->where, 'Phone patterns must not also filter contact keywords: ' . $search);
    callcenter_Numbers::on_AfterPrepareListFilter($mvc, $data);

    return $data->query;
}

// The same digits occur at the start, in the middle, at the end and only in a contact name.
$rows = array(
    array('id' => 1, 'number' => '123456', 'type' => 'internal', 'contact' => 'Alice'),
    array('id' => 2, 'number' => '+359881239999', 'type' => 'mobile', 'contact' => 'Bob'),
    array('id' => 3, 'number' => '+359888000123', 'type' => 'mobile', 'contact' => 'Carol'),
    array('id' => 4, 'number' => '+35929999999', 'type' => 'tel', 'contact' => 'Company 123'),
    array('id' => 5, 'number' => '+359888000123', 'type' => 'fax', 'contact' => 'Other card'),
    array('id' => 6, 'number' => '0123', 'type' => 'internal', 'contact' => 'Dave'),
);
function selectNumbers($query, $rows)
{
    return array_column(array_values(array_filter($rows, function ($row) use ($query) {
        foreach ($query->where as $condition) {
            if ($condition[0] === "#number LIKE '[#1#]'") {
                $pattern = '/^' . str_replace('%', '.*', preg_quote($condition[1], '/')) . '$/D';
                if (!preg_match($pattern, $row['number'])) return false;
            } elseif ($condition[0] === "#type = '[#1#]'") {
                if ($row['type'] !== $condition[1]) return false;
            } else {
                throw new RuntimeException('Unexpected filter: ' . $condition[0]);
            }
        }

        return true;
    })), 'id');
}
foreach (array(
    array('*123', null, array(3, 5, 6)),
    array('123*', null, array(1)),
    array('*123*', null, array(1, 2, 3, 5, 6)),
    array('+35988*', null, array(2, 3, 5)),
    array('  +359 (88)*  ', null, array(2, 3, 5)),
    array('*1-2.3', 'mobile', array(3)),
    array('* 1 2 3 *', 'fax', array(5)),
    array('*0123', null, array(3, 5, 6)),
    array('0*', 'internal', array(6)),
) as $case) {
    check(selectNumbers(filterNumbers($case[0], $case[1]), $rows) === $case[2], 'Correct phone boundaries, duplicates and type: ' . $case[0]);
    $data = (object) array('listFilter' => new NumberSearchForm($case[0], $case[1]), 'title' => 'Numbers');
    callcenter_ListOperationsPlg::on_AfterPrepareListTitle(new NumberSearchMvc(), null, $data);
    check($data->title === 'Numbers' && !isset($data->callLink) && !isset($data->smsLink), 'Fragments must not offer dial or message actions');
}

// Ordinary names, mixed expressions, invalid patterns and bare numbers keep their existing parser behavior.
foreach (array('Alice', 'Company*', '*Company', '123', '0', 'Alice *123', '123*456', '*', '**', '*123%', '*123_', "*123'", '"*123"', '') as $search) {
    $words = plg_Search::parseQuery($search);
    $before = $words;
    callcenter_Numbers::on_AfterParseSearchQuery(new NumberSearchMvc(), $words);
    check($words === $before, 'Preserve ordinary keyword search: ' . $search);
}

echo "PASS: {$checks} phone registry search checks\n";

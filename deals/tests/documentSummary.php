<?php

/**
 * Изолирана проверка на справката за документи по продажби и покупки.
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Stefan Arsov <stefan.arsof@gmail.com> with Codex
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit("Run from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });

class core_exception_Expect extends RuntimeException {}
function expect($condition) { if (!$condition) throw new core_exception_Expect('Неизпълнено условие'); }
function countR($value) { return is_array($value) ? count($value) : 0; }

class core_Master
{
    public $className;
    public static $records = array();
    public function __construct() { $this->className = get_class($this); }
    public function getClassId() { return $this->className; }
    public static function fetchRec($id) { return is_object($id) ? $id : (self::$records[static::class][$id] ?? null); }
    public function haveRightFor($action, $rec) { return empty($rec->restricted); }
    public function hasPlugin($plugin) { return false; }
    public function getField($field, $required) { return true; }
    public function getVerbal($rec, $field) { return $rec->{$field} ?? ''; }
}

class deals_DealMaster extends core_Master {}
class sales_Sales extends deals_DealMaster {}
class purchase_Purchases extends deals_DealMaster {}
class store_DocumentMaster extends core_Master {}
class store_ShipmentOrders extends store_DocumentMaster {}
class store_Receipts extends store_DocumentMaster {}
class bank_Document extends core_Master {}
class bank_IncomeDocuments extends bank_Document {}
class bank_SpendingDocuments extends bank_Document {}
class findeals_DebitDocuments extends core_Master {}
class findeals_CreditDocuments extends core_Master {}

class core_Classes { public static function getId($class) { return $class; } }
class cls
{
    public static function load($class, $silent) { return class_exists($class); }
    public static function get($class) { return new $class(); }
}
class doc_Containers
{
    public static $rows = array();
    private $filters = array();
    public static function getQuery() { return new self(); }
    public function in($field, $values) { $this->filters[$field] = $values; }
    public function show($fields) {}
    public function fetchAll()
    {
        return array_filter(self::$rows, function ($row) {
            foreach ($this->filters as $field => $values) {
                if (!in_array($row->{$field} ?? null, $values, true)) return false;
            }

            return true;
        });
    }
}

class arr { public static function make($value) { return $value ? explode(',', $value) : array(); } }
class dt { public static function getLastDayOfMonth($date) { return $date; } }
class deals_Helper { public static function getSmartBaseCurrency($amount, $from, $to) { return $amount; } }
class acc_DocumentTransactionSource {}
class acc_Accounts { public static function getRecBySystemId($id) { return (object) array('id' => $id); } }
class acc_Items { public static function fetchItem($class, $id) { return (object) array('id' => $id); } }
class acc_Journal
{
    public static $entries = array();
    public static function getEntries($deal, &$item)
    {
        $item = acc_Items::fetchItem($deal[0], $deal[1]);

        return self::$entries[$deal[1]] ?? array();
    }
}

$root = dirname(__DIR__, 2);
require $root . '/acc/Balances.class.php';
require $root . '/sales/transaction/Sale.class.php';
require $root . '/purchase/transaction/Purchase.class.php';
require $root . '/deals/InvoiceMaster.class.php';
require $root . '/deals/DocumentSummary.class.php';
class sales_Invoices extends deals_InvoiceMaster {}

function testDocument($class, $id, $thread, $fields = array())
{
    $rec = (object) ($fields + array('id' => $id, 'threadId' => $thread, 'state' => 'active', 'currencyId' => 'EUR', 'currencyRate' => 1, 'valior' => '2026-03-10'));
    core_Master::$records[$class][$id] = $rec;
    doc_Containers::$rows[] = (object) array('docClass' => $class, 'docId' => $id, 'threadId' => $thread, 'state' => $rec->state);

    return $rec;
}

function postTestEntry($dealId, $docClass, $docId, $debit, $credit, $amount, $debitDeal, $creditDeal, $quantity = null)
{
    acc_Journal::$entries[$dealId][] = (object) array(
        'id' => countR(acc_Journal::$entries[$dealId] ?? null) + 1, 'docType' => $docClass, 'docId' => $docId,
        'debitAccId' => $debit, 'creditAccId' => $credit, 'debitItem2' => $debitDeal, 'creditItem2' => $creditDeal,
        'amount' => $amount, 'valior' => '2026-03-10', 'debitQuantity' => $quantity, 'creditQuantity' => $quantity,
    );
}

$checks = 0;
function checkSummary($actual, $expected, $message)
{
    if ($actual != $expected) throw new RuntimeException($message . ': ' . json_encode(array($actual, $expected)));
    $GLOBALS['checks']++;
}

$sale = testDocument('sales_Sales', 10, 1);
postTestEntry(10, 'sales_Sales', 10, '411', '701', 120, 10, null);
postTestEntry(10, 'sales_Sales', 10, '501', '411', 120, null, 10);
testDocument('store_ShipmentOrders', 21, 1);
postTestEntry(10, 'store_ShipmentOrders', 21, '411', '701', 80, 10, null);
testDocument('store_ShipmentOrders', 22, 1);
postTestEntry(10, 'store_ShipmentOrders', 22, '411', '701', -20, 10, null);
$transfer = testDocument('findeals_DebitDocuments', 30, 2);
postTestEntry(10, 'findeals_DebitDocuments', 30, '411', '411', 40, 11, 10);
testDocument('findeals_CreditDocuments', 31, 3);
postTestEntry(10, 'findeals_CreditDocuments', 31, '411', '411', 15, 10, 12);
testDocument('bank_IncomeDocuments', 40, 1, array('amountDeal' => 25, 'amount' => 60));
postTestEntry(10, 'bank_IncomeDocuments', 40, '481', '411', 26, null, 10);
testDocument('bank_SpendingDocuments', 41, 1, array('amountDeal' => 5));
postTestEntry(10, 'bank_SpendingDocuments', 41, '503', '411', -5, null, 10);
testDocument('sales_Invoices', 50, 1, array('date' => '2026-03-09', 'type' => 'invoice', 'containerId' => 150, 'dealValue' => 66, 'vatAmount' => 12, 'discountAmount' => 3));
testDocument('sales_Invoices', 51, 1, array('date' => '2026-03-13', 'type' => 'dc_note', 'originId' => 150, 'dealValue' => -10, 'vatAmount' => -2));
testDocument('sales_Invoices', 52, 1, array('date' => '2026-03-11', 'dealValue' => 100, 'state' => 'draft'));
testDocument('bank_IncomeDocuments', 53, 1, array('amountDeal' => 900, 'state' => 'pending'));
testDocument('store_Receipts', 54, 1, array('amountDelivered' => 250, 'state' => 'draft', 'isReverse' => 'yes'));
testDocument('sales_Invoices', 55, 1, array('date' => '2026-03-12', 'type' => 'debit_note', 'originId' => 150, 'dealValue' => 7, 'state' => 'draft'));
testDocument('sales_Invoices', 56, 1, array('date' => '2026-03-08', 'type' => 'credit_note', 'originId' => 999, 'dealValue' => 5));
testDocument('sales_Invoices', 57, 1, array('date' => '2026-03-14', 'type' => 'dc_note', 'originId' => 150, 'dealValue' => 10, 'vatAmount' => 2));

$summary = deals_DocumentSummary::prepare(new sales_Sales(), $sale);
$rows = array_column($summary['rows'], null, 'id');
checkSummary(array_keys($rows), array(56, 50, 10, 21, 22, 30, 31, 40, 41, 53, 54, 52), 'Видимите известия са само при фактурата си, а несвързаното остава отделно');
checkSummary(array_column($rows[50]['invoiceNotes'], 'id'), array(55, 51, 57), 'Известията в реда на фактурата са хронологични');
checkSummary(array($rows[50]['invoiceTotalWithNotes'], $rows[50]['invoiceNotes'][0]['unpostedState']), array(75, 'draft'), 'Сбор за фактурата с видимите осчетоводени известия');
checkSummary(array($rows[10]['delivered'], $rows[10]['settled']), array(120, 120), 'Договор с доставка и плащане');
checkSummary($rows[22]['delivered'], -20, 'Връщане на стока');
checkSummary(array($rows[30]['settled'], $rows[30]['otherThread'], $rows[30]['nonCash'], $rows[31]['settled']), array(40, true, true, -15), 'Прехвърляне в двете посоки');
checkSummary(array($rows[40]['settled'], $rows[40]['postedAmount'], $rows[41]['settled']), array(25, 26, -5), 'Платени по сделката и обратно плащане');
checkSummary(array($rows[50]['invoiced'], $rows[50]['invoiceNotes'][1]['invoiced'], $rows[50]['invoiceNotes'][1]['title']), array(75, -12, 'Кредитно известие'), 'Крайна сума с ДДС и отстъпка');
checkSummary(array($rows[52]['unpostedState'], $rows[52]['invoiced'], $rows[53]['unpostedState'], $rows[53]['settled'], $rows[54]['delivered']), array('draft', 100, 'pending', 900, -250), 'Видими чернови и заявки');
checkSummary($summary['totals'], array('delivered' => 180, 'settled' => 165, 'invoiced' => 70), 'Общо без повторно сумиране на известията и без черновите');
checkSummary(array($summary['balance'], $summary['difference']), array(14, -1), 'Салдо по журнала и разлика със сбора');

$transfer->restricted = true;
testDocument('sales_Invoices', 58, 1, array('date' => '2026-03-15', 'type' => 'dc_note', 'originId' => 150, 'dealValue' => 900, 'restricted' => true));
$summary = deals_DocumentSummary::prepare(new sales_Sales(), $sale);
checkSummary(array($summary['partial'], $summary['totals']['settled'], $summary['balance']), array(true, 125, 14), 'Скрити документи извън сбора');
$rows = array_column($summary['rows'], null, 'id');
checkSummary(array($summary['totals']['invoiced'], $rows[50]['invoiceTotalWithNotes']), array(70, 75), 'Недостъпното известие не участва в общия сбор и в сбора за фактурата');

$purchase = testDocument('purchase_Purchases', 60, 4);
testDocument('store_Receipts', 61, 4);
postTestEntry(60, 'store_Receipts', 61, '301', '401', 150, null, 60);
testDocument('bank_SpendingDocuments', 62, 4, array('amountDeal' => 40));
postTestEntry(60, 'bank_SpendingDocuments', 62, '402', '503', 40, 60, null);
testDocument('findeals_DebitDocuments', 63, 4);
postTestEntry(60, 'findeals_DebitDocuments', 63, '401', '401', 10, 64, 60);
$summary = deals_DocumentSummary::prepare(new purchase_Purchases(), $purchase);
checkSummary(array($summary['totals']['delivered'], $summary['totals']['settled'], $summary['balance'], $summary['difference']), array(150, 30, -120, 0), 'Доставка, аванс и прехвърляне по покупка');

$saleUsd = testDocument('sales_Sales', 70, 5, array('currencyId' => 'USD', 'currencyRate' => 0.9));
testDocument('store_ShipmentOrders', 71, 5);
postTestEntry(70, 'store_ShipmentOrders', 71, '411', '701', 180, 70, null, 200);
testDocument('bank_IncomeDocuments', 72, 5, array('amountDeal' => 125, 'amount' => 112.50));
postTestEntry(70, 'bank_IncomeDocuments', 72, '503', '411', 112.50, null, 70, 125);
testDocument('sales_Invoices', 73, 5, array('currencyId' => 'USD', 'displayRate' => 0.9, 'dealValue' => 180));
$summary = deals_DocumentSummary::prepare(new sales_Sales(), $saleUsd);
checkSummary(array($summary['totals'], $summary['balance'], $summary['difference']), array(array('delivered' => 200, 'settled' => 125, 'invoiced' => 200), 75, 0), 'Отчетени документи по сделка в чужда валута');

echo "{$checks} document summary checks passed.\n";

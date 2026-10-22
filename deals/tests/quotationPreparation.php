<?php

/**
 * Изолирана проверка на нативната подготовка на офертни редове.
 *
 * @category bgerp
 * @package deals
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 - 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("Run from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
$checks = 0;
function checkQuote($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function countR($value) { return is_array($value) ? count($value) : 0; }
class doc_Detail {}
class doc_Folders
{
    public static function getCover($id) { return new self(); }
    public function getContragentData() { return (object) array('countryId' => 1); }
}
class cat_UoM { public static function fetchBySinonim($name) { return (object) array('id' => 1); } }
class cond_VatExceptions
{
    public static $calls = 0;
    public static function getFromThreadId($id) { self::$calls++; return 17; }
}
class cat_Products
{
    public static $calls = array();
    public static function getVat($id, $date, $exception)
    {
        self::$calls[] = array($id, $date, $exception);
        return 0.2;
    }
}
class doc_plg_HidePrices
{
    public static $visible = true;
    public static function canSeePriceFields($mvc, $rec) { return self::$visible; }
}
// Изчисляването остава в deals_Helper; тук следим кои редове получава той.
class deals_Helper
{
    public static $groups = array();
    public static function fillRecs($mvc, &$recs, $masterRec, $map)
    {
        self::$groups[] = array_keys($recs);
        $mvc->_total = (object) array('vats' => array('0.2' => (object) array()));
    }
    public static function prepareSummary(...$args)
    {
        return (object) array('value' => '100.00', 'total' => '100.00', 'neto' => null, 'discountValue' => null);
    }
}
require dirname(__DIR__) . '/QuotationDetails.class.php';

class QuotePreparation extends deals_QuotationDetails
{
    public $_total;
    public static $liveCalls = array();
    public function prepare($data) { self::on_AfterPrepareListRecs($this, $data); }
    public function showDualPrices($folderId) { return false; }
    public static function calcLivePrice($rec, $masterRec, $force = false)
    {
        self::$liveCalls[] = $rec->id;
        if ($rec->productId == 99) $rec->price = 12.345;
    }
    public function legacyHasAlternatives($recs)
    {
        foreach ($recs as $rec) {
            if ($rec->optional == 'no' && $this->checkUnique($recs, $rec->productId, $rec->id, 'no', $rec->notes)) return true;
        }
        return false;
    }
}
function quoteRow($id, $product = 1, $notes = '', $optional = 'no')
{
    return (object) array('id' => $id, 'productId' => $product, 'notes' => $notes, 'optional' => $optional,
        'price' => 10, 'packPrice' => 20, 'quantityInPack' => 2, 'packQuantity' => 3, 'packagingId' => 2);
}
function quoteData($recs, $vatMode = 'no', $rate = 1)
{
    $master = new QuotePreparation();
    return (object) array('recs' => $recs, 'masterMvc' => $master, 'masterData' => (object) array(
        'rec' => (object) array('folderId' => 5, 'threadId' => 7, 'date' => '2026-01-15', 'currencyRate' => $rate,
            'currencyId' => 'EUR', 'chargeVat' => $vatMode, 'tplLang' => 'bg'),
        'row' => (object) array('chargeVat' => $vatMode)));
}
$fixtures = array(
    'distinct products' => array(quoteRow(1), quoteRow(2, 2)),
    'quantity alternatives' => array(quoteRow(1), quoteRow(2)),
    'different notes' => array(quoteRow(1, 1, 'A'), quoteRow(2, 1, 'B')),
    'optional alternatives' => array(quoteRow(1), quoteRow(2, 1, '', 'yes'), quoteRow(3, 1, '', 'yes')),
    'non-adjacent alternatives' => array(quoteRow(1), quoteRow(2, 2), quoteRow(3)),
    'empty and null notes' => array(quoteRow(1, 1, null), quoteRow(2)),
    'space is significant' => array(quoteRow(1), quoteRow(2, 1, ' ')),
    'numeric product ids' => array(quoteRow(1, '01'), quoteRow(2, 1)),
    'same record twice' => array(quoteRow(1), quoteRow(1)),
    'optional only' => array(quoteRow(1, 1, '', 'yes'), quoteRow(2, 1, '', 'yes')),
    'empty' => array(),
);
$mvc = new QuotePreparation();
foreach ($fixtures as $name => $recs) {
    foreach (array('no', 'yes', 'separate') as $vatMode) {
        foreach (array(1, 1.2345) as $rate) {
            $data = quoteData(array_map(function ($r) { return clone $r; }, $recs), $vatMode, $rate);
            $expected = !$mvc->legacyHasAlternatives($recs) && (bool) array_filter($recs, function ($r) { return $r->optional == 'no'; });
            cond_VatExceptions::$calls = 0;
            deals_Helper::$groups = cat_Products::$calls = QuotePreparation::$liveCalls = array();
            $mvc->prepare($data);
            checkQuote(isset($data->summary) === $expected, "$name: summary must match the previous alternative detection");
            checkQuote(cond_VatExceptions::$calls === (count($recs) ? 1 : 0), "$name: resolve the thread VAT exception once");
            checkQuote(count(cat_Products::$calls) === count($recs) && QuotePreparation::$liveCalls === array(), "$name: preserve per-product VAT and stored prices");
            $mandatory = $optional = array();
            foreach ($recs as $key => $rec) {
                if ($rec->optional == 'no') $mandatory[] = $key; else $optional[] = $key;
                checkQuote(abs($data->recs[$key]->vatPackPrice - 24 / $rate) < 0.0000001, "$name: preserve VAT price and currency conversion");
            }
            checkQuote(deals_Helper::$groups === array($mandatory, $optional), "$name: preserve all rows and native calculation groups");
        }
    }
}
foreach (array(99 => true, 98 => false) as $product => $available) {
    $row = quoteRow(1, $product);
    unset($row->price);
    $data = quoteData(array($row));
    QuotePreparation::$liveCalls = array();
    $mvc->prepare($data);
    checkQuote(QuotePreparation::$liveCalls === array(1), 'Dynamic price calculation still runs');
    checkQuote(isset($data->summary) === $available, 'Missing prices must still suppress totals');
    if ($available) checkQuote($row->livePrice === true && abs($row->amount - 74.07) < 0.0000001, 'Preserve dynamic price and pack quantity');
}
doc_plg_HidePrices::$visible = false;
$data = quoteData(array(quoteRow(1)));
$mvc->prepare($data);
checkQuote(!isset($data->summary) && !empty($data->noTotal), 'Price restrictions must still suppress totals');
doc_plg_HidePrices::$visible = true;
$large = array();
for ($i = 1; $i <= 5000; $i++) $large[$i] = quoteRow($i, 1, 'Synthetic row ' . $i);
$data = quoteData($large);
$mvc->prepare($data);
checkQuote(isset($data->summary) && $data->countNotOptional === 5000, 'All 5000 distinct groups retain a total');
$large[5000]->notes = $large[1]->notes;
$data = quoteData($large);
$mvc->prepare($data);
checkQuote(!isset($data->summary), 'An alternative at the end must still suppress the total');
echo "Quotation preparation: {$checks} checks passed.\n";

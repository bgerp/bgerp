<?php


/**
 * Клас 'wbarcode_Helper'
 *
 * Разчитане на баркодове с променливо тегло: от сканирания баркод до артикул и количество
 *
 *
 * @category  bgerp
 * @package   wbarcode
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class wbarcode_Helper
{
    /**
     * Дължина на маската. При EAN-13 контролното число е едно, затова не е настройваемо
     */
    const MASK_LENGTH = 13;


    /**
     * Разрешеният вид на маската: цифри за префикс, P за код, W за тегло и едно C накрая
     */
    const MASK_PATTERN = '/^[0-9]+P+W+C$/';


    /**
     * Маските от настройките на пакета
     *
     * Подредени са от най-дългия към най-късия префикс, за да печели по-конкретната маска,
     * ако префиксът на едната е начало на префикса на другата
     *
     * @return array $res - масив от обекти с ключ маската:
     *
     *               ->mask         - маската, както е записана в настройките
     *               ->prefix       - цифрите в началото ѝ
     *               ->codeDigits   - брой позиции за кода на артикула
     *               ->weightDigits - брой позиции за теглото
     */
    public static function getMasks()
    {
        $res = array();
        $confMasks = type_Table::toArray(wbarcode_Setup::get('MASKS'));
        foreach ($confMasks as $row) {
            if (empty($row->mask)) continue;

            $maskRec = static::parseMask($row->mask);
            if (empty($maskRec)) continue;

            $res[$maskRec->mask] = $maskRec;
        }

        uasort($res, function ($a, $b) {

            return strlen($b->prefix) - strlen($a->prefix);
        });

        return $res;
    }


    /**
     * Разбива една маска на съставните ѝ части
     *
     * @param string $mask - маска, например '28PPPPWWWWWWC'
     *
     * @return stdClass|false - частите на маската или FALSE, ако маската е невалидна
     */
    private static function parseMask($mask)
    {
        $mask = strtoupper(trim($mask));
        if (strlen($mask) != self::MASK_LENGTH) return false;
        if (!preg_match(self::MASK_PATTERN, $mask)) return false;

        $res = new stdClass();
        $res->mask = $mask;
        $res->prefix = substr($mask, 0, strspn($mask, '0123456789'));
        $res->codeDigits = substr_count($mask, 'P');
        $res->weightDigits = substr_count($mask, 'W');

        return $res;
    }


    /**
     * Разбива сканиран баркод, ако той отговаря на някоя от маските
     *
     * @param string $barcode - сканираният баркод
     *
     * @return stdClass|false $res - извлечената информация или FALSE, ако баркодът не е тегловен
     *
     *               ->barcode        - самият баркод
     *               ->mask           - маската, по която е разчетен
     *               ->productCode    - кодът на артикула, както е в баркода (с водещите нули)
     *               ->weight         - теглото в килограми
     *               ->weightRaw      - тегловната част, както е в баркода, без десетичен знак
     *               ->weightDecimals - колко от последните ѝ цифри са след десетичния знак
     *               ->isValidEan     - вярно ли е контролното число
     */
    public static function parse(string $barcode)
    {
        if (strlen($barcode) != self::MASK_LENGTH) return false;
        if (preg_match('/[^0-9]/', $barcode)) return false;

        foreach (static::getMasks() as $maskRec) {
            if (strpos($barcode, $maskRec->prefix) !== 0) continue;

            $codeStart = strlen($maskRec->prefix);
            $weightStart = $codeStart + $maskRec->codeDigits;

            // Къде е десетичният знак в тегловната част: при 3 теглото в баркода е в грамове
            $decimals = (int) wbarcode_Setup::get('WEIGHT_DECIMALS');

            $res = new stdClass();
            $res->barcode = $barcode;
            $res->mask = $maskRec->mask;
            $res->productCode = substr($barcode, $codeStart, $maskRec->codeDigits);
            $res->weightRaw = (int) substr($barcode, $weightStart, $maskRec->weightDigits);
            $res->weightDecimals = $decimals;
            $res->weight = round($res->weightRaw / pow(10, $decimals), $decimals);

            // Контролното число се проверява, но не е причина баркодът да се отхвърли,
            // защото някои везни печатат етикети с невалидна контролна сума
            $res->isValidEan = cls::get('gs1_TypeEan')->isValidEan($barcode, self::MASK_LENGTH);

            return $res;
        }

        return false;
    }


    /**
     * Артикулът и теглото от сканиран тегловен баркод
     *
     * @param string $barcode - сканираният баркод
     * @param string|null $codeMode - как да се тълкуват водещите нули в кода на артикула;
     *                           ако не е зададен, се взима от настройките на пакета:
     *
     *               'padded'  - кодът се търси точно както е записан в баркода
     *               'trimmed' - водещите нули се махат и остатъкът се приема за код
     *               'smart'   - пробва се първо с целия код, после се маха по една водеща нула,
     *                           докато не останат; ако и тогава няма артикул - не се намира
     *
     *                           Ако няколко кандидата дадат артикул, предимство има първият активен,
     *                           а не първият намерен - иначе по-краткият код може да улучи затворен артикул
     *
     * @return stdClass|false $res - FALSE, ако няма маски, баркодът не отговаря на никоя от тях
     *                               или няма артикул с този код
     *
     *               ->productId   - ид на намерения артикул
     *               ->productCode - кодът, по който е намерен артикулът (може да е с махнати водещи нули)
     *               ->measureId   - мярката, в която е количеството: основната мярка на артикула, ако
     *                               тя е производна на килограма, иначе втора мярка производна на
     *                               килограма; NULL, ако артикулът няма такава
     *               ->quantity    - теглото, преизчислено в тази мярка; NULL, ако мярка няма
     *               ->weight      - теглото от баркода, в килограми
     *               ->error       - съобщение, ако артикулът е намерен, но няма тегловна мярка
     */
    public static function getProduct(string $barcode, ?string $codeMode = null)
    {
        $parsed = static::parse($barcode);
        if (empty($parsed)) return false;

        if (!isset($codeMode)) {
            $codeMode = wbarcode_Setup::get('CODE_MODE');
        }

        $code = $parsed->productCode;
        switch ($codeMode) {
            case 'padded':
                $codesToTry = array($code);
                break;
            case 'trimmed':
                $codesToTry = array(ltrim($code, '0'));
                break;
            default:
                $codesToTry = array($code);
                while (substr($code, 0, 1) === '0') {
                    $code = substr($code, 1);
                    $codesToTry[] = $code;
                }
                break;
        }

        $found = $fallback = null;
        foreach ($codesToTry as $codeToTry) {

            // Празен код не се търси - cat_Products::getByCode() очаква непразна стойност и иначе гърми
            if (empty($codeToTry)) continue;

            $productRec = cat_Products::getByCode($codeToTry);
            if (empty($productRec)) continue;

            // Активният артикул има предимство: при махане на водещите нули по-краткият код
            // може да улучи затворен артикул, а активният да е чак при следващия кандидат
            if (cat_Products::fetchField($productRec->productId, 'state') == 'active') {
                $found = array($productRec->productId, $codeToTry);
                break;
            }

            // Първият неактивен артикул се пази - ако никой кандидат не е активен, се връща той
            if (!isset($fallback)) {
                $fallback = array($productRec->productId, $codeToTry);
            }
        }

        $found = $found ?? $fallback;
        if (!isset($found)) return false;

        $res = new stdClass();
        list($res->productId, $res->productCode) = $found;
        $res->weight = $parsed->weight;
        $res->measureId = static::getWeightMeasureId($res->productId);
        $res->quantity = null;
        $res->error = null;

        if (empty($res->measureId)) {
            $res->error = "Намереният артикул|* #Art{$res->productId} |по код|* {$res->productCode} |няма мярка в килограми или производна на нея|*!";
        } else {
            $res->quantity = cat_UoM::convertValue($res->weight, cat_UoM::fetchBySysId('kg')->id, $res->measureId);
        }

        return $res;
    }


    /**
     * В коя мярка на артикула може да се запише тегло
     *
     * Първо се гледа основната мярка - ако тя е килограм или производна на него, тя се ползва.
     * Иначе се търси втора мярка (от опаковките), която е производна на килограма.
     *
     * @param int $productId - ид на артикул
     *
     * @return int|null - ид на мярката или NULL, ако артикулът няма тегловна мярка
     */
    private static function getWeightMeasureId($productId)
    {
        // Ако основната мярка на артикула е тегловна - тя се ползва
        $measureId = cat_Products::fetchField($productId, 'measureId');
        if (isset($measureId) && cat_UoM::isWeightMeasure($measureId)) {

            return $measureId;
        }

        // Всички мерки, производни на килограма
        $kgRec = cat_UoM::fetchBySysId('kg');
        if (!is_object($kgRec)) return null;

        $massMeasures = cat_UoM::getSameTypeMeasures($kgRec->id);
        unset($massMeasures['']);
        if (!countR($massMeasures)) return null;

        // Търси се втора мярка, производна на килограма
        $query = cat_products_Packagings::getQuery();
        $query->EXT('type', 'cat_UoM', 'externalName=type,externalKey=packagingId');
        $query->where("#productId = {$productId} AND #type = 'uom' AND #state != 'closed'");
        $query->in('packagingId', array_keys($massMeasures));
        $query->orderBy('id', 'ASC');
        $query->show('packagingId,isSecondMeasure');

        $res = null;
        while ($packRec = $query->fetch()) {

            // Изрично посочената втора мярка има предимство, иначе се взима първата поред
            if ($packRec->isSecondMeasure == 'yes') return $packRec->packagingId;
            if (!isset($res)) {
                $res = $packRec->packagingId;
            }
        }

        return $res;
    }


    /**
     * Проверка на въведените маски в настройките на пакета
     *
     * @param array      $values - стойностите от таблицата
     * @param type_Table $Table  - типът на полето
     *
     * @return array $res - грешките, ако има такива
     */
    public static function validateMasks($values, $Table)
    {
        $res = array();
        $usedPrefixes = $errors = array();

        $masks = isset($values['mask']) ? (array) $values['mask'] : array();
        foreach ($masks as $key => $mask) {
            $mask = strtoupper(trim((string) $mask));
            if (!strlen($mask)) continue;

            $error = null;
            if (strlen($mask) != self::MASK_LENGTH) {
                $error = 'Маската трябва да е точно|* ' . self::MASK_LENGTH . ' |символа|*!';
            } elseif (!preg_match(self::MASK_PATTERN, $mask)) {
                $error = 'Маската трябва да е от цифри за префикс, после|* P |за кода на артикула|*, W |за теглото и едно|* C |накрая - например|* 28PPPPWWWWWWC!';
            } else {
                $prefix = substr($mask, 0, strspn($mask, '0123456789'));
                if (isset($usedPrefixes[$prefix])) {
                    $error = 'Вече има маска с този префикс|*!';
                } else {
                    $usedPrefixes[$prefix] = true;
                }
            }

            if (isset($error)) {
                $res['errorFields']['mask'][$key] = $error;
                $errors[] = "|*<li>| {$error}";
            }
        }

        if (countR($errors)) {
            $res['error'] = implode('', $errors);
        }

        return $res;
    }
}

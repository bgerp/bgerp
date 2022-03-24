<?php


/**
 * Помощен клас за конвертиране на суми и цени, изпозлван в бизнес документите
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class deals_Helper
{
    /**
     * Масив за мапване на стойностите от мениджърите
     */
    private static $map = array(
        'priceFld' => 'packPrice',
        'quantityFld' => 'packQuantity',
        'amountFld' => 'amount',
        'rateFld' => 'currencyRate',
        'productId' => 'productId',
        'chargeVat' => 'chargeVat',
        'valior' => 'valior',
        'currencyId' => 'currencyId',
        'discAmountFld' => 'discAmount',
        'discount' => 'discount',
        'autoDiscount' => 'autoDiscount',
        'alwaysHideVat' => false, // TRUE всичко трябва да е без ДДС
    );
    
    
    /**
     * Константа за умно конвертиране
     */
    const SMART_PRICE_CONVERT = '0.015';
    
    
    /**
     * Умно закръгляне на цена
     *
     * @param float $price     - цена, която ще се закръгля
     * @param int   $minDigits - минимален брой значещи цифри
     *
     * @return float $price  - закръглената цена
     */
    public static function roundPrice($price, $minDigits = 7)
    {
        $p = 0;
        if ($price) {
            $p = round(log10(abs($price)));
        }
        
        // Плаваща прецизност
        $precision = max(2, $minDigits - $p);
        
        // Изчисляваме закръглената цена
        $price = round($price, $precision);
        
        return $price;
    }
    
    
    /**
     * Пресмята цена с ддс и без ддс
     *
     * @param float $price - цената в основна валута без ддс
     * @param float $vat   - процента ддс
     * @param float $rate  - курса на валутата
     *
     * @return stdClass->noVat - цената без ддс
     *                         stdClass->withVat - цената с ддс
     */
    private static function calcPrice($price, $vat, $rate)
    {
        $arr = array();
        
        // Конвертиране цените във валутата
        if (!empty($rate)) {
            $arr['noVat'] = $price / $rate;
            $arr['withVat'] = ($price * (1 + $vat)) / $rate;
        } else {
            $arr['noVat'] = $price;
            $arr['withVat'] = ($price * (1 + $vat));
        }
        
        $arr['noVat'] = $arr['noVat'];
        $arr['withVat'] = $arr['withVat'];
        
        return (object) $arr;
    }
    
    
    /**
     * Помощен метод използван в бизнес документите за показване на закръглени цени на редовете
     * и за изчисляване на общата цена
     *
     * @param array    $recs      - записи от детайли на модел
     * @param stdClass $masterRec - мастър записа
     * @param array    $map       - масив с мапващи стойностите на полета от фунцкията
     *                            с полета в модела, има стойности по подрабзиране (@see static::$map)
     */
    public static function fillRecs(&$mvc, &$recs, &$masterRec, $map = array())
    {
        if (countR($recs) === 0) {
            unset($mvc->_total);
            
            return;
        }
        
        expect(is_object($masterRec));
        
        // Комбиниране на дефолт стойнсотите с тези подадени от потребителя
        $map = array_merge(self::$map, $map);

        // Дали трябва винаги да не се показва ддс-то към цената
        $hasVat = ($map['alwaysHideVat']) ? false : (($masterRec->{$map['chargeVat']} == 'yes') ? true : false);
        $amountJournal = $discount = $amount = $amountVat = $amountTotal = $amountRow = 0;
        $vats = array();
        
        $vatDecimals = sales_Setup::get('SALE_INV_VAT_DISPLAY', true) == 'yes' ? 20 : 2;
        $testRound = deals_Setup::get('TEST_VAT_CALC');

        // Обработваме всеки запис
        foreach ($recs as &$rec) {
            $vat = 0;
            if ($masterRec->{$map['chargeVat']} == 'yes' || $masterRec->{$map['chargeVat']} == 'separate') {
                $vat = cat_Products::getVat($rec->{$map['productId']}, $masterRec->{$map['valior']});
            }

            // Калкулира се цената с и без ддс и се показва една от тях взависимост трябвали да се показва ддс-то
            $price = self::calcPrice($rec->{$map['priceFld']}, $vat, $masterRec->{$map['rateFld']});
            $rec->{$map['priceFld']} = ($hasVat) ? $price->withVat : $price->noVat;
            
            $noVatAmount = round($price->noVat * $rec->{$map['quantityFld']}, $vatDecimals);
            $discountVal = isset($rec->{$map['discount']}) ? $rec->{$map['discount']} : $rec->{$map['autoDiscount']};
            
            if ($discountVal) {
                $withoutVatAndDisc = $noVatAmount * (1 - $discountVal);
            } else {
                $withoutVatAndDisc = $noVatAmount;
            }
            
            
            $vatRow = round($withoutVatAndDisc * $vat, $vatDecimals);
            
            $rec->{$map['amountFld']} = $noVatAmount;
            if ($masterRec->{$map['chargeVat']} == 'yes' && !$map['alwaysHideVat']) {
                $rec->{$map['amountFld']} = round($rec->{$map['amountFld']} + round($noVatAmount * $vat, $vatDecimals), $vatDecimals);
            }
            
            if ($discountVal) {
                if (!($masterRec->type === 'dc_note' && $rec->changedQuantity !== true && $rec->changedPrice !== true)) {
                    $discount += $rec->{$map['amountFld']} * $discountVal;
                }
            }
            
            // Ако документа е кредитно/дебитно известие сабираме само редовете с промяна
            if ($masterRec->type === 'dc_note') {
                if ($rec->changedQuantity === true || $rec->changedPrice === true) {
                    $amountRow += $rec->{$map['amountFld']};
                    $amount += $noVatAmount;
                    $amountVat += $vatRow;
                    
                    $amountJournal += $withoutVatAndDisc;
                    if ($masterRec->{$map['chargeVat']} == 'yes') {
                        $amountJournal += $vatRow;
                    }
                }
            } else {

                // За всички останали събираме нормално
                if($testRound == 'yes'){
                    $amountRow += round($rec->{$map['amountFld']}, 2);
                    $amount += round($noVatAmount, 2);
                } else {
                    $amountRow += $rec->{$map['amountFld']};
                    $amount += $noVatAmount;
                }

                $amountVat += $vatRow;

                if ($masterRec->{$map['chargeVat']} == 'yes') {
                    $amountJournal += $withoutVatAndDisc;
                    $amountJournal += $vatRow;
                } else {
                    if($testRound == 'yes') {
                        $amountJournal += round($withoutVatAndDisc, 2);
                    } else {
                        $amountJournal += $withoutVatAndDisc;
                    }
                }
            }
            
            if (!($masterRec->type === 'dc_note' && ($rec->changedQuantity !== true && $rec->changedPrice !== true))) {
                if (!array_key_exists($vat, $vats)) {
                    $vats[$vat] = (object) array('amount' => 0, 'sum' => 0);
                }
                
                $vats[$vat]->amount += $vatRow;

                if($testRound == 'yes') {
                    $vats[$vat]->sum += round($withoutVatAndDisc, 2);
                } else {
                    $vats[$vat]->sum += $withoutVatAndDisc;
                }
            }
        }
        
        $mvc->_total = new stdClass();
        $mvc->_total->amount = round($amountRow, 2);
        $mvc->_total->vat = round($amountVat, 2);
        $mvc->_total->vats = $vats;
        
        if (!$map['alwaysHideVat']) {
            $mvc->_total->discount = round($amountRow, 2) - round($amountJournal, 2);
        } else {
            $mvc->_total->discount = round($discount, 2);
        }

        // "Просто" изчисляване на ДДС-то в документа, ако има само една ставка
        if (countR($vats) == 1 && ($masterRec->type == 'invoice' || $masterRec->type == 'dc_note')) {

            $vat = key($vats);
            $vats[$vat]->sum = round($vats[$vat]->sum, 2);
            $vats[$vat]->amount = round($vats[$vat]->sum * $vat, 2);
            //$mvc->_total->amount = $vats[$vat]->sum;
            $mvc->_total->vat = $vats[$vat]->amount;
            $mvc->_total->vats = $vats;
        }
    }
    
    
    /**
     * Подготвя данните за съмаризиране ценовата информация на един документ
     *
     * @param array     $values       - масив с стойности на сумата на всеки ред, ддс-то и отстъпката
     * @param datetime  $date         - дата
     * @param float     $currencyRate - курс
     * @param string(3) $currencyId   - код на валута
     * @param string    $chargeVat    - ддс режима
     * @param bool      $invoice      - дали документа е фактура
     *
     * @return stdClass $arr  - Масив с нужната информация за показване:
     *                  ->value           - Стойността
     *                  ->discountValue   - Отстъпката
     *                  ->neto 		      - Нето (Стойност - отстъпка) // Показва се ако има отстъпка
     *                  ->baseAmount      - Данъчната основа // само при фактура се показва
     *                  ->vat             - % ДДС // само при фактура или ако ддс-то се начислява отделно
     *                  ->vatAmount       - Стойност на ДДС-то // само при фактура или ако ддс-то се начислява отделно
     *                  ->total           - Крайната стойност
     *                  ->sayWords        - крайната сума изписана с думи
     *
     */
    public static function prepareSummary($values, $date, $currencyRate, $currencyId, $chargeVat, $invoice = false, $lang = 'bg')
    {
        // Стойностите на сумата на всеки ред, ддс-то и отстъпката са във валутата на документа
        $arr = array();
        
        $values = (array) $values;
        $arr['currencyId'] = $currencyId;                          // Валута на документа
        
        $baseCurrency = acc_Periods::getBaseCurrencyCode($date);   // Основната валута
        $arr['value'] = $values['amount']; 						   // Стойноста е сумираната от показваното на всеки ред
        
        if ($values['discount']) { 								// ако има отстъпка
            $arr['discountValue'] = $values['discount'];
            $arr['discountCurrencyId'] = $currencyId; 			// Валутата на отстъпката е тази на документа
            
            $arr['neto'] = $arr['value'] - round($arr['discountValue'], 2); 	// Стойността - отстъпката
            $arr['netoCurrencyId'] = $currencyId; 				// Валутата на нетото е тази на документа
        }
        
        
        // Ако има нето, крайната сума е тази на нетото, ако няма е тази на стойността
        $arr['total'] = (isset($arr['neto'])) ? $arr['neto'] : $arr['value'];
        
        $coreConf = core_Packs::getConfig('core');
        $pointSign = $coreConf->EF_NUMBER_DEC_POINT;
        
        if ($invoice || $chargeVat == 'separate') {
            if (is_array($values['vats'])) {
                foreach ($values['vats'] as $percent => $vi) {
                    if (is_object($vi)) {
                        $index = str_replace('.', '', $percent);
                        $arr["vat{$index}"] = $percent * 100 . '%';
                        $arr["vat{$index}Amount"] = $vi->amount * (($invoice) ? $currencyRate : 1);
                        $arr["vat{$index}AmountCurrencyId"] = ($invoice) ? $baseCurrency : $currencyId;
                        
                        if ($invoice) {
                            $arr["vat{$index}Base"] = $arr["vat{$index}"];
                            $arr["vat{$index}BaseAmount"] = $vi->sum * (($invoice) ? $currencyRate : 1);
                            $arr["vat{$index}BaseCurrencyId"] = ($invoice) ? $baseCurrency : $currencyId;
                        }
                    }
                }
            } else {
                $arr['vat02Amount'] = 0;
                $arr['vat02AmountCurrencyId'] = ($invoice) ? $baseCurrency : $currencyId;
            }
        }
        
        if ($invoice) { // ако е фактура
            //$arr['vatAmount'] = $values['vat'] * $currencyRate; // С-та на ддс-то в основна валута
            //$arr['vatCurrencyId'] = $baseCurrency; 				// Валутата на ддс-то е основната за периода
            $arr['baseAmount'] = $arr['total'] * $currencyRate; // Данъчната основа
            $arr['baseAmount'] = ($arr['baseAmount']) ? $arr['baseAmount'] : "<span class='quiet'>0" . $pointSign . '00</span>';
            $arr['baseCurrencyId'] = $baseCurrency; 			// Валутата на данъчната основа е тази на периода
        }   // ако не е фактура
            //$arr['vatAmount'] = $values['vat']; 		// ДДС-то
            //$arr['vatCurrencyId'] = $currencyId; 		// Валутата на ддс-то е тази на документа
        
        
        if (!$invoice && $chargeVat != 'separate') { 				 // ако документа не е фактура и не е с отделно ддс
            //unset($arr['vatAmount'], $arr['vatCurrencyId']); // не се показват данни за ддс-то
        } else { // ако е фактура или е сотделно ддс
            if ($arr['total']) {
                //$arr['vat'] = round(($values['vat'] / $arr['total']) * 100); // % ддс
                $arr['total'] = $arr['total'] + $values['vat']; 	  // Крайното е стойноста + ддс-то
            }
        }
        
        $SpellNumber = cls::get('core_SpellNumber');
        if ($arr['total'] != 0) {
            $arr['sayWords'] = $SpellNumber->asCurrency($arr['total'], $lang, false, $currencyId);
            $arr['sayWords'] = str::mbUcfirst($arr['sayWords']);
        }
        
        $arr['value'] = ($arr['value']) ? $arr['value'] : "<span class='quiet'>0" . $pointSign . '00</span>';
        $arr['total'] = ($arr['total']) ? $arr['total'] : "<span class='quiet'>0" . $pointSign . '00</span>';
        
        if (!$arr['vatAmount'] && ($invoice || $chargeVat == 'separate')) {
            //$arr['vatAmount'] = "<span class='quiet'>0" . $pointSign . "00</span>";
        }
        
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        foreach ($arr as $index => $el) {
            if (is_numeric($el)) {
                $arr[$index] = $Double->toVerbal($el);
            }
        }
        
        // Дефолтни стойности ако няма записи
        if ($invoice && empty($values)) {
            $arr['vat02BaseAmount'] = '0.00';
            $arr['vat02BaseCurrencyId'] = $baseCurrency;
        }

        return (object) $arr;
    }
    
    
    /**
     * Помощна ф-я обръщаща цена от от основна валута без ддс до валута
     *
     * @param float  $price     - цена във валута
     * @param float  $vat       - ддс
     * @param float  $rate      - валутен курс
     * @param string $chargeVat - как се начислява ДДС-то
     * @param int    $round     - до колко знака да се закръгли
     *
     * @return float $price - цената във валутата
     */
    public static function getDisplayPrice($price, $vat, $rate, $chargeVat, $round = null)
    {
        // Ако няма цена, но има такъв запис се взима цената от него
        if ($chargeVat == 'yes') {
              
              // Начисляване на ДДС в/у цената
            $price *= 1 + $vat;
        }
        
        expect($rate, 'Не е подаден валутен курс');
        
        // Обръщаме във валутата, чийто курс е подаден
        if ($rate != 1) {
            $price /= $rate;
        }
        
        // Закръгляме при нужда
        if ($round) {
            $price = round($price, $round);
        } else {
            
            // Ако не е посочено закръгляне, правим машинно закръгляне
            $price = deals_Helper::roundPrice($price);
        }
        
        // Връщаме обработената цена
        return $price;
    }
    
    
    /**
     * Помощна ф-я обръщаща цена от от сума във валута в основната валута
     * това е обратната ф-я на `deals_Helper::getDisplayPrice`
     *
     * @param float  $price     - цена във валута
     * @param float  $vat       - ддс
     * @param float  $rate      - валутен курс
     * @param string $chargeVat - как се начислява ддс-то
     *
     * @return float $price - цената в основна валута без ддс
     */
    public static function getPurePrice($price, $vat, $rate, $chargeVat)
    {
        // Ако няма цена, но има такъв запис се взима цената от него
        if ($chargeVat == 'yes') {
             
             // Премахваме ДДС-то при нужда
            $price /= 1 + $vat;
        }
        
        // Обръщаме в основната валута
        $price *= $rate;
        
        // Връщаме обработената цена
        return $price;
    }
    
    
    /**
     * Връща обект с информацията за наличното в склада к-во
     *
     * @return stdClass $obj
     *                  ->formInfo - информация за формата
     *                  ->warning - предупреждението
     */
    public static function checkProductQuantityInStore($productId, $packagingId, $packQuantity, $storeId, $date, &$foundQuantity = null)
    {
        if (empty($packQuantity)) {
            $packQuantity = 1;
        }

        $stRec = store_Products::getQuantities($productId, $storeId, $date);
        $quantity = $stRec->free;
        $quantityInStock = $stRec->quantity;
        $Double = core_Type::getByName('double(smartRound)');

        $pInfo = cat_Products::getProductInfo($productId);
        $shortUom = cat_UoM::getShortName($pInfo->productRec->measureId);
        $storeName = isset($storeId) ? (" |в|* " . store_Stores::getTitleById($storeId)) : '';
        $verbalQuantity = $Double->toVerbal($quantity);
        $verbalQuantityInStock = $Double->toVerbal($quantityInStock);
        $foundQuantity = $quantity;

        $exRec = store_Products::fetch("#storeId = '{$storeId}' AND #productId = {$productId}");
        $minQuantityDate = is_object($exRec) ? $exRec->dateMin : null;
        $freeQuantityMin = is_object($exRec) ? ($exRec->quantity - $exRec->reservedQuantityMin + $exRec->expectedQuantityMin) : null;

        $date = (!empty($date)) ? $date : dt::today();
        if(isset($minQuantityDate) && $date <= $minQuantityDate){
            $displayDate = dt::verbal2mysql($minQuantityDate);
            $displayText = "Минимално разполагаемо към|*";
            $verbalQuantity = $Double->toVerbal($freeQuantityMin);

        } else {
            $displayDate = $date;
            $displayText = "Разполагаемо към|*";
        }

        if(!empty($displayDate)){
            if(strpos($displayDate, ' 00:00:00') !== false){
                $displayDate = dt::mysql2verbal($displayDate, 'd.m.Y');
            } else {
                $displayDate = dt::mysql2verbal($displayDate, 'd.m.Y H:i');
            }
        }

        $verbalQuantity = ht::styleNumber($verbalQuantity, $quantity);
        $text = "|Налично|* <b>{$storeName}</b> : {$verbalQuantityInStock} {$shortUom}<br> {$displayText} <b class='small'>{$displayDate}</b>: {$verbalQuantity} {$shortUom}";
        if (!empty($stRec->reserved)) {
            $verbalReserved = $Double->toVerbal($stRec->reserved);
            $text .= ' ' . "|*( |Запазено|* {$verbalReserved} {$shortUom} )";
        }
        
        $info = tr($text);
        $obj = (object) array('formInfo' => $info);
        $quantityInPack = ($pInfo->packagings[$packagingId]) ? $pInfo->packagings[$packagingId]->quantity : 1;
        
        // Показваме предупреждение ако наличното в склада е по-голямо от експедираното
        if ($packQuantity > ($quantity / $quantityInPack)) {
            $obj->warning = "Въведеното количество е по-голямо от разполагаемо|* <b>{$verbalQuantity}</b> |в склада|*";
        }


        return $obj;
    }
    
    
    /**
     * Добавя забележки към описанието на артикул
     */
    public static function addNotesToProductRow(&$productRow, $notes)
    {
        if (!$notes) {
            return;
        }
        
        $RichText = cls::get('type_Richtext');
        $notes = $RichText->toVerbal($notes);
        if (is_string($productRow)) {
            $productRow .= "<div class='small'>{$notes}</div>";
        } else {
            $productRow->append(new core_ET("<div class='small'>[#NOTES#]</div>"));
            $productRow->replace($notes, 'NOTES');
        }
    }
    
    
    /**
     * Помощна функция за показване на пдоробната информация за опаковката при нужда
     *
     * @param string $packagingRow
     * @param int    $productId
     * @param int    $packagingId
     * @param float  $quantityInPack
     *
     * @return void
     */
    public static function getPackInfo(&$packagingRow, $productId, $packagingId, $quantityInPack = null)
    {
        if ($packRec = cat_products_Packagings::getPack($productId, $packagingId)) {
            if (cat_UoM::fetchField($packagingId, 'showContents') == 'yes') {
                $quantityInPack = isset($quantityInPack) ? $quantityInPack : $packRec->quantity;
                $measureId = cat_Products::fetchField($productId, 'measureId');
                $packagingRow .= ' ' . self::getPackMeasure($measureId, $quantityInPack, $packRec);
            }
        }
    }
    
    
    /**
     * Връща описание на опаковка, заедно с количеството в нея
     */
    public static function getPackMeasure($measureId, $quantityInPack, $packRec = null)
    {
        $qP = $quantityInPack;
        $quantityInPack = cat_UoM::round($measureId, $quantityInPack);
        
        $hint = false;
        
        if (is_object($packRec)) {
            $originalQuantityInPack = $packRec->quantity;
            $difference = round(abs($qP - $originalQuantityInPack) / $originalQuantityInPack, 2);
            if ($difference > 0.1) {
                $hint = true;
            }
        }
        
        $quantityInPack = ($quantityInPack == 1) ? '' : core_Type::getByName('double(smartRound)')->toVerbal($quantityInPack) . ' ';
        if ($hint === true) {
            $quantityInPack = ht::createHint($quantityInPack, 'Има отклонение спрямо очакваното', 'warning', true, 'width=12px,height=12px');
        }
        
        $tpl = new core_ET("<span class='nowrap'>&nbsp;<small class='quiet'>[#quantityInPack#] [#shortUomName#]</small></span>");
        $tpl->append(cat_UoM::getShortName($measureId), 'shortUomName');
        $tpl->append($quantityInPack, 'quantityInPack');
        $tpl->removeBlocks();
        
        return $tpl;
    }
    
    
    /**
     * Извлича масив с използваните артикули-документи в бизнес документа
     *
     * @param core_Mvc $mvc        - клас на документа
     * @param int      $id         - ид на документа
     * @param string   $productFld - името на полето в което е ид-то на артикула
     *
     * @return array
     */
    public static function getUsedDocs(core_Mvc $mvc, $id, $productFld = 'productId')
    {
        $res = array();
        
        $Detail = cls::get($mvc->mainDetail);
        $dQuery = $Detail->getQuery();
        $dQuery->EXT('state', $mvc->className, "externalKey={$Detail->masterKey}");
        $dQuery->where("#{$Detail->masterKey} = '{$id}'");
        $dQuery->groupBy($productFld);
        while ($dRec = $dQuery->fetch()) {
            $cid = cat_Products::fetchField($dRec->{$productFld}, 'containerId');
            $res[$cid] = $cid;
        }
        
        return $res;
    }
    
    
    /**
     * Проверява имали такъв запис
     *
     * @param core_Detail $mvc
     * @param int         $masterId
     * @param int         $id
     * @param int         $productId
     * @param int         $packagingId
     * @param float       $price
     * @param NULL|float  $discount
     * @param NULL|float  $tolerance
     * @param NULL|int    $term
     * @param NULL|string $batch
     *
     * @return FALSE|stdClass
     */
    public static function fetchExistingDetail(core_Detail $mvc, $masterId, $id, $productId, $packagingId, $price, $discount, $tolerance = null, $term = null, $batch = null, $expenseItemId = null, $notes = null, $quantity = null)
    {
        $cond = "#{$mvc->masterKey} = ${masterId}";
        $vars = array('productId' => $productId, 'packagingId' => $packagingId, 'price' => $price, 'discount' => $discount);
        
        if ($mvc->getField('tolerance', false)) {
            $vars['tolerance'] = $tolerance;
        }
        if ($mvc->getField('term', false)) {
            $vars['term'] = $term;
        }
        
        if ($mvc->getField('batch', false)) {
            $vars['batch'] = $batch;
        }
        
        foreach ($vars as $key => $var) {
            if (isset($var)) {
                $cond .= " AND #{$key} = '{$var}'";
            } else {
                $cond .= " AND #{$key} IS NULL";
            }
        }
        
        if ($id) {
            $cond .= " AND #id != {$id}";
        }
        
        if ($mvc->getField('expenseItemId', false)) {
            if (isset($expenseItemId)) {
                $cond .= " AND #expenseItemId = {$expenseItemId}";
            } else {
                $cond .= ' AND #expenseItemId IS NULL';
            }
        }
        
        if (isset($quantity)) {
            $cond .= " AND #quantity = '{$quantity}'";
        }
        
        // Ако има забележки
        if (!empty($notes)) {
            
            // Сравняване на хеша на забележките с този на новата забележка
            $query = $mvc->getQuery();
            $query->XPR('hashNotes', 'double', 'MD5(#notes)');
            $notes = md5(gzcompress($notes));
            $cond .= " AND #hashNotes = '{$notes}'";
            $query->where($cond);
            
            return $query->fetch();
        }
        $cond .= " AND (#notes = '' OR #notes IS NULL)";
        
        return $mvc->fetch($cond);
    }
    
    
    /**
     * Сумиране на записи от бизнес документи по артикули
     *
     * @param $arrays - масив от масиви със детайли на бизнес документи
     *
     * @return array
     */
    public static function normalizeProducts($arrays, $subtractArrs = array())
    {
        $combined = array();
        
        foreach (array('arrays', 'subtractArrs') as $parameter) {
            $var = ${$parameter};
            
            if (is_array($var)) {
                foreach ($var as $arr) {
                    if (is_array($arr)) {
                        foreach ($arr as $p) {
                            $index = $p->productId;
                            
                            if (!empty($p->notes)) {
                                $index .= '|' . serialize($p->notes) . '|';
                            }
                            
                            if (!isset($combined[$index])) {
                                $combined[$index] = new stdClass();
                                $combined[$index]->productId = $p->productId;
                                
                                if (!empty($p->notes)) {
                                    $combined[$index]->notes = $p->notes;
                                }
                            }
                            
                            $d = &$combined[$index];
                            if ($p->discount != 1) {
                                $d->discount = max($d->discount, $p->discount);
                            }
                            
                            if (isset($p->fee) && $p->fee > 0) {
                                $d->fee += $p->fee;
                            }
                            
                            if (isset($p->deliveryTimeFromFee)) {
                                $d->deliveryTimeFromFee = min($d->deliveryTimeFromFee, $p->deliveryTimeFromFee);
                            }
                            
                            if ($p->syncFee === true) {
                                $d->syncFee = true;
                            }
                            
                            $sign = ($parameter == 'arrays') ? 1 : -1;
                            
                            //@TODO да може да е -
                            $d->quantity += $sign * $p->quantity;
                            $d->sumAmounts += $sign * ($p->quantity * $p->price * (1 - $p->discount));
                            
                            if (empty($d->packagingId)) {
                                $d->packagingId = $p->packagingId;
                                $d->quantityInPack = $p->quantityInPack;
                            } else {
                                if ($p->quantityInPack < $d->quantityInPack) {
                                    $d->packagingId = $p->packagingId;
                                    $d->quantityInPack = $p->quantityInPack;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if (countR($combined)) {
            foreach ($combined as &$det) {
                $delimiter = ($det->quantity * (1 - $det->discount));
                if (!empty($delimiter)) {
                    $det->price = $det->sumAmounts / $delimiter;
                    
                    if ($det->price < 0) {
                        $det->price = 0;
                    }
                } else {
                    $det->price = 0;
                }
            }
        }
        
        return $combined;
    }
    
    
    /**
     * Връща хинт с количеството в склада
     *
     * @param mixed $html
     * @param core_Mvc $mvc
     * @param int   $productId
     * @param int   $storeId
     * @param float $quantity
     * @param string $state
     * @param datetime $date
     * @param int $ignoreFirstDocumentPlannedInThread
     *
     * @return void
     */
    public static function getQuantityHint(&$html, $mvc, $productId, $storeId, $quantity, $state, $date = null, $ignoreFirstDocumentPlannedInThread = null)
    {
        if (!in_array($state, array('draft', 'pending'))) {
            return;
        }

        $canStore = cat_Products::fetchField($productId, 'canStore');
        if ($canStore != 'yes') {
            return;
        }

        $date = isset($date) ? $date : null;
        $showStoreInMsg = isset($storeId) ? tr('в склада') : '';
        $stRec = store_Products::getQuantities($productId, $storeId, $date);

        $exRec = store_Products::fetch("#storeId = '{$storeId}' AND #productId = {$productId}");
        $minQuantityDate = is_object($exRec) ? $exRec->dateMin : null;

        // Ако има посочена нишка, чийто първи документ да се игнорира от хоризонтите,
        if(isset($ignoreFirstDocumentPlannedInThread)){
            if($firstDocument = doc_Threads::getFirstDocument($ignoreFirstDocumentPlannedInThread)){
                $skip = false;
                if($firstDocument->isInstanceOf('deals_DealMaster')){
                    $firstDocumentStoreId = $firstDocument->fetchField('shipmentStoreId');
                    if(empty($firstDocumentStoreId)){
                        $skip = true;
                    }
                }

                if($skip != true){
                    $iQuery = store_StockPlanning::getQuery();
                    $iQuery->where("#productId = {$productId} AND #sourceClassId = {$firstDocument->getInstance()->getClassId()} AND #sourceId = {$firstDocument->that}");
                    $iQuery->show('quantityIn,quantityOut');
                    $iRec = $iQuery->fetch();

                    // Ако първия документ в нишката е запазил, игнорират се запазените к-ва от него за документите в същия тред
                    if(is_object($iRec)){
                        if(is_object($stRec)){
                            $stRec->reserved -= $iRec->quantityOut;
                            $stRec->reserved = abs($stRec->reserved);
                            $stRec->expected -= $iRec->quantityIn;
                            $stRec->expected = abs($stRec->expected);
                            $stRec->free = $stRec->quantity - $stRec->reserved + $stRec->expected;
                        }
                    }
                }
            }
        }

        $freeQuantityOriginal = $stRec->free;
        $Double = core_Type::getByName('double(smartRound)');
        $freeQuantity = ($state == 'draft') ? $freeQuantityOriginal - $quantity : $freeQuantityOriginal;
        $freeQuantityMin = is_object($exRec) ? ($exRec->quantity - $exRec->reservedQuantityMin + $exRec->expectedQuantityMin) : null;

        $futureQuantity = $stRec->quantity - $quantity;
        $measureName = cat_UoM::getShortName(cat_Products::fetchField($productId, 'measureId'));
        $inStockVerbal = $Double->toVerbal($stRec->quantity);
        $class = 'doc-warning-quantity';
        $showNegativeWarning = $makeLink = true;

        if($mvc instanceof sales_SalesDetails){
            $showNegativeWarning = cat_Products::fetchField($productId, 'isPublic') == 'yes';
        }

        // Проверка дали има минимално разполагаемо
        $firstCheck = false;
        if(isset($minQuantityDate) && $date <= $minQuantityDate){
            if(($state == 'pending' && $freeQuantityMin < 0) || (($mvc instanceof sales_SalesDetails) && $state == 'draft' && $quantity > $freeQuantityMin)){
                if($showNegativeWarning){
                    if(isset($date) && $date != dt::today()){
                        $minDateVerbal = dt::mysql2verbal($minQuantityDate, 'd.m.Y');
                        $freeQuantityMinVerbal = core_Type::getByName('double(smartRound)')->toVerbal($freeQuantityMin);
                        $hint = "Разполагаемо минимално налично към|* {$minDateVerbal}: {$freeQuantityMinVerbal} |{$measureName}|*";
                    } else {
                        $hint = "Недостатъчна наличност|*: {$inStockVerbal} |{$measureName}|*. |Контирането на документа ще доведе до отрицателна наличност|* |{$showStoreInMsg}|*!";
                    }
                }

                $firstCheck = true;
            }
        }

        if(!$firstCheck){
            if ($futureQuantity < 0 && $freeQuantity < 0) {
                if($showNegativeWarning){
                    $hint = "Недостатъчна наличност|*: {$inStockVerbal} |{$measureName}|*. |Контирането на документа ще доведе до отрицателна наличност|* |{$showStoreInMsg}|*!";
                    $class = 'doc-negative-quantity';
                    $makeLink = false;
                }
            } elseif ($futureQuantity < 0 && $freeQuantity >= 0) {
                if($showNegativeWarning) {
                    $freeQuantityOriginalVerbal = $Double->toVerbal($freeQuantityOriginal);
                    $hint = "Недостатъчна наличност|*: {$inStockVerbal} |{$measureName}|*. |Контирането на документа ще доведе до отрицателна наличност|* |{$showStoreInMsg}|*! |Очаква се доставка - разполагаема наличност|*: {$freeQuantityOriginalVerbal} |{$measureName}|*";
                }
            } elseif ($futureQuantity >= 0 && $freeQuantity < 0) {
                if($showNegativeWarning) {
                    $freeQuantityOriginalVerbal = $Double->toVerbal($freeQuantityOriginal);
                    $hint = "Разполагаема наличност|*: {$freeQuantityOriginalVerbal} |{$measureName}|* |Наличното количество|*: {$inStockVerbal} |{$measureName}|* |е резервирано|*.";
                }
            }
        }
        
        if (!empty($hint)) {
            $html = ht::createHint($html, $hint, 'warning', false, null, "class={$class}");

            //  Показване на хоризонта при нужда
            $url = array('store_Products', 'list', 'storeId' => $storeId, 'productId' => $productId);
            if(isset($date)){
                $diff = dt::secsBetween(dt::verbal2mysql($date, false), dt::today());
                $url['horizon'] = $diff;
            }

            // Линк към наличното в склада ако има права
            if ($makeLink === true && store_Stores::haveRightFor('select', $storeId) && store_Products::haveRightFor('list') && !Mode::isReadOnly()) {
                $html = ht::createLinkRef($html, $url);
            }
        }
    }
    
    
    /**
     * Помощна ф-я обръщащи намерените к-ва и суми върнати от acc_Balances::getBlQuantities
     *  от една валута в друга подадена
     *
     * @see acc_Balances::getBlQuantities
     *
     * @param array    $array        - масив от обекти с ключ ид на перо на валута и полета amount и quantity
     * @param string   $currencyCode - към коя валута да се конвертират
     * @param DateTime $date         - дата
     *
     * @return array $res
     *               ->quantity - Количество във подадената валута
     *               ->amount   - Сума в основната валута
     */
    public static function convertJournalCurrencies($array, $currencyCode, $date)
    {
        $res = (object) array('quantity' => 0, 'amount' => 0);
        
        // Ако е масив
        if (is_array($array) && !empty($array)) {
            $currencyItemId = $currencyItemId = acc_Items::fetchItem('currency_Currencies', currency_Currencies::getIdByCode($currencyCode))->id;
            $currencyListId = acc_Lists::fetchBySystemId('currencies')->id;
            
            // За всеки обект от него
            foreach ($array as $itemId => $obj) {
                
                // Подсигуряваме се че ключа е перо от номенклатура валута
                $itemRec = acc_Items::fetch($itemId);
                $cCode = currency_Currencies::getCodeById($itemRec->objectId);
                expect(keylist::isIn($currencyListId, $itemRec->lists));
                
                // Ако ключа е търсената валута просто събираме
                if ($currencyItemId == $itemId) {
                    $quantity = $obj->quantity;
                } else {
                    if ($obj->amount) {
                        
                        // Ако има сума обръщаме сумата в количеството на основната валута чрез основния курс
                        $rate = currency_CurrencyRates::getRate($date, $currencyCode, null);
                        $quantity = $obj->amount / $rate;
                    } else {
                        // Ако не е конвертираме количеството във търсената валута
                        $quantity = currency_CurrencyRates::convertAmount($obj->quantity, $date, $cCode, $currencyCode);
                    }
                }
                
                // Ако няма сума я изчисляваме възоснова на основния курс
                if ($obj->amount) {
                    $amount = $obj->amount;
                } else {
                    $rate = currency_CurrencyRates::getRate($date, $cCode, null);
                    $amount = $rate * $quantity;
                }
                
                // Сумираме к-та и сумите към търсената валута
                $res->quantity += $quantity;
                $res->amount += $amount;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Помощен метод връщащ дали не може да бъде избран документ от посочения вид
     * използва се за проверка дали при контиране/възстановяване/оттегляне дали потребителя
     * може да избере посочения обект: каса/б. сметка/склад
     *
     * @param string   $action        - действие с документа
     * @param stdClass $rec           - запис на документа
     * @param string   $ObjectManager - мениджър на обекта, който ще проверяваме можели да се избере
     * @param string   $objectIdField - поле на ид-то на обекта, който ще проверяваме можели да се избере
     *
     * @return bool - можели да се избере обекта или не
     */
    public static function canSelectObjectInDocument($action, $rec, $ObjectManager, $objectIdField)
    {
        // Ако действието е контиране/възстановяване/оттегляне
        if (($action == 'conto' || $action == 'restore' || $action == 'reject') && isset($rec)) {
            
            // Ако документа е чернова не проверяваме дали потребителя може да избере обекта
            if ($action == 'reject' && $rec->state == 'draft') {
                return true;
            }
            
            // Ако документа е бил чернова не проверяваме дали потребителя може да избере обекта
            if ($action == 'restore' && $rec->brState == 'draft') {
                return true;
            }
            
            // Ако има избран обект и потребителя не може да го избере връщаме FALSE
            if (isset($rec->{$objectIdField}) && !bgerp_plg_FLB::canUse($ObjectManager, $rec->{$objectIdField})) {
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Помощна ф-я връщаща подходящо представяне на клиентсктие данни и тези на моята фирма
     * в бизнес документите
     *
     * @param mixed $contragentClass - клас на контрагента
     * @param int   $contragentId    - ид на контрагента
     * @param int   $contragentName  - името на контрагента, ако е предварително известно
     *
     * @return array $res
     *               ['MyCompany']         - Името на моята фирма
     *               ['MyAddress']         - Адреса на моята фирма
     *               ['MyCompanyVatNo']    - ДДС номера на моята фирма
     *               ['uicId']             - Националния номер на моята фирма
     *               ['contragentName']    - Името на контрагента
     *               ['contragentAddress'] - Адреса на контрагента
     *               ['vatNo']             - ДДС номера на контрагента
     */
    public static function getDocumentHeaderInfo($contragentClass, $contragentId, $contragentName = null)
    {
        $res = array();
       
        // Данните на 'Моята фирма'
        $ownCompanyData = crm_Companies::fetchOwnCompany();

        // Името и адреса на 'Моята фирма'
        $Companies = cls::get('crm_Companies');
        $res['MyCompany'] = $ownCompanyData->companyVerb;
        
        // ДДС и националния номер на 'Моята фирма'
        $uic = isset($ownCompanyData->uicId) ? $ownCompanyData->uicId : drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
        if ($uic != $ownCompanyData->vatNo) {
            $res['MyCompanyVatNo'] = core_Type::getByName('drdata_VatType')->toVerbal($ownCompanyData->vatNo);
        }
        $res['MyCompanyEori'] = core_Type::getByName('drdata_type_Eori')->toVerbal($ownCompanyData->eori);
        $res['uicId'] = $uic;
        
        // името, адреса и ДДС номера на контрагента
        if (isset($contragentClass, $contragentId)) {
            $ContragentClass = cls::get($contragentClass);
            $cData = $ContragentClass->getContragentData($contragentId);
            $cName = ($cData->personVerb) ? $cData->personVerb : $cData->companyVerb;
            $res['contragentName'] = isset($contragentName) ? $contragentName : $cName;
            if($res['contragentName'] != $cName){
                if(!Mode::isReadOnly()){
                    $res['contragentName'] = ht::createHint($res['contragentName'], 'Името на контрагента е променено в документа|*!', 'warning');
                }
            }
            $res['inlineContragentName'] = $res['contragentName'];

            $res['eori'] = core_Type::getByName('drdata_type_Eori')->toVerbal($cData->eori);
            $res['vatNo'] = core_Type::getByName('drdata_VatType')->toVerbal($cData->vatNo);
            $res['contragentUicId'] = $cData->uicId;
            if (!empty($cData->uicId)) {
                $res['contragentUicCaption'] = ($ContragentClass instanceof crm_Companies) ? tr('ЕИК') : tr('ЕГН||Personal №');
            }
        } elseif (isset($contragentName)) {
            $res['contragentName'] = $contragentName;
        }
        
        $makeLink = (!Mode::is('pdf') && !Mode::is('text', 'xhtml') && !Mode::is('text', 'plain'));
        
        // Имената на 'Моята фирма' и контрагента са линкове към тях, ако потребителя има права
        if ($makeLink === true) {
            $res['MyCompany'] = ht::createLink($res['MyCompany'], crm_Companies::getSingleUrlArray($ownCompanyData->companyId));
            $res['MyCompany'] = $res['MyCompany']->getContent();
            
            if (isset($contragentClass, $contragentId)) {
                $res['contragentName'] = ht::createLink($res['contragentName'], $ContragentClass::getSingleUrlArray($contragentId));
                $res['contragentName'] = $res['contragentName']->getContent();
            }
        }
        
        $showCountries = ($ownCompanyData->countryId == $cData->countryId) ? false : true;
        
        if (isset($contragentClass, $contragentId)) {
            $res['contragentAddress'] = $ContragentClass->getFullAdress($contragentId, false, $showCountries)->getContent();
            $res['inlineContragentAddress'] = $ContragentClass->getFullAdress($contragentId, false, $showCountries)->getContent();
            $res['inlineContragentAddress'] = str_replace('<br>', ',', $res['inlineContragentAddress']);
        }
        
        $res['MyAddress'] = $Companies->getFullAdress($ownCompanyData->companyId, true, $showCountries)->getContent();

        if(drdata_Countries::isEu($cData->countryId) && empty($cData->eori)){
            unset($res['MyCompanyEori']);
        }

        return $res;
    }
    
    
    /**
     * Помощна ф-я проверяваща дали подаденото к-во може да се зададе за опаковката
     *
     * @param int    $packagingId  - ид на мярка/опаковка
     * @param float  $packQuantity - к-во опаковка
     * @param string $warning      - предупреждение, ако има
     * @param string $type         - само за опаковки или мерки, или null за всички
     *
     * @return bool - дали к-то е допустимо или не
     */
    public static function checkQuantity($packagingId, $packQuantity, &$warning = null, $type = null)
    {
        $decLenght = strlen(substr(strrchr($packQuantity, '.'), 1));
        $uomRec = cat_UoM::fetch($packagingId, 'round,type');
        
        // Ако е указано да се проверява само за опаковка или мярка, и записа не е такъв, не се прави проверка
        if (isset($type) && $uomRec->type != $type) {
            return true;
        }
        
        if (isset($uomRec->round) && $decLenght > $uomRec->round) {
            if ($uomRec->round == 0) {
                $warning = 'Количеството трябва да е цяло число';
            } else {
                $round = cls::get('type_Int')->toVerbal($uomRec->round);
                $warning = "Количеството трябва да е с точност до|* <b>{$round}</b> |цифри след десетичния знак|*";
            }
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Помощна ф-я проверяваща дали цената не е много малка
     *
     * @param float|NULL  $price     - цена
     * @param float       $quantity  - количество
     * @param bool        $autoPrice - дали е автоматично изчислена
     * @param string|NULL $msg       - съобщение за грешка ако има
     *
     * @return bool - дали цената е под допустимото
     */
    public static function isPriceAllowed($price, $quantity, $autoPrice = false, &$msg = null)
    {
        if (!$price) {
            return true;
        }
        if ($quantity == 0) {
            return true;
        }
        
        $amount = $price * $quantity;
        
        $round = round($amount, 2);
        $res = ((double) $round >= 0.01);
        
        if ($res === false) {
            if ($autoPrice === true) {
                $msg = 'Сумата на реда не може да бъде под|* <b>0.01</b>! |Моля увеличете количеството, защото цената по политика е много ниска|*';
            } else {
                $msg = 'Сумата на реда не може да бъде под|* <b>0.01</b>! |Моля променете количеството и/или цената|*';
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща динамично изчисления толеранс
     *
     * @param int   $tolerance
     * @param int   $productId
     * @param float $quantity
     *
     * @return mixed
     */
    public static function getToleranceRow($tolerance, $productId, $quantity)
    {
        $hint = false;
        
        if (!isset($tolerance)) {
            $tolerance = cat_Products::getTolerance($productId, $quantity);
            if ($tolerance) {
                $hint = true;
            }
        }
        
        if (isset($tolerance)) {
            $toleranceRow = core_Type::getByName('percent(smartRound)')->toVerbal($tolerance);
            if ($hint === true) {
                $toleranceRow = ht::createHint($toleranceRow, 'Толерансът е изчислен автоматично на база количеството и параметрите на артикула');
            }
            
            return $toleranceRow;
        }
    }
    
    
    /**
     * Проверка дали к-то е под МКП-то на артикула
     *
     * @param core_Form $form
     * @param int       $productId
     * @param float     $quantity
     * @param float     $quantityInPack
     * @param string    $quantityField
     * @param string    $action
     *
     * @return void
     */
    public static function isQuantityBellowMoq(&$form, $productId, $quantity, $quantityInPack, $quantityField = 'packQuantity', $action = 'sell')
    {
        $moq = $form->rec->_moq;
        
        if (!$moq) {
            $moq = cat_Products::getMoq($productId, $action);
        }
        
        if (isset($moq, $quantity) && $quantity < $moq) {
            $moq /= $quantityInPack;
            $verbal = core_Type::getByName('double(smartRound)')->toVerbal($moq);
            if (haveRole('powerUser')) {
                $form->setWarning($quantityField, "Минималното количество за поръчка в избраната мярка/опаковка e|*: <b>{$verbal}</b>");
            } else {
                $form->setError($quantityField, "Минималното количество за поръчка в избраната мярка/опаковка e|*: <b>{$verbal}</b>");
            }
        }
    }
    
    
    /**
     * Помощна ф-я за показване на всички условия идващи от артикулите на един детайл
     *
     * @param core_Detail $Detail
     * @param int         $masterId
     * @param core_Master $Master
     * @param string|NULL $lg
     *
     * @return array $res
     */
    public static function getConditionsFromProducts($Detail, $Master, $masterId, $lg)
    {
        $res = array();
        
        // Намиране на детайлите
        $Detail = cls::get($Detail);
        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = {$masterId}");
        $dQuery->show('productId,quantity');
        $type = ($Master instanceof purchase_Purchases) ? 'purchase' : (($Master instanceof deals_QuotationMaster) ? 'quotation' : 'sale');
        $allProducts = $productConditions = array();
        
        if (!empty($lg)) {
            core_Lg::push($lg);
        }
        
        while ($dRec = $dQuery->fetch()) {
            
            // Опит за намиране на условията
            $conditions = cat_Products::getConditions($dRec->productId, $type, $lg);
            $allProducts[$dRec->productId] = $dRec->productId;
            
            if (is_array($conditions)) {
                foreach ($conditions as $t) {
                    
                    // Нормализиране на условието
                    $key = md5(strtolower(str::utf2ascii(trim($t))));
                    $value = preg_replace('!\s+!', ' ', str::mbUcfirst($t));
                    $res[$key] = $value;
                    
                    $productConditions[$key] = is_array($productConditions[$key]) ? $productConditions[$key] : array();
                    
                    // Запомня се кои артикули подават същото условие
                    if (!array_key_exists($dRec->productId, $productConditions[$key])) {
                        $code = cat_Products::fetchField($dRec->productId, 'code');
                        $code = (!empty($code)) ? $code : "Art{$dRec->productId}";
                        $productConditions[$key][$dRec->productId] = $code;
                    }
                }
            }
        }
        
        foreach ($res as $key => &$val) {
            if (is_array($productConditions[$key]) && countR($productConditions[$key]) != countR($allProducts)) {
                $valSuffix = new core_ET(tr('За|* [#Articles#]'));
                $valSuffix->replace(implode(',', $productConditions[$key]), 'Articles');
                $valSuffix = ' <i>(' . $valSuffix->getContent() . ')</i>';
                
                $bold = false;
                foreach (array('strong', 'b') as $tag) {
                    if (preg_match("/<{$tag}>(.*)<\/{$tag}>/", $val, $m)) {
                        $bold = $tag;
                        break;
                    }
                }
                
                if ($bold !== false) {
                    $valSuffix = "<{$bold}>{$valSuffix}</{$bold}>";
                }
                $val .= $valSuffix;
            }
        }
        
        if (!empty($lg)) {
            core_Lg::pop();
        }
        
        return $res;
    }
    
    
    /**
     * Помощна ф-я връщаща дефолтното количество за артикула в бизнес документ
     *
     * @param int $productId
     * @param int $packagingId
     *
     * @return float|NULL $defQuantity
     */
    public static function getDefaultPackQuantity($productId, $packagingId)
    {
        $defQuantity = cat_Products::getMoq($productId);
        $defQuantity = !empty($defQuantity) ? $defQuantity : cat_UoM::fetchField($packagingId, 'defQuantity');
        
        return ($defQuantity) ? $defQuantity : null;
    }
    
    
    /**
     * Помощна ф-я за рекалкулиране на курса на бизнес документ
     *
     * @param mixed  $masterMvc
     * @param int    $masterId
     * @param float  $newRate
     * @param string $priceFld
     * @param string $rateFld
     */
    public static function recalcRate($masterMvc, $masterId, $newRate, $priceFld = 'price', $rateFld = 'currencyRate')
    {
        $rec = $masterMvc->fetchRec($masterId);

        if ($masterMvc instanceof deals_InvoiceMaster) {
            $rateFld = 'rate';
        }

        $updateMaster = false;
        if ($masterMvc instanceof acc_ValueCorrections) {
            $rec->amount = round(($rec->amount / $rec->rate) * $newRate, 6);
            foreach ($rec->productsData as &$pData){
                $pData->allocated = round(($pData->allocated / $rec->rate) * $newRate, 6);
            }
            $rec->rate = $newRate;
        } elseif($masterMvc instanceof deals_PaymentDocument) {
            if(round($rec->amountDeal,2) == round($rec->amount, 2)) {
                $rec->rate = $newRate;
            }
        } elseif(isset($masterMvc->mainDetail)) {
            $Detail = cls::get($masterMvc->mainDetail);
            $dQuery = $Detail->getQuery();
            $dQuery->where("#{$Detail->masterKey} = {$rec->id}");
            while ($dRec = $dQuery->fetch()) {
                $dRec->{$priceFld} = ($dRec->{$priceFld} / $rec->{$rateFld}) * $newRate;

                if ($masterMvc instanceof deals_InvoiceMaster) {
                    $dRec->packPrice = $dRec->{$priceFld} * $dRec->quantityInPack;
                    $dRec->amount = $dRec->packPrice * $dRec->quantity;
                }

                $Detail->save($dRec);
            }

            $updateMaster = true;
            $rec->{$rateFld} = $newRate;
            if ($masterMvc instanceof deals_InvoiceMaster) {
                $rec->displayRate = $newRate;

                if ($rec->dpOperation == 'accrued' || isset($rec->changeAmount)) {
                    // Изчисляване на стойността на ддс-то
                    $vat = acc_Periods::fetchByDate()->vatRate;
                    if ($rec->vatRate != 'yes' && $rec->vatRate != 'separate') {
                        $vat = 0;
                    }

                    $diff = $rec->changeAmount * $newRate;
                    $rec->vatAmount = $diff * $vat;

                    // Стойността е променената сума
                    $rec->dealValue = $diff;
                    $updateMaster = false;
                }
            }
        }
        
        $masterMvc->save($rec);
        $masterMvc->logWrite('Ръчна промяна на курса', $rec->id);

        if ($updateMaster) {
            $masterMvc->updateMaster_($rec->id);
        }
        
        if ($rec->state == 'active') {
            acc_Journal::deleteTransaction($masterMvc->getClassId(), $rec->id);
            acc_Journal::saveTransaction($masterMvc->getClassId(), $rec->id, false);
            $masterMvc->logWrite('Реконтиране след промяна на курса', $rec->id);
        }
    }
    
    
    /**
     * Помощна ф-я за намиране на транспортното тегло/обем
     */
    private static function getMeasureRow($productId, $packagingId, $quantity, $type, $value = null, $masterState)
    {
        expect(in_array($type, array('volume', 'weight')));
        $hint = $warning = false;
        
        // Ако артикула не е складируем не му се изчислява транспортно тегло
        $isStorable = cat_products::fetchField($productId, 'canStore');
        if ($isStorable != 'yes') {
            return;
        }

        if(in_array($masterState, array('draft', 'pending')))
        if ($type == 'weight') {
            $liveValue = cat_Products::getTransportWeight($productId, $quantity);
        } else {
            $liveValue = cat_Products::getTransportVolume($productId, $quantity);
        }
        
        // Ако няма тегло взима се 'live'
        if (!isset($value)) {
            $value = $liveValue;
            
            if (isset($value)) {
                $hint = true;
            }
        } elseif ($liveValue) {
            $percentChange = abs(round((1 - $value / $liveValue) * 100, 3));
            if ($percentChange >= 25) {
                $warning = true;
            }
        }
        
        // Ако няма тегло не се прави нищо
        if (!isset($value)) {
            return;
        }
        
        $valueType = ($type == 'weight') ? 'cat_type_Weight(decimals=2)' : 'cat_type_Volume';
        $value = round($value, 3);
        
        // Ако стойноста е 0 да не се показва
        if (empty($value)) {
            return;
        }
       
        // Вербализиране на теглото
        $valueRow = core_Type::getByName($valueType)->toVerbal($value);
        if ($hint === true) {
            $hintType = ($type == 'weight') ? 'Транспортното тегло e прогнозно' : 'Транспортният обем е прогнозен';
            $valueRow = "<span style='color:blue'>{$valueRow}</span>";
            $valueRow = ht::createHint($valueRow, "{$hintType} на база количеството", 'notice', false);
        }
       
        // Показване на предупреждение
        if ($warning === true) {
            $liveValueVerbal = core_Type::getByName($valueType)->toVerbal($liveValue);
            $valueRow = ht::createHint($valueRow, "Има разлика от над 25% с очакваното|* {$liveValueVerbal}", 'warning', false);
        }
        
        return $valueRow;
    }
    
    
    /**
     * Връща реда за транспортният обем на артикула
     *
     * @param int        $productId   - артикул
     * @param int        $packagingId - ид на опаковка
     * @param int        $quantity    - общо количество
     * @param string     $masterState - общо количество
     * @param float|NULL $weight      - обем на артикула (ако няма се взима 'live')
     *
     * @return core_ET|NULL - шаблона за показване
     */
    public static function getVolumeRow($productId, $packagingId, $quantity, $masterState, $volume = null)
    {
        return self::getMeasureRow($productId, $packagingId, $quantity, 'volume', $volume, $masterState);
    }


    /**
     * Показва реда за логистичната информация за артикула
     *
     * @param $productId
     * @param $packagingId
     * @param $quantity
     * @param string $masterState
     * @param null|int $transUnitId
     * @param null|double $transUnitQuantity
     * @return null|array
     */
    public static function getTransUnitRow($productId, $packagingId, $quantity, $masterState, $transUnitId = null, $transUnitQuantity = null)
    {
        if(isset($transUnitId) && isset($transUnitQuantity)){

            return trans_TransportUnits::display($transUnitId, $transUnitQuantity);
        }

        if(in_array($masterState, array('draft', 'pending'))){
            $bestArr = trans_TransportUnits::getBestUnit($productId, $quantity, $packagingId);
            if(isset($bestArr)){
                $row = trans_TransportUnits::display($bestArr['unitId'], $bestArr['quantity']);
                $row = "<span style='color:blue'>{$row}</span>";

                return ht::createHint($row, 'Логистичните единици са изчислени динамично', 'notice', false);

            }
        }

        return null;
    }


    /**
     * Връща реда за транспортното тегло на артикула
     *
     * @param int        $productId   - артикул
     * @param int        $packagingId - ид на опаковка
     * @param int        $quantity    - общо количество
     * @param string     $masterState - общо количество
     * @param float|NULL $weight      - тегло на артикула (ако няма се взима 'live')
     *
     * @return core_ET|NULL - шаблона за показване
     */
    public static function getWeightRow($productId, $packagingId, $quantity, $masterState, $weight = null)
    {
        return self::getMeasureRow($productId, $packagingId, $quantity, 'weight', $weight, $masterState);
    }
    
    
    /**
     * Връща масив с фактурите в треда (тредовете)
     *
     * @param mixed         $threadId        - ид на нишка или масив от ид-та на нишки
     * @param datetime|NULL $valior          - ф-рите до дата, или NULL за всички
     * @param bool          $showInvoices    - да се показват само обикновените ф-ри
     * @param bool          $showDebitNotes  - да се показват и ДИ
     * @param bool          $showCreditNotes - да се показват и КИ
     *
     * @return array $invoices         - масив с ф-ри или броя намерени фактури
     */
    public static function getInvoicesInThread($threadId, $valior = null, $showInvoices = true, $showDebitNotes = true, $showCreditNotes = true)
    {
        $invoices = array();
        $threads = is_array($threadId) ? $threadId : array($threadId => $threadId);
        
        foreach (array('sales_Invoices', 'purchase_Invoices') as $class) {
            $Cls = cls::get($class);
            $iQuery = $Cls->getQuery();
            $iQuery->in('threadId', $threads);
            $iQuery->where("#state = 'active'");
            $iQuery->orderBy('date,number,type,dealValue', 'ASC');
            $iQuery->show('number,containerId');
            
            if (isset($valior)) {
                $iQuery->where("#date <= '{$valior}'");
            }
            
            $whereArr = array();
            if ($showInvoices === true) {
                $whereArr[] = "#type = 'invoice'";
            }
            
            if ($showDebitNotes === true) {
                $whereArr[] = "#type = 'dc_note' && #dealValue > 0";
            }
            
            if ($showCreditNotes === true) {
                $whereArr[] = "#type = 'dc_note' && #dealValue <= 0";
            }
            
            if (countR($whereArr)) {
                $iQuery->where(implode(' || ', $whereArr));
            }
            
            while ($iRec = $iQuery->fetch()) {
                $Document = doc_Containers::getDocument($iRec->containerId);
                $invoices[$iRec->containerId] = $Document->getInstance()->getVerbal($Document->fetch(), 'number');
            }
        }
        
        return $invoices;
    }


    /**
     * Връща нишките, които обединява или са обединени от дадена нишка
     *
     * @param int $threadId
     * @return array
     */
    public static function getCombinedThreads($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        if (!$firstDoc->isInstanceOf('deals_DealBase')) return array();

        // Ако сделката е приключена, проверява се дали не е приключена с друга сделка
        if ($firstDoc->fetchField('state') == 'closed') {
            $dQuery = $firstDoc->getInstance()->getQuery();
            $dQuery->where("LOCATE('|{$firstDoc->that}|', #closedDocuments)");

            // Ако е подменя се треда с този на обединяващата сделка, защото тя ще се използва за основа
            if ($combinedThread = $dQuery->fetch()->threadId) {
                $firstDoc = doc_Threads::getFirstDocument($combinedThread);
                $threadId = $combinedThread;
            }
        }

        // Ако сделката е обединяваща взимат се всички нишки, които обединява
        $threads = array($threadId => $threadId);
        $closedDocs = $firstDoc->fetchField('closedDocuments');
        $closedDocs = keylist::toArray($closedDocs);
        if (is_array($closedDocs) && countR($closedDocs)) {
            foreach ($closedDocs as $docId) {
                if ($dThreadId = $firstDoc->getInstance()->fetchField($docId, 'threadId')) {
                    $threads[$dThreadId] = $dThreadId;
                }
            }
        }

        return $threads;
    }


    /**
     * Помощен метод връщащ разпределението на плащанията по фактури
     *
     * @param int           $threadId          - ид на тред (ако е на обединена сделка ще се гледа обединението на нишките)
     * @param datetime|NULL $valior            - към коя дата
     * @param bool          $onlyExactPayments - дали да са всички плащания или само конкретните към всяка ф-ра
     *
     * @return array $paid      - масив с разпределените плащания
     */
    public static function getInvoicePayments($threadId, $valior = null, $onlyExactPayments = false)
    {
        // Всички ф-ри в посочената нишка/нишки
        $threads = static::getCombinedThreads($threadId);
        if(!countR($threads)) return array();

        // Кои са фактурите в посочената нишка/нишки
        $invoicesArr = self::getInvoicesInThread($threads, $valior, true, true, true);
        if (!countR($invoicesArr)) return array();

        $newInvoiceArr = $invMap = $payArr = array();
        foreach ($invoicesArr as $containerId => $handler) {
            $Document = doc_Containers::getDocument($containerId);
            $iRec = $Document->fetch('dealValue,discountAmount,vatAmount,rate,type,originId,containerId');
            
            $amount = round((($iRec->dealValue - $iRec->discountAmount) + $iRec->vatAmount) / $iRec->rate, 2);
            $key = ($iRec->type != 'dc_note') ? $containerId : $iRec->originId;
            $invMap[$containerId] = $key;
            
            if (!array_key_exists($key, $newInvoiceArr)) {
                $newInvoiceArr[$key] = (object) array('containerId' => $key, 'amount' => $amount, 'payout' => 0, 'payments' => array());
            } else {
                $newInvoiceArr[$key]->amount += $amount;
            }
        }

        foreach (array('cash_Pko', 'cash_Rko', 'bank_IncomeDocuments', 'bank_SpendingDocuments', 'findeals_CreditDocuments', 'findeals_DebitDocuments') as $Pay) {
            $Pdoc = cls::get($Pay);
            $pQuery = $Pdoc->getQuery();
            $pQuery->in('threadId', $threads);
            $pQuery->where("#state = 'active'");
            $pQuery->show('containerId,amountDeal,amount,isReverse,activatedOn,valior');
            if (isset($valior)) {
                $pQuery->where("#valior <= '{$valior}'");
            }
            
            while ($pRec = $pQuery->fetch()) {

                $sign = ($pRec->isReverse == 'yes') ? -1 : 1;
                $invArr = deals_InvoicesToDocuments::getInvoiceArr($pRec->containerId);
                $pData = $Pdoc->getPaymentData($pRec->id);

                if (in_array($Pay, array('findeals_CreditDocuments', 'findeals_DebitDocuments'))) {
                    $type = 'intercept';
                    $amount = round($pRec->amount, 2);
                } else {
                    $amount = round($pRec->amountDeal, 2);
                    $type = ($Pay == 'cash_Pko' || $Pay == 'cash_Rko') ? 'cash' : 'bank';
                }
                $rate = !empty($pRec->amountDeal) ? round($pRec->amount / $pRec->amountDeal, 4) : 0;

                if(countR($invArr)){
                    foreach ($invArr as $iRec){
                        $pData->amount -= $iRec->amount;
                        $iAmount = !empty($rate) ? $sign * round($iRec->amount / $rate, 2) : 0;
                        $payArr["{$pRec->containerId}|{$iRec->containerId}"] = (object) array('containerId' => $pRec->containerId, 'amount' => $iAmount, 'available' => $iAmount, 'to' => $invMap[$iRec->containerId], 'paymentType' => $type, 'isReverse' => ($pRec->isReverse == 'yes'));
                    }

                    $pData->amount = round($pData->amount, 2);
                    if(!empty($pData->amount)){
                        $rAmount = $sign * $pData->amount;
                        $payArr["{$pRec->containerId}|"] = (object) array('containerId' => $pRec->containerId, 'amount' => $rAmount, 'available' => $rAmount, 'to' => null, 'paymentType' => $type, 'isReverse' => ($pRec->isReverse == 'yes'));
                    }
                } else {
                    $amount = $sign * $amount;
                    $payArr[$pRec->containerId] = (object) array('containerId' => $pRec->containerId, 'amount' => $amount, 'available' => $amount, 'to' => $invMap[$pRec->fromContainerId], 'paymentType' => $type, 'isReverse' => ($pRec->isReverse == 'yes'));
                }
            }
        }

        // Ако в нишките има активни или приключени сделки с плащане да участват и те
        foreach(array('sales_Sales', 'purchase_Purchases') as $dealDoc){
            $DealDoc = cls::get($dealDoc);
            $dQuery = $DealDoc->getQuery();
            $dQuery->in('threadId', $threads);
            $dQuery->where("#state = 'active' || #state = 'closed'");
            $dQuery->where(array("#contoActions LIKE '%pay%'"));
            if (isset($valior)) {
                $dQuery->where("#valior <= '{$valior}'");
            }

            while ($dRec = $dQuery->fetch()) {
                $amount = round($dRec->amountDeal / $dRec->currencyRate, 6);
                $payArr[$dRec->containerId] = (object) array('containerId' => $dRec->containerId, 'amount' => $amount, 'available' => $amount, 'to' => null, 'paymentType' => 'cash', 'isReverse' => false);
            }
        }

        if($onlyExactPayments){

            // Ако се изискват само конкретните платежни документи към ф-те - оставят се само те
            // плащанията, които не са към конкретна фактура не се показват
            $payArr = array_filter($payArr, function ($a) {return isset($a->to);});
        }

        self::allocationOfPayments($newInvoiceArr, $payArr);

        return $newInvoiceArr;
    }
    
    
    /**
     * Ъпдейтва начина на плащане на фактурите в нишката
     *
     * @param int $threadId - ид на крака
     *
     * @return void
     */
    public static function updateAutoPaymentTypeInThread($threadId)
    {
        // Разпределените начини на плащане
        core_Cache::remove('threadInvoices1', "t{$threadId}");
        $invoicePayments = deals_Helper::getInvoicePayments($threadId);
        core_Cache::set('threadInvoices1', "t{$threadId}", $invoicePayments, 1440);
        
        // Всички ф-ри в нишката
        $invoices = self::getInvoicesInThread($threadId);
        if (!countR($invoices)) {
            return;
        }
        
        foreach ($invoices as $containerId => $hnd) {
            $Doc = doc_Containers::getDocument($containerId);
            $rec = $Doc->fetch();
            $rec->autoPaymentType = $Doc->getAutoPaymentType();
            
            $Doc->getInstance()->save_($rec, 'autoPaymentType');
            doc_DocumentCache::cacheInvalidation($rec->containerId);
        }
    }
    
    
    /**
     * Помощен метод дали в даден тред на сделка да се показва бутона за фактура
     *
     * @param int $threadId
     *
     * @return bool
     */
    public static function showInvoiceBtn($threadId)
    {
        expect($firstDoc = doc_Threads::getFirstDocument($threadId));
        if (!$firstDoc->isInstanceOf('deals_DealMaster')) {
            return false;
        }
        
        $contragentClassId = $firstDoc->fetchField('contragentClassId');
        if ($contragentClassId == crm_Persons::getClassId()) {
            return true;
        }
        
        $makeInvoice = $firstDoc->fetchField('makeInvoice');
        $res = ($makeInvoice == 'yes') ? true : false;
        
        return $res;
    }
    
    
    /**
     * Дефолтното име на платежната операция
     *
     * @param string $operationSysId
     *
     * @return string
     */
    public static function getPaymentOperationText($operationSysId)
    {
        $payments = cls::get('sales_Sales')->allowedPaymentOperations + cls::get('purchase_Purchases')->allowedPaymentOperations;
        
        return array_key_exists($operationSysId, $payments) ? $payments[$operationSysId]['title'] : '';
    }
    
    
    /**
     * Разпределяне на плащанията според приоритетите
     */
    public static function allocationOfPayments(&$invArr, &$payArr)
    {
        // Разпределяне на свързаните приходни документи
        foreach ($payArr as $i => $pay) {
            if ($pay->to) {
                $invArr[$pay->to]->payout += $pay->available;
                $pay->available = 0;
                $invArr[$pay->to]->used[$i] = $pay;
                self::pushPaymentType($invArr[$pay->to]->payments, $pay);
            }
        }
        
        $revInvArr = array_reverse($invArr, true);
        
        // Разпределяме всички остатъци от плащания
        foreach ($payArr as $k => $pay) {
            if ($pay->available > 0) {
                // Обикаляме по фактурите от начало към край и попълваме само дупките
                foreach ($invArr as $inv) {
                    if ($inv->amount > $inv->payout) {
                        $sum = min($inv->amount - $inv->payout, $pay->available);
                        $inv->payout += $sum;
                        $pay->available -= $sum;
                        
                        $inv->used[$k] = $pay;
                        self::pushPaymentType($inv->payments, $pay);
                    }
                }
            } elseif ($pay->available < 0) {
                // Обикаляме по фактурите от края към началото и връщаме пари само на надплатените
                foreach ($revInvArr as $inv) {
                    // Пропускаме фактурите, които са след плащането
                    // Предполагаме, че пари можем да връщаме само по минали фактури
                    if ($inv->number > $pay->number) {
                        continue;
                    }
                    if ($inv->payout > $inv->amount) {
                        $sum = min($inv->payout - $inv->amount, -$pay->available);
                        $inv->payout -= $sum;
                        $pay->available += $sum;
                        
                        $inv->used[$k] = $pay;
                        self::pushPaymentType($inv->payments, $pay);
                    }
                }
            }
        }

        // Събираме остатъците от всички платежни документи и ги нанасяме от зад напред
        $rest = 0;
        $used = $payments = array();
        foreach ($payArr as $pay) {
            if ($pay->available != 0) {
                $rest += $pay->available;
                $pay->available = 0;
                $used[$pay->containerId] = $pay->number;
                self::pushPaymentType($payments, $pay);
            }
        }
        
        foreach ($invArr as $inv) {
            $first = $inv;
            break;
        }
        
        foreach ($revInvArr as $inv) {
            if (!is_array($inv->used)) {
                $inv->used = array();
            }
            
            if ($rest > 0) {
                $inv->payout += $rest;
                $rest = 0;
                $inv->used += $used;
                $inv->payments += $payments;
            }
            
            if ($rest < 0) {
                if ($inv->number == $first->number) {
                    $sum = -$rest;
                } else {
                    $sum = min(-$rest, $inv->payout);
                }
                $inv->payout -= $sum;
                $rest += $sum;
                $inv->used += $used;
                $inv->payments += $payments;
            }
            
            if ($rest == 0) {
                break;
            }
        }
        
        // Обикаляме по фактурите и надплатените ги разнасяме към следващите
        $cInvArr = $invArr;
        foreach ($invArr as $inv) {
            $overPaid = $inv->payout - $inv->amount;
            if ($overPaid > 0) {
                foreach ($cInvArr as $cInv) {
                    $underPaid = $cInv->amount - $cInv->payout;
                    if ($underPaid > 0 && is_array($inv->used) && countR($inv->used)) {
                        $payDoc = $inv->used[countR($inv->used) - 1];
                        $transfer = min($underPaid, $overPaid);
                        $inv->payout -= $transfer;
                        $cInv->payout += $transfer;
                        if (is_array($cInv->used) && !in_array($payDoc, $cInv->used)) {
                            $cInv->used[$payDoc->containerId] = $payDoc;
                            self::pushPaymentType($cInv->payments, $payDoc);
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Помощна ф-я за добавяне на платежния метод
     */
    private static function pushPaymentType(&$payments, $pay)
    {
        if ($pay->paymentType == 'cash' && $pay->isReverse !== true) {
            $payments['cash'] = 'cash';
        } elseif ($pay->paymentType == 'intercept' && $pay->isReverse !== true) {
            $payments['intercept'] = 'intercept';
        } elseif ($pay->paymentType == 'bank' && $pay->isReverse !== true) {
            $payments['bank'] = 'bank';
        }
    }


    /**
     * Дефолтния режим на ДДС за папката
     *
     * @param int $folderId
     * @param int|null $ownCompanyId
     * @return string
     */
    public static function getDefaultChargeVat($folderId, $ownCompanyId = null)
    {
        if(!crm_Companies::isOwnCompanyVatRegistered($ownCompanyId)) return 'no';

        // Ако не може да се намери се търси от папката
        $coverId = doc_Folders::fetchCoverId($folderId);
        $Class = cls::get(doc_Folders::fetchCoverClassName($folderId));
        if (cls::haveInterface('crm_ContragentAccRegIntf', $Class)) {
            return ($Class->shouldChargeVat($coverId)) ? 'yes' : 'no';
        }
        
        return 'yes';
    }
    
    
    /**
     * Предупреждение ако избраната валута се различава от очакваната
     *
     * @param string $defaultVat
     * @param string $selectedVatType
     *
     * @return string
     */
    public static function getVatWarning($defaultVat, $selectedVatType)
    {
        if(!haveRole('debug')){
            if ($defaultVat == 'yes' && in_array($selectedVatType, array('exempt', 'no'))) {
                return 'Избран е режим за неначисляване на ДДС, при очакван с ДДС';
            } elseif ($defaultVat == 'no' && in_array($selectedVatType, array('yes', 'separate'))) {
                return 'Избран е режим за начисляване на ДДС, при очакван без ДДС';
            }
        }
    }
    
    
    /**
     * Предупреждения за множеството артикули с отрицателни количества в склада
     *
     * @param array  $arr
     * @param int    $storeId
     * @param string $productFld
     * @param string $quantityFld
     *
     * @return NULL|string
     */
    public static function getWarningForNegativeQuantitiesInStore($arr, $storeId, $state, $productFld = 'productId', $quantityFld = 'quantity')
    {
        $warning = null;
        $productsWithNegativeQuantity = array();
        if (!is_array($arr) || !countR($arr)) return;

        foreach ($arr as $obj) {
            $canStore = cat_Products::fetchField($obj->{$productFld}, 'canStore');
            if ($canStore != 'yes') continue;

            $available = self::getAvailableQuantityAfter($obj->{$productFld}, $storeId, ($state == 'pending' ? 0 : $obj->{$quantityFld}));
            if ($available < 0) {
                $productsWithNegativeQuantity[] = cat_Products::getTitleById($obj->{$productFld}, false);
            }
        }

        if (countR($productsWithNegativeQuantity)) {
            $warning = 'Контирането на документа ще доведе до отрицателни количества по|*: ' . implode(', ', $productsWithNegativeQuantity) . ', |в склад|* ' . store_Stores::getTitleById($storeId);
        }
        
        return $warning;
    }
    
    
    /**
     * Наличното к-во което ще остане в склада
     *
     * @param int   $productId
     * @param int   $storeId
     * @param float $quantity
     *
     * @return float
     */
    public static function getAvailableQuantityAfter($productId, $storeId, $quantity)
    {
        $stRec = store_Products::fetch("#productId = '{$productId}' AND #storeId = {$storeId}", 'quantity,reservedQuantity');
        $quantityInStore = $stRec->quantity - $stRec->reservedQuantity;
        
        return $quantityInStore - $quantity;
    }
    
    
    /**
     * Кой потребител да се показва, като съставителя на документа
     *
     * @param int      $createdBy   - ид на създателя
     * @param int      $activatedBy - ид на активаторът
     * @param int|null $userId      - ид на избрания потребител от двата
     *
     * @return null|string $names  - имената на съставителя, или null ако няма
     */
    public static function getIssuer($createdBy, $activatedBy, &$userId = null)
    {
        $userId = deals_Setup::get('ISSUER_USER');
        
        if (empty($userId)) {
            $selected = deals_Setup::get('ISSUER');
            $userId = ($selected == 'activatedBy') ? $activatedBy : $createdBy;
            $userId = (!core_Users::isContractor($userId)) ? $userId : $activatedBy;
        }
        
        $names = null;
        if (isset($userId)) {
            $names = core_Type::getByName('varchar')->toVerbal(core_Users::fetchField($userId, 'names'));
        }
        
        return $names;
    }
    
    
    /**
     * Проверки за свойствата на документите
     *
     * @param array $productArr
     * @param mixed $haveMetas
     * @param mixed $haveNotMetas
     *
     * @return array
     */
    public static function checkProductForErrors($productArr, $haveMetas, $haveNotMetas = null)
    {
        $errorNotActive = $errorMetas = array();
        $productArr = arr::make($productArr, true);
        
        if (countR($productArr)) {
            $haveMetas = arr::make($haveMetas, true);
            $haveNotMetas = arr::make($haveNotMetas, true);
            
            $pQuery = cat_Products::getQuery();
            $pQuery->in('id', $productArr);
            $pQuery->show(implode(',', $haveMetas + $haveNotMetas) . ',state,isPublic,name,nameEn');
            while ($pRec = $pQuery->fetch()) {
                $error = false;
                foreach ($haveMetas as $meta) {
                    if ($pRec->{$meta} != 'yes') {
                        $error = true;
                        break;
                    }
                }
                
                foreach ($haveNotMetas as $meta1) {
                    if ($pRec->{$meta1} != 'no') {
                        $error = true;
                        break;
                    }
                }
                
                if ($error) {
                    $errorMetas[$pRec->id] = cat_Products::getRecTitle($pRec, false);
                } elseif ($pRec->state != 'active') {
                    $errorNotActive[$pRec->id] = cat_Products::getRecTitle($pRec, false);
                }
            }
        }
        
        return array('notActive' => $errorNotActive, 'metasError' => $errorMetas);
    }
    
    
    /**
     * Помощна ф-я за умно конвертиране на цена и к-во
     *
     * Функцията намира d1, d2 и d3 - абсолютните разлики между:
     * round(Количество * Цена, 2) и
     * 1. round(Количество, 3) * round(Цена, 2)
     * 2. round(Количество /1000, 3) * round(Цена *1000, 2)
     * 3. round(Количество * 1000, 3) * round(Цена/1000, 2)
     * Алгоритъм:
     * 1. Ако d1 е по-малко от дадена константа (0.015) продължава към 4.
     * 2. Ако d2 е най-малкото измежду d1, d2 и d3 и има мярка с 1000 пъти по-голямо съдържание, връща: цена = цена*1000, количество = количество/1000 и по-голямата мярка
     * 3. Ако d3 е най-малкото измежду d1, d2 и d3 и има мярка с 1000 пъти по-малко съдържание, връща: цена = цена/1000, количество = количество*1000 и по-голямата мярка
     * 4. Връща непроменени количество, опаковка и цена
     *
     * @param int   $packQuantity
     * @param int   $packagingId
     * @param float $price
     *
     * @return void
     */
    public static function getSmartDisplay(&$packQuantity, &$packagingId, &$price)
    {
        $packagingRec = cat_UoM::fetchRec($packagingId, 'type');
        if ($packagingRec->type != 'uom') {
            return;
        }
        
        $similarUoms = cat_UoM::getSameTypeMeasures($packagingRec->id);
        unset($similarUoms['']);
        foreach (array_keys($similarUoms) as $uomId) {
            $similarUoms[$uomId] = cat_UoM::fetchField($uomId, 'baseUnitRatio');
        }
        
        if (countR($similarUoms) == 1) {
            return;
        }
        
        $start = round($packQuantity * $price, 2);
        $d1 = abs($start - round($packQuantity, 3) * round($price, 2));
        $d2 = abs($start - round($packQuantity / 1000, 3) * round($price * 1000, 2));
        $d3 = abs($start - round($packQuantity * 1000, 3) * round($price / 1000, 2));
        
        
        if ($d1 < self::SMART_PRICE_CONVERT) {
            return;
        }
        
        if ($d2 < $d1 && $d2 < $d3) {
            foreach ($similarUoms as $uomId => $ratio) {
                if (($ratio / 1000 >= 1) && $uomId != $packagingRec->id) {
                    $price *= 1000;
                    $packQuantity /= 1000;
                    $packagingId = $uomId;
                    
                    return;
                }
            }
        }
        
        if ($d3 < $d1 && $d3 < $d2) {
            foreach ($similarUoms as $uomId => $ratio) {
                if (($ratio * 1000 >= 1) && $uomId != $packagingRec->id) {
                    $price /= 1000;
                    $packQuantity *= 1000;
                    $packagingId = $uomId;
                    
                    return;
                }
            }
        }
    }

    /**
     * Проверка дали артикулите отговарят на свойствата
     *
     * @param $productArr
     * @param $haveMetas
     * @param null $haveNotMetas
     * @param null $metaError
     * @return string|void
     */
    public static function getContoRedirectError($productArr, $haveMetas, $haveNotMetas = null, $metaError = null)
    {
        // При ръчно реконтиране се подтискат всякакви грешки
        if(Mode::is('recontoTransaction')) return;

        $productCheck = deals_Helper::checkProductForErrors($productArr, $haveMetas, $haveNotMetas);
        if ($productCheck['notActive']) {
            return 'Артикулите|*: ' . implode(', ', $productCheck['notActive']) . ' |са затворени|*!';
        }
        
        if ($productCheck['metasError']) {
            return 'Артикулите|*: ' . implode(', ', $productCheck['metasError']) . " |{$metaError}|*!";
        }
    }


    /**
     * Проверка дали цената е под очакваната за клиента
     *
     * @param $productId
     * @param $price
     * @param $discount
     * @param $quantity
     * @param $quantityInPack
     * @param $contragentClassId
     * @param $contragentId
     * @param $valior
     * @param null $listId
     * @param bool $useQuotationPrice
     * @param $mvc
     * @param $threadId
     * @param double $rate
     * @param string $currencyId
     * @param null|stdClass $transportFeeRec
     *
     * @return stdClass|null
     */
    public static function checkPriceWithContragentPrice($productId, $price, $discount, $quantity, $quantityInPack, $contragentClassId, $contragentId, $valior, $listId = null, $useQuotationPrice = true, $mvc, $threadId, $rate, $currencyId, $transportFeeRec = null)
    {
        $price = $price * (1 - $discount);
        $minListId = sales_Setup::get('MIN_PRICE_POLICY');
        $isPublic = cat_Products::fetchField($productId, 'isPublic');
        $foundMinPrice = null;
        if ($minListId && $isPublic == 'yes') {
            $foundMinPrice = cls::get('price_ListToCustomers')->getPriceInfo($contragentClassId, $contragentId, $productId, null, $quantity, $valior, 1, 'no', $minListId, $useQuotationPrice);
        }

        $foundPrice = null;
        if($mvc instanceof store_ShipmentOrderDetails){
            if($firstDocument = doc_Threads::getFirstDocument($threadId)){
                if($firstDocument->isInstanceOf('sales_Sales')){
                    $sQuery = sales_SalesDetails::getQuery();
                    $sQuery->where("#saleId = {$firstDocument->that} AND #productId = {$productId}");
                    $sQuery->orderBy('price', 'ASC');
                    $sQuery->limit(1);
                    $sRec = $sQuery->fetch();
                    if(is_object($sRec)){
                        $foundPrice = (object)array('price' => $sRec->price, 'discount' => $sRec->discount);
                    }
                }
            }
        }

        if(empty($foundPrice)){
            $foundPrice = cls::get('price_ListToCustomers')->getPriceInfo($contragentClassId, $contragentId, $productId, null, $quantity, $valior, 1, 'no', $listId, $useQuotationPrice);
        }

        foreach (array($foundMinPrice, $foundPrice) as $i => $var){
            if(is_object($var)){

                // От записаната цена се маха тази на скрития транспорт, за да се сравни правилно с очакваната
                $msgSuffix = '';
                if(is_object($transportFeeRec)){
                    $var->price += $transportFeeRec->fee / $quantity;
                    $var->price = round($foundPrice->price, 6);
                    $msgSuffix .= ", |вкл. транспорт|*";
                }

                $toleranceDiff = 0;
                if (isset($var->listId)) {
                    $toleranceDiff = price_Lists::fetchField($var->listId, 'discountComparedShowAbove');
                }
                $toleranceDiff = !empty($toleranceDiff) ? $toleranceDiff * 100 : 1;
                $foundPrice = $var->price * (1 - $var->discount);
                
                $price1Round = round($price, 5);
                $price2Round = round($foundPrice, 5);
                
                if ($price2Round) {
                    $percent = core_Math::diffInPercent($price1Round, $price2Round);
                    $diff = abs(core_Math::diffInPercent($price1Round, $price2Round));
                    $price2Round /= $rate;

                    if ($diff > $toleranceDiff) {
                        $obj = array();

                        if($i == 0 && $percent >= 0){
                            $primeVerbal = core_Type::getByName('double(smartRound)')->toVerbal($price2Round * $quantityInPack);
                            $obj['hint'] ='Цената е под минималната за клиента';
                            $obj['hint'] .= "|*: {$primeVerbal} {$currencyId} |без ДДС|*{$msgSuffix}";
                            $obj['hintType'] = 'error';
                            
                            return $obj;
                        } 
                        
                        if($i == 1){
                            $primeVerbal = core_Type::getByName('double(smartRound)')->toVerbal($price2Round * $quantityInPack);
                            $obj['hint'] = ($percent < 0) ? 'Цената е над очакваната за клиента' : 'Цената е под очакваната за клиента';
                            $obj['hint'] .= "|*: {$primeVerbal} {$currencyId} |без ДДС|*{$msgSuffix}";
                            $obj['hintType'] = ($percent < 0) ? 'notice' : 'warning';
                        
                            return $obj;
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    
    /**
     * Има ли в документа артикули с продажба цена под минималната за клиента
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param null|string $msg
     *
     * @return bool
     */
    public static function hasProductsBellowMinPrice($mvc, $rec, &$msg = null)
    {
        $minPolicyId = sales_Setup::get('MIN_PRICE_POLICY');

        $products = array();
        if (isset($mvc->mainDetail) && !empty($minPolicyId)) {
            $rec = $mvc->fetchRec($rec);
            $Detail = cls::get($mvc->mainDetail);
            
            $dQuery = $Detail::getQuery();
            $dQuery->where("#{$Detail->masterKey} = {$rec->id}");
            $priceDate = ($rec == 'draft') ? null : $rec->valior;

            if($mvc instanceof sales_Sales){
                $useQuotationPrice = isset($rec->originId);
            } elseif($mvc instanceof sales_Quotations){
                $useQuotationPrice = false;
            } elseif($mvc instanceof store_ShipmentOrders){
                $useQuotationPrice = false;
                if($firstDocument = doc_Threads::getFirstDocument($rec->threadId)){
                    if($firstDocument->isInstanceOf('sales_Sales')){
                        $firstDocumentOrigin = $firstDocument->fetchField('originId');
                        $useQuotationPrice = isset($firstDocumentOrigin);
                    }
                }
            }

            while ($dRec = $dQuery->fetch()) {
                $discount = isset($dRec->discount) ? $dRec->discount : $dRec->autoDiscount;
                $transportFeeRec = sales_TransportValues::get($mvc, $rec->id, $dRec->id);
                if($checkedObject = deals_Helper::checkPriceWithContragentPrice($dRec->productId, $dRec->price, $discount, $dRec->quantity, $dRec->quantityInPack, $rec->contragentClassId, $rec->contragentId, $priceDate, $rec->priceListId, $useQuotationPrice, $mvc, $rec->threadId, $rec->currencyRate, $rec->currencyId, $transportFeeRec)){
                    if($checkedObject['hintType'] == 'error'){
                        $products[$dRec->productId] = cat_Products::getTitleById($dRec->productId);
                    }
                }
            }

            if(countR($products)){
                $msg = "Следните артикули са с продажни цени под минималната|*: " . implode(', ', $products);
                return true;
            }
        }
        
        return false;
    }


    /**
     * Канонизиране на нац. номер/ЕИК
     *
     * @param string $number
     * @param int $countryId
     * @return string
     */
    public static function canonizeUicNumber($number, $countryId)
    {
        $canonize = preg_replace('/[^a-z\d]/i', '', $number);

        return strtoupper($canonize);
    }


    /**
     * Може ли да се правят още доставки в нишката
     *
     * @param $threadId                - ид на нишка
     * @param null $ignoreContainerId  - игнориране на документ
     * @param bool $ignoreDrafts       - игнориране на черновите документи
     * @return bool                    - има ли финална експедиция или не
     */
    public static function canHaveMoreDeliveries($threadId, $ignoreContainerId = null, $ignoreDrafts = false)
    {
        $firstDocument = doc_Threads::getFirstDocument($threadId);
        $firstDocRec = $firstDocument->fetch('oneTimeDelivery,contoActions');
        if($firstDocRec->oneTimeDelivery != 'yes') return true;

        $contoActions = type_Set::toArray($firstDocRec->contoActions);
        if(isset($contoActions['ship'])) {
            if($firstDocument->hasStorableProducts()) return false;
        }

        // Всички документи касаещи експедиции в нишката
        $cQuery = doc_Containers::getQuery();
        $cQuery->in("docClass", array(store_Receipts::getClassId(), store_ShipmentOrders::getClassId()));
        $cQuery->where("#threadId = {$threadId} AND #state != 'rejected'");
        if(isset($ignoreContainerId)){
            $cQuery->where("#id != {$ignoreContainerId}");
        }

        if($ignoreDrafts){
            $cQuery->where("#state != 'draft'");
        }

        $cQuery->show('id');
        $count = $cQuery->count();

        return empty($count);
    }
}

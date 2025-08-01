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

        $vatExceptionId = cond_VatExceptions::getFromThreadId($masterRec->threadId);

        expect(is_object($masterRec));
        
        // Комбиниране на дефолт стойнстите с тези подадени от потребителя
        $map = array_merge(self::$map, $map);
        $haveAtleastOneDiscount = false;

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
                $vat = cat_Products::getVat($rec->{$map['productId']}, $masterRec->{$map['valior']}, $vatExceptionId);
            }

            // Калкулира се цената с и без ддс и се показва една от тях взависимост трябвали да се показва ддс-то
            $price = self::calcPrice($rec->{$map['priceFld']}, $vat, $masterRec->{$map['rateFld']});
            $rec->{$map['priceFld']} = ($hasVat) ? $price->withVat : $price->noVat;
            $noVatAmount = round($price->noVat * $rec->{$map['quantityFld']}, $vatDecimals);
            $discountVal = $rec->{$map['discount']};

            if(!empty($rec->{$map['autoDiscount']})){
                if(in_array($masterRec->state, array('draft', 'pending'))){
                    $discountVal = round((1-(1-$discountVal)*(1-$rec->{$map['autoDiscount']})), 8);
                }
            }

            if($discountVal) {
                $haveAtleastOneDiscount = true;
            }

            $noVatAmountOriginal = $noVatAmount;
            if($testRound == 'yes') {
                $noVatAmount = round($noVatAmount, 2);
            }
            if ($discountVal) {
                $withoutVatAndDisc = $noVatAmountOriginal * (1 - $discountVal);
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

            if($testRound == 'yes') {
                $discount = round($discount, 2);
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
                $vats[$vat]->sum += $withoutVatAndDisc;
            }
        }
        
        $mvc->_total = new stdClass();
        $mvc->_total->amount = round($amountRow, 2);
        $mvc->_total->vat = round($amountVat, 2);
        $mvc->_total->vats = $vats;
        $mvc->_total->haveAtleastOneDiscount = $haveAtleastOneDiscount;

        if (!$map['alwaysHideVat']) {
            $mvc->_total->discount = round($amountRow, 2) - round($amountJournal, 2);
        } else {
            $mvc->_total->discount = round($discount, 2);
        }

        // "Просто" изчисляване на ДДС-то в документа, ако има само една ставка
        if (countR($vats) == 1 && ($mvc instanceof deals_InvoiceMaster)) {
            $vat = key($vats);
            $vats[$vat]->sum = round($vats[$vat]->sum, 2);
            $vats[$vat]->amount = round($vats[$vat]->sum * $vat, 2);

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
    public static function prepareSummary($values, $date, $currencyRate, $currencyId, $chargeVat, $invoice = false, $lang = 'bg', $dualCurrencyData = array())
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
            core_Lg::push($lang);
            $arr['discountCaption'] = $values['haveAtleastOneDiscount'] ? tr('Отстъпка') : "<i class='quiet'>" . tr('Разлики от закръгляне') . "</i>";
            core_Lg::pop();
        }
        
        // Ако има нето, крайната сума е тази на нетото, ако няма е тази на стойността
        $arr['total'] = (isset($arr['neto'])) ? $arr['neto'] : $arr['value'];
        
        $coreConf = core_Packs::getConfig('core');
        $pointSign = $coreConf->EF_NUMBER_DEC_POINT;
        $countVats = countR($values['vats']);

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
                            if($countVats == 1 && isset($arr['neto'])) {
                                $arr["vat{$index}BaseAmount"] = $arr['neto'] * $currencyRate;
                            } else {
                                $arr["vat{$index}BaseAmount"] = $vi->sum * $currencyRate;
                            }

                            $arr["vat{$index}BaseCurrencyId"] = $baseCurrency;
                        }
                    }
                }
            } else {
                $arr['vat02Amount'] = 0;
                $arr['vat02AmountCurrencyId'] = ($invoice) ? $baseCurrency : $currencyId;
            }
        }
        
        if ($invoice) {
            $arr['baseAmount'] = $arr['total'] * $currencyRate; // Данъчната основа
            $arr['baseAmount'] = ($arr['baseAmount']) ? $arr['baseAmount'] : "<span class='quiet'>0" . $pointSign . '00</span>';
            $arr['baseCurrencyId'] = $baseCurrency; 			// Валутата на данъчната основа е тази на периода
        }

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

        if($arr['value'] != 0){
            $arr['sayWordsValue'] = $SpellNumber->asCurrency($arr['value'], $lang, false, $currencyId);
            $arr['sayWordsValue'] = str::mbUcfirst($arr['sayWordsValue']);
        }

        if($arr['neto'] != 0){
            $arr['sayWordsNetto'] = $SpellNumber->asCurrency($arr['neto'], $lang, false, $currencyId);
            $arr['sayWordsNetto'] = str::mbUcfirst($arr['sayWordsNetto']);
        }

        $arr['value'] = ($arr['value']) ? $arr['value'] : "<span class='quiet'>0" . $pointSign . '00</span>';
        $arr['total'] = ($arr['total']) ? $arr['total'] : "<span class='quiet'>0" . $pointSign . '00</span>';

        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        foreach ($arr as $index => $el) {
            if (is_numeric($el)) {
                $arr[$index] = $Double->toVerbal($el);
                if(countR($dualCurrencyData)) {
                    $arr[$index] = deals_Helper::displayDualAmount($arr[$index], $el, $dualCurrencyData['date'], $currencyId, $dualCurrencyData['countryId']);
                }
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
        $obj = (object) array('formInfo' => "<div class='formCustomInfo'>{$info}</div>");
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

        if(!isset($mvc->mainDetail)) return $res;

        $Detail = cls::get($mvc->mainDetail);
        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = '{$id}'");
        $productIds = arr::extractValuesFromArray($dQuery->fetchAll(), $productFld);
        if(countR($productIds)){
            $pQuery = cat_Products::getQuery();
            $pQuery->in('id', $productIds);
            $pQuery->show('containerId');
            $res = arr::extractValuesFromArray($pQuery->fetchAll(), 'containerId');
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

                                if(is_array($p->batches)){
                                    $combined[$index]->batches = array();
                                    $combined[$index]->batchesSums = array();
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
                            $d->quantity += $sign * $p->quantity;
                            $d->sumAmounts += $sign * ($p->quantity * $p->price * (1 - $p->discount));

                            if(is_array($p->batches)){
                                foreach ($p->batches as $batch => $batchQuantity){
                                    $d->batches[$batch] += $sign * $batchQuantity;
                                    $d->batchesSums[$batch] += $sign * ($batchQuantity * $p->price * (1 - $p->discount));
                                }
                            }

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
                //$det->sumAmounts = core_Math::roundNumber($det->sumAmounts);
                if(is_array($det->batches) && countR($det->batches)){
                    $sumBatches = $sumQuantities = 0;
                    foreach ($det->batches as $b => $q){
                        if($q <= 0) {
                            unset($det->batches[$b]);
                            unset($det->batchesSums[$b]);
                        } else {
                            $sumBatches += $det->batchesSums[$b];
                            $sumQuantities += $det->batches[$b];
                        }
                    }
                    $sumBatches = core_Math::roundNumber($sumBatches);
                    $sumQuantities = core_Math::roundNumber($sumQuantities);

                    $det->sumAmounts = max($det->sumAmounts, $sumBatches);
                    $det->quantity = max($det->quantity, $sumQuantities);
                    unset($det->batchesSums);
                }

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
        if (!in_array($state, array('draft', 'pending'))) return;

        $pRec = cat_Products::fetch($productId, 'canStore,isPublic');

        // Ако артикулът е с моментна рецепта няма да се проверява за наличност
        if($mvc->manifactureProductsOnShipment) {
            $lastInstantBom = cat_Products::getLastActiveBom($productId, 'instant');
            if(is_object($lastInstantBom)) {
                $html = ht::createHint($html, "Артикулът е с моментна рецепта и ще бъде произведен при изписване от склада|*!", 'img/16/cog.png', false, null, "class=doc-positive-quantity");
                return;
            }
        }

        if ($pRec->canStore != 'yes') return;

        $date = $date ?? null;
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

                if(!$skip){
                    $iQuery = store_StockPlanning::getQuery();
                    $iQuery->where("#productId = {$productId} AND #sourceClassId = {$firstDocument->getInstance()->getClassId()} AND #sourceId = {$firstDocument->that} AND #storeId IS NOT NULL");
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
            $showNegativeWarning = $pRec->isPublic == 'yes';
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
                        if($stRec->quantity >= $quantity) {
                            $hint = "Наличността в склада е достатъчна за изпълнение / контиране на документа, но разполагаемата наличност е недостатъчна за изпълнението на всички чакащи документи!";
                        } else {
                            $hint = "Недостатъчна наличност|*(1): {$inStockVerbal} |{$measureName}|*! |Контирането на документа ще доведе до отрицателна наличност|* |{$showStoreInMsg}|*!";
                        }
                    }
                }

                $firstCheck = true;
            }
        }

        if(!$firstCheck){
            if ($futureQuantity < 0 && $freeQuantity < 0) {
                if($showNegativeWarning){
                    $hint = "Недостатъчна наличност|*(2): {$inStockVerbal} |{$measureName}|*! |Контирането на документа ще доведе до отрицателна наличност|* |{$showStoreInMsg}|*!";
                    if(haveRole('debug')) {
                        $hint .= " (debug) количество: {$quantity}, бъдещо: {$futureQuantity}, разполагаемо {$freeQuantity} (текущо разп. {$freeQuantityOriginal}), налично {$stRec->quantity}";
                    }
                    $class = 'doc-negative-quantity';
                    $makeLink = false;
                }
            } elseif ($futureQuantity < 0 && $freeQuantity >= 0) {
                if($showNegativeWarning) {
                    $freeQuantityOriginalVerbal = $Double->toVerbal($freeQuantityOriginal);
                    $hint = "Недостатъчна наличност|*: {$inStockVerbal} |{$measureName}|*! |Контирането на документа ще доведе до отрицателна наличност|* |{$showStoreInMsg}|*! |Очаква се доставка - разполагаема наличност|*: {$freeQuantityOriginalVerbal} |{$measureName}|*";
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

        if($pRec->isPublic == 'no') {
            if($futureQuantity > 0) {
                $html = ht::createHint($html, "Наличността в склада е по-голяма|*: {$inStockVerbal} {$measureName}", 'notice', false, null, "class=doc-positive-quantity");
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
     * @param int $containerId       - ид на контейнер на документа
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
    public static function getDocumentHeaderInfo($containerId, $contragentClass, $contragentId, $contragentName = null)
    {
        // Ако е инсталиран пакета за многофирменост - моята фирма е тази посочена в първия документ на нишката
        $ownCompanyId = null;
        $Document = doc_Containers::getDocument($containerId);
        $docRec = $Document->fetch("activatedOn,threadId,{$Document->valiorFld}");

        if(core_Packs::isInstalled('holding')) {
            $firstDoc = doc_Threads::getFirstDocument($docRec->threadId);
            if($firstDoc->isInstanceOf('deals_DealMaster')) {
                if(isset($firstDoc->ownCompanyFieldName)) {
                    $ownCompanyId = $firstDoc->fetchField($firstDoc->ownCompanyFieldName);
                }
            }
        }

        // Данните на 'Моята фирма' към дата 00:00 на вальора
        $res = array();
        $dateFromWhichToGetName = !empty($docRec->{$Document->valiorFld}) ? $docRec->{$Document->valiorFld} : dt::now();
        $dateFromWhichToGetName = dt::mysql2verbal($dateFromWhichToGetName, 'Y-m-d 00:00:00');
        $ownCompanyData = crm_Companies::fetchOwnCompany($ownCompanyId, $dateFromWhichToGetName);

        // Името и адреса на 'Моята фирма'
        $Companies = cls::get('crm_Companies');
        $res['MyCompany'] = $ownCompanyData->companyVerb;
        $now = dt::now();

        if((!empty($ownCompanyData->validTo) && $now >= $ownCompanyData->validTo) || $now <= $ownCompanyData->validFrom) {
            $ownCompanyData2 =  crm_Companies::fetchOwnCompany($ownCompanyId);
            $warningMyCompanyArr =  static::getContragentDataCompareString($ownCompanyData, $ownCompanyData2);
            if(!empty($warningMyCompanyArr)) {
                if(core_Users::isPowerUser()) {
                    $res['MyCompany'] = ht::createHint($res['MyCompany'], 'Следните данни на моята фирма във визитката се различават от тези към вальора на документа|*: ' . implode(', ', $warningMyCompanyArr), 'warning');
                }
            }
        }

        // ДДС и националния номер на 'Моята фирма'
        $uic = $ownCompanyData->uicId ?? drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
        if ($uic != $ownCompanyData->vatNo) {
            $res['MyCompanyVatNo'] = core_Type::getByName('drdata_VatType')->toVerbal($ownCompanyData->vatNo);
        }
        $res['MyCompanyEori'] = core_Type::getByName('drdata_type_Eori')->toVerbal($ownCompanyData->eori);
        $res['uicId'] = $uic;
        
        // името, адреса и ДДС номера на контрагента
        if (isset($contragentClass, $contragentId)) {
            $ContragentClass = cls::get($contragentClass);
            $cData = $ContragentClass->getContragentData($contragentId, $dateFromWhichToGetName);
            $cName = ($cData->personVerb) ? $cData->personVerb : $cData->companyVerb;
            $res['contragentName'] = $contragentName ?? $cName;

            $res['inlineContragentName'] = $res['contragentName'];
            $res['eori'] = core_Type::getByName('drdata_type_Eori')->toVerbal($cData->eori);
            $res['vatNo'] = core_Type::getByName('drdata_VatType')->toVerbal($cData->vatNo);
            $res['contragentUicId'] = $cData->uicId;
            if (!empty($cData->uicId)) {
                $res['contragentUicCaption'] = ($ContragentClass instanceof crm_Companies) ? tr('ЕИК') : tr('ЕГН||Personal №');
            }

            if((!empty($cData->validTo) && $now >= $cData->validTo) || $now <= $cData->validFrom) {
                // Ако се извлича версия към по-стара дата - проверка дали се различават съществените данни
                $currentContragentData = $ContragentClass->getContragentData($contragentId);
                $warningMsgArr = static::getContragentDataCompareString($cData, $currentContragentData);
                if(!empty($warningMsgArr)) {
                    if(core_Users::isPowerUser()){
                        $res['contragentName'] = ht::createHint($res['contragentName'], "Следните полета във визитката се различават от тези към вальора на документа|*: " . implode(', ', $warningMsgArr), 'warning');
                    }
                }
            } elseif($res['contragentName'] != $cName){
                if(core_Users::isPowerUser()){
                    $res['contragentName'] = ht::createHint($res['contragentName'], 'Името на контрагента е променено в документа|*!', 'warning');
                }
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
        
        $showCountries = !(($ownCompanyData->countryId == $cData->countryId));
        if (isset($contragentClass, $contragentId)) {
            $res['contragentAddress'] = $ContragentClass->getFullAdress($contragentId, false, $showCountries, true, $dateFromWhichToGetName)->getContent();
            $res['inlineContragentAddress'] = $ContragentClass->getFullAdress($contragentId, false, $showCountries, true, $dateFromWhichToGetName)->getContent();
            $res['inlineContragentAddress'] = str_replace('<br>', ',', $res['inlineContragentAddress']);
        }
        
        $res['MyAddress'] = $Companies->getFullAdress($ownCompanyData->companyId, true, $showCountries, true, $dateFromWhichToGetName)->getContent();

        if(drdata_Countries::isEu($cData->countryId) && empty($cData->eori)){
            unset($res['MyCompanyEori']);
        }

        return $res;
    }


    /**
     * Помощна ф-я връщаща разликата между контрагентските данни на един и същ контрагент
     *
     * @param stdClass $cData1
     * @param stdClass $cData2
     * @return array $warningMsgArr
     */
    private static function getContragentDataCompareString($cData1, $cData2)
    {
        $warningMsgArr = array();
        $cName1 = ($cData1->personVerb) ? $cData1->personVerb : $cData1->companyVerb;
        $cName2 = ($cData2->personVerb) ? $cData2->personVerb : $cData2->companyVerb;
        if ($cName1 != $cName2) {
            $warningMsgArr[] = tr('Име') . (!empty($cName2) ? " [{$cName2}]" : "");
        }
        if ($cData1->vatNo != $cData2->vatNo) {
            $warningMsgArr[] = tr('ДДС№') . (!empty($cData2->vatNo) ? " [{$cData2->vatNo}]" : "");
        }
        if ($cData1->eori != $cData2->eori) {
            $warningMsgArr[] = tr('ЕОРИ') . " [{$cData2->eori}]";
        }
        if ($cData1->uicId != $cData2->uicId) {
            $warningMsgArr[] = ($cData1->personVerb) ? tr('ЕГН') : (tr('Нац. №') . (!empty($cData2->uicId) ? " [{$cData2->uicId}]" : ''));
        }

        return $warningMsgArr;
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
            try{
                $conditions = cat_Products::getConditions($dRec->productId, $type, $lg);
            } catch(core_exception_Expect $e){
                $conditions = array();
            }

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

            Mode::push("stopMasterUpdate{$rec->id}", true);
            while ($dRec = $dQuery->fetch()) {
                if($rec->{$rateFld}){
                    $dRec->{$priceFld} = ($dRec->{$priceFld} / $rec->{$rateFld}) * $newRate;
                } else {
                    $dRec->{$priceFld} = $dRec->{$priceFld} * $newRate;
                    wp($dRec, $rec, $rateFld);
                }

                if ($masterMvc instanceof deals_InvoiceMaster) {
                    $dRec->packPrice = $dRec->{$priceFld} * $dRec->quantityInPack;
                    $dRec->amount = $dRec->packPrice * $dRec->quantity;
                }

                $Detail->save($dRec);
            }
            Mode::pop("stopMasterUpdate{$rec->id}");

            $updateMaster = true;
            $oldRate = $rec->{$rateFld};
            $rec->{$rateFld} = $newRate;
            if ($masterMvc instanceof deals_InvoiceMaster) {
                //$rec->displayRate = $newRate;
                if ($rec->dpOperation == 'accrued' || isset($rec->changeAmount)) {
                    // Изчисляване на стойността на ддс-то
                    $vat = acc_Periods::fetchByDate()->vatRate;
                    if(isset($rec->dpVatGroupId)){
                        $vat = acc_VatGroups::fetchField($rec->dpVatGroupId, 'vat');
                    }
                    if ($rec->vatRate != 'yes' && $rec->vatRate != 'separate') {
                        $vat = 0;
                    }

                    if(isset($rec->dpAmount)){
                        $rec->dealValue = ($rec->dpAmount / $oldRate) * $rec->displayRate;
                        $diff = ($rec->dpAmount / $oldRate) * $newRate;
                        $rec->dpAmount = $diff;
                    } else {
                        $diff = $rec->changeAmount * $rec->displayRate;
                        $rec->dealValue = $diff;
                    }

                    $rec->vatAmount = $diff * $vat;
                    $updateMaster = false;
                } elseif($rec->dpOperation == 'deducted' && isset($rec->dpAmount)){
                    $diff = ($rec->dpAmount / $oldRate) * $newRate;
                    $rec->dpAmount = $diff;
                }
            }
        }
        $rec->_recalcRate = true;
        Mode::push('dontUpdateKeywords', true);
        $masterMvc->save($rec);

        $logMsg = 'Промяна на курс';
        if ($updateMaster) {
            $masterMvc->updateMaster_($rec);
        }
        Mode::pop('dontUpdateKeywords');
        if ($rec->state == 'active') {

            $deletedRec = null;
            acc_Journal::deleteTransaction($masterMvc->getClassId(), $rec->id, $deletedRec);

            $popReconto = $popRecontoDate = false;
            try{
                if(is_object($deletedRec)){
                    Mode::push('recontoWithCreatedOnDate', $deletedRec->createdOn);
                    $popRecontoDate = true;
                }
                Mode::push('recontoTransaction', true);
                $popReconto = true;
                acc_Journal::saveTransaction($masterMvc->getClassId(), $rec->id, false);
                Mode::pop('recontoTransaction');
                $popReconto = false;
                if($popRecontoDate){
                    Mode::pop('recontoWithCreatedOnDate');
                    $popRecontoDate = false;
                }
                $logMsg = 'Реконтиране след промяна на курса';
            } catch(acc_journal_RejectRedirect  $e) {
                if(is_object($deletedRec)) {
                    acc_Journal::restoreDeleted($masterMvc->getClassId(), $rec->id, $deletedRec, $deletedRec->_details);
                }
                if($popReconto){
                    Mode::pop('recontoTransaction');
                }
                if($popRecontoDate){
                    Mode::pop('recontoWithCreatedOnDate');
                }
                wp($e);
                $logMsg = 'Грешка при опит за реконтиране';
            }
        }

        $masterMvc->logWrite($logMsg, $rec->id);
    }
    
    
    /**
     * Помощна ф-я за намиране на транспортното тегло/обем
     */
    private static function getMeasureRow($productId, $packagingId, $quantity, $type, &$value = null, $masterState)
    {
        expect(in_array($type, array('volume', 'weight', 'netWeight', 'tareWeight')));
        $hint = $warning = false;
        
        // Ако артикула не е складируем не му се изчислява транспортно тегло
        $isStorable = cat_products::fetchField($productId, 'canStore');
        if ($isStorable != 'yes') {
            return;
        }
        if(in_array($masterState, array('draft', 'pending'))) {
            $liveValue = null;
            if ($type == 'weight') {
                $liveValue = cat_Products::getTransportWeight($productId, $quantity);
            } elseif($type == 'netWeight') {
                $netWeight = cat_Products::convertToUom($productId, 'kg');

                if(isset($netWeight)) {
                    $liveValue = $netWeight * $quantity;
                }
            } elseif($type == 'volume') {
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
        }

        // Ако няма тегло не се прави нищо
        if (!isset($value)) return;
        
        $valueType = ($type == 'volume') ? 'cat_type_Volume' : 'cat_type_Weight(decimals=2)';
        $value = round($value, 3);
       
        // Вербализиране на теглото
        $valueRow = core_Type::getByName($valueType)->toVerbal($value);
        if(!Mode::isReadOnly() && $hint === true) {
            $hintType = ($type == 'weight') ? 'Транспортното тегло e прогнозно' : (($type == 'volume') ? 'Транспортният обем е прогнозен' : (($type == 'netWeight') ? 'Нето теглото е прогнозно' : 'Тарата е прогнозна'));
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
    public static function getVolumeRow($productId, $packagingId, $quantity, $masterState, &$volume = null)
    {
        $res = self::getMeasureRow($productId, $packagingId, $quantity, 'volume', $volume, $masterState);

        return $res;
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
    public static function getWeightRow($productId, $packagingId, $quantity, $masterState, &$weight = null)
    {
        $res = self::getMeasureRow($productId, $packagingId, $quantity, 'weight', $weight, $masterState);

        return $res;
    }


    /**
     * Връща нето теглото на реда
     *
     * @param int        $productId   - артикул
     * @param int        $packagingId - ид на опаковка
     * @param int        $quantity    - общо количество
     * @param string     $masterState - общо количество
     * @param float|NULL $netWeight   - тегло на артикула (ако няма се взима 'live')
     *
     * @return core_ET|NULL - шаблона за показване
     */
    public static function getNetWeightRow($productId, $packagingId, $quantity, $masterState, &$netWeight = null)
    {
        $res = self::getMeasureRow($productId, $packagingId, $quantity, 'netWeight', $netWeight, $masterState);

        return $res;
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
        if(!is_object($firstDoc)) return array();
        if (!$firstDoc->isInstanceOf('deals_DealBase')) return array();

        // Ако сделката е приключена, проверява се дали не е приключена с друга сделка
        if ($firstDoc->fetchField('state') == 'closed') {
            $firstDocRec = $firstDoc->fetch('folderId,id');
            $dQuery = $firstDoc->getInstance()->getQuery();
            $dQuery->where("LOCATE('|{$firstDocRec->id}|', #closedDocuments) AND #folderId = {$firstDocRec->folderId}");

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
     * @param int            $threadId               - ид на тред (ако е на обединена сделка ще се гледа обединението на нишките)
     * @param datetime|NULL  $valior                 - към коя дата
     * @param boolean        $onlyExactPayments      - дали да са всички плащания или само конкретните към всяка ф-ра
     * @param boolean        $applyNotesToTheInvoice - дали да наслагва известията към фактурата
     *
     * @return array         $paid - масив с разпределените плащания
     */
    public static function getInvoicePayments($threadId, $valior = null, $onlyExactPayments = true, $applyNotesToTheInvoice = true)
    {
        // Всички ф-ри в посочената нишка/нишки
        $threads = static::getCombinedThreads($threadId);
        if(!countR($threads)) return array();

        // Кои са фактурите в посочената нишка/нишки
        $invoicesArr = self::getInvoicesInThread($threads, $valior, true, true, true);
        if (!countR($invoicesArr)) return array();

        core_Debug::startTimer("CALC_INVOICE_PAYMENTS");
        $newInvoiceArr = $invMap = $payArr = array();
        foreach ($invoicesArr as $containerId => $handler) {
            $Document = doc_Containers::getDocument($containerId);
            $iRec = $Document->fetch('dealValue,discountAmount,vatAmount,rate,type,originId,containerId,dueDate');
            $dueDate = !empty($iRec->dueDate) ? $iRec->dueDate : $iRec->date;

            $amount = round((($iRec->dealValue - $iRec->discountAmount) + $iRec->vatAmount) / $iRec->rate, 2);

            $key = $applyNotesToTheInvoice ? ($iRec->type != 'dc_note' ? $containerId : $iRec->originId) : $containerId;
            $invMap[$containerId] = $key;
            
            if (!array_key_exists($key, $newInvoiceArr)) {
                $newInvoiceArr[$key] = (object) array('containerId' => $key, 'amount' => $amount, 'payout' => 0, 'payments' => array(), 'dueDate' => $dueDate, 'rate' => $iRec->rate);
            } else {
                $newInvoiceArr[$key]->amount += $amount;
            }
            $newInvoiceArr[$key]->dueDate = min($newInvoiceArr[$key]->dueDate, $dueDate);
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
            $dQuery->where("#state IN ('active', 'closed')");
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
        core_Debug::stopTimer("CALC_INVOICE_PAYMENTS");

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
        $res = !($makeInvoice == 'no');
        
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
     * @param core_Mvc $mvc - документ
     * @param stdClasss $rec - запис
     * @param string|null $chargeVatConditionSysId - сис ид на търговско условие
     *
     * @return string
     */
    public static function getDefaultChargeVat($mvc, $rec, $chargeVatConditionSysId = null)
    {
        if(!$mvc->isOwnCompanyVatRegistered($rec)) return 'no';

        // Ако не може да се намери се търси от папката
        $coverId = doc_Folders::fetchCoverId($rec->folderId);
        $Class = cls::get(doc_Folders::fetchCoverClassName($rec->folderId));

        if(isset($chargeVatConditionSysId)){
            $clientValue = cond_Parameters::getParameter($Class, $coverId, $chargeVatConditionSysId);
            if(!empty($clientValue)) return $clientValue;
        }

        if (cls::haveInterface('crm_ContragentAccRegIntf', $Class)) {
            return ($Class->shouldChargeVat($coverId, $mvc)) ? 'separate' : 'no';
        }
        
        return 'separate';
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
        $Type = core_Type::getByName('enum(separate=Отделен ред за ДДС, yes=Включено ДДС в цените, exempt=Освободено от ДДС, no=Без начисляване на ДДС)');
        $showWarning = (in_array($defaultVat, array('yes', 'separate')) && in_array($selectedVatType, array('exempt', 'no'))) || in_array($defaultVat, array('no', 'exempt')) && in_array($selectedVatType, array('yes', 'separate'));
        if ($showWarning) {

            return "Избран е режим за|* <b>|{$Type->toVerbal($selectedVatType)}|*</b>, |при очакван|* <b>|{$Type->toVerbal($defaultVat)}|*</b>!";
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

            $available = self::getAvailableQuantityAfter($obj->{$productFld}, $storeId, $obj->{$quantityFld});
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
        $stRec = store_Products::fetch("#productId = '{$productId}' AND #storeId = {$storeId}", 'quantity');

        return $stRec->quantity - $quantity;
    }


    /**
     * Връща вербално представяне на съставителя на документа
     *
     * @param string $username
     * @param int $createdBy
     * @param int $activatedBy
     * @param string $state
     * @param int|null $issuerId
     * @return core_ET|mixed|string
     */
    public static function getIssuerRow($username, $createdBy, $activatedBy, $state, &$issuerId = null)
    {
        if($username) {

            return core_Type::getByName('varchar')->toVerbal($username);
        }

        if(isset($issuerId)) {
            $fixedIssuerName = core_Type::getByName('varchar')->toVerbal(core_Users::fetchField($issuerId, 'names'));

            return transliterate($fixedIssuerName);
        }

        $issuerName = deals_Helper::getIssuer($createdBy, $activatedBy, $issuerId);
        $issuerName = transliterate($issuerName);

        if(!Mode::isReadOnly() && in_array($state, array('pending', 'draft'))) {
            if(empty($issuerName)) {
                $hint = "За съставител ще се запише потребителя, контирал документа!";
            } else {
                $hint = "Ще бъде записан след активиране";
                $issuerName = "<span style='color:blue'>{$issuerName}</span>";
            }

            $issuerName = ht::createHint($issuerName, $hint);
        }

        return $issuerName;
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
        $userId = deals_Setup::get('ISSUER_USER', false, $createdBy);
        
        if (empty($userId)) {
            $selected = deals_Setup::get('ISSUER', false, $createdBy);
            $userId = ($selected == 'activatedBy') ? $activatedBy : $createdBy;
            $userId = (!core_Users::isContractor($userId)) ? $userId : $activatedBy;
        }
        
        $names = null;
        if (isset($userId)) {
            $names = core_Users::fetchField($userId, 'names');
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
                if(is_object($transportFeeRec) && $transportFeeRec->fee > 0){
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

                        $startMsg = !empty($discount) ? 'Цената (с приспадната отстъпка)' : 'Цената';
                        if($i == 0 && $percent >= 0){
                            $primeVerbal = core_Type::getByName('double(smartRound)')->toVerbal($price2Round * $quantityInPack);
                            $obj['hint'] = "{$startMsg} е под минималната за клиента";
                            $obj['hint'] .= "|*: {$primeVerbal} {$currencyId} |без ДДС|*{$msgSuffix}";
                            $obj['hintType'] = 'error';
                            
                            return $obj;
                        } 
                        
                        if($i == 1){
                            $primeVerbal = core_Type::getByName('double(smartRound)')->toVerbal($price2Round * $quantityInPack);
                            $obj['hint'] = ($percent < 0) ? "{$startMsg} е над очакваната за клиента" : "{$startMsg} е под очакваната за клиента";
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


    /**
     * Сумира артикулите с техните количества в модела
     *
     * @param mixed $detail           - детайл
     * @param int $masterId           - ид на мастъра
     * @param boolean $onlyStorable   - дали да са само складируеми
     * @param string $productFldName  - име на полето с ид-то на артикула
     * @param string $quantityFldName - име на полето с к-то на артикула
     *
     * @return array $products
     */
    public static function sumProductsByQuantity($detail, $masterId, $onlyStorable = false, $productFldName = 'productId', $quantityFldName = 'quantity')
    {
        $Detail = cls::get($detail);
        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = {$masterId}");
        $dQuery->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey={$productFldName}");
        if($Detail instanceof store_InternalDocumentDetail){
            $dQuery->XPR('totalQuantity', 'double', "SUM(#packQuantity * #quantityInPack)");
        } else {
            $dQuery->XPR('totalQuantity', 'double', "SUM(#{$quantityFldName})");
        }

        if($onlyStorable){
            $dQuery->where("#canStore = 'yes'");
        }
        $dQuery->groupBy($productFldName);
        $dQuery->show("{$productFldName},totalQuantity");
        $products = array();
        while($dRec = $dQuery->fetch()){
            $products[$dRec->{$productFldName}] = $dRec->totalQuantity;
        }

        return $products;
    }


    /**
     * Какъв е вашия реф от първия документ към нишката
     *
     * @param int $threadId
     * @return null|string
     */
    public static function getYourReffInThread($threadId)
    {
        $firstDocument = doc_Threads::getFirstDocument($threadId);
        if($firstDocument->isInstanceOf('deals_DealMaster')){
            $show = $firstDocument->isInstanceOf('sales_Sales') ? sales_Setup::get('SHOW_REFF_IN_SALE_THREAD') : purchase_Setup::get('SHOW_REFF_IN_PURCHASE_THREAD');
            if($show == 'yes') {
                $reff = $firstDocument->fetchField('reff');
                if(!empty($reff)) return $reff;
            }
        }

        return null;
    }


    /**
     * Помощна функция връщаща използваните файлове в един документ
     *
     * @param mixed $mvc                  - модел
     * @param stdClass|int $rec           - запис
     * @param array $masterRichtextFields - масив с полета от мастъра, където да се търсят файлове
     * @param array $detailRichtextFields - масив с полета от детайла, където да се търсят файлове
     * @return array $fhArr               - намерените файлове с ключ хендлъра им и стойност името
     */
    public static function getLinkedFilesInDocument($mvc, $rec, $masterRichtextFields = array(), $detailRichtextFields = array())
    {
        $fhArr = $showFields = array();
        $Class = cls::get($mvc);
        $rec = $Class->fetchRec($rec);

        // Ако има зададени мастър полета в които да се търсят файлове, извличат се
        $masterRichtextFields = arr::make($masterRichtextFields);
        foreach ($masterRichtextFields as $masterRichTextField){
            if(!empty($rec->{$masterRichTextField})){
                $fhArr += fileman_RichTextPlg::getFiles($rec->{$masterRichTextField});
            }
        }

        // Ако има детайл и има зададени полета от детайла, от които да се извличат файлове
        if(isset($mvc->mainDetail)){
            $detailRichtextFields = arr::make($detailRichtextFields);
            $Detail = cls::get($mvc->mainDetail);
            $dQuery = $Detail->getQuery();
            $dQuery->where("#{$Detail->masterKey} = {$rec->id}");
            if(countR($detailRichtextFields)){
                $showFields = $detailRichtextFields;
            }
            if(isset($Detail->productFld)){
                $showFields[] = $Detail->productFld;
            }
            if(countR($showFields)){
                $dQuery->show(implode(',', $showFields));

                // Извличане на файлове от детайла
                while($dRec = $dQuery->fetch()){
                    foreach ($detailRichtextFields as $detField){
                        if(!empty($dRec->{$detField})){
                            $fhArr += fileman_RichTextPlg::getFiles($dRec->{$detField});
                        }

                        // Ако има артикул извличат се файловете от него
                        if(isset($Detail->productFld)) {
                            if($Driver = cat_Products::getDriver($dRec->{$Detail->productFld})){
                                $fhArr += $Driver->getLinkedFiles($dRec->{$Detail->productFld});
                            }
                        }
                    }
                }
            }
        }

        // Връщане на намерените файлове, ако има такива
        return $fhArr;
    }


    /**
     * Помощна ф-я намираща максималния срок за доставка от детайлите
     *
     * @param core_Mvc $masterMvc           - модел на мастъра
     * @param mixed $masterId               - ид/запис на мастъра
     * @param core_Mvc $Detail              - детайла
     * @param core_Query $dQuery            - заявка към детайлите
     * @param int|null $defaultDeliveryTime - дефолтно време за доставка
     * @param string $productFieldName      - името на полето с ид-то на артикула
     * @param string $termFieldName         - името на полето със записания срок
     * @param string $quantityFld           - името на полето с к-то
     * @param string $storeFieldName        - името на полето за склада
     *
     * @return int|null $maxDeliveryTime - максималния срок за доставка от детайлите
     */
    public static function calcMaxDeliveryTime($masterMvc, $masterId, $Detail, core_Query $dQuery, $defaultDeliveryTime = null, $productFieldName = 'productId', $termFieldName = 'term', $quantityFld = 'quantity', $storeFieldName = null)
    {
        $deliveryTimes = array();
        $masterRec = $masterMvc->fetchRec($masterId);

        // Обиколка на детайлите
        while ($dRec = $dQuery->fetch()) {
            $term = null;

            // Ако има ръчно въведен срок - него
            if(isset($dRec->{$termFieldName})){
                $term = $dRec->{$termFieldName};
            } else {

                // Ако няма се търси производствения срок на артикула за нужното к-во
                if($productDeliveryTime = cat_Products::getDeliveryTime($dRec->{$productFieldName}, $dRec->{$quantityFld})){
                    $term = $productDeliveryTime;

                    // Ако има изчислена доставка и за нея има срок на доставка добавя се
                    if ($deliveryTime = sales_TransportValues::get($masterMvc, $dRec->{$Detail->masterKey}, $dRec->id)->deliveryTime) {
                        $term += $deliveryTime;
                    } elseif($defaultDeliveryTime){

                        // Ако няма за реда, но има дефолтна изчислява се тя
                        $term += $defaultDeliveryTime;
                    }
                }
            }

            if (isset($term)) {
                $deliveryTimes[] = $term;
            }
        }


        $maxDeliveryTime = null;
        if(countR($deliveryTimes)){

            // Взима се най-големия срок за доставка от детайлите
            $maxDeliveryTime = max($deliveryTimes);

            // Към тях се добавя нужното време за подготовка от склада (ако има)
            $storeId = isset($storeFieldName) ? $masterRec->{$storeFieldName} : null;
            $defaultShipmentTime = store_Stores::getShipmentPreparationTime($storeId);
            if(!empty($defaultShipmentTime)){
                $maxDeliveryTime += $defaultShipmentTime;
            }
        }

        return $maxDeliveryTime;
    }


    /**
     * Помощна ф-я за експорт на csv от лист изгледа на документите
     *
     * @param core_Mvc $mvc
     * @param core_FieldSet $fieldset
     * @return void
     */
    public static function getExportCsvProductFieldset($mvc, &$fieldset)
    {
        $fieldset->FLD('code', 'varchar', 'caption=Код,detailField');
        $fieldset->FLD($mvc->productFld, 'varchar', 'caption=Артикул,detailField');
        if(core_Packs::isInstalled('batch')){
            $fieldset->FLD('batch', 'varchar', 'caption=Партида,detailField');
        }
        $fieldset->FLD('packagingId', 'varchar', 'caption=Мярка,detailField');
        $fieldset->FLD('packQuantity', 'varchar', 'caption=Количество,detailField');
        if(!($mvc instanceof store_TransfersDetails)){
            $fieldset->FLD('packPrice', 'varchar', 'caption=Цена,detailField');
            $fieldset->FLD('discount', 'varchar', 'caption=Отстъпка,detailField');
            $fieldset->FLD('vatPercent', 'percent', 'caption=ДДС %,detailField');
            $fieldset->FLD('chargeVat', 'varchar', 'caption=ДДС режим,detailField');
        }
    }


    /**
     * Помощна ф-я разпъваща редовете с артикули от документа към данните за експорт в csv
     *
     * @param core_Master $mvc
     * @param stdClass $masterRec
     * @param array $expandedRecs
     * @return void
     */
    public static function addCsvExportProductRecs4Master($mvc, $masterRec, &$expandedRecs)
    {
        // Извличат се данните за артикулите от детайла му
        $Master = cls::get($mvc->Master);
        $csvFields = new core_FieldSet();
        $recs = cls::get('cat_Products')->getRecsForExportInDetails($Master, $masterRec, $csvFields, core_Users::getCurrent());
        $detailFields = array_combine(array_keys($csvFields->fields), array_keys($csvFields->fields));

        // Ако има артикули в детайла то дублират се мастър данните във всеки ред за всеки артикул
        if(countR($recs)){
            foreach ($recs as $dRec){
                $clone = clone $masterRec;
                foreach ($detailFields as $key){
                    $clone->{$key} = $dRec->{$key};
                }
                if(isset($clone->packagingId)){
                    $clone->packagingId = cat_UoM::fetchField($clone->packagingId, 'name');
                }

                $expandedRecs[] = $clone;
            }
        } else {
            $expandedRecs[] = clone $masterRec;
        }
    }


    /**
     * Помощна ф-я за парсиране на цена дали е въведена за подаденото к-во
     *
     * @param $priceInput
     * @param $quantity
     * @param $error
     * @return float|int|void|null
     */
    public static function isPrice4Quantity(&$priceInput, $quantity, &$error)
    {
        $price4Quantity = null;
        if(!empty($priceInput)){

            // Ако цената започва с "=" значи ще е цена за к-то
            $isPrice4Quantity = false;
            $packPrice = $priceInput;

            if(strpos($priceInput, '/') === strlen($priceInput) - 1){
                $isPrice4Quantity = true;
                $packPrice = rtrim($packPrice, '/');
            }

            // Проверка дали цената е валидна
            $Double = core_Type::getByName('double');
            $packPrice = $Double->fromVerbal($packPrice);
            if(!empty($Double->error)){
                $error = $Double->error;
                return;
            }

            // Ако е цената ще е за к-то
            $priceInput = $packPrice;
            if($isPrice4Quantity && !empty($quantity)){
                $price4Quantity = $packPrice / $quantity;
            }
        }

        return $price4Quantity;
    }


    /**
     * Проверка на транспортните данни в записа
     *
     * @param $weight
     * @param $netWeight
     * @param $tareWeight
     * @param $weightFieldName
     * @param $netWeightFieldName
     * @param $tareWeightFieldName
     * @return array|array[]
     */
    public static function checkTransData(&$weight, &$netWeight, &$tareWeight, $weightFieldName, $netWeightFieldName, $tareWeightFieldName)
    {
        $res = array('errors' => array());

        $weightIsCalced = $netWeightIsCalced = $tareWeightIsCalced = false;
        if(!isset($weight) && isset($netWeight) && isset($tareWeight)) {
            $weight = round($netWeight + $tareWeight, 4);
            $weightIsCalced = true;
        }

        if(!isset($netWeight) && isset($weight) && isset($tareWeight)) {
            $netWeight = round($weight - $tareWeight, 4);
            $netWeightIsCalced = true;
        }
        if(!isset($tareWeight) && isset($weight) && isset($netWeight)) {
            $tareWeight = round($weight - $netWeight, 4);
            $tareWeightIsCalced = true;
        }

        if(isset($weight) && isset($netWeight) && isset($tareWeight)){
            $calcedTare = $weight - $netWeight;
            $tareDiff = abs(round($calcedTare - $tareWeight, 3));
            if($tareDiff > 0.1) {
                $res['errors'][] = array('fields' => "{$weightFieldName},{$netWeightFieldName},{$tareWeightFieldName}", 'text' => 'Разликата между бруто и нето не отговаря на тарата');
            }
        }

        if(isset($weight) && isset($netWeight)){
            if($weight < $netWeight){
                $res['errors'][] = array('fields' => "{$weightFieldName},{$netWeightFieldName}", 'text' => 'Брутото е по-малко от нетото');
            }
        }

        if(isset($tareWeight) && isset($weight)){
            if($weight < $tareWeight){
                $res['errors'][] = array('fields' => "{$weightFieldName},{$tareWeightFieldName}", 'text' => 'Тарата е по-малко от брутото');
            }
        }

        if(countR($res['errors'])) {
            if($weightIsCalced) {
                $weight = null;
            }
            if($netWeightIsCalced) {
                $netWeight = null;
            }
            if($tareWeightIsCalced) {
                $tareWeight = null;
            }
        }

        return $res;
    }


    public static function getDiscountRow($calcedDiscount, $manualDiscount, $autoDiscount, $state)
    {
        $Percent = core_Type::getByName('percent');
        $autoDiscountVerbal = $Percent->toVerbal($autoDiscount);
        if(!in_array($state, array('draft', 'pending'))){
            $calcedDiscountVerbal = $Percent->toVerbal($calcedDiscount);
            if(empty($manualDiscount) && !empty($calcedDiscount) && empty($autoDiscount)) {
                $manualDiscount = $calcedDiscount;
            }
            $res = $Percent->toVerbal($manualDiscount);
            if($calcedDiscount != $manualDiscount){
                $res = ht::createHint($res, "Осреднена отстъпка|*: {$calcedDiscountVerbal}. |Авт.|*: {$autoDiscountVerbal}", 'notice', false);
            }
        } else {
            $res = $Percent->toVerbal($calcedDiscount);
            if(isset($autoDiscount)){
                $type = ($autoDiscount > 1) ? 'warning' : 'notice';
                if(isset($calcedDiscount)){
                    $middleDiscount = round((1 - (1 - $calcedDiscount) * (1 - $autoDiscount)), 8);
                    $middleDiscountVerbal = $Percent->toVerbal($middleDiscount);
                    $res = ht::createHint($res, "Осреднена отстъпка|*: {$middleDiscountVerbal}. |Авт.|*: {$autoDiscountVerbal}", $type, false);
                } else {
                    $res = ht::createHint($res, "Авт. отстъпка|*: {$autoDiscountVerbal}", $type, false);
                }
            }
        }

        return $res;
    }


    /**
     * Помощна ф-я показваща сумата в две валути
     *
     * @param mixed $amountRow    - сума за показване
     * @param double $amount      - чиста сума
     * @param date $date          - към коя дата
     * @param string $currencyId  - валута
     * @param int $countryId      - за коя държава
     * @param string $divider     - разделител
     * @return mixed|string
     */
    public static function displayDualAmount($amountRow, $amount, $date, $currencyId, $countryId, $divider = "<br />")
    {
        if(!in_array($currencyId, array('BGN', 'EUR')))  return $amountRow;
        $date = isset($date) ? dt::verbal2mysql($date, false) : dt::today();

        if($date > '2026-12-31' || $date <= '2025-07-08') return $amountRow;

        $bulgariaId = drdata_Countries::getIdByName('Bulgaria');
        if($currencyId == "EUR" && $countryId != $bulgariaId) return $amountRow;

        $amountRes = currency_Currencies::decorate($amountRow, $currencyId, true);

        $rate = currency_CurrencyRates::getRate($date, 'EUR', 'BGN');
        $decimals = str::countDecimals($amountRow, false);

        if($currencyId == 'BGN' && $date <= '2025-12-31') {
            $amountInEuro = round($amount, $decimals) / $rate;
            $amountInEuroRow = core_Type::getByName("double(decimals={$decimals})")->toVerbal($amountInEuro);

            return $amountRes . "<br />" . currency_Currencies::decorate($amountInEuroRow, 'EUR', true);
        } elseif($currencyId == 'EUR' && $date > '2025-12-31' && $date <= '2026-12-31') {

            $amountInBgn = round($amount, $decimals) * $rate;
            $amountInBgnRow = core_Type::getByName("double(decimals={$decimals})")->toVerbal($amountInBgn);

            return $amountRes . "<br />" . currency_Currencies::decorate($amountInBgnRow, 'BGN', true);
        }

        return $amountRow;
    }
}

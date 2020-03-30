<?php


/**
 * Модел "Изчисляване на навла"
 *
 *
 * @category  bgerp
 * @package   tcost
 *
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tcost_Fees extends core_Detail
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'trans_Fees';
    
    
    /**
     * Заглавие
     */
    public $title = 'Навла';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Sorting, plg_RowTools2, tcost_Wrapper, plg_AlignDecimals2, plg_SaveAndNew';
    
    
    /**
     * Ключ към core_Master
     */
    public $masterKey = 'feeId';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Държава и п. код';
    
    
    /**
     * Време за опресняване информацията при лист на събитията
     */
    public $refreshRowsTime = 5000;
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,tcost';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,tcost';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,tcost';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,tcost';
    
    
    /**
     * Полета, които се виждат
     */
    public $listFields = 'weight=|Тегло|* (|кг|*), price, secondPrice, thirdPrice, total, createdOn, createdBy';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'secondPrice,thirdPrice,total';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('feeId', 'key(mvc=tcost_FeeZones, select=name)', 'caption=Зона, mandatory, input=hidden,silent');
        $this->FLD('weight', 'double(min=0,smartRound)', 'caption=Правила за изчисление->Тегло, mandatory,unit=кг');
        $this->FLD('price', 'double(min=0)', 'caption=Стойност->Сума, mandatory,unit=без ДДС');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Стойност->Валута, mandatory');
        $this->FLD('secondPrice', 'double(min=0)', 'caption=Втора стойност->Стойност,silent,removeAndRefreshForm=secondCurrencyId,unit=без ДДС');
        $this->FLD('secondCurrencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'caption=Втора стойност->Валута');
        $this->FLD('thirdPrice', 'double(min=0)', 'caption=Трета стойност->Стойност 2,silent,removeAndRefreshForm=thirdCurrencyId,unit=без ДДС');
        $this->FLD('thirdCurrencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'caption=Трета стойност->Валута 2');
        $this->FNC('total', 'double');
        
        // Добавяне на уникални индекси
        $this->setDbUnique('feeId,weight');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
    }
    
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#weight');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if ($form->isSubmitted()) {
            if ((!empty($rec->secondPrice) && empty($rec->secondCurrencyId)) || (!empty($rec->secondCurrencyId) && empty($rec->secondPrice))) {
                $form->setError('secondPrice,secondCurrencyId', 'Двете полета трябва или да са попълнени или да не са');
            }
            
            if ((!empty($rec->thirdPrice) && empty($rec->thirdCurrencyId)) || (!empty($rec->thirdCurrencyId) && empty($rec->thirdPrice))) {
                $form->setError('thirdPrice,thirdCurrencyId', 'Двете полета трябва или да са попълнени или да не са');
            }
        }
    }
    
    
    /**
     * Връща името на транспортната зона според държавата, усложието на доставката и п.Код
     *
     * @param null|stdClass $zone    - запис на зона или NULL ако не е намерена 
     * @param float  $totalWeight    - Посоченото тегло
     * @param int    $singleWeight
     *
     * @return int|array - Ако не може да бъде намерена зона, в която принадлежи пакета
     *                   [0] - Обработената цена, за доставката на цялото количество
     *                   [1] - Резултат за подадената единица $singleWeight
     *                   [2] - Id на зоната
     *                   [3] - Срока на доставка з
     */
    public static function calcFee($zone, $totalWeight, $singleWeight = 1)
    {
        // Общото тегло не трябва да е по-малко от еденичното
        $totalWeight = max($totalWeight, $singleWeight);
        expect(is_numeric($totalWeight) && is_numeric($singleWeight) && $totalWeight > 0, $totalWeight, $singleWeight);
        
        // Ако не се намери зона се връща 0
        if (is_null($zone)) {
            
            return cond_TransportCalc::ZONE_FIND_ERROR;
        }
        
        // Асоциативен масив от тегло(key) и цена(value) -> key-value-pair
        $arrayOfWeightPrice = array();
        
        $weightsLeft = null;
        $weightsRight = INF;
        $smallestWeight = null;
        $biggestWeight = null;
        
        // Преглеждаме базата за зоните, чиито id съвпада с въведенето
        $query = self::getQuery();
        $query->where(array('#feeId = [#1#]', $zone['zoneId']));
        $query->orderBy('#weight');
        
        while ($rec = $query->fetch()) {
            // Слагаме получените цени за по-късно ползване в асоциативния масив
            $price = self::getTotalPrice($rec);
            $arrayOfWeightPrice[round($rec->weight)] = $price;
        }
        
        // дотук имаме масив Тегло -> Сума
        //Създаваме вече индексиран масив от ключовете на по горния асоциативен маскив
        $indexedArray = array_keys($arrayOfWeightPrice);
        if(!countR($indexedArray)){
            
            return cond_TransportCalc::EMPTY_WEIGHT_ZONE_FEE;
        }
        
        // Разглеждаме 4 случая
        // Търсеното тегло е по-малко от най-малкото в масива. Тогава Общата цена е най-малката
        $minWeight = min($indexedArray);
        $maxWeight = max($indexedArray);
        $totalWeight = round($totalWeight, 2);
        
        if ($totalWeight < $minWeight) {
            $finalPrice = $arrayOfWeightPrice[$minWeight];
        } elseif ($totalWeight > $maxWeight) {
            $finalPrice = $arrayOfWeightPrice[$maxWeight] * ($totalWeight / $maxWeight);
        } elseif (isset($arrayOfWeightPrice[$totalWeight])) {
            $finalPrice = $arrayOfWeightPrice[$totalWeight];
        } else {
            $x = $totalWeight;
            foreach ($arrayOfWeightPrice as $x2 => $y2) {
                if (isset($x1) && $x > $x1 && $x < $x2) {
                    $b = ($y1 - $y2) / ($x1 - $x2);
                    $a = $y1 - $x1 * $b;
                    $y = $a + $b * $x;
                    $finalPrice = $y;
                    break;
                }
                $x1 = $x2;
                $y1 = $y2;
            }
        }
        
        // Резултата се получава, като получената цена разделяме на $totalweight и умножаваме по $singleWeight.
        $finalPrice = round($finalPrice, 2);
        if ($totalWeight) {
            $result = round($finalPrice / $totalWeight * $singleWeight, 2);
        } else {
            $result = 0;
        }
        
        // Връща се получената цена и отношението цена/тегло в определен $singleWeight и зоната към която принадлежи
        return array($finalPrice, $result, $zone['zoneId'], $zone['deliveryTime']);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        static $lastPrice, $lastKgPrice;
        
        $rec->total = self::getTotalPrice($rec);
        $row->total = $mvc->getFieldType('total')->toVerbal($rec->total);
        
        $kgPrice = $rec->total / $rec->weight;
        
        if ($lastPrice >= $rec->price) {
            $row->ROW_ATTR = array('style' => 'color:red;');
        } elseif (isset($lastKgPrice) && $lastKgPrice <= $kgPrice) {
            $row->ROW_ATTR = array('style' => 'color:#ff9900');
            $max = round($lastKgPrice * $rec->weight);
            $row->priceHint = $max;
        }
        
        $lastKgPrice = $kgPrice;
        
        $lastPrice = $rec->price;
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        // Промяна на имената на колоните
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
        $data->listFields['price'] = 'Стойност|* |без ДДС|*->Сума';
        $data->listFields['secondPrice'] = 'Стойност|* |без ДДС|*->Втора сума';
        $data->listFields['thirdPrice'] = 'Стойност|* |без ДДС|*->Трета сума';
        $data->listFields['total'] = "Стойност|* |без ДДС|*->Общо|* (<small>{$baseCurrencyCode}</small>)";
        
        if (!countR($data->rows)) {
            
            return;
        }
        $unsetTotal = true;
        
        // За всеки запис
        foreach ($data->rows as $id => &$row) {
            $rec = &$data->recs[$id];
            
            // Зад сумите, се залепва валутата им
            if ($row->priceHint) {
                $row->price = "<span title='Не трябва да е повече от {$row->priceHint}'>" . $row->price . '</span>';
            }
            $row->price .= " <span class='cCode'>{$rec->currencyId}</span>";
            if (!empty($rec->secondPrice) && !empty($rec->secondCurrencyId)) {
                $row->secondPrice .= " <span class='cCode'>{$rec->secondCurrencyId}</span>";
            }
            
            if (!empty($rec->thirdPrice) && !empty($rec->thirdCurrencyId)) {
                $row->thirdPrice .= " <span class='cCode'>{$rec->thirdCurrencyId}</span>";
            }
            
            // Ако общата сума е различна от първата сума, ще се показва общата сума
            if (trim($rec->price) != trim($rec->total)) {
                $unsetTotal = false;
            }
        }
        
        // Ако няма разлика емжду общата сума и първата сума, колоната за обща сума не се показва
        if ($unsetTotal === true) {
            unset($data->listFields['total']);
        }
    }
    
    
    /**
     * Намира сумата на реда в основна валута без ДДС
     *
     * @param stdClass $rec - запис
     *
     * @return float $total - сумата на реда в основна валута без ДДС
     */
    private static function getTotalPrice($rec)
    {
        // Обръщане на сумата в основна валута
        $price1 = currency_CurrencyRates::convertAmount($rec->price, null, $rec->currencyId);
        
        // Ако има втора сума обръща се в основна валута и се събира
        $price2 = 0;
        if (!empty($rec->secondPrice) && !empty($rec->secondCurrencyId)) {
            $price2 = currency_CurrencyRates::convertAmount($rec->secondPrice, null, $rec->secondCurrencyId);
        }
        
        // Ако има трета сума
        $price3 = 0;
        if (!empty($rec->thirdPrice) && !empty($rec->thirdCurrencyId)) {
            $price3 = currency_CurrencyRates::convertAmount($rec->thirdPrice, null, $rec->thirdCurrencyId);
        }
        
        // Събиране на сумите
        $total = $price1 + $price2 + $price3;
        
        // Връщане на общата сума
        return $total;
    }
}

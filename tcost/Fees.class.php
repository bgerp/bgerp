<?php


/**
 * Модел "Изчисляване на навла"
 *
 *
 * @category  bgerp
 * @package   tcost
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
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
    public $title = "Навла";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, tcost_Wrapper, plg_AlignDecimals2";


    /**
     * Ключ към core_Master
     */
    public $masterKey = 'feeId';


    /**
     * Единично заглавие
     */
    public $singleTitle = "държава и п.Код";


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
     public $listFields  = "weight=|Тегло|* (|кг|*), price, secondPrice, thirdPrice, total, createdOn, createdBy";


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
        $this->setDbUnique("feeId,weight");
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
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
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	if($form->isSubmitted()){
    		if((!empty($rec->secondPrice) && empty($rec->secondCurrencyId)) || (!empty($rec->secondCurrencyId) && empty($rec->secondPrice))){
    			$form->setError('secondPrice,secondCurrencyId', 'Двете полета трябва или да са попълнени или да не са');
    		}
    		
    		if((!empty($rec->thirdPrice) && empty($rec->thirdCurrencyId)) || (!empty($rec->thirdCurrencyId) && empty($rec->thirdPrice))){
    			$form->setError('thirdPrice,thirdCurrencyId', 'Двете полета трябва или да са попълнени или да не са');
    		}
    	}
    }
    
    
    /**
     * Връща името на транспортната зона според държавата, усложието на доставката и п.Код
     * 
     * @param int $deliveryTermId - ид на условието на доставка
     * @param int $countryId - id на съотверната държава
     * @param string $pCode - пощенски код
     * @param double $totalWeight - Посоченото тегло
     * @param int $singleWeight
     * 
     * @return int|array - Ако не може да бъде намерена зона, в която принадлежи пакета
     * [0] - Обработената цена, за доставката на цялото количество
     * [1] - Резултат за подадената единица $singleWeight
     * [2] - Id на зоната
     * [3] - Срока на доставка з
     */
    public static function calcFee($deliveryTermId, $countryId, $pCode, $totalWeight, $singleWeight = 1)
    {
    	// Общото тегло не трябва да е по-малко от еденичното
    	$totalWeight = max($totalWeight, $singleWeight);
    	expect(is_numeric($totalWeight) && is_numeric($singleWeight) && $totalWeight > 0, $totalWeight, $singleWeight);
    	
        // Определяне на зоната на транспорт, за зададеното условие на доставка
        $zone = tcost_Zones::getZoneIdAndDeliveryTerm($deliveryTermId, $countryId, $pCode);
        
        // Ако не се намери зона се връща 0
        if(is_null($zone)) return tcost_CostCalcIntf::ZONE_FIND_ERROR;

        // Асоциативен масив от тегло(key) и цена(value) -> key-value-pair
        $arrayOfWeightPrice = array();

        $weightsLeft = NULL;
        $weightsRight = INF;
        $smallestWeight = NULL;
        $biggestWeight = NULL;

        // Преглеждаме базата за зоните, чиито id съвпада с въведенето
        $query = self::getQuery();
        $query->where(array("#feeId = [#1#]", $zone['zoneId']));
        $query->orderBy('#weight');
        
        while($rec = $query->fetch()){
            // Определяме следните променливи - $weightsLeft, $weightsRight, $smallestWeight, $biggestWeight
            if (!isset($smallestWeight) || $smallestWeight > $rec->weight) {
                $smallestWeight = $rec->weight;
            }
            if (!isset($biggestWeight) || $biggestWeight < $rec->weight) {
                $biggestWeight = $rec->weight;
            }
            if($rec->weight >= $weightsLeft && $rec->weight <= $totalWeight){
                $weightsLeft = $rec->weight;
            }
            if ($rec->weight <= $weightsRight && $rec->weight >= $totalWeight) {
                $weightsRight = $rec->weight;
            }

            // Слагаме получените цени за по-късно ползване в асоциативния масив
            $price = self::getTotalPrice($rec);
            $arrayOfWeightPrice[$rec->weight] = $price;
        }
       
        //Създаваме вече индексиран масив от ключовете на по горния асоциативен маскив
        $indexedArray = array_keys($arrayOfWeightPrice);

        //Покриване на специалните случаи, които въведеното тегло е най-малко
        if(!isset($weightsLeft)){
            $weightsLeft = 0;
        }

        // Покриване на специалните случаи, които въведеното тегло е най-голямо
        if($biggestWeight < $weightsRight){
            end($indexedArray);
            $key = key($indexedArray);
            $weightsRight = $indexedArray[$key];
            $weightsLeft = $indexedArray[$key - 1];
        }

        $finalPrice = NULL;
        //Ако е въведеното тегло е по-малко от най-малкото тегло в базата,то трябва да се върне отношение 1:1
       
        //Ако съществува точно такова тегло, трябва да се върне цената директно цената за него
        if($totalWeight == $weightsLeft){
            $finalPrice = $arrayOfWeightPrice[$weightsLeft];
        } elseif($totalWeight == $smallestWeight){
            $finalPrice =  $totalWeight;
        } else{
        	//Ако нищо от посоченото по-горе не се осъществи значи апроксимираме
            
            /** Формули за сметката
             * y = price
             * x = weight
             * y1 = a*x1 + b
             * y2 = a*x2 + b
             * a = (y1 - y2) / (x1 - x2)
             * b = y1 - ((y1 - y2) / (x1 - x2) * x1);
             * y3 = a*x3 + b // y3 = finalPrice
             * Възможно е float да се запази като string, така че ги преобразяваме
             */

            $weightsLeft = floatval($weightsLeft);
            $weightsRight = floatval($weightsRight);
            $priceLeft = floatval($arrayOfWeightPrice[$weightsLeft]);
            $priceRight = floatval($arrayOfWeightPrice[$weightsRight]);

            if($weightsLeft == 0) {
                $priceLeft = $priceRight;
            }

            $delimiter = $weightsLeft - $weightsRight;
            if(!$delimiter) return tcost_CostCalcIntf::DELIMITER_ERROR;
            
            $a = ($priceLeft - $priceRight) / $delimiter;
            $b = $priceLeft - (($priceLeft - $priceRight) / $delimiter * $weightsLeft);

            $finalPrice = $a * $totalWeight + $b;
        }
        
        // Резултата се получава, като получената цена разделяме на $totalweight и умножаваме по $singleWeight.
        $finalPrice = round($finalPrice, 2);
        $result = round($finalPrice / $totalWeight * $singleWeight, 2);

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
    	$rec->total = self::getTotalPrice($rec);
    	$row->total = $mvc->getFieldType('total')->toVerbal($rec->total);
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
    	// Промяна на имената на колоните
    	$baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
    	$data->listFields['price'] = "Стойност|* |без ДДС|*->Сума";
    	$data->listFields['secondPrice'] = "Стойност|* |без ДДС|*->Втора сума";
    	$data->listFields['thirdPrice'] = "Стойност|* |без ДДС|*->Трета сума";
    	$data->listFields['total'] = "Стойност|* |без ДДС|*->Общо|* (<small>{$baseCurrencyCode}</small>)";
    	
    	if(!count($data->rows)) return;
    	$unsetTotal = TRUE;
    	
    	// За всеки запис
    	foreach ($data->rows as $id => &$row){
    		$rec = &$data->recs[$id];
    		
    		// Зад сумите, се залепва валутата им
    		$row->price .=  " <span class='cCode'>{$rec->currencyId}</span>";
    		if(!empty($rec->secondPrice) && !empty($rec->secondCurrencyId)){
    			$row->secondPrice .=  " <span class='cCode'>{$rec->secondCurrencyId}</span>";
    		}
    		
    		if(!empty($rec->thirdPrice) && !empty($rec->thirdCurrencyId)){
    			$row->thirdPrice .=  " <span class='cCode'>{$rec->thirdCurrencyId}</span>";
    		}
    		
    		// Ако общата сума е различна от първата сума, ще се показва общата сума
    		if(trim($rec->price) != trim($rec->total)){
    			$unsetTotal = FALSE;
    		}
    	}
    	
    	// Ако няма разлика емжду общата сума и първата сума, колоната за обща сума не се показва
    	if($unsetTotal === TRUE){
    		unset($data->listFields['total']);
    	}
    }
    
    
    /**
     * Намира сумата на реда в основна валута без ДДС
     * 
     * @param stdClass $rec  - запис
     * @return double $total - сумата на реда в основна валута без ДДС
     */
    private static function getTotalPrice($rec)
    {
    	// Обръщане на сумата в основна валута
    	$price1 = currency_CurrencyRates::convertAmount($rec->price, NULL, $rec->currencyId);
    	
    	// Ако има втора сума обръща се в основна валута и се събира
    	$price2 = 0;
    	if(!empty($rec->secondPrice) && !empty($rec->secondCurrencyId)){
    		$price2 = currency_CurrencyRates::convertAmount($rec->secondPrice, NULL, $rec->secondCurrencyId);
    	}
    	
    	// Ако има трета сума
    	$price3 = 0;
    	if(!empty($rec->thirdPrice) && !empty($rec->thirdCurrencyId)){
    		$price3 = currency_CurrencyRates::convertAmount($rec->thirdPrice, NULL, $rec->thirdCurrencyId);
    	}
    	
    	// Събиране на сумите
    	$total = $price1 + $price2 + $price3;
    	
    	// Връщане на общата сума
    	return $total;
    }
}
<?php


/**
 * Модел "Изчисляване на навла"
 *
 *
 * @category  bgerp
 * @package   tcost
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
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
     public $listFields  = "weight, price, createdOn, createdBy";


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('feeId', 'key(mvc=tcost_FeeZones, select=name)', 'caption=Зона, mandatory, input=hidden,silent');
        $this->FLD('weight', 'double(min=0)', 'caption=Правила за изчисление->Тегло, mandatory');
        $this->FLD('price', 'double(min=0)', 'caption=Правила за изчисление->Цена, mandatory');
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
     */
    public static function calcFee($deliveryTermId, $countryId, $pCode, $totalWeight, $singleWeight = 1)
    {
    	// Общото тегло не трябва да е по-малко от еденичното
    	$totalWeight = max($totalWeight, $singleWeight);
    	expect(is_numeric($totalWeight) && is_numeric($singleWeight) && $totalWeight > 0, $totalWeight, $singleWeight);
    	
        // Определяне на зоната на транспорт, за зададеното условие на доставка
        $zone = tcost_Zones::getZoneIdAndDeliveryTerm($deliveryTermId, $countryId, $pCode);
		
        // Ако не се намери зона се връща 0
        if(is_null($zone)) return tcost_CostCalcIntf::CALC_ERROR;

        // Асоциативен масив от тегло(key) и цена(value) -> key-value-pair
        $arrayOfWeightPrice = array();

        $weightsLeft = NULL;
        $weightsRight = INF;
        $smallestWeight = NULL;
        $biggestWeight = NULL;

        // Преглеждаме базата за зоните, чиито id съвпада с въведенето
        $query = self::getQuery();
        $query->where(array("#feeId = [#1#]", $zone['zoneId']));

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

            //Слагаме получените цени за по-късно ползване в асоциативния масив
            $arrayOfWeightPrice[$rec->weight] = $rec->price;
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

            $a = ($priceLeft - $priceRight) / ($weightsLeft - $weightsRight);
            $b = $priceLeft - (($priceLeft - $priceRight) / ($weightsLeft - $weightsRight) * $weightsLeft);

            $finalPrice = $a * $totalWeight + $b;
        }

        // Резултата се получава, като получената цена разделяме на $totalweight и умножаваме по $singleWeight.
        $result = round($finalPrice / $totalWeight * $singleWeight, 2);

        // Връща се получената цена и отношението цена/тегло в определен $singleWeight и зоната към която принадлежи
        return array($finalPrice, $result, $zone['zoneId']);
    }
}
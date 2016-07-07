<?php


/**
 * Модел "Изчисляване на налва"
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_Fees extends core_Detail
{


    /**
     * Заглавие
     */
    public $title = "Навла";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, trans_Wrapper, plg_AlignDecimals2";


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
    var $refreshRowsTime = 5000;


    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,admin,trans';


    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,admin,trans';


    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,admin,trans';


    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,admin,trans';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,admin,trans';


    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,admin,trans';


    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,admin,trans';


    /**
     * Полета, които се виждат
     */
     public $listFields  = "id, weight, price, createdOn, createdBy";


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('feeId', 'key(mvc=trans_FeeZones, select=name)', 'caption=Зона, mandatory, input=hidden,silent');
        $this->FLD('weight', 'double(min=0)', 'caption=Правила за изчисление->Тегло, mandatory');
        $this->FLD('price', 'double(min=0)', 'caption=Правила за изчисление->Цена, mandatory');
    }


    /**
     * Връща името на транспортната зона според държавата, усложието на доставката и п.Код
     * 
     * @param int $countryId - id на съотверната държава
     * @param string $pCode - пощенски код
     * @param double $totalWeight - Посоченото тегло
     * @param int $singleWeight
     * 
     * @return int|array - Ако не може да бъде намерена зона, в която принадлежи пакета
     * [0] - Обработената цена
     * [1] - Резултат за подадената единица $singleWeight
     * [2] - Id на зоната
     */
    public static function calcFee($countryId, $pCode, $totalWeight, $singleWeight = 1)
    {
        expect(is_numeric($totalWeight) && is_numeric($singleWeight) && $totalWeight > 0, $totalWeight, $singleWeight);

        //Определяне на зоната на транспорт
        $zone = trans_Zones::getZoneIdAndDeliveryTerm($countryId, $pCode);

        //Ако не се намери зона се връща 0
        if($zone == null){
            
            return 0;
        }

        expect($zone['zoneId'] > 0);

        //Асоциативен масив от тегло(key) и цена(value) -> key-value-pair
        $arrayOfWeightPrice = array();

        $weightsLeft = null;
        $weightsRight = INF;
        $smallestWeight = null;
        $biggestWeight = null;

        //Преглеждаме базата за зоните, чиито id съвпада с въведенето
        $query = trans_Fees::getQuery();
            $query->where(array("#feeId = [#1#]", $zone['zoneId']));

        while($rec = $query->fetch()){
            //Определяме следните променливи - $weightsLeft, $weightsRight, $smallestWeight, $biggestWeight
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
        if (!isset($weightsLeft)){
            $weightsLeft = 0;
        }

        // Покриване на специалните случаи, които въведеното тегло е най-голямо
        if ($biggestWeight < $weightsRight){
            end($indexedArray);
            $key = key($indexedArray);
            $weightsRight = $indexedArray[$key];
            $weightsLeft = $indexedArray[$key - 1];
        }


        $finalPrice = null;
        //Ако е въведеното тегло е по-малко от най-малкото тегло в базата,то трябва да се върне отношение 1:1

        //Ако съществува точно такова тегло, трябва да се върне цената директно цената за него
        if($totalWeight == $weightsLeft){
            $finalPrice = $arrayOfWeightPrice[$weightsLeft];
        }
        elseif($totalWeight == $smallestWeight){
            $finalPrice =  $totalWeight;
        }
        //Ако нищо от посоченото по-горе не се осъществи значи апроксимираме
        else{
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


        /*
         * Резултата се получава, като получената цена разделяме на $totalweight и умножаваме по $singleWeight.
         */
        $result = $finalPrice/ $totalWeight * $singleWeight;

        /*
         * Връща се получената цена и отношението цена/тегло в определен $singleWeight и зоната към която принадлежи
         */
        return array($finalPrice, $result, $zone['zoneId']);
    }
}
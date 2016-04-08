<?php

class trans_Fees extends core_Manager
{
    public $title = "Налва";
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, trans_Wrapper";



    public function description()
    {
        $this->FLD('zoneId', 'key(mvc=trans_ZoneNames, select=name)', 'caption=Зона, mandatory');
        $this->FLD('weight', 'double(min=0)', 'caption=Тегло, mandatory');
        $this->FLD('price', 'double(min=0)', 'caption=Цена, mandatory');
    }

    /**
     * Връща името на транспортната зона според държавата, усложието на доставката и п.Код
     * @param int       $deliveryTerm       Условие на доставка
     * @param int       $countryId          id на съотверната държава
     * @param string    $pCode              пощенски код
     *
     * @param double    $totalWeight        Посоченото тегло
     * @param int       $singleWeight
     *
     * @return array[0] $finalPrice         Обработената цена
     * @return array[1] $result             Резултат за подадената единица $singleWeight
     * @return array[1] $zoneId             Id на зоната
     */

    public static function calcFee($deliveryTerm, $countryId, $pCode, $totalWeight, $singleWeight = 1)
    {
        expect(is_numeric($totalWeight) && is_numeric($singleWeight) && $totalWeight > 0, $totalWeight, $singleWeight);

        //Определяне на зоната на транспорт
        //bp($deliveryTerm, $countryId, $pCode);
        $zoneId = trans_Zones::getZoneId($deliveryTerm, $countryId, $pCode);
//        bp($zoneId);
        //Асоциативен масив от тегло(key) и цена(value) -> key-value-pair
        $arrayOfWeightPrice = array();

        $weightsLeft = null;
        $weightsRight = INF;
        $smallestWeight = null;
        $biggestWeight = null;

        //Преглеждаме базата за зоните, чиито id съвпада с въведенето
        $query = trans_Fees::getQuery();
            expect($zoneId > 0);

            $query->where(['#zoneId = [#1#]', $zoneId]);

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
        if($totalWeight == $smallestWeight){
            $finalPrice =  $totalWeight;
        }
        //Ако съществува точно такова тегло, трябва да се върне цената директно цената за него
        elseif($totalWeight == $weightsLeft){
            $finalPrice = $arrayOfWeightPrice[$weightsLeft];
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
             */
            //Възможно е float да се запази като string, така че ги преобразяваме
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


        return array($finalPrice, $result, $zoneId);
    }
}
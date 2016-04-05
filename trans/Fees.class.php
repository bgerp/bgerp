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


    public static function calcFee($deliveryTerm, $countryId, $pcode, $totalWeight, $singleWeight = 1)
    {
        $zoneId = trans_Zones::getZoneId($deliveryTerm, $countryId, $pcode);
//        bp(trans_ZoneNames::getVerbal_($zoneId, 'name'));
        $query = trans_Fees::getQuery();
        $query->where(['#zoneId = [#1#]', $zoneId]);

//        $weights = array();
//        $prices = array();

        $arrayOfWeightPrice = array();
        //Adding all weights to an array
        $weightsLeft = null;
        $weightsRight = 999999999;
        $smallestWeight = null;
        $biggestWeight = null;
        while($rec = $query->fetch()){
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
            $arrayOfWeightPrice[$rec->weight] = $rec->price;
        }
        if (!isset($weightsLeft)){
            $weightsLeft = $totalWeight;
        }
        if ($biggestWeight < $weightsRight){
            $weightsRight = $totalWeight;
        }


        $finalPrice = null;
        if($totalWeight == $smallestWeight){
            $finalPrice =  $totalWeight;
        }
        elseif($totalWeight == $weightsLeft){
            $finalPrice = $arrayOfWeightPrice[$weightsLeft];
        }
        elseif($totalWeight == $weightsRight){
            $finalPrice = $arrayOfWeightPrice[$weightsRight];
        }
        else{
            /**
             * y = price
             * x = weight
             * y1 = a*x1 + b
             * y2 = a*x2 + b
             * a = (y1 - y2) / (x1 - x2)
             * b = y1 - ((y1 - y2) / (x1 - x2) * x1);
             * y3 = a*x3 + b // y3 = finalPrice
             */
            $priceLeft = $arrayOfWeightPrice[$weightsLeft];
            $priceRight = $arrayOfWeightPrice[$weightsRight];

            $a = ($priceLeft - $priceRight) / ($weightsLeft - $weightsRight);
            $b = $priceLeft - (($priceLeft - $priceRight) / ($weightsLeft - $weightsRight) * $weightsLeft);

            $y3 = $a * $totalWeight + $b;
            bp($priceRight, $priceLeft,$weightsRight, $weightsLeft);
            $finalPrice = $y3;
        }

        return $weightsLeft . '|' . $weightsRight . '|' . $totalWeight . '|' . $finalPrice;

    }
}
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
        $query = trans_Fees::getQuery();
        $query->where(['#zoneId = [#1#]', $zoneId]);

        $weights = array();
        $prices = array();

        //Adding all weights to an array
        while($rec = $query->fetch()){
            array_push($weights, $rec->weight);
            array_push($prices, $rec->price);
        }

        //Adding the $totalWeight to the same array
        array_push($weights, $totalWeight);
        //Sorting the array
        sort($weights);
        //Finding the index where it is placed
        $key = array_search($totalWeight, $weights);

        $weightLeft = $weights[$key - 1];
        $weightRight = $weights[$key + 1];


        //Needed for special case with last index
        $lastIndex = count($weights) - 1;


        if(key == 0){
            //Special case where it is first index

        }
        elseif($key == $lastIndex){
            //Special case where it is last index

        }
        else{
            //W = weight
            //P = Price
            // (W1 / W3) * (P1 - (P1*W1 - P2*W2) / (W1 - W2)) + (P1*W1 - P2*W2) / (W1 - W2)

            $finalPrice = ($weightLeft / $totalWeight) * ($priceLeft - ( $priceLeft * $weightLeft - $priceRight * $weightRight) /
                    ($weightLeft - $weightRight)) + ($priceLeft * $weightLeft - $priceRight * $weightRight) / ($weightLeft - $weightRight);


        }

        bp($weights, $key);
    }
}
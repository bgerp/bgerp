<?php

class trans_Zones extends core_Manager
{
    public $title = "Транспортни зони";
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, trans_Wrapper";


    public function description()
    {

        $this->FLD('zoneId', 'key(mvc=trans_ZoneNames, select=name)', 'caption=Зона, recently, mandatory');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms, select = codeName)', 'caption=Условие на доставка, mandatory');
        $this->FLD('countryId', 'key(mvc = drdata_Countries, select = letterCode2)', 'caption=Държава, mandatory');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode');
        $this->FLD('totalWeight', 'double(Min=0)', 'caption=Тегло за изчисление,recently');
        $this->FLD('singleWeight', 'double(Min=0)', 'caption=Брой за връщане');

        $this->setDbUnique("deliveryTermId,countryId, pCode");

    }

    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn("Изчисление на разходи по пратка в зона", array("trans_Zones", "test"), "ef_icon=img/16/arrow_out.png");
    }

    /**
     * Тестване на направеното
     */
    public function act_Test()
    {
        //Тестовни примери
        /*
         * $a[] = trans_Fees::calcFee(5, 262, 8000, 0);
         * $a[] = trans_Fees::calcFee(5, 262, 8000, -1);
         * $a[] = trans_Fees::calcFee(5, 262, 8000, 1000000);
         * $a[] = trans_Fees::calcFee(5, 262, 8000, 400);
         * $a[] = trans_Fees::calcFee(5, 262, 8000, 2000);
         * $a[] = trans_Fees::calcFee(5, 262, 8000, "Chris");
         */



        // Вземаме съответстващата форма на този модел
        $form = self::getForm();

        // Премахваме полето "name", защото то тррябва да е резултат от теста, а не да се въвежда
        unset($form->fields['zoneId']);

        // Въвеждаме формата от Request (тази важна стъпка я бяхме пропуснали)
        $form->input();
        $form->setDefault('singleWeight', 1);
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            try {

                $result = trans_Fees::calcFee($rec->deliveryTermId, $rec->countryId, $rec->pCode, $rec->totalWeight, $rec->singleWeight);
                $zoneName = trans_ZoneNames::getVerbal_($result[2], 'name');
                $form->info = "Цената за " . $rec->singleWeight . " на " . $rec->totalWeight . " броя от този пакет ще струва ". round($result[1], 4).
                    ",a всички ".  $rec->totalWeight . " ще струват " . round($result[0], 4) . ". Пратката попада в " . $zoneName ;

            } catch(core_exception_Expect $e) {
                $form->setError("zoneId, deliveryTermId, countryId", "Не може да се изчисли по зададените данни, вашата пратка не попада в никоя зона");
            }
        }
        $form->title = 'Пресмятане на налва';
        $form->toolbar->addSbBtn('Запис');
        return $this->renderWrapping($form->renderHTML());
    }



    /**
     * Връща името на транспортната зона според държавата, усложието на доставката и п.Код
     * @param int       $deliveryTermId Условие на доставка
     * @param int       $countryId      id на съотверната държава
     * @param string    $pCode          пощенски код
     *
     * @return string                   име на зоната
     */

    public static function getZoneId($deliveryTermId, $countryId, $pCode)
    {
        $query = self::getQuery();
        $query->where(array('#deliveryTermId = [#1#] AND #countryId = [#2#] ', $deliveryTermId, $countryId));
        $bestSimilarityCount = 0;
        $bestZone = -1;
        while($rec = $query->fetch()) {
            $similarityCount = self::strNearPCode($pCode, $rec->pCode);

            if ($similarityCount > $bestSimilarityCount) {
                $bestSimilarityCount = $similarityCount;
                $bestZone = $rec->zoneId;
            }

        }
        return $bestZone;
    }

    private static function strNearPCode($pc1, $pc2)
    {

        // Finding the smaller length of the two
        $cycleNumber = min(strlen($pc1), strlen($pc2));

        for($i= 0; $i<$cycleNumber; $i++)
        {
            if($pc1{$i} != $pc2{$i}) {
                return $i;
            }
        }
        return strlen($pc1);
    }
}
<?php


class trans_Zones extends core_Manager
{
    public $title = "Транспортни зони";
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, trans_Wrapper";


    public function description()
    {
        $this->FLD('name', 'varchar(16)', 'caption=Зона, mandatory');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms, select = codeName)', 'caption=Условие на доставка, mandatory');
        $this->FLD('countryId', 'key(mvc = drdata_Countries, select = letterCode2)', 'caption=Държава, mandatory');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode');

        $this->setDbUnique("name, deliveryTermId,countryId, pCode");
    }

    public function act_Test()
    {
        bp(self::getZoneName(5, 266, '8366'));
        $form = self::getForm();

        if($form->isSubmitted() || 1) {
            $rec = $form->rec;
            bp(self::getZoneName($rec->deliveryTermId, $rec->countryId, $rec->pCode));
        }
        $form->toolbar->addSbBtn('Запис', 'hjgh');
        $form->setField('name', 'input = none');
        return $form->renderHTML();
//        $deliveryTermId = Request::get('deliveryTermId');
//        $countryId = Request::get();
//        $pCode = Request::get();
    }
    /**
     * Връща името на транспортната зона според държавата, усложието на доставката и п.Код
     * @param int       $deliveryTermId Условие на доставка
     * @param int       $countryId      id на съотверната държава
     * @param string    $pCode          пощенски код
     *
     * @return string                   име на зоната
     */
    public function getZoneName($deliveryTermId, $countryId, $pCode)
    {
        $query = self::getQuery();
        $query->where(['#deliveryTermId = [#1#] AND #countryId = [#2#] ', $deliveryTermId, $countryId]);
        $bestSimilarityCount = 0;
        $bestZone = "";
        while($rec = $query->fetch())
        {
            $similarityCount = self::strNearPCode($pCode, $rec->pCode);
            if($similarityCount > $bestSimilarityCount){
                $bestSimilarityCount = $similarityCount;
                $bestZone = $rec->name;
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
    }
}
<?php

class trans_FeeZones extends core_Master
{
    public $oldClassName = "trans_ZoneNames";
    public $title = "Имена на зони";
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, trans_Wrapper";
    public $details = "trans_Fees, trans_Zones";
    public $rowToolsSingleField = 'name';
    public function description()
    {
        //id column
        $this->FLD('name', 'varchar(16)', 'caption=Зона, mandatory');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms, select = codeName)', 'caption=Условие на доставка, mandatory');
    }
}
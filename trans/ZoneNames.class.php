<?php

class trans_ZoneNames extends core_Manager
{
    public $title = "Имена на зони";
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, trans_Wrapper";

    public function description()
    {
        //id column
        $this->FLD('name', 'varchar(16)', 'caption=Зона, mandatory');
    }
}
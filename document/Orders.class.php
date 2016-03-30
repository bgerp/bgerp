<?php

class document_Orders extends core_Master
{
    public $title = "Поръчки";
    public $loadList = "plg_Created, document_Wrapper, plg_RowTools2";
    public $details = "document_OrderDetails";

    function description()
    {
        $this->FLD('number', 'int', 'caption=nomer');
    }
}
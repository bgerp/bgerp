<?php

class document_Orders extends core_Master
{
    public $title = "Поръчки";
    public $loadList = "plg_Created, document_Wrapper, plg_RowTools2";
    public $details = "document_OrderDetails";

    public function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->setReadOnly("assigner", core_Users::getCurrent());
    }

    function description()
    {
        $this->FLD('assigner', 'key(mvc=crm_Persons)', 'caption=Поръчител||Assigner');
        $this->FLD('number', 'int', 'caption=nomer');
    }
}
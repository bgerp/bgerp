<?php

/**
 * Created by PhpStorm.
 * User: krisko
 * Date: 30.03.16
 * Time: 09:25
 */
class document_Tags extends core_Manager
{
    public $title = "tagove";
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, document_Wrapper";

    function description()
    {
        $this->FLD('title', 'varchar(128)', "caption=tag,mandatory");
    }
}
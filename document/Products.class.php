<?php

/**
 * Created by PhpStorm.
 * User: user
 * Date: 29.03.16
 * Time: 09:18
 */
class document_Products extends core_Master
{
    public $title = "Producti";
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, document_Wrapper, document_ProductCounts";
//    public $canEdit = "debug";
//    public $canDelete = "debug";

    /**
     *
     */

    function description()
    {

        $this->FLD('title', 'varchar(128)', "caption=zaglavie,mandatory");
        $this->FLD('color', 'enum(green=zeleno, red=cherveno)', "caption=svoistvo->cviat,mandatory");
        $this->FLD('price', 'double(min=0)', "caption=svoistvo->cena,default=0");
        $this->FLD('launchDate', 'date()', 'caption=Data na puskana');

    }

    function act_total()
    {
        $name = Request::get('name', 'varchar');
        $query = self::getQuery();
        $name = htmlentities($name);

        $query->where(array("#title LIKE '%[#1#]%'", $name));
        while($rec = $query->fetch()){
            $total += $rec->countity;
        }

        $src = sbf("img/16/arrow_out.png", "");
        $res = "<img src='{$src}'/>";
        return "Obshto imame {$total} Producta {$res}";

    }

}
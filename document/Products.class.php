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
        $this->FLD('image', 'fileman_FileType(bucket=pictures)', 'caption=kartinka');
        $this->FLD('title', 'varchar(128)', "caption=zaglavie,mandatory");
        $this->FLD('color', 'enum(green=zeleno, red=cherveno)', "caption=svoistvo->cviat,mandatory");
        $this->FLD('price', 'double(min=0)', "caption=svoistvo->cena,default=0");
        $this->FLD('launchDate', 'date()', 'caption=Data na puskana');
        $this->FLD('description', 'richtext(bucket=pictures)', 'caption=opisanie');
        $this->FLD('tags', 'keylist(mvc=document_Tags, select=title)', 'caption=tagove');
        $this->FLD('manufacturer', 'key(mvc=crm_Companies, select=name)', 'caption=proizvoditel');
//        $this->FLD('users', 'users', 'caption=potrebireli');
        $this->FLD('user', 'userOrRole', 'caption=potrebireli');
        $this->FLD('time', 'time(suggestions=12h)', 'caption=vreme, placeholder= Mmoje da pishete i vashi stroinosti)',
            ['attr' => ['style' => "width: 120%; "]]);
    }

    function act_total()
    {
        $name = Request::get('name', 'varchar');
        $query = self::getQuery();
        $name = htmlentities($name);

        $query->where(array("#title LIKE '%[#1#]%'", $name));
        while($rec = $query->fetch()){
            $total = 0;
            $total += $rec->countity;

        }

        $src = sbf("img/16/arrow_out.png", "");
        $res = "<img src='{$src}'/>";
        return "Obshto imame {$total} Producta {$res}";

    }

}
<?php

/**
 * Created by PhpStorm.
 * User: user
 * Date: 29.03.16
 * Time: 10:11
 */
class document_ProductCounts extends core_Plugin
{
    static public function on_AfterDescription($mvc)
    {
        $mvc->FLD('countity', 'int', "caption=svoistvo->kolichestvo,default=0");
    }

    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {

        $rec->price *= 1.2;
        $row->price = $mvc->getVerbal($rec, "price");
        $row->price .= " lv.";


    }

    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec)
    {
        $rec->countity = 10;
    }



    public static function on_AfterPrepareListRecs($mvc, &$res, $data)
    {
        foreach($data->recs as $rec){
            $total += $rec->countity;

        }
//        bp($total);
    }
    /**
     * Изпълнява се след подготовката на листовия изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return boolean
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn("Total", array("document_Products", "total", "name" => 'Pesho'), "ef_icon=img/16/arrow_out.png");
    }
}

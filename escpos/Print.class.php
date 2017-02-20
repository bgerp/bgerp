<?php


/**
 * 
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class escpos_Print extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Отпечатване на документи в мобилен принтер';


    /**
     * Дали id-тата на този модел да са защитени?
     */
    var $protectId = FALSE;
    
    
    /**
     * 
     */
    public $canAdd = 'no_one';
    
    
    /**
     * 
     */
    public $canDelete = 'no_one';
    
    
    /**
     * 
     */
    public $canEdit = 'no_one';
    
    
    /**
     *
     */
    public $canList = 'no_one';
    
    
    /**
     * 
     * 
     * @param bgerp_Print $mvc
     * @param NULL|core_Et $res
     * @param string $action
     */
    function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action != 'print') return ;
        
        $idFullStr = Request::get('id');
        
        $idInt = (int) $idFullStr;
        $idStr = str_replace($idInt, '', $idFullStr);
        
        expect($idInt && $idStr, 'Не е в нужния формат - числа и букви.', $idFullStr);
        
        // За да не се кешира
        header("Expires: Sun, 19 Nov 1978 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        // Указваме, че ще се връща XML
        header('Content-Type: application/xml');
        
        $res = $mvc->getTpl();
        $res->replace('bgERP ' . tr('проба') . ': ' . $idInt, 'title');
        
        $dataTpl = $mvc->getDataTpl();
        
        $dataTpl->replace(date("d-m-Y"), 'date');
        $dataTpl->replace(str_pad($idInt, 10, 0, STR_PAD_LEFT), 'number');
        
        $dataContent = $dataTpl->getContent();
        
        $res->replace(base64_encode($dataContent), 'data');
        
        echo $res->getContent();
        
        // Прекратяваме процеса
        shutdown();
    }
    
    
    /**
     * Мокъп функция за връщане на шаблон за резултат
     * 
     * @return ET
     * 
     * @deprecated
     */
    function getTpl()
    {
        $tpl = '<?xml version="1.0" encoding="utf-8"?>
                <btpDriver Command="DirectIO">
                    <title>[#title#]</title>
                    <data>[#data#]</data>
                </btpDriver>';
        
        $res = new ET(tr('|*' . $tpl));
        
        return $res;
    }
    
    
    /**
     * Мокъп функция за връщане на данните
     * 
     * @return ET
     * 
     * @deprecated
     */
    function getDataTpl()
    {
        $tplArr = array();
        
        $tplArr[] = "|ФАКТУРА|*";
        $tplArr[] = "№ [#number#] / [#date#]";
        $tplArr[] = "|В. Търново|*";
        $tplArr[] = "|ОРИГИНАЛ|*";
        $tplArr[] = "|Получател|*";
        $tplArr[] = "Drehi za vseki";
        $tplArr[] = "България";
        $tplArr[] = "5555 В. Търново";
        $tplArr[] = "ул. Нов адрес №130, вх.Г, ап. 37";
        $tplArr[] = "ЗДДС №:	BG121644736";
        $tplArr[] = "";
        $tplArr[] = "Други суровини и материали ";
        $tplArr[] = "1бр.       543.00	   543.00";
        $tplArr[] = "";
        $tplArr[] = "|Данъчно събитие|*: [#date#]";
        $tplArr[] = "|Краен срок за плащане|*: 22.12.17";
        $tplArr[] = "|Банкова с-ка|*:";
        $tplArr[] = "GB29 NWBK 6016 1331 9268 19";
        $tplArr[] = "";
        $tplArr[] = "|Доставка|*";
        $tplArr[] = "CIF Велико Търново";
        $tplArr[] = "";
        $tplArr[] = "|Стойност|*:	    BGN    543.00";
        $tplArr[] = "|Данъчна основа|* 9%:  BGN	   543.00";
        $tplArr[] = "|ДДС|* 9%:	            BGN	    48.87";
        $tplArr[] = "|Общо|*:	            BGN	   591.87";
        $tplArr[] = "";
        $tplArr[] = "|Словом|*: Петстотин деветдесет и";
        $tplArr[] = "един BGN и 0.87";
        
        $tplStr = implode("\n", $tplArr);
        
        $res = new ET(tr('|*' . $tplStr));
        
        return $res;
    }
}

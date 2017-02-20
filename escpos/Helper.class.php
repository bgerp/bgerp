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
class escpos_Helper
{
    
    
    /**
     * Връща данните в XML формат, за bgERP Agent 
     * 
     * @param core_Manager $clsInst
     * @param integer $id
     * 
     * @return string
     */
    public static function getContentXml($clsInst, $id, $drvName)
    {
        $res = self::getTpl();
        
        $res->replace($clsInst->getTitleById($id), 'title');
        
        $dataContent = self::preparePrintView($clsInst, $id, $drvName);
        $dataContent = escpos_Convert::process($dataContent, $drvName);
        
        $res->replace(base64_encode($dataContent), 'data');
        
        return $res->getContent();
    }
    
    
    
    /**
     * Подготвя данните за отпечатване
     * 
     * @param core_Master $clsInst
     * @param integer $id
     * 
     * @return string
     */
    protected static function preparePrintView($clsInst, $id)
    {
        // TODO - тестово
        $str = "<c F b>{$clsInst->singleTitle} №{$id}/28.02.17" .
                        "<p><r32 =>" .
                        "<p b>1.<l3 b>Кисело мляко" .
                        "<p><l4>2.00<l12>х 0.80<r32>= 1.60" .
                        "<p b>2.<l3 b>Хляб \"Добруджа\"" . "<l f> | годност: 03.03" .
                        "<p><l4>2.00<l12>х 0.80<r32>= 1.60" .
                        "<p b>3.<l3 b>Минерална вода" .
                        "<p><l4>2.00<l12>х 0.80<r32>= 1.60" .
                        "<p><r32 =>" .
                        "<p><r29 F b>Общо: 34.23 лв.";
        
        // @todo -  Трябва да се направи за $clsInst и съответното $id
        
        return $str;
    }
    
    
    /**
     * Мокъп функция за връщане на шаблон за резултат
     * 
     * @return ET
     */
    protected static function getTpl()
    {
        $tpl = '<?xml version="1.0" encoding="utf-8"?>
                <btpDriver Command="DirectIO">
                    <title>[#title#]</title>
                    <data>[#data#]</data>
                </btpDriver>';
        
        $res = new ET(tr('|*' . $tpl));
        
        return $res;
    }
}

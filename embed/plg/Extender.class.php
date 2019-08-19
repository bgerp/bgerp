<?php


/**
 * Плъгин добавящ на драйверите да закачат екстендър към ембедъра си
 *
 * @category  bgerp
 * @package   embed
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class embed_plg_Extender extends core_Plugin
{
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_BaseClass $Driver
     * @param embed_Manager     $Embedder
     * @param stdClass          $data
     */
    public static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        expect($Extender = cls::get($Driver->extenderClass));
        $extenderFields = $Extender->getExtenderFields();
        
        // За всяко поле от екстендъра, добавя се
        foreach ($extenderFields as $key => $fld){
            $fld->name = "{$Extender->className}_{$key}";
            $data->form->addFieldObject("{$Extender->className}_{$key}", $fld);
        }
        
        // Нотифициране на екстендъра че се подготвя форма
        $Extender->invoke('AfterPrepareEditForm', array(&$data, &$data));
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_BaseClass $Driver
     * @param embed_Manager     $Embedder
     * @param stdClass          $data
     */
    public static function on_AfterInputEditForm($Driver, embed_Manager $Embedder, &$form)
    {
        // Нотифициране на екстендъра че се събмитва форма
        expect($Extender = cls::get($Driver->extenderClass));
        $Extender->invoke('AfterInputEditForm', array($form));
    }
    
    
    /**
     * 
     * Извиква се след успешен запис в модела
     *
     * @param core_BaseClass $Driver
     * @param embed_Manager     $Embedder
     * @param int               $id
     * @param stdClass          $rec
     */
    public static function on_AfterSave($Driver, embed_Manager $Embedder, &$id, $rec)
    {
        expect($Extender = cls::get($Driver->extenderClass));
        
        // Има ли запис в екстендъра
        $update = false;
        $exRec = $Extender->getRec($Embedder->getClassId(), $rec->id);
        if(empty($exRec)){
            $update = true;
            $exRec = (object)array("{$Extender->mainClassFieldName}" => $Embedder->getClassId(), "{$Extender->mainIdFieldName}" => $rec->id);
        }
        
        // Ако има, полетата от екстендъра се синхронизират с тези от записа
        $fieldArr =  (array)$rec;
        foreach ($fieldArr as $k => $v){
            if(strpos($k, "{$Extender->className}_") !== false){
                list(,$k1) = explode("{$Extender->className}_", $k);
                if($exRec->{$k1} != $v){
                    $exRec->{$k1} = $v;
                    $update = true;
                }
            }
        }
        
        // Ако има промяна обновява се
        if($update === true){
            $Extender->save($exRec);
        }
        
        // Нотифициране на екстендъра, че мениджъра към който е свързан е обновен
        $Extender->invoke('AfterSyncWithManager', array($exRec, $rec));
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_BaseClass $Driver
     * @param embed_Manager     $Embedder
     * @param stdClass          $data
     */
    public static function on_AfterRead($Driver, $Embedder, &$rec)
    {
        expect($Extender = cls::get($Driver->extenderClass));
        
        // Ако има запис в екстендъра обновява се
        $exRec = $Extender->getRec($Embedder->getClassId(), $rec->id);
        if(is_object($exRec)){
            $extenderFields = array_keys($Extender->getExtenderFields());
            
            foreach ($extenderFields as $k){
                $rec->{"{$Extender->className}_{$k}"} = $exRec->{$k};
            }
        }
    }
    
    
    /**
     * След вербализирането на данните
     *
     * @param core_BaseClass $Driver
     * @param embed_Manager     $Embedder
     * @param stdClass          $row
     * @param stdClass          $rec
     * @param array             $fields
     */
    public static function on_AfterRecToVerbal($Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        expect($Extender = cls::get($Driver->extenderClass));
        $exRec = $Extender->getRec($Embedder->getClassId(), $rec->id);
        
        // Вербализиране на записите от екстендъра
        if(is_object($exRec)){
            $exRowArr = (array)$Extender->recToVerbal($exRec, $Extender->getExtenderFields());
            foreach ($exRowArr as $exFld => $exRow){
                $row->{"{$Extender->className}_{$exFld}"} = $exRow;
            }
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param core_BaseClass $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    public static function on_AfterRenderSingle($Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        expect($Extender = cls::get($Driver->extenderClass));
        $exRec = $Extender->getRec($Embedder->getClassId(), $data->rec->id);
        
        if(is_object($exRec)){
            $Extender->invoke('AfterRenderSingle', array(&$tpl, &$data));
        }
    }
}
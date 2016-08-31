<?php


/**
 * Клас 'spcheck_Plugin'
 * Плъгин за маркиране на грешните думи в черновите документи
 * 
 * @category  vendors
 * @package   spcheck
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class spcheck_Plugin extends core_Plugin
{
    
    
    /**
     * Над колко символо да се проверява за правописни грешки
     */
    protected static $minLenForCheck = 64;
    
    
    /**
     * 
     * 
     * @param core_Master $mvc
     * @param stdObject $res
     * @param stdObject $data
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        if (Mode::isReadOnly()) return ;
        
        if ($data->rec && $data->rec->state != 'draft') return ;
        
        if ($data->rec && ($data->rec->createdBy != core_Users::getCurrent())) return ;
        
        if (!cls::haveInterface('doc_DocumentIntf', $mvc)) return ;
        
        foreach ((array)$mvc->fields as $fName => $field) {
            if ($field->spellcheck == 'no') continue;
            
            if (($field->type instanceof type_Richtext) || ($field->type instanceof type_Text) || ($field->type instanceof type_Varchar)) {
                
                $fName = $field->name;
                
                if (mb_strlen($data->row->{$fName}) < self::$minLenForCheck) continue;
                
                $lg = core_Lg::getCurrent();
                
                $data->row->{$fName} = spcheck_Dictionary::highliteWrongWord($data->row->{$fName}, $lg);
            }
        }
    }
}

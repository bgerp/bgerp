<?php


/**
 * Клас 'recently_Plugin' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    recently
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class recently_Plugin extends core_Plugin
{
    
    
    /**
     *  Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_BeforeRenderFields(&$form)
    {
        setIfNot($prefix, $form->mvc->dbTableName, $form->name, "_");
        
        $Values = cls::get('recently_Values');
        
        $inputFields = $form->selectFields("#input == 'input' || (#kind == 'FLD' && #input != 'none')");
        
        if (count($inputFields)) {
            foreach ($inputFields as $name => $field) {
                if ($field->recently) {
                    $saveName = $prefix . "." . $name;
                    
                    $form->setSuggestions($name, $Values->getSuggestions($saveName));
                }
            }
        }
    }
    
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_AfterInput($form)
    {
        setIfNot($prefix, $form->mvc->dbTableName, $form->name, "_");
        
        $Values = cls::get('recently_Values');
        
        $flds = $form->selectFields("#input == 'input' || (#kind == 'FLD' && #input != 'none')");
        
        $rec = $form->rec;
        
        if (count($flds) ) {
            foreach ($flds as $name => $field) {
                
                if($field->recently && isset($rec->{$name}) && !$form->gotErrors($name)) {
                    
                    $saveName = $prefix . "." . $name;
                    
                    $Values->add($saveName, $rec->{$name});
                }
            }
        }
    }
}
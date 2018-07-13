<?php


/**
 * Клас 'recently_Plugin' -
 *
 *
 * @category  vendors
 * @package   recently
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class recently_Plugin extends core_Plugin
{
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public function on_BeforeRenderFields(&$form)
    {
        setIfNot($prefix, $form->mvc->dbTableName, $form->name, '_');
        
        $inputFields = $form->selectFields("#input == 'input' || (#kind == 'FLD' && #input != 'none')");
        
        if (count($inputFields)) {
            foreach ($inputFields as $name => $field) {
                if ($field->recently) {
                    $saveName = $prefix . '.' . $name;
                    
                    $suggetions = recently_Values::fetchSuggestions($saveName);
                    
                    $form->appendSuggestions($name, $suggetions);
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public function on_AfterInput($form)
    {
        setIfNot($prefix, $form->mvc->dbTableName, $form->name, '_');
        
        $flds = $form->selectFields("#input == 'input' || (#kind == 'FLD' && #input != 'none')");
        
        $rec = $form->rec;
        
        if (count($flds)) {
            foreach ($flds as $name => $field) {
                if ($field->recently && isset($rec->{$name}) && !$form->gotErrors($name)) {
                    $saveName = $prefix . '.' . $name;
                    
                    // Запомняме само стойности, които са над 2 символа
                    if (mb_strlen(trim($rec->{$name})) > 2) {
                        recently_Values::add($saveName, $rec->{$name});
                    }
                }
            }
        }
    }
}

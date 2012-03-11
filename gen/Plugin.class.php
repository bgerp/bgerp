<?php



/**
 * Клас 'gen_Plugin' -
 *
 * Добавя родословно дърво към хората от визитника
 *
 *
 * @category  vendors
 * @package   gen
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class gen_Plugin extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        if(!$mvc->fields['mother']) {
            $mvc->FLD('mother', 'key(mvc=crm_Persons, allowEmpty, select=name)', 'caption=Родители->Майка');
        }
        
        if(!$mvc->fields['father']) {
            $mvc->FLD('father', 'key(mvc=crm_Persons, allowEmpty, select=name)', 'caption=Родители->Баща');
        }
    }
    
    
    /**
     * Извиква се преди извличането на вербална стойност за поле от запис
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->nameList = new ET('[#1#]', $row->nameList);
        
        if($rec->mother) {
            $row->nameList->append("<div><small>Майка:");
            $row->nameList->append(ht::createLink($mvc->getVerbal($rec, 'mother'), array('crm_Persons', 'single', $rec->mother)));
            $row->nameList->append("</small></div>");
        }
        
        if($rec->father) {
            $row->nameList->append("<div><small>Баща:");
            $row->nameList->append(ht::createLink($mvc->getVerbal($rec, 'father') , array('crm_Persons', 'single', $rec->father)));
            $row->nameList->append("</small></div>");
        }
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $mothers = crm_Persons::makeArray4Select('name', "#salutation != 'mr' AND #salutation != 'miss'");
        $fathers = crm_Persons::makeArray4Select('name', "#salutation != 'mrs' AND #salutation != 'miss'");
        
        if($data->form->rec->id) {
            unset($mothers[$data->form->rec->id]);
            unset($fathers[$data->form->rec->id]);
        }
        $data->form->setOptions('mother', $mothers);
        $data->form->setOptions('father', $fathers);
        
        if(!count($mothers)) $data->form->setField('mother', 'input=none');
        
        if(!count($fathers)) $data->form->setField('father', 'input=none');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function insertAfter($sourceArr, $afterField, $key, $value)
    {
        foreach($sourceArr as $k => $v) {
            $destArr[$k] = $v;
            
            if($k == $afterField) {
                $destArr[$key] = $value;
            }
        }
        
        return $destArr;
    }
}
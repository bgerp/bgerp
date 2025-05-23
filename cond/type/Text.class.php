<?php


/**
 * Тип за параметър 'Многоредов текст'
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Многоредов текст
 */
class cond_type_Text extends cond_type_abstract_Proto
{
    /**
     * Кой базов тип наследява
     */
    protected $baseType = 'type_Text';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('rows', 'int(min=1)', 'caption=Конкретизиране->Редове,before=default');
        $fieldset->FLD('richtext', 'enum(no=Не,yes=Да)', 'caption=Конкретизиране->Ричтекст,before=default,silent,removeAndRefreshForm=parser');
        $fieldset->FLD('parser', 'class(interface=cond_ParseStringIntf,select=title,allowEmpty)', 'caption=Конкретизиране->Парсатор,after=richtext');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param cond_type_abstract_Proto $Driver
     * @param embed_Manager     $Embedder
     * @param stdClass          $data
     */
    protected static function on_AfterPrepareEditForm(cond_type_abstract_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        if($data->form->rec->richtext == 'yes'){
            $data->form->setField('parser', 'input=none');
        }
    }


    /**
     * Връща инстанция на типа
     *
     * @param stdClass    $rec         - запис на параметъра
     * @param mixed       $domainClass - клас на домейна
     * @param mixed       $domainId    - ид на домейна
     * @param NULL|string $value       - стойност
     *
     * @return core_Type - готовия тип
     */
    public function getType($rec, $domainClass = null, $domainId = null, $value = null)
    {
        $Type = parent::getType($rec, $domainClass, $domainId, $value);
        $params = array();
        if (isset($rec->rows)) {
            $params['rows'] = $rec->rows;
        }

        if($rec->richtext == 'yes'){
            $params['bucket'] = 'Notes';
            $params['passage'] = 'passage';
            $Type = cls::get('type_Richtext', array('params' => $params));
        } else {
            $Type = cls::get($Type, array('params' => $params));
        }
        
        return $Type;
    }


    /**
     * Вербално представяне на стойноста
     *
     * @param stdClass $rec
     * @param mixed    $domainClass - клас на домейна
     * @param mixed    $domainId    - ид на домейна
     * @param string   $value
     *
     * @return mixed
     */
    public function toVerbal($rec, $domainClass, $domainId, $value)
    {
        if(Mode::is('dontVerbalizeText') || Mode::is('printLabel')) return $value;
        if($rec->richtext == 'yes'){
            $Type = cls::get('type_RichText');
        } else {
            // Ако има посочен парсатор
            $Type = cls::get('type_Text');
            if(isset($rec->parser)){
                if(cls::load($rec->parser, true)){

                    // Парсира се стойноста
                    $Iface = cls::getInterface('cond_ParseStringIntf',$rec->parser);
                    $value = $Iface->parse($rec, $value);

                    // Ако се ще се парсира като Html - ще се рендира като такъв тип
                    if($Iface->isParsedAsHtml($rec)){
                        $Type = cls::get('type_Html');
                    }
                }
            }
        }

        return $Type->toVerbal(trim($value));
    }
}

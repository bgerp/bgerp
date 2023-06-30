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
        $fieldset->FLD('parser', 'class(interface=cond_ParseStringIntf,select=title,allowEmpty)', 'caption=Конкретизиране->Парсатор,after=rows');
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
        
        if (isset($rec->rows)) {
            $Type = cls::get($Type, array('params' => array('rows' => $rec->rows)));
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
        if(Mode::is('dontVerbalizeText')) return $value;
        $Type = cls::get('type_Text');

        // Ако има посочен парсатор
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

        return $Type->toVerbal(trim($value));
    }
}

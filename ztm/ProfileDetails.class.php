<?php


/**
 * Детайл на профил в Zontromat
 *
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 
 * @title     Детайл на профил в Zontromat
 */
class ztm_ProfileDetails extends core_Detail
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'ztm_ProfileDefaults';
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Детайл на профил в Zontromat';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Регистър';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ztm, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ztm, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'ztm, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ztm, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canSingle = 'ztm, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'ztm, ceo';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'profileId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'ztm_Wrapper, plg_Created, plg_RowTools2, plg_Modified, plg_Sorting, plg_SaveAndNew';
    
    
    /**
     * Полета в листовия изглед
     */
    public $listFields = 'registerId, value, modifiedOn,modifiedBy';
    
    
    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        $this->FLD('profileId','key(mvc=ztm_Profiles, select=name)','caption=Профил,mandatory,smartCenter');
        $this->FLD('registerId','key(mvc=ztm_Registers, select=name, allowEmpty)','caption=Регистър,removeAndRefreshForm=value|extValue,silent');
        $this->FLD('value', 'varchar(32)', 'caption=Стойност,input=none');
        
        $this->setDbUnique('profileId, registerId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param embed_Manager $Embedder
     * @param stdClass      $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        ztm_Registers::extendAddForm($form);
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec     Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields  Имена на полетата, които трябва да бъдат записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    protected static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $rec->value = ztm_Registers::recordValue($rec->registerId, $rec->extValue);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $value = ztm_LongValues::getValueByHash($rec->value);
        
        $Type = ztm_Registers::getValueFormType($rec->registerId);
        $row->value = $Type->toVerbal($value);
    }
}
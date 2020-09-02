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
     * Кой може да го разглежда?
     */
    public $canList = 'no_one';
    
    
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
    public $loadList = 'plg_Created, plg_RowTools2, plg_Modified, plg_SaveAndNew, ztm_Wrapper';
    
    
    /**
     * Полета в листовия изглед
     */
    public $listFields = 'registerId, value, modifiedOn,modifiedBy';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'registerId, profileId, value';
    
    
    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        $this->FLD('profileId','key(mvc=ztm_Profiles, select=name)','caption=Профил,mandatory,smartCenter');
        $this->FLD('registerId','key(mvc=ztm_Registers, select=name, allowEmpty)','caption=Регистър,removeAndRefreshForm=value|extValue,silent,mandatory');
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
        
        if ($form->rec->registerId) {
            $rRec = ztm_Registers::fetch($form->rec->registerId);
            $form->setDefault('extValue', $rRec->default);
        }
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
        
        if ($rec->id) {
            $oRec = $mvc->fetch($rec->id);
            
            // Ако променяме стойността
            if ($rec->value != $oRec->value) {
                $rec->__changeValues = true;
                $rec->__oldVal = $oRec->value;
            }
        } else {
            $rec->__changeValues = true;
            $rec->__oldVal = $rec->value;
        }
    }
    
    
    /**
    * Извиква се след успешен запис в модела
    *
    * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
    * @param int          $id      Първичния ключ на направения запис
    * @param stdClass     $rec     Всички полета, които току-що са били записани
    * @param string|array $fields  Имена на полетата, които sa записани
    * @param string       $mode    Режим на записа: replace, ignore
    */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if ($rec->__changeValues) {
            $mvc->changeValues($rec);
        }
    }
    
    
    /**
     * Ако се променя стойността на някой регистър
     * Дефолтно, ако е зададено в профила или в регистрите
     * Да използва новата стойност
     * 
     * @param stdClass $rec
     */
    public function changeValues($rec, $useDef = false)
    {
        $rRec = ztm_Registers::fetch($rec->registerId);
        
        $dQuery = ztm_Devices::getQuery();
        $dQuery->where(array("#profileId = '[#1#]'", $rec->profileId));
        $dQuery->show('id');
        while ($dRec = $dQuery->fetch()) {
            $dArr[$dRec->id] = $dRec->id;
        }
        
        $vQuery = ztm_RegisterValues::getQuery();
        $vQuery->in('deviceId', $dArr);
        $vQuery->where(array("#value = '[#1#]'", $rRec->default));
        $vQuery->orWhere(array("#value = '[#1#]'", $rec->__oldVal));
        
        while ($vRec = $vQuery->fetch()) {
            if ($useDef) {
                $val = $rRec->default;
            } else {
                $val = $rec->value;
            }
            
            $vRec->value = $val;
            $vRec->extValue = $val;
            ztm_RegisterValues::save($vRec, 'value, modifiedOn, modifiedBy, updatedOn');
        }
    }
    
    
    /**
     * След изтриване на запис
     */
    static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            $rec->__oldVal = $rec->value;
            $mvc->changeValues($rec, true);
        }
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
        
        $Type = ztm_Registers::getOurType($rec->registerId, false);
        $row->value = $Type->toVerbal($value);
        
        if($description = ztm_Registers::fetchField($rec->registerId, 'description')){
            $row->registerId = ht::createHint($row->registerId, $description);
        }
        
        $rRec = ztm_Registers::fetch($rec->registerId);
        if ($rRec->state != 'active') {
            $row->ROW_ATTR['class'] = 'state-rejected';
        } else {
            $row->ROW_ATTR['class'] = 'state-active';
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($rec && ($action == 'edit')) {
            
            $rRec = ztm_Registers::fetch($rec->registerId);
            if ($rRec->state != 'active') {
                $requiredRoles = 'no_one';
            }
        }
    }
}

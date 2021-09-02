<?php


/**
 * Клас 'cond_Countries' - Условия на доставка
 *
 * Набор от стандартните условия на доставка (FOB, DAP, ...)
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_Countries extends core_Manager
{
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'ceo,admin';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'ceo,admin';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2,cond_Wrapper,plg_Created,plg_Sorting,plg_SaveAndNew';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'country, conditionId, value, createdOn, createdBy';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'no_one';
    
    
    /**
     * Заглавие
     */
    public $title = 'Търговски условия по държави';
    
    
    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'Търговско условие за държава';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,silent,placeholder=Всички държави');
        $this->FLD('conditionId', 'key(mvc=cond_Parameters,select=name,allowEmpty)', 'input,caption=Условие,mandatory,silent,removeAndRefreshForm=value,remember');
        $this->FLD('value', 'text', 'caption=Стойност, mandatory,remember');
        
        $this->setDbIndex('country,conditionId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        
        
        if ($rec->conditionId) {
            if ($Type = cond_Parameters::getTypeInstance($rec->conditionId, 'drdata_Countries', $rec->country, $rec->value)) {
                $form->setField('value', 'input');
                $form->setFieldType('value', $Type);
                $form->setDefault('value', cond_Parameters::getDefaultValue($rec->conditionId, $rec->cClass, $rec->cId, $rec->value));
            } else {
                $form->setError('conditionId', 'Има проблем при зареждането на типа');
            }
        }
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = &$form->rec;
        
        if ($form->isSubmitted()) {
            if (empty($rec->country)) {
                $rec->country = null;
            }
        }
    }
    
    
    /**
     * Проверява дали посочения запис не влиза в конфликт с някой уникален
     *
     * @param: $rec stdClass записа, който ще се проверява
     * @param: $fields array|string полетата, които не уникални.
     * @return: bool
     */
    public function isUnique($rec, &$fields = array(), &$exRec = null)
    {
        $where = "#id != '{$rec->id}' AND #conditionId = {$rec->conditionId}";
        $where .= (!empty($rec->country)) ? " AND #country = {$rec->country}" : " AND (#country IS NULL OR #country = 0 OR #country = '')";
        
        $res = $this->fetch($where);
        if ($res) {
            $exRec = $res;
            $fields = array('country', 'conditionId');
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $paramRec = cond_Parameters::fetch($rec->conditionId);
        
        if (isset($fields['-list'])) {
            $row->conditionId = cond_Parameters::getVerbal($paramRec, 'typeExt');
            $row->ROW_ATTR['class'] .= ' state-active';
            
            if (empty($rec->country)) {
                $row->country = "<span class='quiet'>" . tr('Всички държави') . '</span>';
            }
        }
        
        $row->value = cond_Parameters::toVerbal($paramRec, 'drdata_Countries', $rec->country, $rec->value);
        
        if (!empty($paramRec->group)) {
            $paramRec->group = tr($paramRec->group);
            $row->group = cond_Parameters::getVerbal($paramRec, 'group');
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        if (!cond_Parameters::count()) {
            cls::get('cond_Parameters')->loadSetupData();
        }
        
        $file = 'cond/csv/Countries.csv';
        $fields = array(
            0 => 'csv_country',
            1 => 'paramSysId',
            2 => 'value',
        );
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    protected static function on_BeforeImportRec($mvc, &$rec)
    {
        if (isset($rec->paramSysId)) {
            expect($rec->conditionId = cond_Parameters::fetchIdBySysId($rec->paramSysId));
        }
        
        $rec->country = (!empty($rec->csv_country)) ? drdata_Countries::getIdByName($rec->csv_country) : null;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Подготовка на филтъра
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'country,conditionId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->input();
        
        // Ако филтъра е събмитнът
        if ($filter = $data->listFilter->rec) {
            if (!empty($filter->country)) {
                $data->query->where("#country = {$filter->country} OR #country IS NULL");
            }
            
            if (!empty($filter->conditionId)) {
                $data->query->where("#conditionId = {$filter->conditionId}");
            }
        }
        
        // Подреждане по държава
        $data->query->XPR('orderCountry', 'int', '(CASE WHEN #country IS NULL THEN 0 ELSE 1 END)');
        $data->query->orderBy('#orderCountry', 'DESC');
    }
    
    
    /**
     * Намира дефолтното търговско условие за посочената държава
     * 
     * @param int $countryId         - ид на държава
     * @param string $conditionSysId - системно ид на търговско условие
     * 
     * @return int|null              - стойноста на търговското условие, или null ако няма
     */
    public static function getParameterByCountryId($countryId, $conditionSysId)
    {
        expect($condId = cond_Parameters::fetchIdBySysId($conditionSysId));
        expect(drdata_Countries::fetch($countryId));
        $value = self::fetchField("#country = {$countryId} AND #conditionId = {$condId}", 'value');
        
        return isset($value) ? $value : null;
    }
}


<?php


/**
 * Модел за групи на зоните
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_ZoneGroups extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Групи на зоните';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Група на зоните';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, rack_Wrapper';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin,rackMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,admin,rackMaster';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,rack';
    
    
    /**
     * Полета за листовия изглед
     */
    public $listFields = 'name=Група,order,createdOn,createdBy';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,admin,rackMaster';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(32)', 'caption=Наименование,mandatory');
        $this->FLD('order', 'int(min=1)', 'caption=Приоритет');
        
        $this->setDbUnique('order');
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
        if($form->rec->createdBy == core_Users::SYSTEM_USER){
            $form->setReadOnly('name');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        if ($form->isSubmitted()) {
            
            // Ако не е посочена подредба, то тя е следващата възможна
            if(empty($rec->order)){
                $rec->order = $mvc->getNextOrder();
            }
        }
    }
    
    
    /**
     * Коя е следващата свободна последователност
     * 
     * @return number $order
     */
    private function getNextOrder()
    {
        $query = $this->getQuery();
        $query->XPR('max', 'int', 'MAX(#order)');
        $max = $query->fetch()->max;
        $max = ($max) ? $max : 0;
        $order = $max + 1;
        
        return $order;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(rack_Zones::haveRightFor('list')){
            $row->name = ht::createLink($row->name, array('rack_Zones', 'list', 'grouping' => $rec->id));
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' && isset($rec)) {
            if(rack_Zones::fetchField("#groupId = {$rec->id}")){
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'rack/csv/ZoneGroups.csv';
        $fields = array(0 => 'name');
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    protected static function on_BeforeImportRec($mvc, &$rec)
    {
        if(empty($rec->order)){
            $rec->order = $mvc->getNextOrder();
        }
    }
}
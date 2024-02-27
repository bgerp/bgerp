<?php


/**
 * Мениджър на изгледи на планове
 *
 *
 * @category  bgerp
 * @package   floor
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class floor_Views extends core_Master {

   /**
     * Необходими плъгини
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_State2, plg_Rejected, floor_Wrapper, plg_StructureAndOrder, plg_Modified, plg_Clone';


    /**
     * Полета, които да не се клонират
     */
    public $fieldsNotToClone = 'createdOn, createdBy, modifiedOn, modifiedBy';


    /**
     * Детайла, на модела
     */
    public $details = 'floor_ViewDetails';


    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'floor_ViewDetails';


    /**
     * Кой може да клонира запис
     */
    public $canClonerec = 'floor,admin,ceo';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'floor,admin,ceo';

    /**
     * Заглавие
     */
    public $title = 'Изгледи';
    

    /**
     * Заглавие в единичния изглед
     */
    public $singleTitle = 'Изглед';
    

    /**
     * Права за писане
     */
    public $canWrite = 'floor,admin,ceo';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'floor,admin,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'floor,admin,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'floor,admin,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'floor,admin,ceo';
    
      
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/layout.png';
    
      
    /**
     * Полета, които ще се показват в листов изглед
     */
    // public $listFields = 'order,name,state';
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Наименование, mandatory');
        $this->FLD('position', 'enum(top=Горе,right=Отдясно)', 'caption=Позиция на табовете');

        $this->setDbUnique('name');
    }


    /**
     * Необходим метод за подреждането
     */
    public static function getSaoItems($rec)
    {
        $res = array();
        $query = self::getQuery();
        while ($rec = $query->fetch()) {
            $res[$rec->id] = $rec;
        }
        
        return $res;
    }

    /**
     *
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     *
     * @return bool|null
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $url = array($mvc, 'View', $data->rec->id);
        $data->toolbar->addBtn('Покажи', $url, "ef_icon={$mvc->singleIcon}, target=_blank,title=Покажи изгледа");
    }


    /**
     * Показва зададения в URL-то изглед
     */
    public function act_View()
    {
        // Изискваме brid / ip или определени роли
        RequireRole('admin,floor');

        // Кой изглед трябва да покажем

        if(!isset($viewId)) {
            $viewId = Request::get('id', 'int');
        }

        expect($vRec = floor_Views::fetch($viewId));

        $planId = Request::get('planId', 'int');

        if(!$planId || !floor_ViewDetails::fetch(array("#planId = '[#1#]'", $planId))) {
            $dQuery = floor_ViewDetails::getQuery();
            $planRec = $dQuery->fetch("#viewId = {$vRec->id}");
            $planId = $planRec->planId;
        }

        $tabs = "<div class='{$vRec->position}Position floorTabs'>";

        $dQuery = floor_ViewDetails::getQuery();
        while($dRec = $dQuery->fetch("#viewId = {$vRec->id}")) {
            $planRec = floor_Plans::fetch($dRec->planId);
            if($planId == $dRec->planId) {
                $active = 'active';
            } else {
                $active = '';
            }

            $tabs .= "<div class='tab {$active}'>" . ht::createLink($planRec->name, array($this, 'View', $viewId, 'planId' => $dRec->planId));
            $tabs .= '</div>';
        }

        $tabs .= '</div>';

        $Plans = cls::get('floor_Plans');

        return $Plans->act_View($planId, false, $tabs);
    }
 
}
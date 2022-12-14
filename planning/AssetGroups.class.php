<?php


/**
 * Мениджър на групите на оборудването
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_AssetGroups extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Видове оборудване';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, planning_Wrapper, plg_State2, plg_Search, plg_Sorting';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, planningMaster';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo, planningMaster';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planningMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, planningMaster';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, planning';
    
    
    /**
     * Кой има достъп до сингъла?
     */
    public $canSingle = 'ceo, planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name=Вид,type,showInPlanningTasks=Допустимост в ПО,createdOn,createdBy,state';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Вид';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutAssetGroup.shtml';
    
    
    /**
     * Детайли
     */
    public $details = 'planning_AssetResourcesNorms,planning_AssetResources';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'name';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64,ci)', 'caption=Име, mandatory');
        $this->FLD('type', 'enum(material=Материален, nonMaterial=Нематериален)', 'caption=Тип, mandatory, notNull');
        $this->FLD('showInPlanningTasks', 'enum(yes=Да,no=Не)', 'caption=Производствени операции->Допустимост, mandatory,notNull,value=no,silent,removeAndRefreshForm=planningParams');
        $this->FLD('planningParams', 'keylist(mvc=cat_Params,select=typeExt)', 'caption=Параметри за планиране->Списък,input=none');
        $this->setDbUnique('name');
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$data->form->rec;

        if($rec->showInPlanningTasks == 'yes'){

            // Ако групата ще се използва в ПО се показва полето за избор на планиращи параметри
            $form->setField('planningParams', 'input');
            $paramSuggestions = cat_Params::getTaskParamOptions($form->rec->planningParams);
            $form->setSuggestions("planningParams", $paramSuggestions);
        }

        if($rec->createdBy == core_Users::SYSTEM_USER){
            foreach (array('name', 'type', 'showInPlanningTasks') as $fld){
                $form->setReadOnly($fld);
            }
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' && isset($rec)) {
            if (planning_AssetResources::fetchField("#groupId = {$rec->id} AND #state = 'active'") || planning_AssetResourcesNorms::fetchField("#objectId = {$rec->id} AND #classId = {$mvc->getClassId()}")) {
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'changestate' && isset($rec)){
            if($rec->createdBy == core_Users::SYSTEM_USER){
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Ще има ли предупреждение при смяна на състоянието
     *
     * @param stdClass $rec
     *
     * @return string|FALSE
     */
    public function getChangeStateWarning($rec)
    {
        $msg = ($rec->state == 'active') ? 'Наистина ли желаете да деактивирате вида и всички оборудвания към него|*?' : 'Наистина ли желаете да активирате вида и всички оборудвания към него|*?';
        
        return $msg;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if ($fields == 'state') {
            foreach (array('planning_AssetResources', 'planning_AssetResourcesNorms') as $det) {
                $Detail = cls::get($det);
                $dQuery = $Detail->getQuery();
                $dQuery->where("#groupId = {$rec->id}");
                while ($dRec = $dQuery->fetch()) {
                    $dRec->state = $rec->state;
                    $Detail->save($dRec, 'state');
                }
            }
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        // Подготвяме пътя до файла с данните
        $file = 'planning/csv/Groups.csv';
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => 'name',
            1 => 'type',
            2 => 'showInPlanningTasks',
        );
        
        // Импортираме данните от CSV файла.
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($this, $file, $fields, null, null);
        
        // Записваме в лога вербалното представяне на резултата от импортирането
        $res = $cntObj->html;
        
        return $res;
    }


    /**
     * Подредба на записите
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'search,type,showInPlanningTasks';
        $data->listFilter->setFieldType('type', 'enum(all=Всички,material=Материален,nonMaterial=Нематериален)');
        $data->listFilter->setFieldType('showInPlanningTasks', 'enum(all=Всички,yes=Допустими в ПО,no=Недопустими в ПО)');
        $data->listFilter->setDefault('type', 'all');
        $data->listFilter->setDefault('showInPlanningTasks', 'all');
        $data->listFilter->input();
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        if ($rec = $data->listFilter->rec) {

            if (!empty($rec->type) && $rec->type != 'all') {
                $data->query->where("#type = '{$rec->type}'");
            }

            if (!empty($rec->showInPlanningTasks) && $rec->showInPlanningTasks != 'all') {
                $data->query->where("#showInPlanningTasks = '{$rec->showInPlanningTasks}'");
            }
        }
    }
}

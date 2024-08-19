<?php


/**
 * Мениджър на Етапи в проектите
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_UnsortedFolderSteps extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Етапи в проекти';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, doc_Wrapper, plg_Sorting, plg_State2, plg_Modified, plg_SaveAndNew, plg_StructureAndOrder';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, admin';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, admin';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, admin';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code,name=Етап,state,lastUsedOn=Последно,modifiedOn,modifiedBy,createdOn=Създаване->На,createdBy=Създаване->От';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Етап в проект';


    /**
     * Шаблон (ET) за заглавие
     *
     * @var string
     */
    public $recTitleTpl = '[[#code#]] [#name#]';


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $saoTitleField = 'name';


    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'doc/tpl/SingleUnsortedFolderSteps.shtml';


    /**
     * Заглавие в единствено число
     */
    public $details = 'StepFolders=doc_UnsortedFolders,StepTasks=cal_Tasks';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование,mandatory, remember');
        $this->FLD('code', 'varchar(16)', 'caption=Код,mandatory, remember');
        $this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none, remember');
        $this->FLD('description', 'richtext(rows=2,bucket=Notes)', 'caption=Допълнително->Описание');
        $this->setDbUnique('code');
    }


    /**
     * Необходим метод за подреждането
     *
     * @see plg_StructureAndOrder
     */
    public static function getSaoItems($rec)
    {
        $res = array();
        $query = self::getQuery();
        $query->where("#state = 'active'");

        while ($rec1 = $query->fetch()) {
            $res[$rec1->id] = $rec1;
        }

        return $res;
    }


    /**
     * Имплементация на метод, необходим за plg_StructureAndOrder
     */
    public function saoCanHaveSublevel($rec, $newRec = null)
    {
        return true;
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($fields['-single'])){
            $row->fullName = $mvc->getSaoFullName($rec);
            if(isset($rec->saoParentId)){
                $row->saoParentId = $mvc->getSaoFullName($rec->saoParentId);
                $row->saoParentId = ht::createLink($row->saoParentId, $mvc->getSingleUrlArray($rec->saoParentId));
            }
        }
    }


    /**
     * Забранява изтриването, ако в елемента има деца
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'delete' && !empty($rec->lastUsedOn)){
            $requiredRoles = 'no_one';
        }
    }
}
<?php


/**
 * Клас 'trans_Features'
 *
 * Документ за Особености на транспорта
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_Features extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Особености на транспорта';


    /**
     * Заглавие
     */
    public $singleTitle = 'Особеност на транспорт';


    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'trans_Wrapper,plg_RowTools2,plg_Created,plg_State2,plg_SaveAndNew';


    /**
     * Кой може да редактира
     */
    public $canEdit = 'transMaster,ceo';


    /**
     * Кой може да добавя
     */
    public $canAdd = 'transMaster,ceo';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'transMaster,ceo';


    /**
     * Кой може да разглежда
     */
    public $canList = 'trans,ceo';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name,state,lastUsedOn=Последно,createdOn,createdBy';


    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'trans,ceo';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(24)', 'caption=Наименование');
        $this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');

        $this->setDbUnique('name');
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'delete' && isset($rec)){
            if(!empty($rec->lastUsedOn)){
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * След началното установяване на този мениджър
     */
    public function loadSetupData()
    {
        $file = 'trans/data/Features.csv';
        $fields = array(0 => 'name');

        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;

        return $res;
    }


    /**
     * Преди импортиране на записите
     */
    protected static function on_BeforeImportRec($mvc, &$rec)
    {
        // Ако има вече запис с това име
        $exId = $mvc->fetchField(array("#name = '[#1#]'", $rec->name));
        if($exId){

            // и той е затворен - ще си остане затворен
            $exState = $mvc->fetchField($exId, 'state', false);
            if($exState == 'closed'){
                $rec->state = $exState;
            }
        }
    }
}
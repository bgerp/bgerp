<?php 


/**
 * Типове договори
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_ContractTypes extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = "Шаблони за трудови договори";
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Шаблон";
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, hr_Wrapper, plg_Printing,
                     plg_SaveAndNew, WorkingCycles=hr_WorkingCycles, plg_Modified';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,hr';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo,hr';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,hr';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,hr';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,hr';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,hr';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, name,createdBy,modifiedOn';

    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory, width=100%');
        $this->FLD('script', 'text', "caption=Текст,column=none, width=100%");
        $this->FLD('sysId', 'varchar', "caption=Служебно ид,input=none");
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Създава начални шаблони за трудови договори, ако такива няма
     */
    function on_AfterSetUpMvc($mvc, &$res)
    {
        if(!self::count()) {
            // Безсрочен трудов договор
            $rec = new stdClass();
            $rec->name = 'Безсрочен трудов договор';
            $rec->script = getFileContent('hr/tpl/PermanentContract.ls.shtml');
            $rec->sysId = $rec->name;
            self::save($rec);
            
            // Срочен трудов договор
            $rec = new stdClass();
            $rec->name = 'Срочен трудов договор';
            $rec->script = getFileContent('hr/tpl/FixedTermContract.ls.shtml');
            $rec->sysId = $rec->name;
            self::save($rec);
            
            // Срочен трудов договор
            $rec = new stdClass();
            $rec->name = 'Трудов договор за заместване';
            $rec->script = getFileContent('hr/tpl/ReplacementContract.ls.shtml');
            $rec->sysId = $rec->name;
            self::save($rec);
            
            // Ако имаме вече създадени шаблони 
        } else {
            
            $query = self::getQuery();
            
            // Намираме тези, които са създадени от системата
            $query->where("#createdBy = -1");
            $sysContracts = array();
            
            while ($recPrev = $query->fetch()){
                $sysContracts[] = $recPrev;
            }
            
            if(is_array($sysContracts)){
                // и ги ъпдейтваме с последните промени в шаблоните
                foreach($sysContracts as $sysContract){
                    switch ($sysContract->name) {
                        case 'Безсрочен трудов договор' :
                            $rec = new stdClass();
                            $rec->id = $sysContract->id;
                            $rec->script = getFileContent('hr/tpl/PermanentContract.ls.shtml');
                            
                            self::save($rec, 'script');
                            break;
                        
                        case 'Срочен трудов договор' :
                            $rec = new stdClass();
                            $rec->id = $sysContract->id;
                            $rec->script = getFileContent('hr/tpl/FixedTermContract.ls.shtml');
                            
                            self::save($rec, 'script');
                            break;
                        
                        case 'Трудов договор за заместване' :
                            $rec = new stdClass();
                            $rec->id = $sysContract->id;
                            $rec->script = getFileContent('hr/tpl/ReplacementContract.ls.shtml');
                            self::save($rec, 'script');
                            break;
                    }
                }
            }
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        // Ако записът е създаден от системата
        if($data->rec->sysId){
            
            // Добавяме бутон за клонирането му
            $data->toolbar->addBtn('Клонирай', array('hr_ContractTypes', 'add', 'originId' => $data->rec->id), 'ef_icon=img/16/copy16.png');
        }
    }
    
    
    /**
     * След потготовка на формата за добавяне / редактиране.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        // Вземаме ид-то на оригиналния запис
        if($originId = Request::get('originId', 'int')){
            
            // Намираме оригиналния запис
            $originRec = $mvc->fetch($originId);
            
            // слагаме стойностите във формата
            $form->setDefault('name', $originRec->name . "1");
            $form->setDefault('script', $originRec->script);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако методът е редакция и вече имаме rec
        if($action == 'edit' && isset($rec)){
            
            // и записът е създаден от системата
            if($rec->createdBy == "-1"){
                
                // никой не може да го редактирва
                $requiredRoles = 'no_one';
            }
            
            if(hr_EmployeeContracts::fetch("#typeId = '{$rec->id}' AND #state = 'active'")){
                
                // никой не може да го редактирва
                $requiredRoles = 'no_one';
            }
        }
        
        if($action == 'delete' && isset($rec)){
            if(hr_EmployeeContracts::fetch("#typeId = '{$rec->id}' AND #state = 'active'")){
                
                // никой не може да го редактирва
                $requiredRoles = 'no_one';
            }
        }
    }
}

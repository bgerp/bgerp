<?php 


/**
 * Типове декларации
 *
 *
 * @category  bgerp
 * @package   dec
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class dec_DeclarationTypes extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Шаблони за декларации";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Шаблон";
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = "Търговия";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_SaveAndNew, plg_Modified, sales_Wrapper, 
                     doc_ActivatePlg, plg_Printing, plg_RowTools2';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,dec';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo,dec';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,dec';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,dec';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,dec';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    var $canEditsysdata = 'ceo,dec';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, name,createdBy,modifiedOn';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    var $singleLayoutFile = 'dec/tpl/SingleDeclarationTemplateLayout.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
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
            // Декларация за сответствие
            $rec = new stdClass();
            $rec->name = 'Декларация за съответствие';
            $rec->script = getFileContent('dec/tpl/AgreementDeclaration.shtml');
            $rec->sysId = $rec->name;
            $rec->createdBy = -1;
            self::save($rec);
            
            // Декларация за сответствие EN
            $rec = new stdClass();
            $rec->name = 'Declaration of compliance';
            $rec->script = getFileContent('dec/tpl/DeclarationOfCompliance.shtml');
            $rec->sysId = $rec->name;
            $rec->createdBy = -1;
            self::save($rec);
            
            // Декларация за сответствие Приложение 1
            $rec = new stdClass();
            $rec->name = 'Приложение №1';
            $rec->script = getFileContent('dec/tpl/Application1.shtml');
            $rec->sysId = $rec->name;
            $rec->createdBy = -1;
            self::save($rec);
            
            // Декларация за сответствие Приложение 5
            $rec = new stdClass();
            $rec->name = 'Приложение №5';
            $rec->script = getFileContent('dec/tpl/Application5.shtml');
            $rec->sysId = $rec->name;
            $rec->createdBy = -1;
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
                        case 'Декларация за съответствие' :
                            $rec = new stdClass();
                            $rec->id = $sysContract->id;
                            $rec->script = getFileContent('dec/tpl/AgreementDeclaration.shtml');
                            
                            self::save($rec, 'script');
                            break;
                        
                        case 'Declaration of compliance' :
                            $rec = new stdClass();
                            $rec->id = $sysContract->id;
                            $rec->script = getFileContent('dec/tpl/DeclarationOfCompliance.shtml');
                            
                            self::save($rec, 'script');
                            break;
                        
                        case 'Приложение №1' :
                            $rec = new stdClass();
                            $rec->id = $sysContract->id;
                            $rec->script = getFileContent('dec/tpl/Application1.shtml');
                            
                            self::save($rec, 'script');
                            break;
                        
                        case 'Приложение №5' :
                            $rec = new stdClass();
                            $rec->id = $sysContract->id;
                            $rec->script = getFileContent('dec/tpl/Application5.shtml');
                            
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
    function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        // Ако записът е създаден от системата
        if($data->rec->sysId){
            
            // Добавяме бутон за клонирането му
            $data->toolbar->addBtn('Клонирай', array('dec_DeclarationTypes', 'add', 'originId' => $data->rec->id), 'ef_icon=img/16/copy16.png');
        }
    }
    
    
    /**
     * След потготовка на формата за добавяне / редактиране.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        // Вземаме ид-то на оригиналния запис
        if($originId = Request::get('originId', 'int')){
            
            // Намираме оригиналния запис
            $originRec = $this->fetch($originId);
            
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
    
    }
    
    
    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
    	$mvc->currentTab = "Декларации->Бланки";
    	$mvc->menuPage = "Търговия:Продажби";
    }
}
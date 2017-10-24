<?php
/**
 * Клас 'boilerplate_Manager'
 *
 * Шаблон за bgerp мениджър
 *
 *
 * @category  bgerp
 * @package   [име на пакет]
 * @author    [Име на автора] <[имейл на автора]>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo      Текстовете в [правоъгълни скоби] да се заменят със съотв. стойности
 */
class boilerplate_Manager extends core_Manager
{
    /**
     * Заглавие в множествено число
     * 
     * @var string
     */
    public $title;
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList;


    /**
     * Поддържани интерфейси
     * 
     * var string|array
     */
    public $interfaces;
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage;
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead;
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit;
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd;
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    public $canView;
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete;
    
    
    /**
     * Брой записи на страница
     * 
     * @var integer
     */
    public $listItemsPerPage;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields;
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     * 
     * @var string
     * @see plg_RowTools2
     */
    public $rowToolsField;
    

    /**
     * Заглавие в единствено число
     * 
     * @var string
     */
    public $singleTitle;

    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     * 
     *  @var string
     */
    public $hideListFieldsIfEmpty;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    }


    /**
     * След дефиниране на полетата на модела
     * 
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
    }
    

    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    }
    

    /**
     * Извиква се преди изпълняването на екшън
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
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
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    }


    /**
     * След изтриване на записи
     *
     * @param core_Mvc $mvc
     * @param int $numRows  
     * @param core_Query $query
     * @param string|array
     *
     * @return bool Дали да продължи обработката на опашката от събития
     */
    public static function on_AfterDelete($mvc, $numRows, $query, $cond)
    {
    }
    
    
    /**
     * Изпълнява се след извличане на запис чрез ->fetch()
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     *
     * @return bool Дали да продължи обработката на опашката от събития
     */
    static function on_AfterRead($mvc, $rec)
    {
    }


    /**
     * Изпълнява се след подготовката на листовия изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return boolean
     */
    protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {   
    }
   
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return boolean
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
    }


    /**
     * Изпълнява се след подготвянето на тулбара в листовия изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return boolean
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
    }


    /**
     * Изпълнява се преди опаковане на съдаржанието от мениджъра
     *
     * @param core_Mvc $mvc
     * @param null|string|core_ET $res
     * @param string|core_ET $tpl
     * @param stdClass $data
     *
     * @return boolean
     */
    protected static function on_BeforeRenderWrapping(core_Manager $mvc, &$res, &$tpl = NULL, $data = NULL)
    {
    }


    /**
     * Изпълнява се след опаковане на съдаржанието от мениджъра
     *
     * @param core_Mvc $mvc
     * @param string|core_ET $res
     * @param string|core_ET $tpl
     * @param stdClass $data
     *
     * @return boolean
     */
    protected static function on_AfterRenderWrapping(core_Manager $mvc, &$res, &$tpl = NULL, $data = NULL)
    {
    }
    


}

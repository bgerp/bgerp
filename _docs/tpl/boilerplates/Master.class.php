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
     * Детайла, на модела
     *
     * @var string|array
     */
    public $details;
    

    /**
     * Заглавие в единствено число
     * 
     * @var string
     */
    public $singleTitle;

    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
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
}

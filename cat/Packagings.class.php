<?php



/**
 * Мениджър на опаковки
 *
 * които могат да бъдат пакетирани продуктите (@see cat_Products), принадлежащи на категорията.
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Опаковки
 */
class cat_Packagings extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Опаковки";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, cat_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,contentPlastic,contentPaper,contentGlass,contentMetals,contentWood';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'cat,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'cat,ceo';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'cat,ceo';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(32)', 'caption=Име, mandatory');
        $this->FLD('contentPlastic', 'percent', 'caption=Полимер');
        $this->FLD('contentPaper', 'percent', 'caption=Хартия');
        $this->FLD('contentGlass', 'percent', 'caption=Стъкло');
        $this->FLD('contentMetals', 'percent', 'caption=Метали');
        $this->FLD('contentWood', 'percent', 'caption=Дървесина');
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
        if($action == 'delete' && $rec->id) {
            if(cat_products_Packagings::fetch("#packagingId = $rec->id")) {
                $requiredRoles = 'no_one';
            }
        }
    }
}

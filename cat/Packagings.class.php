<?php



/**
 * Мениджър на опаковки
 *
 * които могат да бъдат пакетирани продуктите (@see cat_Products).
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
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
    
    
    var $singleTitle = "Опаковка"; 
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = "Каталог";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, cat_Wrapper, plg_Translate';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,contentPlastic,contentPaper,contentGlass,contentMetals,contentWood,showContents';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'cat,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'cat,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'cat,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'cat,ceo';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'cat,ceo';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    var $canEditsysdata = 'ceo,cat';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(32)', 'caption=Име, mandatory, translate');
        $this->FLD('contentPlastic', 'percent', 'caption=Полимер');
        $this->FLD('contentPaper', 'percent', 'caption=Хартия');
        $this->FLD('contentGlass', 'percent', 'caption=Стъкло');
        $this->FLD('contentMetals', 'percent', 'caption=Метали');
        $this->FLD('contentWood', 'percent', 'caption=Дървесина');
        $this->FLD('showContents', 'enum(yes=Показване,no=Скриване)', 'caption=Показване в документи->К-во в опаковка,notNull,default=yes');
        
        $this->setDbUnique("name");
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
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = "cat/csv/Packagings.csv";
        $fields = array(0 => "name");
        
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields);
        $res .= $cntObj->html;
        
        return $res;
    }
}
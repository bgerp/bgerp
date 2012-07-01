<?php



/**
 * Ценови групи на продуктите от каталога
 *
 * Ценовите групи са средство за обединение на продукти (@see cat_Products) споделящи общи
 * правила за ценообразуване.
 *
 *
 * @category  bgerp
 * @package   catpr
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ценови групи
 */
class catpr_Pricegroups extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Максимални отстъпки по ценови групи продукти';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools,
                     catpr_Wrapper, plg_Sorting, plg_Rejected';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name, baseDiscount';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,user';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'admin,catpr';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,catpr,broker';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin,catpr,broker';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin,catpr,broker';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,catpr';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'input,caption=Наименование');
        $this->FLD('baseDiscount', 'percent', 'input,caption=Базова Отстъпка');
    }
}
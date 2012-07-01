<?php



/**
 * Детайл на модела
 *
 *
 * @category  bgerp
 * @package   catpr
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ценоразпис-детайли
 * @link      catpr_Pricelists
 */
class catpr_pricelists_Details extends core_Detail
{
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Ценоразпис';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_State2,
                     catpr_Wrapper, plg_AlignDecimals';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     *
     * @var string
     */
    var $masterKey = 'pricelistId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'priceGroupId, productId, price, tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой има право да го прочете?
     */
    var $canRead = 'admin,user';
    
    
    /**
     * Кой има право да го промени?
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
        $this->FLD('pricelistId', 'key(mvc=catpr_Pricelists,select=id)', 'mandatory,input,caption=Ценоразпис');
        $this->FLD('priceGroupId', 'key(mvc=catpr_Pricegroups,select=name)', 'mandatory,input,caption=Група');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'mandatory,input,caption=Продукт');
        $this->FLD('price', 'double(minDecimals=2)', 'mandatory,input,caption=Цена');
        $this->FLD('state', 'enum(draft,active,rejected)');
    }
}
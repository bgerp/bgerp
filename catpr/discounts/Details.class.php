<?php



/**
 * Детайл на модела
 *
 * Всеки запис от модела съдържа конкретен процент отстъпка за конкретна ценова група
 * (@see catpr_Pricegroups) към дата.
 *
 *
 * @category  all
 * @package   catpr
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Отстъпки-детайли
 * @link      catpr_Discounts
 */
class catpr_discounts_Details extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Отстъпки';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'catpr_Wrapper, plg_Created,
                     plg_LastUsedKeys, plg_AlignDecimals';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     *
     * @var string
     */
    var $masterKey = 'discountId';
    
    
    /**
     * Списък от полета, които са външни ключове към други модели
     *
     * @see plg_LastUsedKeys
     *
     * @var string
     */
    var $lastUsedKeys = 'priceGroupId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'priceGroupId, discount, baseDiscount';
    
    
    /**
     * @todo Чака за документация...
     */
    var $zebraRows = TRUE;
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,user';
    
    
    /**
     * Кой има право да променя?
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
     * Кой има право да го изтрие?
     */
    var $canDelete = 'admin,catpr';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'catpr_Discounts';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('discountId', 'key(mvc=catpr_Discounts,select=name,allowEmpty)', 'mandatory,input=hidden,caption=Пакет,remember');
        $this->FLD('priceGroupId', 'key(mvc=catpr_Pricegroups,select=name,allowEmpty)', 'mandatory,input,caption=Група,remember');
        
        // процент на отстъпка от публичните цени
        $this->FLD('discount', 'percent', 'mandatory,input,caption=Отстъпка->Търговска');
        $this->EXT('baseDiscount', 'catpr_Pricegroups', 'externalKey=priceGroupId', 'caption=Отстъпка->Максимална');
        
        $this->setDbUnique('discountId, priceGroupId');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, необходимо за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if (in_array($action, array('add', 'edit', 'delete'))) {
            $requiredRoles = 'no_one';
        }
    }
}
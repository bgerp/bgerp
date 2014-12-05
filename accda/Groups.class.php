<?php



/**
 * Мениджър на групи от дълготрайни активи
 *
 *
 * @category  bgerp
 * @package   accda
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     ДА Групи
 */
class accda_Groups extends core_Master
{
    
    
    /**
     * Кой линк от главното меню на страницата да бъде засветен?
     */
    var $menuPage = 'Счетоводство';
    
    
    /**
     * Заглавие
     */
    var $title = 'ДА Групи';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "ДА Група";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_SaveAndNew, 
                    accda_Wrapper, plg_Translate';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,accda';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,accda';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,accda';
    
    
    /**
     * Кой има права за сингъла на документа
     */
    var $canSingle = 'ceo,accda';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,accda';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,accda';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,accda';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/category-icon.png';
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    var $cssClass = 'folder-cover';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'cat/tpl/SingleGroup.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Наименование, mandatory, translate');
        $this->FLD('sysId', 'varchar(32)', 'caption=System Id,oldFieldName=systemId,input=none,column=none');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Бележки');
        $this->FLD('productCnt', 'int', 'input=none');
        
        // Свойства присъщи на продуктите в групата
        $this->FLD('meta', 'set(canSell=Продаваеми,
                                canBuy=Купуваеми,
                                canStore=Складируеми,
                                canConvert=Вложими,
                                fixedAsset=ДМА,
                                canManifacture=Производими)', 'caption=Свойства->Списък,columns=2');
        
        $this->setDbUnique("sysId");
    }
}
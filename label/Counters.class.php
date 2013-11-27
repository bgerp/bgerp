<?php 


/**
 * 
 * 
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_Counters extends core_Master
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Броячи';
    
    
    /**
     * 
     */
    var $singleTitle = 'Брояч';
    
    
    /**
     * Път към картинка 16x16
     */
//    var $singleIcon = 'img/16/.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
//    var $singleLayoutFile = 'label/tpl/.shtml';
    
    
    /**
     * Полета, които ще се клонират
     */
//    var $cloneFields = '';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'debug';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'debug';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'debug';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'debug';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'debug';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'debug';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'debug';
    
    
    /**
     * Плъгини за зареждане
     */
//    var $loadList = 'plg_Printing, bgerp_plg_Blank, plg_Search';
    var $loadList = 'label_Wrapper, plg_RowTools, plg_Created, plg_State';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
//    var $listFields = '';
    
    
    /**
     * 
     */
//    var $rowToolsField = 'id';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
//    var $rowToolsSingleField = 'id';
    

    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
//    var $searchFields = '';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'label_CounterItems';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Име, mandatory, width=100%');
        $this->FLD('min', 'int', 'caption=Минимално');
        $this->FLD('max', 'int', 'caption=Максимално');
        $this->FLD('step', 'int', 'caption=Стъпка');
    }
    
    
    /**
     * връща последователни числа в диапазона между минимално и максимално
     * за което няма запис в Броячи->Записи и прави запис за него
     * 
     * @param unknown_type $counterId
     */
    static function getCurrent($counterId)
    {
        
    }
}
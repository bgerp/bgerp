<?php



/**
 * Мениджър на задължения по покупки
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задължения по покупки
 */
class purchase_Debt extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Задължения по покупки';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, plg_SaveAndNew, 
                    purchase_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,purchase';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,purchase';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, person, companies,document, date, sum, offer ';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
   	 	 $this->FLD('person', 'key(mvc=crm_Persons,select=name,group=suppliers, allowEmpty=true)', 'caption=Контрагент->Лице');
    	 $this->FLD('companies', 'key(mvc=crm_Companies,select=name,group=suppliers, allowEmpty=true)', 'caption=Контрагент->Фирма');
    	 $this->FLD('document', 'varchar', 'caption=Оферта->Номер');
       	 $this->FLD('date', 'date', 'caption=Оферта->Дата');
       	 $this->FLD('sum', 'double', 'caption=Оферта->Сума');
    	 $this->FLD('offer', 'richtext(bucket=Notes)', 'caption=Оферта->Детайли');
    	 
    	 
    }
    
    /**
     * @todo Чака за документация...
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = "Задължения №{$rec->id}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        return $row;
    }

}
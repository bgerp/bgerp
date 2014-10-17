<?php 


/**
 * Документ с който се създава нов цвят мастило
 *
 * @category  bgerp
 * @package   inks
 * @author    Gabrirla Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class labels_Default extends core_Master
{
    

    /**
     * Заглавие на модела
     */
    var $title = 'Етикети';
    
    
    /**
     * 
     */
    var $singleTitle = 'Етикети';
    
    
    /**
     * 
     */
    var $abbr = 'Labe';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'labels,ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'labels,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'labels,ceo';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'labels,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'labels,ceo';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'labels,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да възлага задачата
     */
    var $canAssign = 'labels,ceo';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Кой може да променя активирани записи
     */
    var $canChangerec = 'labels,ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'labels_Wrapper, doc_DocumentPlg, plg_RowTools, plg_Printing, doc_ActivatePlg, bgerp_plg_Blank, plg_Search, 
    				 plg_Sorting,  doc_SharablePlg';

    
    /**
     * Дали може да бъде само в началото на нишка
     */
    // TODO може да се добави в папки на някои фирми, където да се добави по средата на нишката
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'labels/tpl/SinglePalette.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/barcode-icon.png';

    
    /**
     * Поле за търсене
     */
    var $searchFields = 'name, code';
    
    
    /**
     * 
     */
    var $listFields = 'tools=Пулт, name,code,groups';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';

    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "18.3|Други";
	
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('name', 'varchar', 'caption=Наименование, mandatory,remember=info,width=100%');
		$this->FLD('code', 'varchar(64)', 'caption=Код, mandatory,remember=info,width=15em');
        $this->FLD('eanCode', 'gs1_TypeEan', 'input,caption=EAN,width=15em');
		$this->FLD('info', 'varchar', 'caption=Количество');
		$this->FLD('groups', 'key(mvc=cat_Packagings, select=name)', 'caption=Групи,maxColumns=2');
        $this->FLD('date', 'date', 'caption=Дата,mandatory');
  
        $this->setDbUnique('code');
 
    }

    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     * @return stdClass $row
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->name = $this->getVerbal($rec, 'name');
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $rec->name;
        
        return $row;
    }
}

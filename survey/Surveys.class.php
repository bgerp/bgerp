<?php



/**
 * Модел "Анкети"
 *
 *
 * @category  bgerp
 * @package   survey
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class survey_Surveys extends core_Master {
    
    
	/**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Анкети';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, survey_Wrapper,  plg_Printing,
     	plg_Sorting,  doc_DocumentPlg, bgerp_plg_Blank';
    
  
    /**
     * Кои полета да се показват в листовия изглед
     */
    //var $listFields = 'id, iban, contragent=Контрагент, currencyId, type';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Анкета";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/survey.png';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsSingleField = 'title';

    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'survey, ceo, admin';
    
    
    /**
	 * Коментари на статията
	 */
	var $details = 'survey_Alternatives';
	
	
	/**
     * Абревиатура
     */
    var $abbr = "An";
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'survey, ceo, admin';
    
    
    /**
	 * Файл за единичен изглед
	 */
	var $singleLayoutFile = 'survey/tpl/SingleSurvey.shtml';
	
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar(50)', 'caption=Заглавие, mandatory, width=400px');
		$this->FLD('description', 'text(rows=2)', 'caption=Oписание, mandatory, width=100%');
    	$this->FLD('deadline', 'date(format=d.m.Y)', 'caption=Краен срок,width=8em,mandatory');
    	$this->FLD('summary', 'enum(internal=Вътрешно,personal=Персонално,public=Публично)', 'caption=Обобщение,mandatory,width=8em');
    	$this->FLD('state', 'enum(draft=Чернова,active=Публикувана,rejected=Оттеглена)', 'caption=Състояние,mandatory,width=8em');
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	
    	if($fields['-single']) {
    		
    		// Показваме заглавието само ако не сме в режим принтиране
	    	if(!Mode::is('printing')){
	    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
	    	}
    	}
    }
    
    
   /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
   		
   	}
    
   	
   	/**
     * Пушваме css файла
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	$tpl->push('survey/tpl/css/styles.css', 'CSS');
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $rec->reason;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
}
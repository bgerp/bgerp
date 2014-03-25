<?php



/**
 * Мениджър на оферти за покупки
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Оферти за покупки
 */
class purchase_Offers extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Pqt';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Оферта от доставчик';
    
     
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,person, companies, product, date, offer, sum, document';

    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/doc_table.png';
    
    
    /**
     * Заглавие
     */
    var $title = 'Оферти за покупки';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Rejected, plg_State2, plg_SaveAndNew,
						purchase_Wrapper, doc_DocumentPlg, doc_EmailCreatePlg, doc_ActivatePlg,
						plg_AutoFilter';

    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,purchase';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,purchase';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,purchase';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'folderId, threadId, containerId';
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.1|Логистика";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	 $this->FLD('person', 'key(mvc=crm_Persons,select=name,group=suppliers, allowEmpty=true)', 'caption=Контрагент->Лице,autoFilter');
    	 $this->FLD('companies', 'key(mvc=crm_Companies,select=name,group=suppliers, allowEmpty=true)', 'caption=Контрагент->Фирма,autoFilter');
    	 $this->FLD('product', 'varchar', 'caption=Продукт');
    	 $this->FLD('sum', 'double', 'caption=Оферта->Цена');
    	 $this->FLD('date', 'date', 'caption=Оферта->Дата');
    	 $this->FLD('offer', 'richtext(bucket=Notes)', 'caption=Оферта->Детайли');
    	 $this->FLD('document', 'fileman_FileType(bucket=Notes)', 'caption=Оферта->Документ');
    }

    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'person, companies';
        
        $form->input('person, companies', 'silent');

    	if($form->rec->person){
        	$data->query->where(array("#person = '[#1#]'", $form->rec->person));
        }
        
    	if($form->rec->companies){
        	$data->query->where(array("#companies = '[#1#]'", $form->rec->companies));
        }
    }
 
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресата
     */
    static function getContragentData($id)
    {
        //TODO
        
        return $contragentData;
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото наимей по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        //TODO
        $handle = purchase_Offers::getHandle($id);
        
        //Създаваме шаблона
        $tpl = new ET(tr("Предлагаме на вашето внимание нашата оферта:") . '\n[#handle#]');
        
        //Заместваме датата в шаблона
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = "Оферта №{$rec->id}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        return $row;
    }
}

<?php



/**
 * Модел за клиентски карти
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pos_Cards extends core_Manager {
    
    
    /**
     * Заглавие
     */
    public $title = 'Клиентски карти';
    
    
    /**
     * Плъгини за зареждане
     */
   public $loadList = 'pos_Wrapper, plg_Printing, plg_Search, plg_Sorting, plg_State2, plg_RowTools2';
   
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Клиентска карта";
 
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'pos, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, pos';
    
	
    /**
     * Кой има право да контира?
     */
    public $canConto = 'pos, ceo';
	
	
	/**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'number, contragentId=Контрагент';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = FALSE;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('number', 'varchar(32)', 'caption=Номер, mandatory');
    	$this->FLD('contragentId', 'int', 'input=hidden,silent');
    	$this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,silent');
    	
    	$this->setDbUnique('number,contragentId,contragentClassId');
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	if(isset($rec->contragentClassId) && isset($rec->contragentId)){
    		$data->form->title = core_Detail::getEditTitle($rec->contragentClassId, $rec->contragentId, $mvc->singleTitle, $rec->id, $mvc->formTitlePreposition);
    	}
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-list'])){
    		$Contragent = cls::get($rec->contragentClassId);
    		$row->contragentId = $Contragent->getHyperLink($rec->contragentId, TRUE);
    		$row->contragentId = "<span style='float:left'>{$row->contragentId}</span>";
    	}
    }
    
    
	/**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->showFields = 'search';
    }
    
    
    /**
     * Подготовка на клиентските карти на избрания клиент
     */
    public function prepareCards($data)
    {
    	$Contragent = $data->masterMvc;
    	$masterRec = $data->masterData->rec;
    	
    	$query = $this->getQuery();
    	$query->where("#contragentClassId = '{$Contragent->getClassId()}' AND #contragentId = {$masterRec->id}");
    	$query->where("#state = 'active'");
    	while($rec = $query->fetch()){
    		$data->rows[$rec->id] = $this->recToVerbal($rec);
    	}
    	
    	if($Contragent->haveRightFor('edit', $data->masterId) && $this->haveRightFor('add')){
		    $addUrl = array($this, 'add', 'contragentClassId' => $Contragent->getClassId(), 'contragentId' => $data->masterId, 'ret_url' => TRUE);
		    $data->addBtn = ht::createLink('', $addUrl, NULL, array('ef_icon' => 'img/16/add.png', 'class' => 'addSalecond', 'title' => 'Добавяне на нова клиентска карта')); 
        }
    }
    
    
    /**
     * Рендиране на клиентските карти на избрания клиент
     */
    public function renderCards($data)
    {
    	$tpl = new core_ET("");
        $tpl->append(tr('Клиентски карти'), 'cardTitle');
        
        if(isset($data->addBtn)){
        	$tpl->append($data->addBtn, 'cardTitle');
        }
        
    	if(count($data->rows)) {
			foreach($data->rows as $id => $row) {
				$tpl->append("<div style='white-space:normal;font-size:0.9em;'>");
				
				$tools = $row->_rowTools->renderHtml();
				$tpl->append($row->number  . "<span style='position:relative;top:4px'>{$tools}</span>");
				$tpl->append("</div>");
			}
	    } else {
	    	$tpl->append(tr("Няма записи"));
	    }
	    
        return $tpl;
    }
    
    
    /**
     * Връща контрагента отговарящ на номера на картата
     * 
     * @param varchar $number - номер на карта
     * @param int $ctrClassId - ид на класа от който трябва да е контрагента
     * @return FALSE|core_ObjectReference - референция към контрагента
     */
    public static function getContragent($number, $ctrClassId = NULL)
    {
    	$query = static::getQuery();
    	$query->where("#number = '{$number}'");
    	if(isset($ctrClassId)){
    		$query->where("#contragentClassId = $ctrClassId");
    	}
    	
    	if($rec = $query->fetch()){
    		return new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)){
    		if(!cls::get($rec->contragentClassId)->haveRightFor('edit', $rec->contragentId)){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
}
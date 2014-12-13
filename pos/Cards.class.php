<?php



/**
 * Модел за клиентски карти
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pos_Cards extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'Клиентски карти';
    
    
    /**
     * Плъгини за зареждане
     */
   var $loadList = 'pos_Wrapper, plg_Printing, plg_Search, plg_Sorting, plg_State2, plg_RowTools';
   
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Клиентски карти";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'pos, ceo';
 
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'pos, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo, pos';
    
	
    /**
     * Кой има право да контира?
     */
    var $canConto = 'pos, ceo';
	
	
	/**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, number, contragentId=Контрагент';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'number';
    
    
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
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $data->toolbar->removeBtn('btnAdd');
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$Contragent = cls::get($rec->contragentClassId);
    	$row->contragentId = $Contragent->getHyperLink($rec->contragentId, TRUE);
    	$row->contragentId = "<span style='float:left'>{$row->contragentId}</span>";
    }
    
    
	/**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, $data)
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
    	$data->TabCaption = 'Карти';
    	
    	$Contragent = $data->masterMvc;
    	$masterRec = $data->masterData->rec;
    	
    	$query = $this->getQuery();
    	$query->where("#contragentClassId = '{$Contragent->getClassId()}' AND #contragentId = {$masterRec->id}");
    	$query->where("#state = 'active'");
    	while($rec = $query->fetch()){
    		$data->rows[$rec->id] = $this->recToVerbal($rec);
    	}
    	
    	if($Contragent->haveRightFor('edit', $data->masterId) && $this->haveRightFor('add')){
        	$img = sbf('img/16/add.png');
		    $addUrl = array($this, 'add', 'contragentClassId' => $Contragent->getClassId(), 'contragentId' => $data->masterId, 'ret_url' => TRUE);
		    $data->addBtn = ht::createLink('', $addUrl, NULL, array('style' => "background-image:url({$img})", 'class' => 'linkWithIcon addSalecond', 'title' => 'Добавяне на нова клиентска карта')); 
        }
    }
    
    
    /**
     * Рендиране на клиентските карти на избрания клиент
     */
    public function renderCards($data)
    {
    	$tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $tpl->append(tr('Клиентски карти'), 'title');
        
        if(isset($data->addBtn)){
        	$tpl->append($data->addBtn, 'title');
        }
        
    	if(count($data->rows)) {
			foreach($data->rows as $id => $row) {
				$tpl->append("<div style='white-space:normal;font-size:0.9em;'>", 'content');
				$tpl->append($row->number  . "<span style='position:relative;top:4px'> &nbsp;" . $row->tools . "</span>", 'content');
				$tpl->append("</div>", 'content');
			}
	    } else {
	    	$tpl->append(tr("Няма записи"), 'content');
	    }
        
        return $tpl;
    }
    
    
    /**
     * Връща контрагента отговарящ на номера на картата
     * 
     * @param varchar $number - номер на карта
     * @return core_ObjectReference - референция към контрагента
     */
    public static function getContragent($number)
    {
    	if($rec = static::fetch(array("#number = '[#1#]'", $number))){
    		return new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
    	}
    	
    	return FALSE;
    }
}
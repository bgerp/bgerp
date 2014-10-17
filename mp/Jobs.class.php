<?php



/**
 * Мениджър на Задания за производство
 *
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задания за производство
 */
class mp_Jobs extends core_Master
{
    
    
	/**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Задания за производство';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Задание за производство';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Job';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, doc_DocumentPlg, mp_Wrapper, doc_ActivatePlg, plg_Search, doc_SharablePlg';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, mp';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, mp';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, mp';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, mp';

	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, mp';
    
    
	/**
	 * Полета за търсене
	 */
	public $searchFields = 'folderId';
	
	
	/**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/clipboard_text.png';
    
    
	/**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, originId=Спецификация, folderId, dueDate, quantity, state, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Шаблон за единичен изглед
     */
    public $singleLayoutFile = 'mp/tpl/SingleLayoutJob.shtml';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('specId', 'key(mvc=techno_Specifications,select=title)', 'caption=Спецификация,mandatory,silent');
    	$this->FLD('dueDate', 'date(smartTime)', 'caption=Падеж,mandatory');
    	$this->FLD('quantity', 'double(decimals=2)', 'caption=Количество,mandatory,silent');
    	$this->FLD('notes', 'richtext(rows=3)', 'caption=Забележки');
    	$this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Доставка->Условие');
    	$this->FLD('deliveryDate', 'date(smartTime)', 'caption=Доставка->Срок');
    	$this->FLD('deliveryPlace', 'key(mvc=crm_Locations,select=title)', 'caption=Доставка->Място');
    	$this->FLD('weight', 'cat_type_Weight', 'caption=Тегло,input=none');
    	$this->FLD('brutoWeight', 'cat_type_Weight', 'caption=Бруто,input=none');
    	$this->FLD('data', 'blob(serialize,compress)', 'input=none');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Активирано, rejected=Отказано)', 
            'caption=Статус, input=none'
        );
    }
    
    
	/**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'search';
		
        // Активиране на филтъра
        $data->listFilter->input();
    }
    
    
	 /**
      * Добавя ключови думи за пълнотекстово търсене
      */
    function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	if($rec->specId){
    		// Извличане на ключовите думи от документа
	     	$object = techno_Specifications::getDriver($rec->specId);
	    	$title = $object->getTitleById();
	     	
	    	$res = plg_Search::normalizeText($title);
	    	$res = " " . $res;
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
        if(empty($rec->specId)){
	        expect($docClass = Request::get('originClass', 'int'));
		    expect($docId = Request::get('originDocId', 'int'));
		    
		    expect(cls::get($docClass) instanceof techno_Specifications);
		    expect(cls::get($docClass)->fetch($docId));
		    $rec->specId = $docId;
		    $form->setReadOnly('specId');
		    
        	if($jobRec = $mvc->getLastJob($rec->specId, 'draft')){
        		$link = ht::createLink($mvc->getHandle($jobRec->id), array($mvc, 'single', $jobRec->id));
    			$form->setWarning('specId', "Тази спецификация има вече задание чернова|* {$link}");
    		}
        }
        
    	if($rec->id){
        	$form->setReadOnly('specId');
        }
        
        $Driver = techno_Specifications::getDriver($rec->specId);
        $mvc->prepareAdditionalFields($form, $Driver->getAdditionalParams(), $rec->data);
        
    	$locations = crm_Locations::getContragentOptions('crm_Companies', crm_Setup::BGERP_OWN_COMPANY_ID);
    	$form->setOptions('deliveryPlace', array('' => '') + $locations);
    	
    	$mvc->setField('sharedUsers', 'input=none');
    }
    
    
    /**
     * Подготвя полетата за допълнитлени данни на формата взависимост от драйвъра
     * @param core_Form $form - формата
     * @param array $array - масив върнат от драйвера (@see techno_ProductsIntf::getAdditionalParams)
     */
    private function prepareAdditionalFields(&$form, $array, $rData)
    {
    	if(count($array)){
    		foreach ($array as $name => $obj){
    			$form->FNC($name, $obj->type, "caption=Допълнително->{$obj->name},input,additional");
    			if(isset($rData[$name])){
    				$form->setDefault($name, $rData[$name]);
    			}
    		}
    	}
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		
    		// Преобразува допълнителната информация във вид удобен за съхраняване
    		$data = array();
    		$dataFlds = $form->selectFields('#additional');
    		if(count($dataFlds)){
    			foreach ((array)$dataFlds as $k => $v){
    				if(isset($rec->$k) && strlen($rec->$k)){
    					$data[$k] = $rec->$k;
    				}
    			}
    			
    			$rec->data = $data;
    		}
    	}
    }
    
    
    /**
     * Връща последното задание за дадена спецификация 
     * @param int $specId - ид на спецификация
     * @param string $state - състояние на заданието
     * @return stdClass - заданието
     */
    public function getLastJob($specId, $state = 'active')
    {
    	$query = static::getQuery();
    	$query->where("#state = 'draft'");
    	$query->where("#specId = {$specId}");
    	$query->orderBy("#createdOn", "DESC");
    	
    	return $query->fetch();
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$Driver = techno_Specifications::getDriver($rec->specId);
    	
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		$row->originId = $Driver->getHyperLink(TRUE);
    	}
    	
    	if($fields['-single']){
    		$row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})" ;
    		
    		$pInfo = $Driver->getProductInfo();
    		$row->quantity .= " " . cat_UoM::getShortName($pInfo->productRec->measureId);
    		
    		$dData = $Driver->prepareData();
    		$row->origin = $Driver->renderJobView($dData);
    	}
    }
    
    
	/**
     * Пушваме css и js файла
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	$tpl->push('mp/tpl/styles.css', 'CSS');
    	$Driver = techno_Specifications::getDriver($data->rec->specId);
    	$tpl->replace($Driver->renderAdditionalParams($data->rec->data), 'ADDITIONAL');
    }
    
    
	/**
     * При нова продажба, се ънсетва threadId-то, ако има
     */
    static function on_AfterPrepareDocumentLocation($mvc, $form)
    {   
    	if($form->rec->threadId && !$form->rec->id){
		     unset($form->rec->threadId);
		}
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Задание за производство №{$id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = "Задание за производство №{$id}";
		
        return $row;
    }
    
    
	/**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        if((Request::get('originClass', 'int') && Request::get('originDocId', 'int')) || Request::get('specId', 'int')){
        	return TRUE;
        }
        
    	return FALSE;
    }
    
    
    /**
	 * Връща масив от използваните документи в даден документ (като цитат или
     * са включени в детайлите му)
     * @param int $data - сериализираната дата от документа
     * @return param $res - масив с използваните документи
     * 					[class] - инстанция на документа
     * 					[id] - ид на документа
     */
    function getUsedDocs_($jobId)
    {
    	$specId = static::fetchField($jobId, 'specId');
    	$Driver = techno_Specifications::getDriver($specId);
    	$res[] = (object)array('class' => $Driver->instance, 'id' => $Driver->that);
    
    	return $res;
    }
}
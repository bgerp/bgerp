<?php 

/**
 * Декларации за съответствия
 *
 *
 * @category  bgerp
 * @package   dec
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class dec_Declarations extends core_Master
{
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Декларации за съответствия";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Декларация за съответствие";
    
    
    /**
     * Заглавие на менюто
     */
    var $pageMenu = "Декларации";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'sales_Wrapper, bgerp_plg_Blank, dec_Wrapper, recently_Plugin, doc_ActivatePlg, plg_Printing, cond_plg_DefaultValues, 
    				 plg_RowTools, doc_DocumentIntf, doc_DocumentPlg, doc_EmailCreatePlg';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,dec';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,dec';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,dec';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo,dec';
    
    
    /**
     * Кой е детайла на каласа
     */
    var $details = 'dec_DeclarationDetails';
    
    
    /**
     * Кои полета ще виждаме в листовия изглед
     */
    var $listFields = 'id, typeId, doc, createdOn, createdBy';
    
    
    /**
     * Кой е тетущият таб от менюто
     */
    var $currentTab = 'Декларации';
     
     
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'dec/tpl/SingleLayoutDeclarations.shtml';
    
    
    /**
     * Единична икона
     */
    //var $singleIcon = 'img/16/construction-work-icon.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'typeId';

    
    /**
     * Абревиатура
     */
    var $abbr = "Dec";
    
    
    /**
     * Групиране на документите
     */ 
   	var $newBtnGroup = "3.8|Търговия";
    
   	
   	/**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'typeId, doc, managerId, location, materials';

     
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    
    	'statements'   => 'lastDocUser|lastDoc|LastDocSameCuntry',
    	'materials'    => 'lastDocUser|lastDoc|LastDocSameCuntry',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('typeId', 'key(mvc=dec_DeclarationTypes,select=name)', "caption=Бланка");
    	    	
		$this->FLD('doc', 'key(mvc=doc_Containers)', 'caption=Към документ, input=none');
		
		$this->FLD('managerId', 'key(mvc=crm_Persons,select=name, group=managers)', 'caption=Представлявана от');
		
		$this->FLD('statements', 'keylist(mvc=dec_Statements,select=title)', 'caption=Твърдения');
		
		$this->FLD('location', 'varchar', "caption=Произведени в, recently, class=contactData,hint=Населено място: град или село и община");
		//стетейтмънт
		$this->FLD('materials', 'varchar', 'caption=Материали, recently');
		
		$this->FLD('date', 'date', 'caption=Дата');
				
		$this->FLD('productId', 'keylist(mvc=sales_InvoiceDetails,select=productId)', 'caption=Продукти');
		
		$this->FLD('note', 'richtext(bucket=Notes)', 'caption=Пояснения');
	
		// по подразбиране на вземе мениджъра и позицията му
		//$this->FLD('declaratorId', 'varchar', 'caption=Декларатор->Име и Фамилия, recently');
		
		//$this->FLD('declaratorPosition', 'varchar', 'caption=Декларатор->Позиция, recently');
    }

    
     /**
     * След потготовка на формата за добавяне / редактиране.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        
    	// Записваме оригиналното ид, ако имаме такова
    	if($data->form->rec->originId){
    		$data->form->setDefault('doc', $data->form->rec->originId);
    		
    		// и е по  документ фактура намираме кой е той
    		$doc = doc_Containers::getDocument($data->form->rec->originId);
    		$class = $doc->className;
    		$dId = $doc->that;
    		$rec = $class::fetch($dId);
    		
    		// съдържа обобщена информация за сделките в нишката
    		$firstDoc = doc_Threads::getFirstDocument($data->form->rec->threadId);
    		if($firstDoc->haveInterface('bgerp_DealAggregatorIntf')){
    			$deal = $firstDoc->getAggregateDealInfo();
    		} elseif($firstDoc->haveInterface('bgerp_DealIntf')){
    			$deal = $firstDoc->getDealInfo();
    		}
    		expect($deal);
    		
    		
	       	// Продуктите
	       	if (count($deal->invoiced->products)) {
	       		
		       	foreach($deal->invoiced->products as $iProduct){
		    		$ProductMan = cls::get($iProduct->classId);
		        	$productName [] = $ProductMan::getTitleById($iProduct->productId);
				}
				
				$data->form->setSuggestions('productId', $productName);
	       	}
    	}
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->doc = doc_Containers::getLinkForSingle($rec->doc);
    }
    
    
    /**
     * Подготвя иконата за единичния изглед
     */
    static function on_AfterPrepareSingle($mvc, &$tpl, &$data)
    {
    	$row = &$data->row;
        $rec = &$data->rec;
        $recDec = $tpl->rec;
        
        // Зареждаме бланката в шаблона на документа
        $row->content = new ET (dec_DeclarationTypes::fetchField($rec->typeId, 'script'));
        $decContent = $row->content;
          	
    	// Зареждаме данните за собствената фирма
        $ownCompanyData = crm_Companies::fetchOwnCompany();

        // Адреса на фирмата
        $address = trim($ownCompanyData->place . ' ' . $ownCompanyData->pCode);
        if ($address && !empty($ownCompanyData->address)) {
            $address .= '&nbsp;' . $ownCompanyData->address;
        } 
        $row->MyCompany = $ownCompanyData->company;
        $row->MyCountry = $ownCompanyData->country;
        $row->MyAddress = $address;

        
        // Ват номера й
        $uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
        if($uic != $ownCompanyData->vatNo){
        	$row->MyCompanyVatNo = $ownCompanyData->vatNo;

    	} 
    	$row->uicId = $uic;
    	
    	// информация за управителя/декларатора
    	$managerData = crm_Persons::fetch($rec->managerId);
    	$row->manager = $managerData->name;
    	$row->managerEGN = $managerData->egn;

    	if ($rec->location) {
    		
    		// информация за локацията/ мястото на производство
	    	//$locationData = crm_Locations::fetch($rec->locationId);
	    	$row->place = $rec->location;
    	}

    	if($rec->date == NULL){
    		$row->date = $rec->createdOn;
    	}
    	
    	if ($rec->materials) {
    		        	
			$row->material = $rec->materials;
    	}
    	
    	// ако декларацията е към документ
    	if ($data->rec->originId) {
			// и е по  документ фактура намираме кой е той
    		$doc = doc_Containers::getDocument($data->rec->originId);
    		$class = $doc->className;
    		$dId = $doc->that;
    		$rec = $class::fetch($dId);
    		
    		// съдържа обобщена информация за сделките в нишката
    		//$deal = static::getDealInfo($data->rec->threadId);
    		$firstDoc = doc_Threads::getFirstDocument($data->rec->threadId);
    		if($firstDoc->haveInterface('bgerp_DealAggregatorIntf')){
    			$deal = $firstDoc->getAggregateDealInfo();
    		} elseif($firstDoc->haveInterface('bgerp_DealIntf')){
    			$deal = $firstDoc->getDealInfo();
    		}
    		expect($deal);
    		
    		// Попълваме данните от контрагента. Идват от фактурата
    		$addressContragent = trim($rec->contragentPlace . ' ' . $rec->contragentPCode);
	        if ($addressContragent && !empty($rec->contragentAddress)) {
	            $addressContragent .= '&nbsp;' . $rec->contragentAddress;
	        }  
	        $row->contragentCompany = $rec->contragentName;
	        $row->contragentCountry = drdata_Countries::fetchField($rec->contragentCountryId, 'commonNameBg');
	        $row->contragentAddress = $addressContragent;
	        
	        $uicContragent = drdata_Vats::getUicByVatNo($rec->contragentVatNo);
	        if ($uic != $rec->contragentVatNo) {
	        	$row->contragentCompanyVatNo = $rec->contragentVatNo;
	    	} 
	    	$row->contragentUicId = $uicContragent;
    		
       		$invoiceNo = str_pad($rec->number, '10', '0', STR_PAD_LEFT) . " / " . dt::mysql2verbal($rec->date, "d.m.Y");
       		$row->invoiceNo = $invoiceNo;
            
	       	// Продуктите
	       	if (count($deal->invoiced->products)) {
	       		$row->products = "<ol>";
	       		
		       	foreach($deal->invoiced->products as $iProduct){
		    		$ProductMan = cls::get($iProduct->classId);
		        	$productName = $ProductMan::getTitleById($iProduct->productId);
		        	$row->products .= "<li>$productName</li>";
				}
				
				$row->products .= "</ol>";
	       	}
	    
    	}
    	
    	$cTpl = $decContent->getBlock("declaratorInfo");
    	$cTpl->replace($managerData->name, 'declaratorName');
	    $cTpl->replace($recDec->declaratorPosition, 'declaratorPosition');
	    $cTpl->append2master();

    }

    
    /**
	 * Рендираме обобщаващата информация на отчетите
	 */
	static function on_AfterRenderSingle($mvc, $tpl, $data)
    {
    	$tpl->removePlaces();
    }

    
    /**
     * След проверка на ролите
     */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	switch ($action) { 
    		
            case 'activate':
                if (empty($rec->id)) {
                    // не се допуска активиране на незаписани декларации
                    $requiredRoles = 'no_one';
                } elseif (dec_DeclarationDetails::count("#declarationId = {$rec->id}") == 0) { 
                    // Не се допуска активирането на празни декларации без детайли
                    $requiredRoles = 'no_one';
                }
                break;
    	}
    }
    

    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * Връща заглавието и мярката на перото за продукта
     *
     * Част от интерфейса: intf_Register
     */
    function getItemRec($objectId)
    {
        $result = NULL;
        
        if ($rec = self::fetch($objectId)) {
            $result = (object)array(
                'title' => $this->getVerbal($rec, 'personId') . " [" . $this->getVerbal($rec, 'startFrom') . ']',
                'num' => $rec->id,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        $self = cls::get(__CLASS__);
        
        if ($rec = $self->fetch($objectId)) {
            $result = ht::createLink(static::getVerbal($rec, 'typeId'), array($self, 'Single', $objectId));
        } else {
            $result = '<i>неизвестно</i>';
        }
        
        return $result;
    }

	
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
        
    
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
    
    
    /****************************************************************************************
     *                                                                                      *
     *  ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf                                             *
     *                                                                                      *
     ****************************************************************************************/
   
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        $row->title = $this->getVerbal($rec, 'typeId');
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
    /**
     * Дали документа може да се добави към нишката
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	if(sales_Invoices::fetch("#threadId = {$threadId} AND #state = 'active'")){
    		return TRUE;
    	}
    	
    	return FALSE;
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
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашата декларация за съответствие") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
	/**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        return tr("|Декларация|* №{$rec->id}");
    }
}
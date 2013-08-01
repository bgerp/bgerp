<?php
/**
 * Документ "Оферта"
 *
 * Мениджър на документи за Оферта за продажба
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Quotations extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Оферти';


    /**
     * Абревиатура
     */
    var $abbr = 'Q';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'sales_Quotes';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, doc_ContragentDataIntf, email_DocumentIntf,  bgerp_DealIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, plg_Printing, doc_EmailCreatePlg, plg_Search,
                    doc_DocumentPlg, doc_ActivatePlg, bgerp_plg_Blank, doc_plg_BusinessDoc, acc_plg_DocumentSummary';
       
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Поле за търсене по дата
     */
    public $filterDateField = 'date';
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/document_quote.png';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, date, folderId, deliveryTermId, createdOn,createdBy';
    

    /**
     * Детайла, на модела
     */
    public $details = 'sales_QuotationsDetails' ;
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Оферта';
    
    
   /**
     * Шаблон за еденичен изглед
     */
   var $singleLayoutFile = 'sales/tpl/SingleLayoutQuote.shtml';
   
   
   /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'paymentMethodId, reff, recipient, attn, email, address';
    
    
   /**
     * Групиране на документите
     */ 
   var $newBtnGroup = "3.7|Търговия";
   
   
   /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('date', 'date', 'caption=Дата, mandatory'); 
        $this->FLD('validFor', 'time(uom=days,suggestions=10 дни|15 дни|30 дни|45 дни|60 дни|90 дни)', 'caption=Валидност,width=8em');
        $this->FLD('reff', 'varchar(255)', 'caption=Ваш реф,width=100%', array('attr' => array('style' => 'max-width:500px;')));
        $this->FLD('others', 'text(rows=4)', 'caption=Условия,width=100%', array('attr' => array('style' => 'max-width:500px;')));
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        $this->FLD('paymentMethodId', 'key(mvc=salecond_PaymentMethods,select=name)','caption=Плащане->Метод,width=8em');
        $this->FLD('paymentCurrencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута,width=8em');
        $this->FLD('rate', 'double(decimals=2)', 'caption=Плащане->Курс,width=8em');
        $this->FLD('vat', 'enum(yes=с начисляване,freed=освободено,export=без начисляване)','caption=Плащане->ДДС,oldFieldName=wat');
        $this->FLD('deliveryTermId', 'key(mvc=salecond_DeliveryTerms,select=codeName)', 'caption=Доставка->Условие,width=8em');
        $this->FLD('deliveryPlaceId', 'varchar(126)', 'caption=Доставка->Място,width=10em');
        
		$this->FLD('recipient', 'varchar', 'caption=Адресант->Фирма,class=contactData, changable');
        $this->FLD('attn', 'varchar', 'caption=Адресант->Лице,class=contactData, changable');
        $this->FLD('email', 'varchar', 'caption=Адресант->Имейл,class=contactData, changable');
        $this->FLD('tel', 'varchar', 'caption=Адресант->Тел.,class=contactData, changable');
        $this->FLD('fax', 'varchar', 'caption=Адресант->Факс,class=contactData, changable');
        $this->FLD('country', 'varchar', 'caption=Адресант->Държава,class=contactData, changable');
        $this->FLD('pcode', 'varchar', 'caption=Адресант->П. код,class=contactData, changable');
        $this->FLD('place', 'varchar', 'caption=Адресант->Град/с,class=contactData, changable');
        $this->FLD('address', 'varchar', 'caption=Адресант->Адрес,class=contactData, changable');
    	$this->FNC('quantity1', 'int', 'caption=Оферта->К-во 1,width=4em');
    	$this->FNC('quantity2', 'int', 'caption=Оферта->К-во 2,width=4em');
    	$this->FNC('quantity3', 'int', 'caption=Оферта->К-во 3,width=4em');
    }
    
    
	/**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	 $data->listFilter->showFields = 'search,' . $data->listFilter->showFields;
    	 $data->listFilter->input();
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
       $rec = &$data->form->rec;
       $mvc->populateDefaultData($rec);
       $locations = crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId, FALSE);
       $data->form->setSuggestions('deliveryPlaceId',  array('' => '') + $locations);
      
       if($rec->originId){
       		$data->form->setField('quantity1,quantity2,quantity3', 'input');
       }
    }
    
    
	/** 
	 * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
	    if($data->rec->state == 'active'){
	    	$items = $mvc->getItems($data->rec->id);
	       	if($items && sales_Sales::haveRightFor('add')){
	       		$data->toolbar->addBtn('Продажба', array('sales_Sales', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), NULL, 'ef_icon=img/16/star_2.png,title=Създаване на продажба по офертата');
	       	} elseif(!$items && (sales_Sales::haveRightFor('add') || haveRole('contractor'))) {
	       		$data->toolbar->addBtn('Заявка', array('sales_SaleRequests', 'CreateFromOffer', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), NULL, 'ef_icon=img/16/star_2.png,title=Създаване на нова заявка за продажба');	
	       	}
	    }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
	    	$rec = &$form->rec;
	    	
		    if(!$rec->rate){
			    $rec->rate = round(currency_CurrencyRates::getRate($rec->date, $rec->paymentCurrencyId, NULL), 4);
			}
		
	    	if(!currency_CurrencyRates::hasDeviation($rec->rate, $rec->date, $rec->paymentCurrencyId, NULL)){
			    $form->setWarning('rate', 'Изходната сума има голяма ралзика спрямо очакваното.
			    					  Сигурни ли сте че искате да запишете документа');
			}
		}
    }
    
    
	/**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
    	if($rec->originId){
			$origin = doc_Containers::getDocument($rec->originId);
			if($origin->className == 'techno_Specifications'){
				$originRec = $origin->fetch();
				$quantities = array($rec->quantity1, $rec->quantity2, $rec->quantity3);
				if($originRec->isOfferable == 'yes' && $quantities[0] || $quantities[1] || $quantities[2]){
					$mvc->sales_QuotationsDetails->insertFromSpecification($rec, $origin->that, $quantities);
				}
			}
		}
    }
    
    
    /**
     * Попълваме дефолт данните
     */
    public function populateDefaultData(&$rec)
    {
    	$rec->date = dt::now();
    	expect($data = doc_Folders::getContragentData($rec->folderId), "Проблем с данните за контрагент по подразбиране");
    	$contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
    	$contragentId = doc_Folders::fetchCoverId($rec->folderId);
    	$rec->contragentClassId = $contragentClassId;
    	$rec->contragentId = $contragentId;
    	
    	$currencyCode = ($data->countryId) ? drdata_Countries::fetchField($data->countryId, 'currencyCode') : acc_Periods::getBaseCurrencyCode($rec->date);
    	$rec->paymentCurrencyId = $currencyCode;
    	
    	if($rec->threadId){
    		$query = $this->getQuery();
    		$query->where("#threadId = {$rec->threadId}");
    		$query->orderBy('#createdOn', 'DESC');
    		$lastOffer = $query->fetch();
    	} 
    	
    	if(!$lastOffer){
    		$query = $this->getQuery();
    		$query->where("#folderId = {$rec->folderId}");
    		$query->orderBy('#createdOn', 'DESC');
    		$lastOffer = $query->fetch();
    	}
    	
    	if($lastOffer){
    		$fields = $this->selectFields("#class == contactData");
    		foreach ($fields as $name => $fld){
    			if(isset($lastOffer->$name)){
    				$rec->$name = $lastOffer->$name;
    			}
    		}
    	} else {
    		if ($data->company) {
    			$rec->recipient = $data->company;
    		}
    		
    		if($data->person) {
    			$rec->attn = $data->person;
    		}
    		
    		if(!$data->country){
    			$conf = core_Packs::getConfig('crm');
    			$data->country = $conf->BGERP_OWN_COMPANY_COUNTRY;
    		}
    		$rec->country = $data->country;
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
		if($fields['-single']){
			if(!Mode::is('printing')){
	    		$row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})" ;
	    	}
	    
	    	$row->number = $mvc->getHandle($rec->id);
			
			$username = core_Users::fetch($rec->createdBy);
			$row->username = core_Users::recToVerbal($username, 'names')->names;
			
			if($row->address){
				$row->contragentAdress = $row->address . ",";
			}
			$row->contragentAdress .= trim(sprintf(" <br />%s %s<br />%s",$row->pcode, $row->place, $row->country)); 
			
			switch($rec->vat){
				case 'yes':
					$row->vat = tr('с');
					break;
				case 'freed':
				case 'export':
					$row->vat = tr('без');
					break;
			}
			
			if($rec->rate == 1){
				unset($row->rate);
			}
			
			if($rec->others){
				$others = explode('<br>', $row->others);
				$row->others = '';
				foreach($others as $other){
					$row->others .= "<li>{$other}</li>";
				}
			}
			
			if($rec->deliveryPlaceId){
				if($placeId = crm_Locations::fetchField("#title = '{$rec->deliveryPlaceId}'", 'id')){
	    			$row->deliveryPlaceId = ht::createLinkRef($row->deliveryPlaceId, array('crm_Locations', 'single', $placeId), NULL, 'title=Към локацията');
				}
			}
			
			if(salecond_DeliveryTerms::haveRightFor('single', $rec->deliveryTermId) && !Mode::is('text', 'xhtml') && !Mode::is('printing')){
				$row->deliveryTermId = ht::createLinkRef($row->deliveryTermId, array('salecond_DeliveryTerms', 'single', $rec->deliveryTermId));
			}
		}
		
    	if($fields['-list']){
	    	if(doc_Folders::haveRightFor('single', $rec->folderId)){
	    		$img = doc_Folders::getIconImg($rec->folderId);
	    		$attr = array('class' => 'linkWithIcon', 'style' => 'background-image:url(' . $img . ');');
	    		$link = array('doc_Threads', 'list', 'folderId' => $rec->folderId);
            	$row->folderId = ht::createLink($row->folderId, $link, NULL, $attr);
	    	}
	    }
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
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Оферта №" .$this->abbr . $rec->id;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;

        return $row;
    }
    
    
	/**
     * Вкарваме css файл за единичния изглед
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$tpl->push('sales/tpl/styles.css', 'CSS');
    }
    
    
    /**
     * След проверка на ролите
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
    {
    	if($action == 'activate'){
    		if(!$rec->id) {
    			
    			// Ако документа се създава, то неможе да се активира
    			$res = 'no_one';
    		} else {
    			
    			// Ако няма задължителни продукти/услуги неможе да се активира
    			$detailQuery = sales_QuotationsDetails::getQuery();
    			$detailQuery->where("#quotationId = {$rec->id}");
    			$detailQuery->where("#optional = 'no'");
    			if(!$detailQuery->count()){
    				$res = 'no_one';
    			}
    		}
    	}
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашата оферта:") . ' #[#handle#]');
        $tpl->append($handle, 'handle');
        return $tpl->getContent();
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     * 
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
	public static function canAddToThread($threadId)
    {
    	$threadRec = doc_Threads::fetch($threadId);
    	$coverClass = doc_Folders::fetchCoverClassName($threadRec->folderId);
    	
    	return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
	/**
     * Документи-оферти могат да се добавят само в папки с корица контрагент.
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
    
        return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     * Ако офертата е базирана на чернова спецификация, активираме и нея
     */
    public static function on_Activation($mvc, &$rec)
    {
    	$rec = $mvc->fetch($rec->id);
    	$rec->state = 'active';
    	if($rec->originId){
    		$origin = doc_Containers::getDocument($rec->originId);
	    	if($origin->className == 'techno_Specifications'){
	    		$originRec = $origin->fetch();
	    		if($originRec->state == 'draft'){
	    			$originRec->state = 'active';
	    			techno_Specifications::save($originRec);
	    		}		
	    	}
    	}
    	
    	if($rec->deliveryPlaceId){
		    if(!crm_Locations::fetchField(array("#title = '[#1#]'", $rec->deliveryPlaceId), 'id')){
		    	$newLocation = (object)array(
		    						'title' => $rec->deliveryPlaceId,
		    						'countryId' => drdata_Countries::fetchField("#commonNameBg = '{$rec->country}' || #commonName = '{$rec->country}'", 'id'),
		    						'pCode' => $rec->pcode,
		    						'place' => $rec->place,
		    						'contragentCls' => $rec->contragentClassId,
		    						'contragentId' => $rec->contragentId,
		    						'type' => 'correspondence');
		    		
		    	// Ако локацията я няма в системата я записваме
		    	crm_Locations::save($newLocation);
		    }
		}
    }
    
    
    /**
     * Връща масив от изпозлваните документи в офертата
     * @param int $id - ид на оферта
     * @return param $res - масив с използваните документи
     * 					['class'] - Инстанция на документа
     * 					['id'] - Ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->sales_QuotationsDetails->getQuery();
    	$dQuery->EXT('state', 'sales_Quotations', 'externalKey=quotationId');
    	$dQuery->where("#quotationId = '{$id}'");
    	$dQuery->groupBy('productId,policyId');
    	while($dRec = $dQuery->fetch()){
    		$productMan = cls::get($dRec->policyId)->getProductMan();
    		if(cls::haveInterface('doc_DocumentIntf', $productMan)){
    			$res[] = (object)array('class' => $productMan, 'id' => $dRec->productId);
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     * 
     * @param int|object $id
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
    	$rec = $this->fetchRec($id);
    	$products = $this->getItems($id, $amount);
    	if(!count($products)) return FALSE;
    	
    	/* @var $result bgerp_iface_DealResponse */
        $result = new bgerp_iface_DealResponse();
    	$result->dealType = bgerp_iface_DealResponse::TYPE_SALE;
        
        $result->agreed->amount                  = $amount;
        $result->agreed->currency                = $rec->paymentCurrencyId;
        if($rec->deliveryPlaceId){
        	$result->agreed->delivery->location  = crm_Locations::fetchField("#title = '{$rec->deliveryPlaceId}'", 'id');
        }
        $result->agreed->delivery->term          = $rec->deliveryTermId;
    	$result->agreed->payment->method         = $rec->paymentMethodId;
    	
    	$result->agreed->products = $products;
        
        return $result;
    }
    
    
    /**
     * Помощна ф-я за връщане на всички продукти от офертата.
     * Ако има вариации на даден продукт и неможе да се
     * изчисли общата сума ф-ята връща NULL
     * @param int $id - ид на оферта
     * @param double $total - обща сума на продуктите
     */
    private function getItems($id, &$total = 0)
    {
    	$query = $this->sales_QuotationsDetails->getQuery();
    	$query->where("#quotationId = {$id} AND #optional = 'no'");
    	$total = 0;
    	$products = array();
    	while($detail = $query->fetch()){
    		$uIndex =  "{$detail->productId}|{$detail->policyId}";
    		if(array_key_exists($uIndex, $products) || !$detail->quantity) return NULL;
    		$total += $detail->quantity * ($detail->price * (1 + $detail->discount));
    		$products[$uIndex] = new sales_model_QuotationProduct($detail);
    	}
    	
    	return array_values($products);
    }
}
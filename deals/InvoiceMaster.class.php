<?php



/**
 * Базов клас за наследяване на ф-ри
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_InvoiceMaster extends core_Master
{
	
	
	/**
	 * Поле за единичния изглед
	 */
	public $rowToolsSingleField = 'number';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'dealValue,vatAmount,baseAmount,total,vatPercent,discountAmount';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'date';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $valiorFld = 'date';
    
    
    /**
     * Можели да се принтират оттеглените документи?
     */
    public $printRejected = TRUE;
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();
    
    
    /**
     * На кой ред в тулбара да се показва бутона за принтиране
     */
    public $printBtnToolbarRow = 1;
    
    
    /**
     * След описанието на полетата
     */
    protected static function setInvoiceFields(core_Master &$mvc)
    {
    	$mvc->FLD('date', 'date(format=d.m.Y)', 'caption=Дата,  notNull, mandatory');
    	$mvc->FLD('place', 'varchar(64)', 'caption=Място, class=contactData');
    	$mvc->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
    	$mvc->FLD('contragentId', 'int', 'input=hidden');
    	$mvc->FLD('contragentName', 'varchar', 'caption=Контрагент->Име, mandatory, class=contactData');
    	$mvc->FLD('responsible', 'varchar(255)', 'caption=Контрагент->Отговорник, class=contactData');
    	$mvc->FLD('contragentCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg)', 'caption=Контрагент->Държава,mandatory,contragentDataField=countryId');
    	$mvc->FLD('contragentVatNo', 'drdata_VatType', 'caption=Контрагент->VAT №,contragentDataField=vatNo');
    	$mvc->FLD('uicNo', 'type_Varchar', 'caption=Контрагент->Национален №,contragentDataField=uicId');
    	$mvc->FLD('contragentPCode', 'varchar(16)', 'caption=Контрагент->П. код,recently,class=pCode,contragentDataField=pCode');
    	$mvc->FLD('contragentPlace', 'varchar(64)', 'caption=Контрагент->Град,class=contactData,contragentDataField=place');
    	$mvc->FLD('contragentAddress', 'varchar(255)', 'caption=Контрагент->Адрес,class=contactData,contragentDataField=address');
    	$mvc->FLD('changeAmount', 'double(decimals=2)', 'input=none');
    	$mvc->FLD('reason', 'text(rows=2)', 'caption=Плащане->Основание, input=none');
    	$mvc->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods, select=description,allowEmpty)', 'caption=Плащане->Метод');
    	$mvc->FLD('dueDate', 'date', 'caption=Плащане->Краен срок');
    	$mvc->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута->Код,input=hidden');
    	$mvc->FLD('rate', 'double(decimals=2)', 'caption=Валута->Курс,input=hidden');
    	$mvc->FLD('deliveryId', 'key(mvc=cond_DeliveryTerms, select=codeName, allowEmpty)', 'caption=Доставка->Условие,input=hidden');
    	$mvc->FLD('deliveryPlaceId', 'key(mvc=crm_Locations, select=title)', 'caption=Доставка->Място');
    	$mvc->FLD('vatDate', 'date(format=d.m.Y)', 'caption=Данъчни параметри->Дата на ДС');
    	$mvc->FLD('vatRate', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)', 'caption=Данъчни параметри->ДДС,input=hidden');
    	$mvc->FLD('vatReason', 'varchar(255)', 'caption=Данъчни параметри->Основание,recently');
    	$mvc->FLD('additionalInfo', 'richtext(bucket=Notes, rows=6)', 'caption=Допълнително->Бележки');
    	$mvc->FLD('dealValue', 'double(decimals=2)', 'caption=Стойност, input=hidden,summary=amount');
    	$mvc->FLD('vatAmount', 'double(decimals=2)', 'caption=ДДС, input=none,summary=amount');
    	$mvc->FLD('discountAmount', 'double(decimals=2)', 'caption=Отстъпка->Обща, input=none,summary=amount');
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
    	if(!Request::get('Rejected', 'int')){
    		$data->listFilter->FNC('invState', 'enum(all=Всички, draft=Чернова, active=Контиран)', 'caption=Състояние,input,silent');
    		 
    		$data->listFilter->showFields .= ',invState';
    		$data->listFilter->input();
    		$data->listFilter->setDefault('invState', 'all');
    		 
    		if($rec = $data->listFilter->rec){
    		
    			// Филтър по състояние
    			if($rec->invState){
    				if($rec->invState != 'all'){
    					$data->query->where("#state = '{$rec->invState}'");
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * След като се поготви заявката за модела
     */
    public static function on_AfterGetQuery(core_Mvc $mvc, &$query)
    {
    	// Сортираме низходящо по номер
    	$query->orderBy('#number', 'DESC');
    }
    
    
    /**
     * Изпълнява се след обновяване на информацията за потребител
     */
    public static function on_AfterUpdate($mvc, $rec, $fields = NULL)
    {
    	if($rec->type === 'dc_note'){
    		
    		// Ако е известие и има поне един детайл обновяваме мастъра
    		$Detail = $mvc->mainDetail;
    		$query = $mvc->$Detail->getQuery();
    		$query->where("#{$mvc->$Detail->masterKey} = '{$rec->id}'");
    		if($query->fetch()){
    			$mvc->updateQueue[$rec->id] = $rec->id;
    		}
    	}
    }


    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id, $save = TRUE)
    {
    	$rec = $this->fetchRec($id);
    	$Detail = $this->mainDetail;
    	
    	$query = $this->$Detail->getQuery();
    	$query->where("#{$this->$Detail->masterKey} = '{$rec->id}'");
    	$recs = $query->fetchAll();
    	
    	if(count($recs)){
    		foreach ($recs as &$dRec){
    			$dRec->price = $dRec->price * $dRec->quantityInPack;
    		}
    	}
    	
    	$this->$Detail->calculateAmount($recs, $rec);
    	
    	$rec->dealValue = $this->_total->amount * $rec->rate;
    	$rec->vatAmount = $this->_total->vat * $rec->rate;
    	$rec->discountAmount = $this->_total->discount * $rec->rate;
    	
    	if($save){
    		return $this->save($rec);
    	}
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    public static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
    	$type = Request::get('type');
    	if(!$type || $type == 'invoice') return;
    	
    	$title = ($type == 'debit_note') ? 'Дебитно известие' : 'Кредитно известие';
    	$mvc->singleTitle = $title;
    }
    
    
    /**
     * Връща датата на последната ф-ра
     */
    protected function getNewestInvoiceDate()
    {
    	$query = $this->getQuery();
    	$query->where("#state = 'active'");
    	$query->orderBy('date', 'DESC');
    	$query->limit(1);
    	$lastRec = $query->fetch();
    	
    	return $lastRec->date;
    }
    
    
    /**
     * Валидиране на полето 'vatDate' - дата на данъчно събитие (ДС)
     *
     * Грешка ако ДС е след датата на фактурата или на повече от 5 дни преди тази дата.
     */
    public static function on_ValidateVatDate(core_Mvc $mvc, $rec, core_Form $form)
    {
    	if (empty($rec->vatDate)) {
    		return;
    	}
    
    	// Датата на ДС не може да бъде след датата на фактурата, нито на повече от 5 дни преди нея.
    	if ($rec->vatDate > $rec->date || dt::addDays(5, $rec->vatDate) < $rec->date) {
    		$form->setError('vatDate', '|Данъчното събитие трябва да е до 5 дни|* <b>|преди|*</b> |датата на фактурата|*');
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public function renderSingleLayout($data)
    {
    	$tpl = parent::renderSingleLayout($data);
    	
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    	
    	return $tpl;
    }


    /**
     * Подготвя вербалните данни на моята фирма
     */
    protected function prepareMyCompanyInfo(&$row)
    {
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
    	$Companies = cls::get('crm_Companies');
    	$row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
    	$row->MyCompany = tr(core_Lg::transliterate($row->MyCompany));
    	$row->MyAddress = $Companies->getFullAdress($ownCompanyData->companyId)->getContent();
    	$row->MyAddress = core_Lg::transliterate($row->MyAddress);
    	
    	$uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
    	if($uic != $ownCompanyData->vatNo){
    		$row->MyCompanyVatNo = $ownCompanyData->vatNo;
    	}
    
    	$row->uicId = $uic;
    }


    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	 
    	if($rec->type == 'invoice' && $rec->state == 'active'){
    		if($mvc->haveRightFor('add', (object)array('type' => 'dc_note','threadId' => $rec->threadId)) && $mvc->canAddToThread($rec->threadId)){
    			$data->toolbar->addBtn('Известие', array($mvc, 'add', 'originId' => $rec->containerId, 'type' => 'dc_note', 'ret_url' => TRUE), 'ef_icon=img/16/layout_join_vertical.png,title=Дебитно или кредитно известие към документа,rows=2');
    		}
    	}
    }


    /**
     * Попълва дефолтите на Дебитното / Кредитното известие
     */
    protected function populateNoteFromInvoice(core_Form &$form, core_ObjectReference $origin)
    {
    	$caption = ($form->rec->type == 'debit_note') ? 'Увеличение' : 'Намаление';
    	
    	$invArr = (array)$origin->fetch();
    	
    	// Трябва фактурата основание да не е ДИ или КИ
    	expect($invArr['type'] == 'invoice');
    	
    	$number = $origin->getInstance()->recToVerbal((object)$invArr)->number;
    	 
    	$invDate = dt::mysql2verbal($invArr['date'], 'd.m.Y');
    	
    	if($invArr['type'] != 'dc_note'){
    		$form->setField('changeAmount', "unit={$invArr['currencyId']} без ДДС");
    		$form->setField('changeAmount', "input,caption=Задаване на увеличение/намаление на фактура->Промяна");
    		
    		if($invArr['dpOperation'] == 'accrued'){
    			
    			// Ако е известие към авансова ф-ра поставяме за дефолт сумата на фактурата
    			$caption = '|Промяна на авансово плащане|*';
    			$form->setField('changeAmount', "caption={$caption}->|Аванс|*,mandatory");
    		}
    	}
    
    	foreach(array('id', 'number', 'date', 'containerId', 'additionalInfo', 'dealValue', 'vatAmount', 'state', 'discountAmount', 'createdOn', 'createdBy', 'modifiedOn', 'modifiedBy', 'vatDate', 'dpAmount', 'dpOperation') as $key){
    		unset($invArr[$key]);
    	}
    
    	if($form->rec->type == 'credit_note'){
    		unset($invArr['dueDate']);
    		$form->setField('dueDate', 'input=none');
    	}
    	
    	// Копиране на повечето от полетата на фактурата
    	foreach($invArr as $field => $value){
    		$form->setDefault($field, $value);
    	}
    	 
    	$form->setDefault('date', dt::today());
    	
    	$form->setField('vatRate', 'input=hidden');
    	$form->setField('deliveryId', 'input=none');
    	$form->setField('deliveryPlaceId', 'input=none');
    
    	foreach(array('rate', 'currencyId', 'contragentName', 'contragentVatNo', 'uicNo', 'contragentCountryId', 'dueDate') as $name){
    		if($form->rec->$name){
    			$form->setReadOnly($name);
    		}
    	}
    }
    

    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейла по подразбиране
     */
    public static function getDefaultEmailBody($id)
    {
    	$handle = static::getHandle($id);
    	$me = cls::get(get_called_class());
    	
    	if($me->getField('type', FALSE)){
    		$rec = static::fetch($id);
    		switch($rec->type){
    			case 'invoice':
    				$type = "приложената фактура";
    				break;
    			case 'debit_note':
    				$type = "приложеното дебитно известие";
    				break;
    			case 'credit_note':
    				$type = "";
    			case 'dc_note':
    				$type = ($rec->dealValue <= 0) ? "приложеното кредитно известие" : "приложеното дебитно известие";
    				break;
    		}
    	} else {
    		$type = 'приложената проформа фактура';
    	}
    	
    	// Създаване на шаблона
    	$tpl = new ET(tr("Моля запознайте се с") . " [#type#]:\n#[#handle#]");
    	$tpl->append($handle, 'handle');
    	$tpl->append(tr($type), 'type');
    
    	return $tpl->getContent();
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
		$row = new stdClass();
		
		$template = $this->getTemplate($id);
		$lang = doc_TplManager::fetchField($template, 'lang');
		
		if($lang){
			core_Lg::push($lang);
		}
		
        $row->title = static::getRecTitle($rec);
        
        if($lang){
        	core_Lg::pop();
        }
        
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->authorId = $rec->createdBy;
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
   }

   
   /**
    * Връща масив от използваните нестандартни артикули в фактурата
    * @param int $id - ид на фактура
    * @return param $res - масив с използваните документи
    * 					['class'] - инстанция на документа
    * 					['id'] - ид на документа
    */
   public function getUsedDocs_($id)
   {
	   	return deals_Helper::getUsedDocs($this, $id);
   }


   /**
    * Извиква се след подготовката на toolbar-а за табличния изглед
    */
   public static function on_AfterPrepareListToolbar($mvc, &$data)
   {
	   	if(!empty($data->toolbar->buttons['btnAdd'])){
	   		$data->toolbar->removeBtn('btnAdd');
	   	}
   }


   /**
    * Документа не може да се активира ако има детайл с количество 0
    */
   public static function on_AfterCanActivate($mvc, &$res, $rec)
   {
	   	if($rec->type == 'dc_note' && isset($rec->changeAmount)){
	   		return $res = TRUE;
	   	}
	   	
	   	// Ако няма ид, не може да се активира документа
	   	if(empty($rec->id) && !isset($rec->dpAmount)) {
	   		
	   		return $res = FALSE;
	   	}
	   	 
	   	// Ако има Авансово плащане може да се активира
	   	if(isset($rec->dpAmount)){
	   		$res = (round($rec->dealValue, 2) < 0 || is_null($rec->dealValue)) ? FALSE : TRUE;
	   		
	   		return;
	   	}
	   	 
	   	$Detail = $mvc->mainDetail;
	   	$dQuery = $mvc->$Detail->getQuery();
	   	$dQuery->where("#{$mvc->$Detail->masterKey} = {$rec->id}");
	   	
	   	if($rec->type == 'dc_note'){
	   		$cached = $mvc->getInvoiceDetailedInfo($rec->originId);
	   		
	   		$cloneQuery = clone $dQuery;
	   		while($dRec = $cloneQuery->fetch()){
	   			$difQuantity = $dRec->quantity - $cached[$dRec->productId][$dRec->packagingId]['quantity'];
	   			$difPrice = $dRec->packPrice - $cached[$dRec->productId][$dRec->packagingId]['price'];
	   			
	   			if(round($difQuantity, 5) != 0 || round($difPrice, 5) != 0){
	   				$res = TRUE;
	   				return;
	   			}
	   		}
	   		
	   		// Ако няма детайли и има сума за промяна може да се активира
	   		if(!$dRec && isset($rec->changeAmount)){
	   			$res = TRUE;
	   			return;
	   		}
	   		
	   		$res = FALSE;
	   	}
	   	
	   	$dQuery->where("#quantity = 0");
	   	 
	   	// Ако има поне едно 0-во к-во документа, не може да се активира
	   	if($dQuery->fetch()){
	   		$res = FALSE;
	   	}
   }
   
   
   /**
    * Генерира фактура от пораждащ документ: може да се породи от:
    * 
    * 1. Продажба / Покупка
    * 2. Фактура тоест се прави ДИ или КИ
    */
   public static function on_AfterCreate($mvc, $rec)
   {
	   	expect($origin = $mvc::getOrigin($rec));
	   	 
	   	if ($origin->haveInterface('bgerp_DealAggregatorIntf')) {
	   		$info = $origin->getAggregateDealInfo();
	   		$agreed = $info->get('products');
	   		$products = $info->get('shippedProducts');
	   		$invoiced = $info->get('invoicedProducts');
	   		$packs = $info->get('shippedPacks');
	   		
	   		$mvc::prepareProductFromOrigin($mvc, $rec, $agreed, $products, $invoiced, $packs);
	   	} elseif($origin->getInstance() instanceof $mvc){
	   		$dpOperation = $origin->fetchField('dpOperation');
	   		
	   		// Ако начисляваме аванс или има въведена нова стойност не се копират детайлите
	   		if($dpOperation == 'accrued' || isset($rec->changeAmount)) return;
	   		
	   		$Detail = $mvc->mainDetail;
	   		$query = $mvc->$Detail->getQuery();
	   		$query->where("#{$mvc->$Detail->masterKey} = '{$origin->that}'");
	   		
	   		while($dRec = $query->fetch()){
	   			$dRec->{$mvc->$Detail->masterKey} = $rec->id;
	   			unset($dRec->id);
	   			
	   			$Detail::save($dRec);
	   		}
	   	}
   }
   

   /**
    * Подготвя продуктите от ориджина за запис в детайла на модела
    */
   protected static function prepareProductFromOrigin($mvc, $rec, $agreed, $products, $invoiced, $packs)
   {
	   	if(count($products) != 0){
	   		
	   		// Записваме информацията за продуктите в детайла
	   		foreach ($products as $product){
	   			$continue = FALSE;
	   			$diff = $product->quantity;
	   			if(count($invoiced)){
	   				foreach ($invoiced as $inv){
	   					if($inv->productId == $product->productId){
	   						$diff = $product->quantity - $inv->quantity;
	   						if($diff <= 0){
	   							$continue = TRUE;
	   						}
	   						break;
	   					}
	   				}
	   			} elseif($diff <= 0){
	   				
	   				$continue = TRUE;
	   			}
	   	
	   			if($continue) continue;
	   			
	   			$mvc::saveProductFromOrigin($mvc, $rec, $product, $packs, $diff);
	   		}
	   	}
   }
   
   
   /**
    * Записва продукт от ориджина
    */
   protected static function saveProductFromOrigin($mvc, $rec, $product, $packs, $restAmount)
   {
	   	$dRec = clone $product;
	   	$index = $product->productId;
	   	
	   	// Ако няма информация за експедираните опаковки, визмаме основната опаковка
   		if(!isset($packs[$index])){
   			$packs1 = cat_Products::getPacks($product->productId);
   			$dRec->packagingId = key($packs1);
   			
   			$packQuantity = 1;
   			if($pRec = cat_products_Packagings::fetch("#productId = {$product->productId} AND #packagingId = {$dRec->packagingId}")){
   				$packQuantity = $pRec->quantity;
   			}
	   	} else {
	   		// Иначе взимаме най-удобната опаковка
	   		$packQuantity = $packs[$index]->inPack;
	   		$dRec->packagingId = $packs[$index]->packagingId;
	   	}
	   	
	   	$Detail = $mvc->mainDetail;
	   	$dRec->{$mvc->$Detail->masterKey} = $rec->id;
	   	$dRec->discount        			  = $product->discount;
	   	$dRec->price 		  			  = ($product->amount) ? ($product->amount / $product->quantity) : $product->price;
	   	$dRec->quantityInPack 			  = $packQuantity;
	   	$dRec->quantity       			  = $restAmount / $packQuantity;
	   	
	   	if($dRec->amount !== 0) {
	   		$mvc->$Detail->save($dRec);
	   	}
   }
   
   
   /**
    * Подготвя данните (в обекта $data) необходими за единичния изглед
    */
   public function prepareSingle_($data)
   {
	   	parent::prepareSingle_($data);
	   	$rec = &$data->rec;
	   	
	   	if(empty($data->noTotal)){
	   		if(isset($rec->type) && $rec->type != 'invoice'){
	   			$this->_total = new stdClass();
	   			$this->_total->amount = $rec->dealValue / $rec->rate;
	   			$this->_total->vat = $rec->vatAmount / $rec->rate;
	   		}
	   		
	   		$this->invoke('BeforePrepareSummary', array($this->_total));
	   		$data->summary = deals_Helper::prepareSummary($this->_total, $rec->date, $rec->rate, $rec->currencyId, $rec->vatRate, TRUE, $rec->tplLang);
	   		$data->row = (object)((array)$data->row + (array)$data->summary);
	   		$data->row->vatAmount = $data->summary->vatAmount;
	   	}
   }
    
    
   /**
    * След подготовка на тулбара на единичен изглед.
    */
   public static function on_AfterPrepareSingle($mvc, &$res, &$data)
   {
    	$rec = &$data->rec;
    	
    	$myCompany = crm_Companies::fetchOwnCompany();
    	if($rec->contragentCountryId != $myCompany->countryId){
    		$data->row->place = str::utf2ascii($data->row->place);
    	}
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене, това са името на
     * документа или папката
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	// Тук ще генерираме всички ключови думи
    	$detailsKeywords = '';
    
    	// заявка към детайлите
    	$Detail = cls::get($mvc->mainDetail);
    	$query = $Detail->getQuery();
    	
    	// точно на тази фактура детайлите търсим
    	$query->where("#{$Detail->masterKey} = '{$rec->id}'");
    
    	while ($recDetails = $query->fetch()){
    		// взимаме заглавията на продуктите
    		$productTitle = cat_Products::getTitleById($recDetails->productId);
    		// и ги нормализираме
    		$detailsKeywords .= " " . plg_Search::normalizeText($productTitle);
    	}
    	 
    	// добавяме новите ключови думи към основните
    	$res = " " . $res . " " . $detailsKeywords;
    }
    
    
    /**
     * След подготовка на формата
     */
    protected static function prepareInvoiceForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('date', dt::today());
    	
    	$coverClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
    	$coverId = doc_Folders::fetchCoverId($form->rec->folderId);
    	$form->rec->contragentName = $coverClass::fetchField($coverId, 'name');
    
    	$className = doc_Folders::fetchCoverClassName($form->rec->folderId);
    	if($className == 'crm_Persons'){
    		$numType = 'bglocal_EgnType';
    		$form->setField('uicNo', 'caption=Контрагент->ЕГН');
    		$form->getField('uicNo')->type = cls::get($numType);
    	}
    
    	$type = Request::get('type');
    	if(empty($type)){
    		$type = 'invoice';
    	}
    	$form->setDefault('type', $type);
    	 
    	// При създаване на нова ф-ра зареждаме полетата на
    	// формата с разумни стойности по подразбиране.
    	expect($origin = $mvc::getOrigin($form->rec));
    	
    	if($origin->haveInterface('bgerp_DealAggregatorIntf')){
    		$aggregateInfo         = $origin->getAggregateDealInfo();
    		 
    		$form->rec->vatRate    = $aggregateInfo->get('vatType');
    		$form->rec->currencyId = $aggregateInfo->get('currency');
    		$form->rec->rate       = $aggregateInfo->get('rate');
    		 
    		if($aggregateInfo->get('paymentMethodId')){
    			$form->rec->paymentMethodId = $aggregateInfo->get('paymentMethodId');
    			$form->setField('paymentMethodId', 'input=hidden');
    		}
    		 
    		$form->rec->deliveryId = $aggregateInfo->get('deliveryTerm');
    		if($aggregateInfo->get('deliveryLocation')){
    			$form->setDefault('deliveryPlaceId', $aggregateInfo->get('deliveryLocation'));
    		}
    		
    		$form->setField('dueDate', 'input=none');
    		$data->aggregateInfo = $aggregateInfo;
    		$form->aggregateInfo = $aggregateInfo;
    	} 
    	 
    	// Ако ориджина също е фактура
    	if($origin->className  == $mvc->className){
    		$mvc->populateNoteFromInvoice($form, $origin);
    		$data->flag = TRUE;
    	}
    	 
    	if(empty($data->flag)){
    		$form->setDefault('currencyId', drdata_Countries::fetchField(($form->rec->contragentCountryId) ? $form->rec->contragentCountryId : $mvc->fetchField($form->rec->id, 'contragentCountryId'), 'currencyCode'));
    		$locations = crm_Locations::getContragentOptions($coverClass, $coverId);
    		$form->setOptions('deliveryPlaceId',  array('' => '') + $locations);
    	}
    	 
    	// Метод който да бъде прихванат от deals_plg_DpInvoice
    	$mvc->prepareDpInvoicePlg($data);
    }
    
    
    /**
     * След изпращане на формата
     */
    protected static function inputInvoiceForm(core_Mvc $mvc, core_Form $form)
    {
    	if ($form->isSubmitted()) {
    		$rec = &$form->rec;
    		
    		// Извлича се платежния план
    		if($form->rec->paymentMethodId){
    			if(isset($form->aggregateInfo)){
    				$plan = cond_PaymentMethods::getPaymentPlan($form->rec->paymentMethodId, $form->aggregateInfo->get('amount'), $form->rec->date);
    				
    				if(isset($plan['deadlineForBalancePayment'])){
    					$rec->dueDate = $plan['deadlineForBalancePayment'];
    				}
    			}
    		}
    		
    		if(!$rec->rate){
    			$rec->rate = round(currency_CurrencyRates::getRate($rec->date, $rec->currencyId, NULL), 4);
    			if(!$rec->rate){
    				$form->setError('rate', "Не може да се изчисли курс");
    			}
    		} else {
    			if($msg = currency_CurrencyRates::hasDeviation($rec->rate, $rec->date, $rec->currencyId, NULL)){
    				$form->setWarning('rate', $msg);
    			}
    		}
    		 
    		$Vats = cls::get('drdata_Vats');
    		$rec->contragentVatNo = $Vats->canonize($rec->contragentVatNo);
    		 
    		foreach ($mvc->fields as $fName => $field) {
    			$mvc->invoke('Validate' . ucfirst($fName), array($rec, $form));
    		}
    		 
    		if(strlen($rec->contragentVatNo) && !strlen($rec->uicNo)){
    			$rec->uicNo = drdata_Vats::getUicByVatNo($rec->contragentVatNo);
    		} elseif(!strlen($rec->contragentVatNo) && !strlen($rec->uicNo)){
    			$form->setError('contragentVatNo,uicNo', 'Трябва да е въведен поне един от номерата');
    		}
    		 
    		// Ако е ДИ или КИ
    		if($rec->type != 'invoice'){
    			if(isset($rec->changeAmount)){
    				if($rec->changeAmount == 0){
    					$form->setError('changeAmount', 'не може да се създаде известие с нулева стойност');
    					
    					return;
    				}
    			}
    			
    			// Изчисляване на стойността на ддс-то
    			$vat = acc_Periods::fetchByDate()->vatRate;
    			
    			// Ако не трябва да се начислява ддс, не начисляваме
    			if($rec->vatRate != 'yes' && $rec->vatRate != 'separate'){
    				$vat = 0;
    			}
    			
    			$origin = doc_Containers::getDocument($rec->originId);
    			$originRec = $origin->fetch('dpAmount,dpOperation,dealValue');
    			
    			if($originRec->dpOperation == 'accrued' || isset($rec->changeAmount)){
    				$diff = ($rec->changeAmount * $rec->rate);
    				$rec->vatAmount = $diff * $vat;
    				
    				// Стойността е променената сума
    				$rec->dealValue = $diff;
    			}
    		}
    	}
    
    	acc_Periods::checkDocumentDate($form);
    
    	// Метод който да бъде прихванат от deals_plg_DpInvoice
    	$mvc->inputDpInvoice($form);
    }
    
    
    /**
     * Преди запис в модела
     */
    protected static function beforeInvoiceSave($rec)
    {
    	if (!empty($rec->folderId)) {
    		$rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
    		$rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
    	}
    
    	if($rec->state == 'active'){
    		 
    		if(empty($rec->place) && $rec->state == 'active'){
    			$inCharge = cls::get($rec->contragentClassId)->fetchField($rec->contragentId, 'inCharge');
    			$inChargeRec = crm_Profiles::getProfile($inCharge);
    			$myCompany = crm_Companies::fetchOwnCompany();
    			$place = empty($inChargeRec->place) ? $myCompany->place : $inChargeRec->place;
    			$countryId = empty($inChargeRec->country) ? $myCompany->countryId : $inChargeRec->country;
    
    			$rec->place = $place;
    			if($rec->contragentCountryId != $countryId){
    				$cCountry = drdata_Countries::fetchField($countryId, 'commonNameBg');
    				$rec->place .= (($place) ? ", " : "") . $cCountry;
    			}
    		}
    	}
    }
    
    
    /**
     * Вербално представяне на фактурата
     */
    protected static function getVerbalInvoice($mvc, $rec, $row, $fields)
    {
    	if($rec->number){
    		$row->number = str_pad($rec->number, '10', '0', STR_PAD_LEFT);
    	}
    	 
    	if($rec->type == 'dc_note'){
    		$row->type = ($rec->dealValue <= 0) ? 'Кредитно известие' : 'Дебитно известие';
    	}
    	
    	if($fields['-list']){
    		if($rec->number){
    			$row->number = ht::createLink($row->number, array($mvc, 'single', $rec->id),NULL, 'ef_icon=img/16/invoice.png');
    		}
    	
    		$total = $rec->dealValue + $rec->vatAmount - $rec->discountAmount;
    		@$row->dealValue = $mvc->getFieldType('dealValue')->toVerbal($total / $rec->rate);
    		$row->dealValue = "<span class='cCode' style='float:left'>{$rec->currencyId}</span>&nbsp;" . $row->dealValue;
    	
    		$baseCode = acc_Periods::getBaseCurrencyCode($rec->date);
    		$row->vatAmount = "<span class='cCode' style='float:left'>{$baseCode}</span>&nbsp;" . $row->vatAmount;
    	}
    	
    	if($fields['-single']){
    		core_Lg::push($rec->tplLang);
    		
    		if($rec->originId && $rec->type != 'invoice'){
    			unset($row->deliveryPlaceId, $row->deliveryId);
    		}
    	
    		if(doc_Folders::fetchCoverClassName($rec->folderId) == 'crm_Persons'){
    			$row->cNum = tr('|ЕГН|* / <i>Personal №</i>');
    		} else {
    			$row->cNum = tr('|ЕИК|* / <i>UIC</i>');
    		}
    	
    		$userRec = core_Users::fetch($rec->createdBy);
    		$row->username = core_Lg::transliterate(core_Users::recToVerbal($userRec, 'names')->names);
    	
    		if($rec->type != 'invoice' && !($mvc instanceof sales_Proformas)){
    			$originRec = $mvc->getOrigin($rec)->fetch();
    			$originRow = $mvc->recToVerbal($originRec);
    			$row->originInv = $originRow->number;
    			$row->originInvDate = $originRow->date;
    		}
    			
    		if($rec->rate == 1){
    			unset($row->rate);
    		}
    			
    		if(!$row->vatAmount){
    			$coreConf = core_Packs::getConfig('core');
    			$pointSign = $coreConf->EF_NUMBER_DEC_POINT;
    			
    			$row->vatAmount = "<span class='quiet'>0" . $pointSign . "00</span>";
    		}
    		
    		if($rec->deliveryPlaceId){
    			if($gln = crm_Locations::fetchField($rec->deliveryPlaceId, 'gln')){
    				$row->deliveryPlaceId .= ', ' . $gln;
    			}
    		}
    		
    		// Ако не е въведена дата на даначно събитие, приема се че е текущата
    		if(empty($rec->vatDate)){
    			$row->vatDate = $mvc->getFieldType('vatDate')->toVerbal($rec->date);
    		}
    		
    		$mvc->prepareMyCompanyInfo($row);
    		core_Lg::pop();
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$row = static::recToVerbal($rec, 'type,number,-list');
    	$row->number = strip_tags($row->number);
    	$num = ($row->number) ? $row->number : $rec->id;
    
    	return tr("|{$row->type}|* №{$num}");
    }


    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     * @return bgerp_iface_DealAggregator
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$aggregator)
    {
    	$rec = $this->fetchRec($id);
    	$total = $rec->dealValue + $rec->vatAmount - $rec->discountAmount;
    	$total = ($rec->type == 'credit_note') ? -1 * $total : $total;
    
    	$aggregator->sum('invoicedAmount', $total);
    	$aggregator->setIfNot('invoicedValior', $rec->date);
    	$aggregator->setIfNot('paymentMethodId', $rec->paymentMethodId);
    	
    	if(isset($rec->dpAmount)){
    		if($rec->dpOperation == 'accrued'){
    			$aggregator->sum('downpaymentInvoiced', $total);
    		} elseif($rec->dpOperation == 'deducted') {
    			$vat = acc_Periods::fetchByDate($rec->date)->vatRate;
    			
    			// Колко е приспаднатото плащане с ддс
    			$deducted = abs($rec->dpAmount);
    			$vatAmount = ($rec->vatRate == 'yes' || $rec->vatRate == 'separate') ? ($deducted) * $vat : 0;
    			$aggregator->sum('downpaymentDeducted', $deducted + $vatAmount);
    		}
    	} else {
    		
    		// Ако е ДИ и КИ към ф-ра за начисляване на авансово плащане, променяме платения аванс по сделката
    		if($rec->type == 'dc_note'){
    			$originOperation = doc_Containers::getDocument($rec->originId)->fetchField('dpOperation');
    			if($originOperation == 'accrued'){
    				$aggregator->sum('downpaymentInvoiced', $total);
    			}
    		}
    	}
    
    	$Detail = $this->mainDetail;
    	
    	$dQuery = $Detail::getQuery();
    	$dQuery->where("#invoiceId = {$rec->id}");
    
    	// Намираме всички фактурирани досега продукти
    	$invoiced = $aggregator->get('invoicedProducts');
    	while ($dRec = $dQuery->fetch()) {
    		$p = new stdClass();
    		$p->productId   = $dRec->productId;
    		$p->packagingId = $dRec->packagingId;
    		$p->quantity    = $dRec->quantity * $dRec->quantityInPack;
    
    		// Добавяме към фактурираните продукти
    		$update = FALSE;
    		if(count($invoiced)){
    			foreach ($invoiced as &$inv){
    				if($inv->productId == $p->productId){
    					$inv->quantity += $p->quantity;
    					$update = TRUE;
    					break;
    				}
    			}
    		}
    		 
    		if(!$update){
    			$invoiced[] = $p;
    		}
    	}
    	
    	$aggregator->set('invoicedProducts', $invoiced);
    }
    
    
    /**
     * След подготовка на авансова ф-ра
     */
    public static function on_AfterPrepareDpInvoicePlg($mvc, &$res, &$data)
    {
    	
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputDpInvoice($mvc, &$res, &$form)
    {
    	
    }
    
    
    /**
     * Кешира информация за оригиналните стойностти на детайлите на известието
     */
    public function getInvoiceDetailedInfo($containerId)
    {
    	expect($document = doc_Containers::getDocument($containerId));
    	expect($document->getInstance() instanceof $this);
    	
    	if(!isset($this->cache[$containerId])){
    		$cache = array();
    		$Detail = $this->mainDetail;
    		$query = $Detail::getQuery();
    		$query->where("#{$this->$Detail->masterKey} = '{$document->that}'");
    		while($dRec = $query->fetch()){
    			$cache[$dRec->productId][$dRec->packagingId] = array('quantity' => $dRec->quantity, 'price' => $dRec->packPrice);
    		}
    		$this->cache[$containerId] = $cache;
    	}
    	
    	return $this->cache[$containerId];
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'reject' && isset($rec)){
    		if($mvc->fetch("#originId = {$rec->containerId} AND #state = 'active'")){
    			$res = 'no_one';
    		}
    	}
    }
}

<?php
/**
 * Клас 'store_ShipmentOrders'
 *
 * Мениджър на експедиционни нареждания
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ShipmentOrders extends core_Master
{
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Експедиционни нареждания';


    /**
     * Абревиатура
     */
    var $abbr = 'exp';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf,
                          acc_RegisterIntf=sales_RegisterImpl,
                          acc_TransactionSourceIntf=store_shipmentorders_Transaction, bgerp_DealIntf';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools, store_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable,
                    doc_DocumentPlg, plg_ExportCsv, acc_plg_DocumentSummary,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices,
                    doc_plg_BusinessDoc2, acc_plg_Registry';

    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'ceo,store';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,store';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'ceo,store';
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    public $canView = 'ceo,store';


    /**
     * Кой може да го види?
     */
    var $canViewprices = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'ceo,store';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
     public $listFields = 'id, valior, contragentClassId, contragentId, amountDelivered,
                             createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     * 
     * @var string
     */
    public $rowToolsField;


    /**
     * Детайла, на модела
     *
     * @var string|array
     */
    public $details = 'store_ShipmentOrderDetails' ;
    

    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Експедиционно нареждане';
    
    
    /**
     * Файл за единичния изглед
     */
    var $singleLayoutFile = 'store/tpl/SingleLayoutShipmentOrder.shtml';

   
    /**
     * Групиране на документите
     */ 
    var $newBtnGroup = "3.2|Търговия";
   
    
   /**
     * Полета свързани с цени
     */
    var $priceFields = 'amountDelivered';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)',
            'input=none,caption=Плащане->Валута');
                
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)',
            'caption=От склад, mandatory'); // наш склад, от където се експедира стоката
        $this->FLD('chargeVat', 'enum(yes=Включено, no=Отделно, freed=Oсвободено,export=Без начисляване)', 'caption=ДДС');
        
        /*
         * Стойности
         */
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Доставено,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountDeliveredVat', 'double(decimals=2)', 'input=none,summary=amount');
        
        /*
         * Контрагент
         */ 
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        /*
         * Доставка
         */
        $this->FLD('termId', 'key(mvc=cond_DeliveryTerms,select=codeName)', 'caption=Условие');
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title)', 
            'caption=Обект до,silent'); // обект, където да бъде доставено (allowEmpty)
        $this->FLD('deliveryTime', 'datetime', 'caption=Срок до'); // до кога трябва да бъде доставено
        
        /*
         * Допълнително
         */
        $this->FLD('note', 'richtext(bucket=Notes)', 'caption=Допълнително->Бележки', array('attr'=>array('rows'=>3)));
    	
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
    }



    /**
     * След промяна в детайлите на обект от този клас
     *
     * @TODO Тук да се запомнят само мастър ид-тата, а същинското обновление на мастъра да се
     *       направи на on_Shutdown
     *
     * @param core_Manager $mvc
     * @param int $id ид на мастър записа, чиито детайли са били променени
     * @param core_Manager $detailMvc мениджър на детайлите, които са били променени
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
        $rec = $mvc->fetchRec($id);
    
        /* @var $query core_Query */
        $query = $detailMvc->getQuery();
        $query->where("#{$detailMvc->masterKey} = '{$id}'");
    
        $rec->amountDeliveredVat = $rec->amountDelivered = 0;
    
        while ($detailRec = $query->fetch()) {
            $vat = 1;
    
            if ($rec->chargeVat == 'yes' || $rec->chargeVat == 'no') {
                $ProductManager = cls::get($detailRec->classId);
    			$vat += $ProductManager->getVat($detailRec->productId, $rec->valior);
            }
    		
            $rec->amountDelivered += $detailRec->amount;
            $rec->amountDeliveredVat += $detailRec->amount * $vat;
        }
    	
        $mvc->save($rec);
    }
    
    
    /**
     * След създаване на запис в модела
     * 
     * @param store_Stores $mvc
     * @param store_model_ShipmentOrder $rec
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        $origin = static::getOrigin($rec);
        
        // Ако новосъздадения документ има origin, който поддържа bgerp_AggregateDealIntf,
        // използваме го за автоматично попълване на детайлите на ЕН
        
        if ($origin->haveInterface('bgerp_DealAggregatorIntf')) {
            /* @var $aggregatedDealInfo bgerp_iface_DealResponse */
            $aggregatedDealInfo = $origin->getAggregateDealInfo();
            
            $remainingToShip = clone $aggregatedDealInfo->agreed;
            $remainingToShip->pop($aggregatedDealInfo->shipped);
            
            /* @var $product bgerp_iface_DealProduct */
            foreach ($remainingToShip->products as $product) {
                if ($product->quantity <= 0) {
                    continue;
                }
                
                $shipProduct = new store_model_ShipmentProduct(NULL);
                
                $shipProduct->shipmentId  = $rec->id;
                $shipProduct->classId     = cls::get($product->classId)->getClassId();
                $shipProduct->productId   = $product->productId;
                $shipProduct->packagingId = $product->packagingId;
                $shipProduct->quantity    = $product->quantity;
                $shipProduct->price       = $product->price;
                $shipProduct->uomId       = $product->uomId;
                $shipProduct->discount    = $product->discount;
                
                $shipProduct->quantityInPack = $shipProduct->getQuantityInPack();
                
                $shipProduct->save();
            }
        }
    }


    /**
     * След оттегляне на документа
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param object|int $id
     */
    public static function on_AfterReject($mvc, &$res, $id)
    {
        // Нотифицираме origin-документа, че някой от веригата му се е променил
        if ($origin = $mvc->getOrigin($id)) {
            $ref = new core_ObjectReference($mvc, $id);
            $origin->getInstance()->invoke('DescendantChanged', array($origin, $ref));
        }
    }
    
    
    /**
     * След рендиране
     */
    function on_AfterRenderSingle($mvc, $tpl, $data)
    {
        // Данните на "Моята фирма"
        $ownCompanyData = crm_Companies::fetchOwnCompany();

        $address = trim($ownCompanyData->place . ' ' . $ownCompanyData->pCode);
        if ($address && !empty($ownCompanyData->address)) {
            $address .= '<br/>' . $ownCompanyData->address;
        }  
        
        $tpl->placeArray(
            array(
                'MyCompany'      => $ownCompanyData->company,
                'MyCountry'      => $ownCompanyData->country,
                'MyAddress'      => $address,
                'MyCompanyVatNo' => $ownCompanyData->vatNo,
            ), 'supplier'
        );
        
        // Данните на клиента
        $contragent = new core_ObjectReference($data->rec->contragentClassId, $data->rec->contragentId);
        $cdata      = static::normalizeContragentData($contragent->getContragentData());
        
        $tpl->placeObject($cdata, 'contragent');
        
        // Описателното (вербалното) състояние на документа
        $tpl->replace($data->row->state, 'stateText');
    }
    
    
    /**
     * След подготовка на единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, $data)
    {
    	if($data->rec->chargeVat == 'yes' || $data->rec->chargeVat == 'no'){
    		$data->row->VAT = " " . tr('с ДДС');
    	}
    }
    
    
    /**
     * Нормализиране на контрагент данните
     */
    public static function normalizeContragentData($contragentData)
    {
        /*
        * Разглеждаме четири случая според данните в $contragentData
        *
        *  1. Има данни за фирма и данни за лице
        *  2. Има само данни за фирма
        *  3. Има само данни за лице
        *  4. Нито едно от горните не е вярно
        */
        
        if (empty($contragentData->company) && empty($contragentData->person)) {
            // Случай 4: нито фирма, нито лице
            return FALSE;
        }
        
        // Тук ще попълним резултата
        $rec = new stdClass();
        
        $rec->contragentCountryId = $contragentData->countryId;
        $rec->contragentCountry   = $contragentData->country;
        
        if (!empty($contragentData->company)) {
            // Случай 1 или 2: има данни за фирма
            $rec->contragentName    = $contragentData->company;
            $rec->contragentAddress = trim(
                sprintf("%s %s\n%s",
                    $contragentData->place,
                    $contragentData->pCode,
                    $contragentData->address
                )
            );
            $rec->contragentVatNo = $contragentData->vatNo;
        
            if (!empty($contragentData->person)) {
                // Случай 1: данни за фирма + данни за лице
        
                // TODO за сега не правим нищо допълнително
            }
        } elseif (!empty($contragentData->person)) {
            // Случай 3: само данни за физическо лице
            $rec->contragentName    = $contragentData->person;
            $rec->contragentAddress = $contragentData->pAddress;
        }

        return $rec;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param store_Stores $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Задаване на стойности на полетата на формата по подразбиране
        
        /* @var $form core_Form */
        $form = $data->form;
        $rec  = &$form->rec;
        
        $form->setDefault('valior', dt::mysql2verbal(dt::now(FALSE)));

        if (empty($rec->folderId)) {
            expect($rec->folderId = core_Request::get('folderId', 'key(mvc=doc_Folders)'));
        }
        
        /*
         * Определяне на контрагента (ако още не е определен)
         */
        if (empty($rec->contragentClassId)) {
            $rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
        }
        if (empty($rec->contragentId)) {
            $rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
        }
        
        /*
         * Условия за доставка по подразбиране - трябва да е след определянето на контрагента, 
         * тъй-като определянето зависи от него
         */
        if (empty($rec->termId)) {
            $rec->termId = $mvc::getDefaultDeliveryTermId($rec);
        }
        
        if (empty($rec->storeId)) {
            $rec->storeId = store_Stores::getCurrent('id', FALSE);
        }
        
        // Поле за избор на локация - само локациите на контрагента по продажбата
        $form->getField('locationId')->type->options = 
            array(''=>'') +
            crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId);
        
        // Ако създаваме нов запис и то базиран на предхождащ документ ...
        if (empty($form->rec->id) && ($form->rec->originId || $form->rec->threadId)) {
        	
            // ... проверяваме предхождащия за bgerp_DealIntf
            $origin = ($form->rec->originId) ? doc_Containers::getDocument($form->rec->originId) : doc_Threads::getFirstDocument($form->rec->threadId);
            expect($origin);
            
            if ($origin->haveInterface('bgerp_DealIntf')) {
                /* @var $dealInfo bgerp_iface_DealResponse */
                $dealInfo = $origin->getDealInfo();
                $form->rec->currencyId = $dealInfo->agreed->currency;
                $form->rec->termId = $dealInfo->agreed->delivery->term;
                $form->rec->locationId = $dealInfo->agreed->delivery->location;
                $form->rec->time = $dealInfo->agreed->delivery->time;
                
                //@TODO да го взима от интерфейса
                if($origin->instance() instanceof sales_Sales){
                	$form->rec->chargeVat = $origin->fetchField('chargeVat');
                }
            }
            
            // ... и стойностите по подразбиране са достатъчни за валидиране
            // на формата, не показваме форма изобщо, а направо създаваме записа с изчислените
            // ст-сти по подразбиране. За потребителя си остава възможността да промени каквото
            // е нужно в последствие.
            
            if ($mvc->validate($form)) {
                if (self::save($form->rec)) {
                    redirect(array($mvc, 'single', $form->rec->id));
                }
            }
        }
    }
    

    /**
     * Условия за доставка по подразбиране
     * 
     * @param stdClass $rec
     * @return int key(mvc=cond_DeliveryTerms)
     */
    public static function getDefaultDeliveryTermId($rec)
    {
        $deliveryTermId = NULL;
        
        // 1. Условията на последната продажба на същия клиент
        if ($recentRec = self::getRecentShipment($rec)) {
            $deliveryTermId = $recentRec->deliveryTermId;
        }
        
        // 2. Условията определени от локацията на клиента (държава, населено място)
        // @see cond_DeliveryTermsByPlace
        if (empty($deliveryTermId)) {
            $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $deliveryTermId = cond_Parameters::getParameter($rec->contragentClassId, $rec->contragentId, 'deliveryTerm');
        }
        
        return $deliveryTermId;
    }
    

    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        $title = tr("Експедиционно нареждане |№" . $rec->id);
        
         
        return $title;
    }
    
    
    /**
     * Най-новата контирана продажба към същия клиент, създадена от текущия потребител, тима му или всеки
     * 
     * @param stdClass $rec запис на модела sales_Sales
     * @param string $scope 'user' | 'team' | 'any'
     * @return stdClass
     */
    protected static function getRecentShipment($rec, $scope = NULL)
    {
        if (!isset($scope)) {
            foreach (array('user', 'team', 'any') as $scope) {
                expect(!is_null($scope));
                if ($recentRec = self::getRecentShipment($rec, $scope)) {
                    return $recentRec;
                }
            }
            
            return NULL;
        }
        
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#state = 'active'");
        $query->where("#contragentClassId = '{$rec->contragentClassId}'");
        $query->where("#contragentId = '{$rec->contragentId}'");
        $query->orderBy("createdOn", 'DESC');
        $query->limit(1);
        
        switch ($scope) {
            case 'user':
                $query->where('#createdBy = ' . core_Users::getCurrent('id'));
                break;
            case 'team':
                $teamMates = core_Users::getTeammates(core_Users::getCurrent('id'));
                $teamMates = keylist::toArray($teamMates);
                if (!empty($teamMates)) {
                    $query->where('#createdBy IN (' . implode(', ', $teamMates) . ')');
                }
                break;
        }
        
        $recentRec = $query->fetch();
        
        return $recentRec ? $recentRec : NULL;
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        if (!count($data->recs)) {
            return;
        }
        
        // Основната валута към момента
        $now            = dt::now();
        $baseCurrencyId = acc_Periods::getBaseCurrencyCode($now);
        
        // Всички общи суми на продажба - в базова валута към съотв. дата
        foreach ($data->recs as &$rec) {
            $rate = currency_CurrencyRates::getRate($now, $rec->currencyId, $baseCurrencyId);
            
            $rec->amountDelivered *= $rate; 
        }
    }
    
    
    /**
     * След вербалното преобразуване на записите
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        // Премахваме някои от полетата в listFields. Те са оставени там за да ги намерим в 
        // тук в $rec/$row, а не за да ги показваме
        $data->listFields = array_diff_key(
            $data->listFields, 
            arr::make('contragentId', TRUE)
        );
        
        if (count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
                $rec = $data->recs[$i];
                
                // "Изчисляване" на името на клиента
                $contragentData = NULL;
                
                if ($rec->contragentClassId && $rec->contragentId) {
    
                    $contragent = new core_ObjectReference(
                        $rec->contragentClassId, 
                        $rec->contragentId 
                    );
                    
                    $row->contragentClassId = $contragent->getHyperlink();
                }
            }
        }     
    }
    
    
    /**
     * След подготовка на заглавието за списъчния изглед
     */
    public static function on_AfterPrepareListTitle($mvc, $data)
    {
        // Използваме заглавието на списъка за заглавие на филтър-формата
        $data->listFilter->title = $data->title;
        $data->title = NULL;
    }


    /**
     * ЕН не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     *
     * Допълнително, първия документ на нишка, в която е допустомо да се създаде ЕН трябва да
     * бъде от клас sales_Sales. Това се гарантира от @see store_ShipmentOrders::canAddToThread()
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
    /**
     * Може ли ЕН да се добави в посочената нишка?
     *
     * Експедиционните нареждания могат да се добавят само в нишки с начало - документ-продажба
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    	
    	if(($firstDoc->haveInterface('bgerp_DealIntf') && $docState != 'active')){
    		
    		return FALSE;
    	}
    }
        
    
    /**
     * @param int $id key(mvc=sales_Sales)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        
        $row = (object)array(
            'title'    => "Експедиционно нареждане №{$rec->id} / " . $this->getVerbal($rec, 'valior'),
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => $this->getRecTitle($rec)
        );
        
        return $row;
    }
    
    
	/**
     * Връща масив от използваните нестандартни артикули в ЕН-то
     * @param int $id - ид на ЕН
     * @return param $res - масив с използваните документи
     * 					['class'] - инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->store_ShipmentOrderDetails->getQuery();
    	$dQuery->EXT('state', 'store_ShipmentOrders', 'externalKey=shipmentId');
    	$dQuery->where("#shipmentId = '{$id}'");
    	$dQuery->groupBy('productId,classId');
    	while($dRec = $dQuery->fetch()){
    		$productMan = cls::get($dRec->classId);
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
        $rec = new store_model_ShipmentOrder($id);
        
        $result = new bgerp_iface_DealResponse();
        
        $result->dealType = bgerp_iface_DealResponse::TYPE_SALE;
        
        $result->shipped->amount             = $rec->amountDeliveredVat;
        $result->shipped->delivery->location = $rec->locationId;
        $result->shipped->delivery->term     = $rec->termId;
        $result->shipped->delivery->time     = $rec->deliveryTime;
        
        /* @var $dRec sales_model_SaleProduct */
        foreach ($rec->getDetails('store_ShipmentOrderDetails') as $dRec) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId     = $dRec->classId;
            $p->productId   = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
            $p->discount    = $dRec->discount;
            $p->isOptional  = FALSE;
            $p->quantity    = $dRec->quantity;
            $p->price       = $dRec->price;
            $p->uomId       = $dRec->uomId;
            
            $result->shipped->products[] = $p;
        }
        
        return $result;
    }
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интефейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
}
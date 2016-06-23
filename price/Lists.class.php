<?php



/**
 * Ценови политики
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ценови политики
 */
class price_Lists extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Ценови политики';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Ценова политика";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Rejected, plg_RowTools2, price_Wrapper, plg_Search';
                    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title,parent';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'price_ListRules';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title, parent, customer=На контрагент,createdOn, createdBy';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'customer';
    
    
    /**
     * Кой може да го промени?
     */
    public $canEdit = 'priceMaster,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'sales,priceMaster,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'priceMaster,ceo';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'priceMaster,ceo';
	
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'priceMaster,ceo';
   
    
    /**
     * Поле за връзка към единичния изглед
     */
    public $rowToolsSingleField = 'title';

    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'price/tpl/SingleLayoutLists.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'mandatory,caption=Наименование,hint=Наименование на ценовата политика');
        $this->FLD('parent', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Наследява');
        $this->FLD('public', 'enum(no=Не,yes=Да)', 'caption=Публичен');
        $this->FLD('currency', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'notNull,caption=Валута');
        $this->FLD('vat', 'enum(yes=Включено,no=Без ДДС)', 'caption=ДДС'); 
        $this->FLD('cId', 'int', 'caption=Клиент->Id,input=hidden,silent');
        $this->FLD('cClass', 'class(select=title,interface=crm_ContragentAccRegIntf)', 'caption=Клиент->Клас,input=hidden,silent');
        $this->FLD('discountCompared', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Показване на отстъпка в документите спрямо->Ценоразпис');
        $this->FLD('roundingPrecision', 'double(smartRound)', 'caption=Закръгляне->Десетични знаци');
        $this->FLD('roundingOffset', 'double(smartRound)', 'caption=Закръгляне->Отместване');
        $this->FLD('defaultSurcharge', 'percent', 'caption=Надценка по подразбиране->Процент');
        
        $this->FLD('minSurcharge', 'percent', 'caption=Надценки за нестандартни продукти->Минимална');
        $this->FLD('maxSurcharge', 'percent', 'caption=Надценки за нестандартни продукти->Максимална');
        
        $this->setDbUnique('title');
        $this->setDbIndex('cId,cClass');
    }

    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
		
        if(isset($rec->parent)){
        	$form->setReadOnly('parent');
        }
        
        if($rec->cId && $rec->cClass) {
            $form->setField('public', 'input=hidden');
            $rec->public = 'no';
        }
        
        if(empty($rec->id)){
        	// Бащата може да бъде от достъпните до потребителя политики
        	$form->setOptions('parent', self::getAccessibleOptions());
        	
        	// По дефолт слагаме за частните политики да наследяват дефолт политиката за контрагента, иначе 'Каталог'
        	$rec->parent = ($rec->cId && $rec->cClass) ? price_ListToCustomers::getListForCustomer($rec->cClass, $rec->cId) : price_ListRules::PRICE_LIST_CATALOG;
        }  

        if(!$rec->currency) {
            $rec->currency = acc_Periods::getBaseCurrencyCode();
        }
        
        // За политиката себестойност, скриваме определени полета
        if($rec->id == price_ListRules::PRICE_LIST_COST){
        	foreach (array('parent', 'public', 'discountCompared', 'defaultSurcharge', 'minSurcharge', 'maxSurcharge') as $fld){
        		$form->setField($fld, 'input=hidden');
        	}
        }
    }


    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	if($rec->cId && $rec->cClass) {
    		$data->form->title = core_Detail::getEditTitle($rec->cClass, $rec->cId, 'ценова политика', $rec->id, 'на');
    	}
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     *
     *
     * @param bgerp_Bookmark $mvc
     * @param object $res
     * @param object $data
     */
    protected static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
    	//Ако създаваме копие, редиректваме до създаденото копие
        if (is_object($data->form) && $data->form->isSubmitted()) {
           
            $data->retUrl = array($mvc, 'single', $data->form->rec->id);
        }
    }
    
    
    /**
     * Намираме ценовите политики, които може да избира потребителя.
     * Ако няма има права price,ceo - може да избира всички
     * Ако ги няма може да избира само публичните + частните, до чийто контрагент има достъп
     * 
     * @param string $userId
     * @return multitype:NULL
     */
    public static function getAccessibleOptions($userId = NULL)
    {
    	// Ако няма права price,ceo може да избира само публичните + частните до чийто сингъл има достъп
    	if(!core_Users::haveRole('price,ceo', $userId)){
    		$options = array();
    		$query = price_Lists::getQuery();
    		$query->show('cClass,cId,title');
    		while($lRec = $query->fetch()){
    			if(!empty($lRec->cClass) && !empty($lRec->cId)){
    				if(cls::get($lRec->cClass)->haveRightFor('single', $lRec->cId, $userId)){
    					$options[$lRec->id] = $lRec->title;
    				}
    			} else {
    				$options[$lRec->id] = $lRec->title;
    			}
    		}
    	} else {
    		
    		// Ако потребителя има права price и/или ceo, може да избира от всички политики
    		$options = price_Lists::makeArray4select('title', '');
    	}
    	
    	// Връщаме намерените политики
    	return $options;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		if(($form->rec->id) && isset($form->rec->discountCompared) && $form->rec->discountCompared == $form->rec->id){
    			$form->setError('discountCompared', 'Не може да изберете същата политика');
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след създаване на нов набор от ценови правила
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        if($rec->cId && $rec->cClass) {
            price_ListToCustomers::setPolicyToCustomer($rec->id,  $rec->cClass, $rec->cId);
        }
    }
    

    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if(isset($rec->parent)) {
            $row->parent = price_Lists::getHyperlink($rec->parent, TRUE);
        }
        
        if(isset($rec->discountCompared)){
        	$row->discountCompared = price_Lists::getHyperlink($rec->discountCompared, TRUE);
        }
        
        if(isset($rec->cClass) && isset($rec->cId)){
        	$row->customer = cls::get($rec->cClass)->getHyperlink($rec->cId, TRUE);
        }
        
        $row->currency = "<span class='cCode'>{$row->currency}</span>";
        $row->ROW_ATTR['class'] = ($rec->state == 'rejected') ? 'state-rejected' : 'state-active';
        $row->STATE_CLASS = $row->ROW_ATTR['class'];
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'delete') {
            if($rec->id && (self::fetch("#parent = {$rec->id}") || price_ListToCustomers::fetch("#listId = {$rec->id}")) ) {
                $requiredRoles = 'no_one';
            }
        }
        
        if($action == 'add' && isset($rec)){
        	
        	// Ако се създава публична политика и потребителя няма роли price или ceo, да не може да създава
        	if(empty($rec->cClass) || empty($rec->cId)){
        		if(!haveRole('price,ceo')){
        			$requiredRoles = 'no_one';
        		}
        	} elseif(isset($rec->cId) && isset($rec->cClass)){
        		if(!cls::get($rec->cClass)->haveRightFor('single', $rec->cId)){
        			$requiredRoles = 'no_one';
        		}
        	}
        }
    }

    
    /**
     * След инсталирането на модела, създава двете базови групи с правила за ценообразуване
     * Себестойност - тук се задават цените на придобиване на стоките, продуктите и услугите
     * Каталог - това са цените които се публикуват
     */
    function loadSetupData()
    {
		if(!$this->fetchField(price_ListRules::PRICE_LIST_COST, 'id')) {
            $rec = new stdClass();
            $rec->id = price_ListRules::PRICE_LIST_COST;
            $rec->parent = NULL;
            $rec->title  = 'Себестойност';
            $rec->currency = acc_Periods::getBaseCurrencyCode();
            $rec->vat      = 'no';
            $rec->public = 'no';
            $rec->createdOn = dt::verbal2mysql();
            $rec->createdBy = -1;
            $this->save($rec, NULL, 'REPLACE');
        }
        
        if(!$this->fetchField(price_ListRules::PRICE_LIST_CATALOG, 'id')) {
            $rec = new stdClass();
            $rec->id = price_ListRules::PRICE_LIST_CATALOG;
            $rec->parent = price_ListRules::PRICE_LIST_COST;
            $rec->title  = 'Каталог';
            $rec->currency = acc_Periods::getBaseCurrencyCode();
            $rec->vat = 'yes';
            $rec->public = 'yes';
            $rec->defaultSurcharge = 0.2;
            $rec->roundingPrecision = 3;
            $rec->createdOn = dt::verbal2mysql();
            $rec->createdBy = -1;
            $this->save($rec, NULL, 'REPLACE');
        }
    }
}

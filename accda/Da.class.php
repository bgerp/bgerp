<?php



/**
 * Мениджър на протоколи за въвеждане в експлоатация на дълготрайни активи (ДА)
 *
 *
 * @category  bgerp
 * @package   accda
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Дълготрайни активи
 */
class accda_Da extends core_Master
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'acc_RegisterIntf,accda_DaAccRegIntf,acc_TransactionSourceIntf=accda_transaction_Da';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Заглавие
     */
    public $title = 'Регистър на дълготрайните активи';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, accda_Wrapper, acc_plg_Contable, acc_plg_DocumentSummary, plg_Printing, plg_Clone, doc_DocumentPlg, plg_Search,
                     bgerp_plg_Blank, acc_plg_Registry, plg_Sorting, plg_SaveAndNew, plg_Search, doc_plg_SelectFolder';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Da';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Пускане в експлоатация на ДА';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/doc_table.png';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,accda';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,accda';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,accda';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,accda';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,accda';
    
    
    /**
     * Кой има достъп до сингъла
     */
    public $canSingle = 'ceo,accda';

	
    /**
     * Кой може да го активира?
     */
    public $canConto = 'ceo,accda';
        
    
    /**
     * Файл за единичен изглед
     */
    public $singleLayoutFile = 'accda/tpl/SingleLayoutDA.shtml';
    
    
    /**
     * Поле за търсене
     */
    public $searchFields = 'num, serial, title, productId, accountId';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "6.2|Счетоводни";
    
    
    /**
     * Полета за показване в списъчния изглед
     */
    public $listFields = 'valior,handler=Документ,title,num,serial,createdOn,createdBy';

    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsSingleField = 'handler';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'valior,title,num';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products)', 'caption=Артикул,mandatory,silent,refreshForm');
    	$this->FLD('accountId', 'acc_type_Account(allowEmpty)', 'caption=Сметка,mandatory,input=hidden');
    	$this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,input=none,silent,refreshForm');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=В употреба от,mandatory');
    	$this->FLD('title', 'varchar', 'caption=Наименование,mandatory,width=400px');
    	$this->FLD('num', 'varchar(32)', 'caption=Наш номер, mandatory');
        $this->FLD('serial', 'varchar', 'caption=Сериен номер');
        
        $this->FLD('info', 'text', 'caption=Описание,column=none,width=400px');
        $this->FLD('origin', 'text', 'caption=Произход,column=none,width=400px');
        $this->FLD('location', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Локация,column=none,width=400px');
        $this->FLD('amortNorm', 'percent', 'caption=ГАН,hint=Годишна амортизационна норма,notNull');
        
        $this->setDbUnique('num');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	$assets = cat_Products::getByProperty('fixedAsset');
    	$form->setOptions('productId', array('' => '') + $assets);
    	$form->setDefault('valior', dt::today());
    	
    	if(!empty($rec->id)){
    		$form->setReadOnly('productId');
    	}
    	
    	if($rec->productId){
    		$pInfo = cat_Products::getProductInfo($rec->productId);
    		$rec->title = $pInfo->productRec->name;
    		
    		if(isset($pInfo->meta['canStore'])){
    			$form->setField('storeId', 'input,mandatory');
    			$form->setFieldTypeParams('accountId', 'root=20');
    		
    			// Ако е избран склад
    			if($rec->storeId){
    				$form->info = deals_Helper::checkProductQuantityInStore($rec->productId, NULL, 1, $rec->storeId)->formInfo;
    			}
    		} else {
    			$form->setFieldTypeParams('accountId', 'root=21');
    		}
    		
    		$form->setField('accountId', 'input,mandatory');
    	}
    	
    	// Показваме само локациите на нашата фирма за ибзор
    	$ownCompany = crm_Companies::fetchOurCompany();
    	$ourLocations = crm_Locations::getContragentOptions('crm_Companies', $ownCompany->id);
    	if(count($ourLocations)){
    		$form->setOptions('location', array('' => '') + $ourLocations);
    	} else {
    		$form->setReadOnly('location');
    	}
    }
    
    
    /**
     * Връща заглавието и мярката на перото за продукта
     *
     * Част от интерфейса: intf_Register
     */
    public static function getItemRec($objectId)
    {
        $result = NULL;
        $self = cls::get(get_called_class());
       
        if ($rec = self::fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->num . " " . mb_strtolower($self->abbr),
                'title' => $rec->title,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
        // @todo!
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$me = cls::get(get_called_class());
    	$title = $me->singleTitle . " №{$rec->id}";
    	
    	return $title;
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    public function getDocumentRow($id)
    {
        if(!$id) return;
        
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        $row->title = static::getRecTitle($rec);
        $row->subTitle = $this->getVerbal($rec, 'title');;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->authorId = $rec->createdBy;
        $row->recTitle = $rec->title;
        
        return $row;
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        $data->row->createdByName = core_Users::getVerbal($data->rec->createdBy, 'names');
        
        if ($data->rec->location) {
            $locationRec = crm_Locations::fetch($data->rec->location);
            
            if($locationRec->address || $locationRec->place || $locationRec->countryId){
                $locationRow = crm_Locations::recToVerbal($locationRec);
                
                if($locationRow->address){
                    $data->row->locationAddress .= ", {$locationRow->address}";
                }
                
                if($locationRow->place){
                    $data->row->locationAddress .= ", {$locationRow->place}";
                }
                
                if($locationRow->countryId){
                    $data->row->locationAddress .= ", {$locationRow->countryId}";
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        if(Mode::is('printing') || Mode::is('text', 'xhtml')){
            $tpl->removeBlock('header');
        }
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(empty($rec->id)){
    		$rec->isContable = 'yes';
    	}
    }
    
    
    /**
     * Дали документа има приключени пера в транзакцията му
     */
    public static function on_AfterGetClosedItemsInTransaction($mvc, &$res, $id)
    {
    	$rec = $mvc->fetchRec($id);
    
    	// От списъка с приключените пера, премахваме това на приключения документ, така че да може
    	// приключването да се оттегля/възстановява въпреки, че има в нея приключено перо
    	$itemId = acc_Items::fetchItem($mvc->getClassId(), $rec->id)->id;
    	
    	unset($res[$itemId]);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->handler = $mvc->getLink($rec->id, 0);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
    	if(planning_AssetResources::haveRightFor('add', (object)array('protocolId' => $data->rec->id))){
    		$data->toolbar->addBtn('Ресурс', array('planning_AssetResources', 'add', 'protocolId' => $data->rec->id, 'ret_url' => TRUE), 'ef_icon = img/16/star_2.png,title=Добавяне като ресурс');
    	}
    
    	if($hRecId = planning_AssetResources::fetchField("#protocolId = {$data->rec->id}", 'id')){
    		$data->toolbar->addBtn('Ресурс', array('planning_AssetResources', 'edit', 'id' => $hRecId, 'ret_url' => TRUE), 'ef_icon = img/16/edit-icon.png,title=Редактиране на ресурс');
    	}
    }
}

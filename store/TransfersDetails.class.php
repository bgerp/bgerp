<?php


/**
 * Клас 'store_TransfersDetails'
 *
 * Детайли на мениджър на детайлите на междускладовите трансфери (@see store_Transfers)
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_TransfersDetails extends doc_Detail
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Детайли на междускладовите трансфери';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'transferId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_Sorting, store_Wrapper, plg_RowNumbering, plg_AlignDecimals, plg_PrevAndNext,plg_SaveAndNew,cat_plg_ShowCodes,store_plg_TransportDataDetail';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'newProductId, packagingId, packQuantity, weight=Тегло, volume=Обем';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Трансфери';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Поле за артикула
     */
    public $productFieldName = 'newProductId';
    
    
    /**
     * Поле за артикула
     */
    public $productFld = 'newProductId';
    
    
    /**
     * Поле за главния склад в мастъра
     */
    public $masterStoreFld = 'fromStore';
    
    
    /**
     * Да се показва ли кода като в отделна колона
     */
    public $showCodeColumn = TRUE;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('transferId', 'key(mvc=store_Transfers)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('newProductId', 'key(mvc=cat_Products,select=name)', 'caption=Продукт,mandatory,silent,refreshForm,tdClass=productCell leftCol wrap');
        $this->FLD('productId', 'key(mvc=store_Products,select=productId)', 'caption=Продукт,input=none,mandatory,silent,refreshForm');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,smartCenter,input=hidden,tdClass=small-field nowrap');
        $this->FLD('quantity', 'double', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
        $this->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input,mandatory');
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    public function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }


    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $fieldsList = NULL)
    {
        // Подсигуряваме наличието на ключ към мастър записа
        if (empty($rec->{$mvc->masterKey})) {
            $rec->{$mvc->masterKey} = $mvc->fetchField($rec->id, $mvc->masterKey);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)){
        	if($mvc->Master->fetchField($rec->transferId, 'state') != 'draft'){
        		$requiredRoles = 'no_one';
        	}
        }
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        $rows = $data->rows;
        
        if(count($data->rows)) {
        	
            foreach ($data->rows as $i => &$row) {
                $rec = &$data->recs[$i];
                
                $singleUrl = cat_Products::getSingleUrlArray($rec->newProductId);
                $row->newProductId = cat_Products::getVerbal($rec->newProductId, 'name');
                $row->newProductId = ht::createLinkRef($row->newProductId, $singleUrl);
                
                // Показваме подробната информация за опаковката при нужда
                deals_Helper::getPackInfo($row->packagingId, $rec->newProductId, $rec->packagingId, $rec->quantityInPack);
            }
        }
    }
        
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	if(!count($data->recs)) return;
    	 
    	$storeId = $data->masterData->rec->storeId;
    	foreach ($data->rows as $id => $row){
    		$rec = $data->recs[$id];
    		
    		$warning = deals_Helper::getQuantityHint($rec->newProductId, $data->masterData->rec->fromStore, $rec->quantity);
    		if(strlen($warning) && $data->masterData->rec->state == 'draft'){
    			$row->packQuantity = ht::createHint($row->packQuantity, $warning, 'warning', FALSE);
    		}
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        
        if(empty($rec->id)){
        	$products = cat_Products::getByProperty('canStore');
        	expect(count($products));
        	$form->setOptions('newProductId', array('' => '') + $products);
        } else {
        	$form->setReadOnly('newProductId');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    { 
    	$rec = &$form->rec;
    	
    	if($rec->newProductId){
    		$fromStoreId = store_Transfers::fetchField($rec->transferId, 'fromStore');
    		$storeInfo = deals_Helper::checkProductQuantityInStore($rec->newProductId, $rec->packagingId, $rec->packQuantity, $fromStoreId);
    		$form->info = $storeInfo->formInfo;
    		
    		$packs = cat_Products::getPacks($rec->newProductId);
    		$form->setField('packagingId', 'input');
    		$form->setOptions('packagingId', $packs);
    		$form->setDefault('packagingId', key($packs));
    	}
    	
    	if ($form->isSubmitted()){
    		$pInfo = cat_Products::getProductInfo($rec->newProductId);
    		$rec->quantityInPack = ($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;
            
    		$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
    	}
    }
    
    
	/**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
			unset($data->toolbar->buttons['btnAdd']);
			$products = cat_Products::getByProperty('canStore', NULL, 1);
			
			if(!count($products)){
				$error = "error=Няма складируеми артикули, ";
			}
	
			$data->toolbar->addBtn('Артикул', array($mvc, 'add', $mvc->masterKey => $data->masterId, 'ret_url' => TRUE),
					"id=btnAdd,{$error} order=10,title=Добавяне на артикул", 'ef_icon = img/16/shopping.png');
		}
    }
    
    
    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    public static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
    	$rec = $mvc->fetchRec($rec);
    	$toStoreId = store_Transfers::fetchField($rec->transferId, 'toStore');
    	$res->operation['in'] = $toStoreId;
    }
}

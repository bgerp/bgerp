<?php
/**
 * Клас 'store_TransfersDetails'
 *
 * Детайли на мениджър на детайлите на междускладовите трансфери (@see store_Transfers)
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
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
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'transferId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_RowNumbering, plg_AlignDecimals';
    
    
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
    public $listFields = 'productId, packagingId, uomId, packQuantity';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Трансфери';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('transferId', 'key(mvc=store_Transfers)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('productId', 'key(mvc=store_Products,select=name)', 'caption=Продукт,notNull,mandatory,silent,refreshForm');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=shortName)', 'caption=Мярка,input=none');
        $this->FLD('quantity', 'double(Min=0)', 'caption=К-во,input=none');
        $this->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
        $this->FNC('packQuantity', 'double(decimals=2)', 'caption=К-во,input,mandatory');
    	$this->FLD('weight', 'cat_type_Weight', 'input=hidden,caption=Тегло');
        $this->FLD('volume', 'cat_type_Volume', 'input=hidden,caption=Обем');
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
    
        $data->listFields = array_diff_key($data->listFields, arr::make('uomId', TRUE));
        if(count($data->rows)) {
            foreach ($data->rows as $i => &$row) {
                $rec = &$data->recs[$i];
                if (empty($rec->packagingId)) {
                    $row->packagingId = ($rec->uomId) ? $row->uomId : '???';
                } else {
                	if(cat_Packagings::fetchField($rec->packagingId, 'showContents') == 'yes'){
                		$shortUomName = cat_UoM::getShortName($rec->uomId);
                		$row->quantityInPack = $mvc->getFieldType('quantityInPack')->toVerbal($rec->quantityInPack);
                		$row->packagingId .= ' <small class="quiet">' . $row->quantityInPack . '  ' . $shortUomName . '</small>';
                	}
                }
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
        $fromStore = $mvc->Master->fetchField($rec->transferId, 'fromStore');
        
        if(empty($rec->id)){
        	$products = store_Products::getProductsInStore($fromStore);
        	expect(count($products));
        	$form->setOptions('productId', array('' => '') + $products);
        } else {
        	$form->setReadOnly('productId');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    { 
    	$rec = &$form->rec;
    	
    	if($rec->productId){
    		$sProd = store_Products::fetch($rec->productId);
    		$ProductMan = cls::get($sProd->classId);
    		
    		$packs = $ProductMan->getPacks($sProd->productId);
    		if(isset($rec->packagingId) && !isset($packs[$rec->packagingId])){
    			$packs[$rec->packagingId] = cat_Packagings::getTitleById($rec->packagingId);
    		}
    		if(count($packs)){
    			$form->setOptions('packagingId', $packs);
    		} else {
    			$form->setReadOnly('packagingId');
    		}
        }
    	
    	if ($form->isSubmitted() && !$form->gotErrors()){
    		$productInfo = $ProductMan->getProductInfo($sProd->productId, $rec->packagingId);
    		
    		if (empty($rec->packagingId)) {
                $rec->quantityInPack = 1;
            } else {
             	if ($rec->packagingId != $productInfo->packagingRec->packagingId) {
                    $form->setError('packagingId', 'Избрания продукт не се предлага в тази опаковка');
                    return;
                }
                $rec->quantityInPack = $productInfo->packagingRec->quantity;
            }
            
            if($sProd->quantity < $rec->packQuantity){
            	$form->setWarning("packQuantity", "Въведеното количество е по-голямо от наличното '{$sProd->quantity}' в склада");
            }
            
            $rec->weight = $ProductMan->getWeight($sProd->productId);
            $rec->volume = $ProductMan->getVolume($sProd->productId);
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
            $rec->uomId = $productInfo->productRec->measureId;
    	}
    }
    
    
	/**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
	    	$products = store_Products::getProductsInStore($data->masterData->rec->fromStore);
    		
    		if(!count($products)){
    			$data->toolbar->buttons['btnAdd']->attr['error'] = "Няма продукти в избрания склад";
    		}
        }
    }
}
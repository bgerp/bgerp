<?php



/**
 * Документ Ценоразпис
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ценоразписи
 */
class price_ListDocs extends core_Master
{
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Ценоразписи';
    
    
     /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, price_Wrapper, doc_DocumentPlg,
    	 plg_Printing, bgerp_plg_Blank, plg_Sorting, plg_Search, doc_ActivatePlg';
                    
    
    /**
	 * Брой дeтайли на страница
	 */
	var $listDetailsPerPage = '30';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, title, date, policyId, productGroups, packagings, state, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Полето за единичен изглед
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой може да го промени?
     */
    var $canWrite = 'price, ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'price, ceo';
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/legend.png';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "3.6|Търговия";
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'price/tpl/SingleLayoutListDoc.shtml';
    
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Ценоразпис';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('date', 'date(smartTime)', 'caption=Дата,mandatory,width=6em;');
    	$this->FLD('policyId', 'key(mvc=price_Lists, select=title)', 'caption=Политика, silent, mandotory,width=15em');
    	$this->FLD('title', 'varchar(155)', 'caption=Заглавие,width=15em');
    	$this->FLD('productGroups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Продукти->Групи,columns=2');
    	$this->FLD('packagings', 'keylist(mvc=cat_Packagings,select=name)', 'caption=Продукти->Опаковки,columns=2');
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
	public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('date', dt::now());
    	$form->setOptions('policyId', $mvc->getDefaultPolicies($form->rec));
    	$folderClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
    	if($folderClass == 'crm_Companies' || $folderClass == 'crm_Persons'){
    		$contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
    		$defaultList = price_ListToCustomers::getListForCustomer($folderClass::getClassId(), $contragentId);
    		$form->setDefault('policyId', $defaultList);
    	}
    }
    
    
    /**
     * Подготвя всички политики до които има достъп потребителя
     * @param stdClass $rec - запис от модела
     * @return array $options - масив с опции
     */
    private function getDefaultPolicies($rec)
    {
    	$options = array();
    	$polQuery = price_Lists::getQuery();
    	while($polRec = $polQuery->fetch()){
    		if(price_Lists::haveRightFor('read')){
    			$polRow = price_Lists::recToVerbal($polRec, 'title');
    			$options[$polRec->id] = $polRow->title;
    		}
    	}
    	
    	return $options;
    }
    
    
    /**
     * Обработка след изпращане на формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		if(!$form->rec->title){
    			$polRec = price_Lists::fetch($form->rec->policyId);
    			$policyName = price_Lists::getVerbal($polRec, 'title');
    			$form->rec->title = $mvc->singleTitle . ' "'. $policyName . '"';
    		}
    	}
    }
    
    
    /**
   	 * Обработка на Single изгледа
   	 */
   	static function on_AfterPrepareSingle($mvc, &$data)
    {
    	$mvc->prepareDetails($data);
    }
    
    
    /**
     * Подготвяне на "Детайлите" на ценоразписа
     */
    private function prepareDetails(&$data)
    {
    	$this->prepareSelectedProducts($data);
    	$this->calculateProductsPrice($data);
    }
    
    
    /**
     * Извличаме до кои продукти имаме достъп. Ако не сме посочили ограничение
     * на групите показваме всички продукти, ако има ограничение - само тези
     * които са в посочените групи
     */
    private function prepareSelectedProducts(&$data)
    {
    	$customerProducts = price_GroupOfProducts::getAllProducts($data->rec->date);
    	foreach($customerProducts as $id => $product){
    		$productRec = cat_Products::fetch($id);
    		if($data->rec->productGroups){
    			$aGroups = type_Keylist::toArray($data->rec->productGroups);
    			$pGroups = type_Keylist::toArray($productRec->groups);
    			$intersectArr = array_intersect($aGroups,$pGroups);
    			if(!count($intersectArr)) continue;
    		}
    		
    		$data->rec->details->products[$productRec->id] = (object)array('productId' => $productRec->id,
    									   'code' => $productRec->code,
    									   'eanCode' => $productRec->eanCode,
    									   'measureId' => $productRec->measureId);
    	}
    }
    
    
    /**
     * 
     */
    private function calculateProductsPrice(&$data)
    {
    	$rec = &$data->rec;
    	if(!count($rec->details->products)) return;
    	
    	foreach($rec->details->products as &$product){
    		$product->price = price_ListRules::getPrice($rec->policyId, $product->productId, NULL, $rec->date);
    		if(!$product->price) {
    			unset($rec->details->products[$product->productId]);
    			continue;
    		}
    		
    		$rec->details->rows[] = $product;
    		if(!$rec->packagings) continue;
    		$packArr = type_Keylist::toArray($rec->packagings);
    		foreach($packArr as $pack){
    			if($price = price_ListRules::getPrice($rec->policyId, $product->productId, $pack, $rec->date)){
    				//bp($product, $pack);
    			}
    			//bp('ne');
    		}
    	}
    	//bp($rec->details->products);
    }
    
    
    /**
     * Вкарваме css файл за единичния изглед
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$mvc->renderDetails($tpl, $data);
    	$tpl->push("price/tpl/ListDocStyles.css", "CSS");
    }
    
    
	/**
     * Рендиране на "Детайлите" на ценоразписа
     */
    private function renderDetails(&$tpl, $data)
    {
    	//$detailTpl = $tpl->getBlock("DETAILS");
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(!$rec->productGroups) {
    		$row->productGroups = tr("Всички");
    	}
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $rec->title;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;

        return $row;
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
}
<?php



/**
 * Документ за "Универсални продукти"
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_GeneralProducts extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'techno_ProductsIntf, doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Универсални продукти";
    
    
    /**
     * Заглавие
     */
    var $singleTitle = "Универсален продукт";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'techno_plg_SpecificationProduct,doc_DocumentPlg,plg_RowTools, techno_Wrapper, plg_Printing, bgerp_plg_Blank, doc_plg_BusinessDoc2,
                    doc_ActivatePlg, plg_Search, doc_SharablePlg';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    
    
   /**
     * Абревиатура
     */
    var $abbr = "Gp";
    
    
    /**
     * Шаблон за показване на кратката версия на изделието
     */
    var $singleShortLayoutFile = 'techno/tpl/SingleLayoutGeneralProductsShort.shtml';
    
    
    /**
     * Шаблон за единичен изглед
     */
    var $singleLayoutFile = 'techno/tpl/SingleLayoutGeneralProducts.shtml';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo,techno';
    
    
    /**
     * Кой може да променя?
     */
    var $canWrite = 'ceo,techno';
    
    
    /**
	 * Детайли на продукта
	 */
	var $details = 'techno_GeneralProductsDetails,Params=techno_GeneralProductsParameters';
	
	
    /**
     * Кой може да променя?
     */
    var $canAdd = 'ceo,techno';
    
    
    /**
     * Полета за списъчния изглед
     */
    var $listFields = 'id=Пулт,title,folderId,createdOn,createdBy';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "3.1|Производство";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, description, measureId, code, eanCode';
    
    
    /**
     * Шаблон за заглавието
     */
    public $recTitleTpl = '[#title#] <!--ET_BEGIN code-->([#code#])<!--ET_END code-->';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие, mandatory,remember=info,width=100%');
    	$this->FLD('description', 'richtext(rows=5, bucket=Notes)', 'caption=Описание,mandatory,width=100%');
		$this->FLD('measureId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory');
    	$this->FLD('image', 'fileman_FileType(bucket=techno_GeneralProductsImages)', 'caption=Параметри->Изображение');
		$this->FLD('code', 'varchar(64)', 'caption=Параметри->Код,remember=info,width=15em');
        $this->FLD('eanCode', 'gs1_TypeEan', 'input,caption=Параметри->EAN,width=15em');
		$this->FLD('meta', 'set(canSell=Продаваем,canBuy=Купуваем,
        						canStore=Складируем,canConvert=Вложим,
        						fixedAsset=Дма,canManifacture=Производим)', 'caption=Свойства->Списък,columns=2');
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})" ;
    	
    	if($fields['-single'] && !$fields['-short']){
	    	if($rec->image){
	     		$size = array(280, 150);
				$Fancybox = cls::get('fancybox_Fancybox');
				$row->image = $Fancybox->getImage($rec->image, $size, array(550, 550));
	    	}
     	}
    	
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}
    	
    	if($fields['-short']){
	    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
	    		$row->title = ht::createLinkRef($row->title, array($mvc, 'single', $rec->id), NULL, 'title=Към спецификацията');
	    	}
	    	
	    	if($rec->image){
	     		$size = array(130, 130);
	     		$file = fileman_Files::fetchByFh($rec->image);
	     		$row->image = thumbnail_Thumbnail::getImg($file->fileHnd, $size);
	     	}
    	}
    }
    
    
	/**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	if(Mode::is('printing') || Mode::is('plain', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    }
    
    
    /**
     * Пушваме css и js файла
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	$tpl->push('techno/tpl/GeneralProductsStyles.css', 'CSS');
    }
    
    
    /*
     * РЕАЛИЗАЦИЯ НА techno_ProductsIntf
     */
    
    
    /**
     * Връща информация за ед цена на продукта, отстъпката и таксите
     * @param stdClass $data - дата от модела
     * @param int $packagingId - ид на опаковка
     * @param double quantity - количество
     * @param datetime $datetime - дата
     * @return stdClass $priceInfo - информация за цената на продукта
     * 				[price]- начална цена
     * 				[discount]  - отстъпка
     * 				[tax]     - нач. такса
     */
    public function getPriceInfo($productId, $packagingId = NULL, $quantity = 1, $datetime = NULL)
    {    
    	return $this->techno_GeneralProductsDetails->getTotalPrice($productId);
    }
    
    
    /**
     * Подготвя данните за краткия изглед
     */
    public function prepareData($id)
    {
    	$fields = $this->selectFields();
    	
    	$data = new stdClass();
    	$data->rec = $this->fetch($id);
    	$fields['-single'] = TRUE;
    	$data->row = $this->recToVerbal($data->rec, $fields);
    	
    	// Извличане на детайлите (компонентите)
    	$data->details = $this->techno_GeneralProductsDetails->prepareDetails($id);
    	
    	// Извличане на параметрите на изделието
    	$data->params = $this->Params->prepareParams($id, TRUE);
    	
    	return $data;
    }
    
    
    /**
     * Връща вербалното представяне на даденото изделие (HTML, може с картинка)
     * @param int $id - ид на продукт
     * @return core_ET $tpl - краткия изглед на продукта
     */
	public function renderShortView($id, $data)
    {
    	// Зареждане на щаблона за краткото представяне
    	$tpl = getTplFromFile('techno/tpl/SingleLayoutGeneralProductsShort.shtml');
    	$tpl->push('techno/tpl/GeneralProductsStyles.css', 'CSS');
    	$tpl->placeObject($data->row);
    	
    	if(count($data->details)){
    		$detailsLayout = $this->techno_GeneralProductsDetails->renderShortView($data->details);
    		$tpl->replace($detailsLayout, 'COMPONENTS');
    	}
    	
    	if(count($data->params)){
    		$paramsLayout = $this->Params->renderParams($data->params, TRUE);
    		$tpl->replace($paramsLayout, 'PARAMS');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Информация за артикула
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return stdClass $rec
     */
    public function getProductInfo($productId, $packagingId = NULL)
    {
	    $res = new stdClass();
	    $res->productRec = $this->fetch($productId);
	    
	    if($res->productRec->meta){
	    	$meta = explode(',', $res->productRec->meta);
	    	foreach($meta as $value){
	    		$res->meta[$value] = TRUE;
	    	}
	    } else {
	    	$res->meta = FALSE;
	    }
	    
	    (!$packagingId) ? $res->packagings = array() : $res = NULL;
	    
	   	return $res;
    }
    
    
    /**
     * Връща ддс-то на продукта
     * @param int $id - ид на продукт
     * @param datetime $date - към дата
     */
    public function getVat($id, $date = NULL)
    {
    	$vat = $this->getParam($id, 'vat');
    	if($vat) return $vat;
    	
    	$period = acc_Periods::fetchByDate($date);
    	
    	return $period->vatRate;
    }
    
    
    /**
     * Връща стойноства на даден параметър на продукта, ако я има
     * @param int $id - ид на продукт
     * @param string $sysId - sysId на параметър
     */
    public function getParam($id, $sysId)
    {
    	expect($paramId = cat_Params::fetchIdBySysId($sysId));
    	
    	return $this->Params->fetchField("#generalProductId = {$id} AND #paramId = '{$paramId}'", 'value');
    }
    
    
	/**
	 * Връща масив от използваните документи в даден документ (като цитат или
     * са включени в детайлите му)
     * @param int $data - сериализираната дата от документа
     * @return param $res - масив с използваните документи
     * 					[class] - инстанция на документа
     * 					[id] - ид на документа
     */
    function getUsedDocs_($productId)
    {
    	$description = $this->fetchField($productId, 'description');
    	if($usedDocs = doc_RichTextPlg::getAttachedDocs($productId->description)) {
	    	foreach ($usedDocs as $doc){
	    		$res[] = (object)array('class' => $doc['mvc'], 'id' => $doc['rec']->id);
	    	}
    	} else {
    		$res = array();
    	}
    	
    	return $res;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$title = $this->recToVerbal($rec, 'title')->title;
        $row = new stdClass();
        $row->title = $this->singleTitle . ' "' . $title . '"';
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = $rec->title;
		
        return $row;
    }
    
    
	/**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	 $data->listFilter->view = 'horizontal';
    	 $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png'); 
    	 $data->listFilter->showFields = 'search';
    	 $data->listFilter->input();
    }
    
    
    /**
     * Рендира изгледа на спецификацията за заданието
     */
    public function renderJobView_($id, $data)
    {
    	// Зареждане на щаблона за краткото представяне
    	$tpl = getTplFromFile('techno/tpl/SingleLayoutGeneralProductJob.shtml');
    	$tpl->push('techno/tpl/GeneralProductsStyles.css', 'CSS');
    	$tpl->placeObject($data->row);
    	
    	if(count($data->details)){
    		$detailsLayout = $this->techno_GeneralProductsDetails->renderShortView($data->details);
    		$tpl->replace($detailsLayout, 'DETAILS');
    	}
    	
    	if(count($data->params)){
    		$paramsLayout = $this->Params->renderParams($data->params);
    		$tpl->replace($paramsLayout, 'PARAMS');
    	}
    	
    	return $tpl;
    }
}
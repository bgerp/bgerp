<?php 

/**
 * Шаблон с примерен текст на Декларация за съответствие
 */
defIfNot(DEC_DECLARATION_HEADER, 'dec/tpl/DeclarationHeader.shtml');


/**
 * Шаблон с примерен текст на подпис към Декларация за съответствие
 */
defIfNot(DEC_DECLARATION_FOOTER, 'dec/tpl/DeclarationFooter.shtml');


/**
 * Декларации за съответствия
 *
 *
 * @category  bgerp
 * @package   dec
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class dec_Declarations extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Декларации за съответствия";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Декларация за съответствие";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Декларации";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_DocumentIntf, doc_DocumentPlg, sales_Wrapper, dec_Wrapper, doc_ActivatePlg, plg_Printing, plg_RowTools';
    
    
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
    var $listFields = 'id, title, doc, createdOn, createdBy';
    
    
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
    var $rowToolsSingleField = 'title';

    
    /**
     * Абревиатура
     */
    var $abbr = "Dec";
    
    
    /**
     * Групиране на документите
     */ 
   	var $newBtnGroup = "3.8|Търговия";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие, width=100%');
		$this->FLD('doc', 'key(mvc=doc_Containers)', 'caption=Към документ, input=none');
		$this->FLD('header', 'richtext', 'caption=Текст на декларацията->Текст');
		$this->FLD('footer', 'richtext', 'caption=Подпис на декларацията->Текст');

    }

    
    /**
     * След потготовка на формата за добавяне / редактиране.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
    	// Вземаме съдържанието на шаблона,
    	// който е примерен текст на декларацията
    	$header = getFileContent(DEC_DECLARATION_HEADER);
    	$footer = getFileContent(DEC_DECLARATION_FOOTER);

    	// Зареждаме ги във формата
    	$data->form->setDefault('title', "Декларация за съответствие");
    	$data->form->setDefault('header', $header);
    	$data->form->setDefault('footer', $footer);
    	
    	// Записваме оригиналното ид, ако имаме такова
    	if($data->form->rec->originId){
    		$data->form->setDefault('doc', $data->form->rec->originId);
    	}    	
    }
    
    
    /**
     * Попълване на шаблона на единичния изглед с данни на доставчика (Моята фирма)  и данните от фактурата
     */
    public function on_AfterRenderSingle($mvc, core_ET $tpl, $data)
    {
    	// Зареждаме данните за собствената фирма
        $ownCompanyData = crm_Companies::fetchOwnCompany();

        // Адреса на фирмата
        $address = trim($ownCompanyData->place . ' ' . $ownCompanyData->pCode);
        if ($address && !empty($ownCompanyData->address)) {
            $address .= '&nbsp;' . $ownCompanyData->address;
        }  
        $tpl->replace($ownCompanyData->company, 'MyCompany');
        $tpl->replace($ownCompanyData->country, 'MyCountry');
        $tpl->replace($address, 'MyAddress');
        
        // Ват номера й
        $uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
        if($uic != $ownCompanyData->vatNo){
    		$tpl->replace($ownCompanyData->vatNo, 'MyCompanyVatNo');
    	} 
    	$tpl->replace($uic, 'uicId');
    	
    	if($data->rec->originId){
			// Ако декларацията е по  документ фактура намираме кой е той
    		$doc = doc_Containers::getDocument($data->rec->originId);
    		$class = $doc->className;
    		$dId = $doc->that;
    		$rec = $class::fetch($dId);
    		
    		// Попълваме данните от контрагента. Идват от фактурата
    		$addressContragent = trim($rec->contragentPlace . ' ' . $rec->contragentPCode);
	        if ($addressContragent && !empty($rec->contragentAddress)) {
	            $addressContragent .= '&nbsp;' . $rec->contragentAddress;
	        }  
	        $tpl->replace($rec->contragentName, 'contragentCompany');
	        $tpl->replace(drdata_Countries::fetchField($rec->contragentCountryId, 'commonNameBg'), 'contragentCountry');
	        $tpl->replace($addressContragent, 'contragentAddress');
	        
	        $uicContragent = drdata_Vats::getUicByVatNo($rec->contragentVatNo);
	        if($uic != $rec->contragentVatNo){
	    		$tpl->replace($rec->contragentVatNo, 'contragentCompanyVatNo');
	    	} 
    		$tpl->replace($uicContragent, 'contragentUicId');
    		
    		$invoiceNo = str_pad($rec->id, '10', '0', STR_PAD_LEFT) . " / " . dt::mysql2verbal($rec->date, "d.m.Y");
    		$tpl->replace($invoiceNo, 'invoiceNo');
    		
    		$query = sales_InvoiceDetails::getQuery();
    		$query->where("#invoiceId = '{$rec->id}'");
    		
    		while($dRec = $query->fetch()){
    			$Policy = cls::get($dRec->classId);
        		$ProductMan = $Policy->getProductMan();
        		$product = $ProductMan::getTitleById($dRec->productId); 
    			$dTpl = $tpl->getBlock("products");
    			$dTpl->replace($product, 'products');
        		$dTpl->append2master();
    		}
    	}
    	
    }    
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	// Правим линк към единичния изглед на оригиналния документ
    	$row->doc = doc_Containers::getLinkForSingle($rec->doc);
    	$row->header = new ET(tr('|*' . $rec->header));
    	$row->footer = new ET(tr('|*' . $rec->footer));
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
    
    
	/**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     * @return stdClass $row
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
       
        //Заглавие
        $row->title = "Декларация за съответствие №{$id}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
		$row->recTitle = $row->title;
        
        return $row;
    }
}
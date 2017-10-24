<?php



/**
 * Ценови групи
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @deprecated
 * @title     Ценови групи
 */
class price_GroupOfProducts extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Ценови групи';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Ценова група';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, price_Wrapper, plg_SaveAndNew, plg_PrevAndNext';
                    
 
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'groupId, productId, validFrom, createdBy, createdOn';
        
    
    /**
     * Кой може да го промени?
     */
    public $canEdit = 'priceMaster,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'priceMaster,ceo';
    
        
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'priceMaster,ceo';
    
    
    /**
     * @todo Чака за документация...
     */
    public $currentTab = 'Групи';
    
    
    /**
     * Поле - ключ към мастера
     */
    public $masterKey = 'productId';
   

    /**
     * Променлива за кеширане на актуалната информация, кой продукт в коя група е;
     */
    public static $products = array();


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Продукт,silent,mandatory,hint=Само продаваеми продукти');
        $this->FLD('groupId', 'key(mvc=price_Groups,select=title,allowEmpty)', 'caption=Група,silent,remember');
        $this->FLD('validFrom', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|21:00)', 'caption=В сила oт');
    }


    /**
     * Връща групата на продукта към посочената дата
     */
    public static function getGroup($productId, $datetime)
    {
        $query = self::getQuery();
        $query->orderBy('#validFrom', 'DESC');
        $query->where("#validFrom <= '{$datetime}'");
        $query->where("#productId = '{$productId}'");
        $query->limit(1);
		$query->show('groupId');
        
        if($rec = $query->fetch()) {
			return $rec->groupId;
        }
    }


    /**
     * Връща масив групите на всички всички продукти към определената дата
     * $productId => $groupId
     */
    public static function getAllProducts($datetime = NULL, $showNames = TRUE)
    {
        price_ListToCustomers::canonizeTime($datetime);
		
        $datetime = price_History::canonizeTime($datetime);
		
        $query = self::getQuery();
		$query->EXT('state', 'cat_Products', 'externalName=state,externalKey=productId');
		$query->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $query->where("state != 'rejected'");
		$query->where("#validFrom <= '{$datetime}'");
		$query->where("#isPublic = 'yes'");
        $query->orderBy("#validFrom", "DESC");
        $query->show('groupId,productId,isPublic');
        
        $res = array();
        while($rec = $query->fetch()) {
            if(!$used[$rec->productId]) {
                if($rec->groupId) {
                	$res[$rec->productId] = ($showNames === TRUE) ? cat_Products::getTitleById($rec->productId, FALSE) : $rec->productId;
                }
                $used[$rec->productId] = TRUE;
            }
        }
        
        asort($res);
		
        return $res;
    }
    
    
    /**
     * Извиква се след подготовка на заявката за детайла
     */
    protected static function on_AfterPrepareDetailQuery(core_Detail $mvc, $data)
    {
        // Историята на ценовите групи на продукта - в обратно хронологичен ред.
        $data->query->orderBy("validFrom,id", 'DESC');
    }


    /**
     * Извиква се след обработка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($rec->validFrom && ($action == 'edit' || $action == 'delete')) {
            if($rec->validFrom <= dt::verbal2mysql()) {
                $requiredRoles = 'no_one';
            }
        }
        
        if(($action == 'add' || $action == 'add' || $action == 'delete') && isset($rec->productId)){
        	if(cat_Products::fetchField($rec->productId, 'state') != 'active'){
        		$requiredRoles = 'no_one';
        	}
        	
        	if($requiredRoles != 'no_one' && !cat_Products::haveRightFor('single', $rec->productId)){
        		$requiredRoles = 'no_one';
        	}
        }
    }
    

    /**
     * Подготвя формата за въвеждане на групи на продукти
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $rec = $data->form->rec;

        if(!$rec->id) {
            $rec->validFrom = Mode::get('PRICE_VALID_FROM');
        }
        
        if($rec->groupId) {
	        $groupName = price_Groups::getTitleById($rec->groupId);
	        $data->formTitle = '|Добавяне на артикул към група|* "' . $groupName . '"';
        }
        
        // За опции се слагат само продаваемите продукти
        $products = cat_Products::getByProperty('canSell');
        expect(count($products), 'Няма продаваеми продукти');
        
        if($data->masterMvc instanceof cat_Products) {
            $data->formTitle = "Добавяне в ценова група";
            $data->form->setField('productId', 'input');
            $data->form->setReadOnly('productId');
            $pInfo = cat_Products::getProductInfo($rec->productId);
            expect(isset($pInfo->meta['canSell']), 'Продуктът не е продаваем');
            
            if(!isset($rec->groupId)) {
                $rec->groupId = self::getGroup($rec->productId, dt::verbal2mysql());
            }
        } else {
        	$now = dt::now();
        	
        	foreach ($products as $id => &$product){
        		if(is_object($product)) continue;
        		 
        		if($groupId = self::getGroup($id, $now)){
        			$groupTitle = price_Groups::fetchField($groupId, 'title');
        			$product .=  " -- " . tr('група') . " {$groupTitle}";
        		}
        	}
        	
        	$data->form->setOptions('productId', $products);
        }
    }
    

    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$data->form->title = $data->formTitle;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
    	if($data->masterMvc instanceof cat_Products) {
    		if (!empty($data->form->toolbar->buttons['saveAndNew'])) {
    			$data->form->toolbar->removeBtn('saveAndNew');
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()) {
            
            $rec = $form->rec;

            $now = dt::verbal2mysql();
            
            if(!$rec->validFrom) {
                $rec->validFrom = $now;
            }

            if($rec->validFrom < $now) {
                $form->setError('validFrom', 'Групата не може да се сменя с минала дата');
            }
            
            if(!$form->gotErrors() ) {
                Mode::setPermanent('PRICE_VALID_FROM', ($rec->validFrom > $now) ? $rec->validFrom : '');
            }
        }
    }
    

    /**
     * Връща съответния мастер
     */
    function getMasterMvc_($rec)
    {
        if($rec->_masterMvc) {
            return $rec->_masterMvc;
        }

        if($rec->groupId && !$rec->productId) {
            return cls::get('price_Groups');
        }

        if($rec->productId) {
            return cls::get('cat_Products');
        }

        return parent::getMasterMvc_($rec);
    }
    

    /**
     * Връща masterKey-а
     */
    function getMasterKey($rec)
    {
        if($rec->_masterKey) { 
            return $rec->_masterKey;
        }

        if($rec->groupId && !$rec->productId) {
            return 'groupId';
        }

        if($rec->productId) {
            return 'productId';
        }
        
        return parent::getMasterKey_($rec);
    }
    

    /**
     * След подготовка на записите във вербален вид
     */
    public static function on_AfterPrepareListRows(core_Detail $mvc, $data)
    {   
        if (!$data->rows) {
            return;
        }
        
        $now  = dt::now(TRUE); // Текущото време (MySQL формат) с точност до секунда
        $currentGroupId = NULL;// ID на настоящата ценова група на продукта
        
        /**
         * @TODO следващата логика вероятно ще трябва и другаде. Да се рефакторира!
         */
        
        // Цветово кодиране на историята на ценовите групи: добавя CSS клас на TR елементите
        // както следва:
        //
        //  * 'future' за бъдещите ценови групи (невлезли все още в сила)
        //  * 'active' за текущата ценова група
        //  * 'past' за предишните ценови групи (които вече не са в сила)
        foreach ($data->rows as $id => &$row) {
            
            $rec = $data->recs[$id];
            
            if ($rec->validFrom > $now) {
                $row->ROW_ATTR['class'] = 'state-draft';
            } else {
                $row->ROW_ATTR['class'] = 'state-closed';

                if (!isset($currentGroupId) || $rec->validFrom > $data->recs[$currentGroupId]->validFrom) {
                    $currentGroupId = $id;
                }
            }
            
            $row->groupId = price_Groups::getHyperLink($rec->groupId, TRUE);
        }
        
        if (isset($currentGroupId)) {
            $data->rows[$currentGroupId]->ROW_ATTR['class'] = 'state-active';
        }
    }
    
    
    /**
     * Подготовка на данните за детайла
     */
    public function preparePriceGroup($data)
    { 
    	if($data->masterData->rec->isPublic == 'no'){
    		$data->dontRender = TRUE;
    	}
    	
    	$query = $this->getQuery();
       	$query->where("#productId = {$data->masterId}");
       	$query->orderBy("#validFrom", "DESC");
       	$data->recs = $data->rows = array();
       	while($rec = $query->fetch()){
       		$data->recs[$rec->id] = $rec;
       		$data->rows[$rec->id] = $this->recToVerbal($rec);
       	}
       	$this->invoke('AfterPrepareListRows', array($data));
       	
        if($this->haveRightFor('add', (object)array('productId' => $data->masterId))){
        	 $pInfo = cat_Products::getProductInfo($data->masterId);
        	 if(isset($pInfo->meta['canSell'])){
        	 	$data->addUrl = array('price_GroupOfProducts', 'add', 'productId' => $data->masterId, 'ret_url' => TRUE);
        	 }
        }
    }
    
    
    /**
     * След подготовка на урл-то за връщане
     */
    protected static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
    	$data->retUrl["#"] = 'detailTabsTop';
    }
    
    
    /**
     * Рендиране изгледа на детайла
     */
    public function renderPriceGroup($data)
    {
        if($data->dontRender === TRUE) return;
        
        // Премахваме продукта - в случая той е фиксиран и вече е показан 
        unset($data->listFields[$this->masterKey]);
        
        $table = cls::get('core_TableView', array('mvc' => $this));
        $data->listFields = $this->listFields;
        
        $data->listFields = array("groupId" => "Група", 'validFrom' => 'В сила oт', 'createdBy' => 'Създаване от||Created by', 'createdOn' => 'Създаване на||Created on');
        $details = $table->get($data->rows, $data->listFields);
        
        $tpl = getTplFromFile('cat/tpl/ProductDetail.shtml');
        $tpl->append($this->singleTitle, 'TITLE');
        $tpl->append($details, 'CONTENT');
        $tpl->replace(get_class($this), 'DetailName');
        
        if ($data->addUrl  && !Mode::is('text', 'xhtml') && !Mode::is('printing')) {
        	$addBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addUrl, NULL, 'title=Задаване на ценова група');
        	$tpl->append($addBtn, 'TITLE');
        }
        
        return $tpl;
    }

    
    /**
     * Премахва кеша за интервалите от време
     */
    public static function on_AfterSave($mvc, &$id, &$rec, $fields = NULL)
    {
        price_History::removeTimeline();
    }
	
    
    /**
     * Подготвя продукт в група
     */
    public function prepareProductInGroup($data)
    {   
        $data->masterKey = 'groupId';
         
        // Очакваме да masterKey да е зададен
        expect($data->masterKey);
        expect($data->masterMvc instanceof core_Master);
		

		// Подготвяме полетата за показване 
		$data->listFields = arr::make('productId=Продукт,validFrom=В сила от,createdBy=Създадено||Created->От||By,createdOn=Създадено||Created->На');
        
        // Подготвяме навигацията по страници
        $this->prepareListPager($data);
        
        // Подготвяме лентата с инструменти
        $this->prepareListToolbar($data);

        $query = self::getQuery();
         
        $query->orderBy('#validFrom', 'DESC');
        
        $data->recs = array();
        
        $now = dt::verbal2mysql();

        $used = $futureUsed = array();

        while($rec = $query->fetch()) {
             
            if($rec->validFrom > $now) {
                $var = 'futureUsed';
            } else {
                $var = 'used';
            }


            if(${$var}[$rec->productId]) continue;
            if($data->masterId == $rec->groupId) {
                $rec->_masterMvc = cls::get('price_Groups');
                $rec->_masterKey = 'groupId';
                $data->recs[$rec->id] = $rec;
            }
            ${$var}[$rec->productId] = TRUE;
        }
 
        if(count($data->recs)) {
            foreach($data->recs as $rec) {
                $data->rows[$rec->id] = self::recToVerbal($rec);  
                $data->rows[$rec->id]->productId = cat_Products::getHyperLink($rec->productId, TRUE);
                
                if(cat_Products::fetchField($rec->productId, 'state') == 'rejected'){
                	$data->rows[$rec->id]->productId = "<span class= 'state-rejected-link'>{$data->rows[$rec->id]->productId}</span>";
                }
                
                if($rec->validFrom > $now) {
                    $data->rows[$rec->id]->ROW_ATTR['class'] = 'state-draft';
                }
            }
        }
    }


    /**
     * Рендира продукт в група
     */
    public function renderProductInGroup($data)
    {
        return self::renderDetail_($data);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	// Ако няма продаваеми продукти, слага се error на бутона
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$products = cat_Products::getByProperty('canSell');
    		if(!count($products)){
    			$data->toolbar->buttons['btnAdd']->error = 'Няма продаваеми продукти, които да се включат в групата';
    		}
    	}
    }
}

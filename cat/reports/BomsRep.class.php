<?php



/**
 * Мениджър на отчети от Задание за производство
 *
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_reports_BomsRep extends frame_BaseDriver
{                  
	
    /**
     * Заглавие
     */
    public $title = 'Артикули » Задание за производство';

    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'cat,ceo,sales,purchase';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'cat,ceo,sales,purchase';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'cat,ceo,sales,purchase';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'cat,ceo,sales,purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'cat,ceo,sales,purchase';

    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $form
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
    	$form->FLD('saleId', 'keylist(mvc=sales_Sales, select=id)', 'caption=Договор за продажба');
    	$form->FLD('groupId', 'keylist(mvc=cat_Groups,select=name)', 'caption=Група');
    }
      

    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
        $opt = $this->prepareOptions();

        $form->setSuggestions('saleId', array('' => '') + $opt);
    	
    	$this->invoke('AfterPrepareEmbeddedForm', array($form));
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
    	    	 
    }  

    
    /**
     * 
     * Подготвя вътрешното състояние, на база въведените данни
     */
    public function prepareInnerState()
    {
    	$data = new stdClass();
        $data->articleCnt = array();
        $data->recs = array();
        $fRec = $data->fRec = $this->innerForm;

        $this->prepareListFields($data);
       
        $salesArr = keylist::toArray($fRec->saleId);
        $salesArr = implode(',', $salesArr);

        $query = planning_Jobs::getQuery();
        $query->where("#saleId IN ('{$salesArr}') AND (#state = 'active' OR #state = 'wakeup')");

        // за всяко едно активно Задания за производство
        while($rec = $query->fetch()) { 

            // Намираме рецептата за артикула (ако има)
            $bomId = cat_Products::getLastActiveBom($rec->productId, 'production')->id;
            if(!$bomId) {
                $bomId = cat_Products::getLastActiveBom($rec->productId, 'sales')->id;
            }
            
            if (isset($bomId)) {
                $queryDetail = cat_BomDetails::getQuery();
                $queryDetail->where("#bomId = '{$bomId}'");
                
                $products = array();
                $materials = array();
              
                while($recDetail = $queryDetail->fetch()) {

                    $index = $recDetail->resourceId;
                    $componentArr = cat_Products::prepareComponents($rec->productId); 

                    foreach($componentArr as $component) { 
                        $divideBy = ($component->divideBy) ? $component->divideBy : 1;
   
                        if(!array_key_exists($index, $data->recs)){
                            
                            $quantity = str_replace(",", ".", $rec->quantity);
                            $propQuantity = str_replace(",", ".",$recDetail->propQuantity);
                             
                            if(!$recDetail->parentId || $recDetail->type == 'stage') {
                                unset($mArr[$index]);
                                $data->recs[$index] =
                                (object) array ('id' => $recDetail->id,
                                    'article' => $recDetail->resourceId,
                                    'articleCnt'	=> ($quantity * $propQuantity) / $divideBy,
                                    'params' => cat_Products::getParams($recDetail->resourceId, NULL, TRUE),
                                    'quantity' => $rec->quantity,
                                    'materials' => 0,
                                );
                            }
                        } else {
                        
                            $obj = &$data->recs[$index];
                            $obj->quantity += ($quantity * $propQuantity) / $divideBy[$recDetail->resourceId];
                        }
                    }
                }
            }
        }
    
        $i = 1;
        foreach ($data->recs as $id=>$rec){ 
            
            $queryProduct = cat_Products::getQuery();
            $queryProduct->where("#id  = '{$rec->article}'");
            $queryProduct->likeKeylist("groups", $fRec->groupId);
            
            if($queryProduct->fetch() == FALSE) {
                unset($data->recs[$id]);
            }
            
            $mArr = cat_Products::getMaterialsForProduction($rec->article,$rec->articleCnt, NULL,TRUE);
            $rec->num = $i;
            if(count($mArr) >=1) {
                foreach($mArr as $id=>$val){
                    
                    $rec->materials = array($id=>$id);
                    $rec->mCnt = array($id=>$val['quantity']);
                    $rec->mParams = key(cat_Products::getPacks($id));
                   
                }
            }
            
            $i++;
        }

        return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public function on_AfterPrepareEmbeddedData($mvc, &$res)
    {

        // Подготвяме страницирането
    	$data = $res;
        
        $pager = cls::get('core_Pager',  array('itemsPerPage' => $mvc->listItemsPerPage));
        $pager->setPageVar($mvc->EmbedderRec->className, $mvc->EmbedderRec->that);
        $pager->addToUrl = array('#' => $mvc->EmbedderRec->instance->getHandle($mvc->EmbedderRec->that));

        $pager->itemsCount = count($data->recs);
        $data->pager = $pager;
        
        $recs = array();
        foreach($data->recs as $rec){
            $recs[] = $rec;
        }

        if(count($recs)){
     
            foreach ($recs as $id => $r){ 
                
                $r->num = $id +1;

				if(!$pager->isOnPage()) continue;
				
				$row = new stdClass();
                $row = $mvc->getVerbal($r);
                $data->rows[$id] = $row;
                
            }
        }

        $res = $data;
    }
    
    
    /**
     * Вербалното представяне на ред от таблицата
     */
    protected function getVerbal_($rec)
    {

        $RichtextType = cls::get('type_Richtext');
        $Blob = cls::get('type_Blob');
        $Int = cls::get('type_Int');
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
        
        $row = new stdClass();
        
        $row->num = $Int->toVerbal($rec->num);
        $row->article = cat_Products::getShortHyperlink($rec->article);
        $row->articleCnt = $Int->toVerbal($rec->articleCnt);
        
        if(is_array($rec->params)) {
            unset($rec->params['$T']);
            
            foreach($rec->params as $name=>$val) {
             
                //if(!is_numeric($val)) continue;

                $name = cat_Params::getNormalizedName($name);
                $name = str_replace("_", " ", $name);
         
                if(strpos($name, "дължина") !== FALSE) { 
                    $row->length = $val;
                    continue;
                }
                
                if(strpos($name, "широчина") !== FALSE) {
                    $row->width = $val;
                    continue;
                }
                
                if(strpos($name, "височина") !== FALSE) {
                    $row->height = $val;
                    continue;
                }
            }
        }
         
        if(is_array($rec->materials)) { 
            foreach ($rec->materials as $material) { 
                $row->materials .= cat_Products::getShortHyperlink($material) . "<br/>";
            }
        }
        
        if(isset($rec->mParams)) {
            $row->mParams = cat_UoM::getShortName($rec->mParams);
        }
        
        if(is_array($rec->mCnt)) {
            foreach ($rec->mCnt as $mCnt) { 
                $row->mCnt = $Int->toVerbal($mCnt) . "<br/>";
            }
        }

        return $row;
    }
    

    /**
     * Връща шаблона на репорта
     *
     * @return core_ET $tpl - шаблона
     */
    public function getReportLayout_()
    {
        $tpl = getTplFromFile('cat/tpl/BomRepLayout.shtml');
         
        return $tpl;
    }
    
    
    /**
     * Полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    protected function prepareListFields_(&$data)
    {
        // Кои полета ще се показват
        $data->listFields = arr::make("num=№,
                             article=Детайл,
                             length=Параметри->Дължина,
    					     width=Параметри->Ширина,
                             height=Параметри->Височина,
                             articleCnt=Брой,
                             materials=Материали->Име,
                             mParams=Материали->Мярка,
                             mCnt=Материали->Количество", TRUE);
  
    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData(&$embedderTpl, $data)
    {
    	
    	if(empty($data)) return;
    	 
    	$tpl = $this->getReportLayout();
    	
    	$title = explode(" » ", $this->title);
    	
    	$tpl->replace($title[1], 'TITLE');

    	$salesArr = keylist::toArray($data->fRec->saleId);
    	
    	if(is_array($salesArr)) {
    	    foreach($salesArr as $id=>$sale){
    	        $link .= sales_Sales::getShortHyperLink($sale). "<br/>";
    	    }
    	}
    	
        $tpl->replace($link, 'saleId');
   
    	$tpl->placeObject($data->rec);

    	$f = cls::get('core_FieldSet');
    	
    	$f->FLD('num', 'int');
    	$f->FLD('article', 'varchar');
    	$f->FLD('articleCnt', 'int', 'tdClass=accItemClass,smartCenter');
    	$f->FLD('length', 'varchar','tdClass=smartCenter');
    	$f->FLD('width', 'varchar','tdClass=smartCenter');
    	$f->FLD('height', 'varchar','tdClass=smartCenter');
    	$f->FLD('materials', 'varchar');
    	$f->FLD('mParams', 'varchar');
    	$f->FLD('mCnt', 'int','tdClass=accItemClass,smartCenter');

    	$table = cls::get('core_TableView', array('mvc' => $f));

    	$tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');

    	if($data->pager){
    	     $tpl->append($data->pager->getHtml(), 'PAGER');
    	}

    	$embedderTpl->append($tpl, 'data');
    } 
    
    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     * @todo да се замести в кода по-горе
     */
    protected function getFields_()
    {
        // Кои полета ще се показват
        $f = new core_FieldSet;
        $f->FLD('num', 'int');
        $f->FLD('article', 'key(mvc=cat_Products,select=name)');
        $f->FLD('articleCnt', 'int');
        $f->FLD('length', 'varchar');
        $f->FLD('width', 'varchar');
        $f->FLD('height', 'varchar');
        $f->FLD('materials', 'key(mvc=cat_Products,select=name)');
        $f->FLD('mParams', 'key(mvc=cat_UoM,select=shortName)');
        $f->FLD('mCnt', 'int');

    
        return $f;
    }
    
    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    protected function getExportFields_()
    {
        // Кои полета ще се показват
        $fields = arr::make("num=№,
                             article=Детайл,
    					     articleCnt=Брой,
                             length=Параметри->Дължина,
                             width=Параметри->Ширина,
                             height=Параметри->Височина,
                             materials=Материали->Име,
    					     mParams=Материали->Мярка,
                             mCnt=Материали->Количество", TRUE);
        
        return $fields;
    }
    
    
    /**
     * 
     * Създаваме csv файл с данните
     */
    public function exportCsv()
    {
        $exportFields = $this->getExportFields();
        $fields = $this->getFields();

        $dataRec = array();
        foreach($this->innerState->recs as $id=>$rec){
            $dataRec[$id] = $rec; 
            $dataRec[$id]->params = self::getVerbal($rec)->params;
            $dataRec[$id]->params = str_replace("<br/>", ";", $dataRec[$id]->params);
            
            if(is_array($rec->mCnt)) {
                foreach($rec->mCnt as $pId=>$cnt){
                    $dataRec[$id]->mCnt =  cls::get('type_Int')->toVerbal($cnt);
                }
            }
            
            if(is_array($rec->materials)) {
                foreach($rec->materials as $mId=>$material){
                    $dataRec[$id]->materials =  $material;
                }
            }
        }

        $csv = csv_Lib::createCsv($dataRec, $fields, $exportFields);
         
        return $csv;
    }
     
    
    /**
     * 
     * Скрива полетата, които потребител с ниски права не може да вижда
     */
    public function hidePriceFields()
    {
    }
    
    
    /**
     * Коя е най-ранната дата на която може да се активира документа
     */
    public function getEarlyActivation()
    {
    	//return $this->innerForm->to;
    }
    
    
    /**
     * Подготвя опциите според състояние и производимост.
     *
     */
    public function prepareOptions()
    {
        // Всички договори/поръчки
        $query = sales_Sales::getQuery();
        // активен ли е?
        $query->where("#state = 'active'");
        
        $options = array();
        
        while($recSale = $query->fetch()){
            // детайла
            $queryDetail = sales_SalesDetails::getQuery();
            $queryDetail->where("#saleId = '{$recSale->id}'");
            while($recDetail = $queryDetail->fetch()){
                // производим ли е?
                $canManifacture = cat_Products::fetchField($recDetail->productId, 'canManifacture');
                // ако е
                if($canManifacture == "yes") {
                    // хендлър
                    $handle = sales_Sales::getHandle($recSale->id);
                    // дата
                    $valior = dt::mysql2verbal($recSale->valior, "d.m.y");
                    // контрагент
                    $Contragent = cls::get($recSale->contragentClassId);
                    $contragent = $Contragent->getTitleById($recSale->contragentId);
                    
                    $string = $handle . "/" . $valior . " " . $contragent;
                    // правим масив с опции
                    $options[$recSale->id] = $string;
                }
            }
        }
    
        return $options;
    }
}
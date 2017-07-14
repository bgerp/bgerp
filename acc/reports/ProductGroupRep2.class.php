<?php
/**
 * Мениджър на отчети от Задание за производство
 *
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Счетоводство » Продукти по групиAAAAAAAAAAAAAAAAAAAA
 */
class acc_reports_ProductGroupRep2 extends frame2_driver_Proto
{                  

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'acc,ceo';
    
    
    /**
     * Нормализираните имена на папките
     *
     * @var array
     */
    //private static $folderNames = array();
    
    
    /**
     * Имената на контрагентите
     *
     * @var array
     */
    //private static $contragentNames = array();
    
    
    /**
     * Дилърите
     *
     * @var array
     */
    //private static $dealers = array();
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    private $listItemsPerPage = 50;
    
    
    /**
     * Връща заглавието на отчета
     *
     * @param stdClass $rec - запис
     * @return string|NULL  - заглавието или NULL, ако няма
     */
    public function getTitle($rec)
    {
        return 'Счетоводство » Продукти по групи';
    }
    

    /**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
	    $fieldset->FLD('from', 'date(smartTime)', 'caption=От,after=title,single=none');
	    $fieldset->FLD('to',    'date(smartTime)', 'caption=До,after=from,single=none');
	    $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Група,after=to,single=none');
	}
      
	
    /**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param frame2_driver_Proto $Driver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
	{
	    $form = &$data->form;
	    // Размяна, ако периодите са объркани
	    if(isset($form->rec->from) && isset($form->rec->to) && ($form->rec->from > $form->rec->to)) {
	        $mid = $form->rec->from;
	        $form->rec->from = $form->rec->to;
	        $form->rec->to = $mid;
	    }
	}
    
	
	/**
	 * Подготвя данните на справката от нулата, които се записват в модела
	 *
	 * @param stdClass $rec        - запис на справката
	 * @return stdClass|NULL $data - подготвените данни
	 */
	public function prepareData($rec)
	{
	    
	    $data = new stdClass();
	    $data->recs = array();
	    $recs = array();
		$products = array();

	    // Обръщаме се към трудовите договори
		$query = sales_PrimeCostByDocument::getQuery();
		// ТОДО
        $query->where("#valior >= '{$rec->from}' AND #valior <= '{$rec->to}'");

	    $num = 1;
    	// за всеки един индикатор
    	while($recPrime = $query->fetch()){ 
    	        $id = $recPrime->productId ."|". $recPrime->containerId;
    	        $id = $recPrime->productId;
    	        // добавяме в масива събитието
    	        if(!array_key_exists($id,$data->recs)) { 
    	            $data->recs[$id]=
    	            (object) array (
    	                'kod' => cat_Products::fetchField($recPrime->productId, 'code'),
    	                'date' => $recPrime->valior,
    	                'docId' => $recPrime->containerId,
    	                'productId' => $recPrime->productId,
    	                'quantity' => $recPrime->quantity,
    	                'primeCost'=> $recPrime->quantity * $recPrime->primeCost,
    	                'sellCost' => $recPrime->quantity * $recPrime->sellCost,
    	                'group' => cat_Products::fetchField($recPrime->productId, 'groups'),
    	            );
    	            $num++;
    	        } else {
    	            $obj = &$data->recs[$id];
    	            $obj->quantity += $recPrime->quantity;
    	            $obj->primeCost += $recPrime->quantity * $recPrime->primeCost;
    	            $obj->sellCost += $recPrime->quantity * $recPrime->sellCost;
    	        }
    	    }
    	    
    	    $arr = array();
    	    foreach($data->recs as $i=>$r) {
    	        if(isset($rec->group)) {
    	            $groups = keylist::toArray($rec->group);
    	            $prodGroup = keylist::toArray($r->group);
    	            
    	            foreach($groups as $group) {
    	                if(array_key_exists($group, $prodGroup)) { 
    	                    //array_push($arr, $group);
    	                    $arr[$i][$group] = $group;
    	                }
    	            }
    	        }
    	    }
    	    
    	    //foreach($arr as $iArr=>$gr) {
    	     //   $data->recs[$iArr]->group = $gr;
    	    //}


	    return $data;
	}
	
	
	/**
	 * Рендиране на данните на справката
	 *
	 * @param stdClass $rec - запис на справката
	 * @return core_ET      - рендирания шаблон
	 */
	public function renderData($rec)
	{   
	    if(empty($rec->data)) return;
	    $tpl = new core_ET("[#PAGER_TOP#][#TABLE#][#PAGER_BOTTOM#]");
	    
	    $data = $rec->data;
	    $data->listFields = $this->getListFields($rec);
	    $data->rows = array();
	    
	    // Подготовка на пейджъра
	    if(!Mode::isReadOnly()){
	        $data->Pager = cls::get('core_Pager',  array('itemsPerPage' => $this->listItemsPerPage));
	        $data->Pager->setPageVar('frame2_Reports', $rec->id);
	        $data->Pager->itemsCount = count($data->recs);
	    }
	    
	    // Вербализиране само на нужните записи
	    $cnt = 1; 
	    if(is_array($data->recs)){
	        foreach ($data->recs as $index => $dRec){ 
	            if(isset($data->Pager) && !$data->Pager->isOnPage()) continue;
	            
	            $data->rows[$index] = $this->detailRecToVerbal($dRec);
	            
	            //if(array_key_exists($index, $data->recs)) {
	            //    $data->rows[$index]->num = $cnt;
	           // }
	            
	            //$cnt++;
	        }
	    }
	  
	    // Рендиране на пейджъра
	    if(isset($data->Pager)){
	        $tpl->append($data->Pager->getHtml(), 'PAGER_TOP');
	        $tpl->append($data->Pager->getHtml(), 'PAGER_BOTTOM');
	    }
	    
	    // Рендиране на лист таблицата
	    $fld = cls::get('core_FieldSet');
	
	        $fld->FLD('kod', 'varchar');
	        //$fld->FLD('num', 'varchar','caption=№');
	        $fld->FLD('productId', 'varchar');
	        $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Количество');
	        $fld->FLD('primeCost', 'varchar', 'smartCenter,caption=Общо->Себестойност');
	        $fld->FLD('sellCost', 'double(smartRound,decimals=2)', 'smartCenter,caption=Общо->Приход');
	    
	        if(isset($rec->group)) {
	            $fld->FLD('group', 'varchar', 'smartCenter,caption=Група');
	        }
	        
	    $table = cls::get('core_TableView', array('mvc' => $fld));
	    
	    // Показване на тагове
	   // if(core_Packs::isInstalled('uiext')){
	   //     uiext_Labels::showLabels($this, $rec->containerId, $data->recs, $data->rows, $data->listFields, 'containerId', 'Таг', $tpl, $fld);
	   // }
	    
	    $data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields);
	    $tpl->append($table->get($data->rows, $data->listFields), 'TABLE');
	    $tpl->removeBlocks();
	    $tpl->removePlaces();
	    
	    // Връщане на шаблона
	    return $tpl;
	}
	
    
    /**
	 * Вербализиране на данните
	 * 
	 * @param stdClass $dRec - запис от детайла
	 * @return stdClass $row - вербалния запис
	 */
	private function detailRecToVerbal(&$dRec)
	{
		$isPlain = Mode::is('text', 'plain');
		$Int = cls::get('type_Int');
		$Date = cls::get('type_Date');
		$Double = cls::get('type_Double');
		$Double->params['decimals'] = 2;
		$groArr  = array();
		$row = new stdClass();


		if(isset($dRec->kod)) {
		    $row->kod = $dRec->kod;
		}

		if(isset($dRec->productId)) {
		    $row->productId =  cat_Products::getShortHyperlink($dRec->productId);
		}

		foreach(array('quantity', 'primeCost', 'sellCost') as $fld) {
		    $row->{$fld} = $Double->toVerbal($dRec->{$fld});
		}

		if(isset($dRec->group)){
		    // избраната позиция
		    $groups = keylist::toArray($dRec->group);
		    foreach ($groups as &$g) {
		        $gro = cat_Groups::fetchField("#id = '{$g}'", 'name');
		        array_push($groArr, $gro);
		    }
		
		    $row->group = implode(', ', $groArr);
		}
		
		return $row;
	}
    
    /**
     * Връща списъчните полета
     *
     * @param stdClass $rec  - запис
     * @return array $fields - полета
     */
    private function getListFields($rec)
    {
        // Кои полета ще се показват
        $fields = array(
            'kod'=>'Код',
    		'productId'=>'Артикул',
    		'quantity'=>'Количество',
    		'primeCost'=>'Общо->Себестойност',
    		'sellCost'=>'Общо->Приход',
            'group'=>'Група',
                        );
        return $fields;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    public static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        $groArr = array();
       
            
        $Date = cls::get('type_Date');
        $row->from = $Date->toVerbal($rec->from);
        $row->to = $Date->toVerbal($rec->to);
        
        if(isset($rec->group)){
            // избраната позиция
            $groups = keylist::toArray($rec->group);
            foreach ($groups as &$g) {
                $gro = cat_Groups::fetchField("#id = '{$g}'", 'name');
                array_push($groArr, $gro);
            }
        
            $row->group = implode(', ', $groArr);
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    public static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
							    <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN group-->|Групи|*: [#group#]<!--ET_END group--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));

        if(isset($data->rec->from)){
            $fieldTpl->append($data->row->from, 'from');
        }
        
        if(isset($data->rec->to)){
            $fieldTpl->append($data->row->to, 'to');
        }

        if(isset($data->rec->group)){
            $fieldTpl->append($data->row->group, 'group');
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
    
    /**
     * Връща редовете на CSV файл-а
     *
     * @param stdClass $rec
     * @return array
     */
    public function getCsvExportRows($rec)
    {
        $dRecs = $rec->data->recs;
        $exportRows = array();
    
        Mode::push('text', 'plain');
        if(is_array($dRecs)){
            foreach ($dRecs as $key => $dRec){
                $exportRows[$key] = $this->detailRecToVerbal($dRec);
            }
        }
        Mode::pop('text');
    
        return $exportRows;
    }
    
    
    /**
     * Връща полетата за експортиране във csv
     *
     * @param stdClass $rec
     * @return array
     */
    public function getCsvExportFieldset($rec)
    {
        $fieldset->FLD('kod', 'varchar','caption=Код');
        $fieldset->FLD('productId', 'varchar','caption=Артикул');
        $fieldset->FLD('quantity', 'varchar','caption=Количество');
        $fieldset->FLD('primeCost', 'varchar','caption=Общо->Себестойност');
        $fieldset->FLD('sellCost', 'varchar','caption=Общо->Приход');
        $fieldset->FLD('group', 'varchar','caption=Група');

        return $fieldset;
    }
    
    
    /**
     * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
     *
     * @param stdClass $rec
     * @return boolean $res
     */
    public function canSendNotificationOnRefresh($rec)
    {
        // Намира се последните две версии
        $query = frame2_ReportVersions::getQuery();
        $query->where("#reportId = {$rec->id}");
        $query->orderBy('id', 'DESC');
        $query->limit(2);
    
        // Маха се последната
        $all = $query->fetchAll();
        unset($all[key($all)]);
    
        // Ако няма предпоследна, бие се нотификация
        if(!count($all)) return TRUE;
        $oldRec = $all[key($all)]->oldRec;
    
        $dataRecsNew = $rec->data->recs;
        $dataRecsOld = $oldRec->data->recs;
     
        $newContainerIds = $oldContainerIds = array();
        if(is_array($rec->data->recs)){
            $newContainerIds = arr::extractValuesFromArray($rec->data->recs, 'docId');
        }
    
        if(is_array($oldRec->data->recs)){
            $oldContainerIds = arr::extractValuesFromArray($oldRec->data->recs, 'docId');
        }
    
        // Ако има нови документи бие се нотификация
        $diff = array_diff_key($newContainerIds, $oldContainerIds);
        $res = (is_array($diff) && count($diff));
    
        return $res;
    }
}
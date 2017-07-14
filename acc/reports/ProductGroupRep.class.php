<?php



/**
 * Мениджър на отчети за продукти по групи
 *
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Счетоводство » Продукти по групи
 */
class acc_reports_ProductGroupRep extends frame2_driver_TableData
{                  
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, acc';

    
    /**
     * Полета от таблицата за скриване, ако са празни
     *
     * @var int
     */
    //protected $filterEmptyListFields = 'deliveryTime';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'productId';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     * @var varchar
     */
    protected $hashField = '$recIndic';
    
    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
     */
    protected $newFieldToCheck = 'docId';

    
    /**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
	    $fieldset->FLD('from', 'date(smartTime)', 'caption=От,after=title,single=none');
	    $fieldset->FLD('to',    'date(smartTime)', 'caption=До,after=title,single=none');
	    $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Група,after=title,single=none');
	}
      

    /**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param frame2_driver_Proto $Driver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
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
	 * Кои записи ще се показват в таблицата
	 * 
	 * @param stdClass $rec
	 * @param stdClass $data
	 * @return array
	 */
	protected function prepareRecs($rec, &$data = NULL)
	{
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
    	        // добавяме в масива събитието
    	        if(!array_key_exists($id,$recs)) { 
    	            $recs[$id]=
    	            (object) array (
    	                //'num' => $num,
    	                'date' => $recPrime->valior,
    	                'docId' => $recPrime->containerId,
    	                'productId' => $recPrime->productId,
    	                'quantity' => $recPrime->quantity,
    	                'primeCost'=> $recPrime->quantity * $recPrime->primeCost,
    	                'sellCost' => $recPrime->quantity * $recPrime->sellCost,
    	                'group' => cat_Products::fetchField($recPrime->productId, 'groups'),
  
    	            );
    	        } 
    	    }
    	    
    	    $arr = array();
    	    foreach($recs as $i=>$r) {
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
    	    
    	    foreach($arr as $iArr=>$gr) {
    	        $recs[$iArr]->group = $gr;
    	    }


		return $recs;
	}
	
	
	/**
	 * Връща фийлдсета на таблицата, която ще се рендира
	 *
	 * @param stdClass $rec      - записа
	 * @param boolean $export    - таблицата за експорт ли е
	 * @return core_FieldSet     - полетата
	 */
	protected function getTableFieldSet($rec, $export = FALSE)
	{
		$fld = cls::get('core_FieldSet');
	
		if($export === FALSE){
    		$fld->FLD('num', 'varchar','caption=№');
    		$fld->FLD('productId', 'varchar', 'caption=Артикул');
    		$fld->FLD('quantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Количество');
    		$fld->FLD('primeCost', 'varchar', 'smartCenter,caption=Сума->Продажна');
    		$fld->FLD('sellCost', 'double(smartRound,decimals=2)', 'smartCenter,caption=Сума->Себестойност');
		    
		    if(isset($rec->group)) {
		        $fld->FLD('group', 'varchar', 'smartCenter,caption=Група');
		    }

		} else { 
			$fld->FLD('num', 'varchar','caption=№');
			$fld->FLD('productId', 'varchar', 'caption=Артикул');
			$fld->FLD('quantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Количество');
	    	$fld->FLD('primeCost', 'varchar', 'caption=Сума->Продажна');
		    $fld->FLD('sellCost', 'double(smartRound,decimals=2)', 'smartCenter,caption=Сума->Себестойност');
		    $fld->FLD('group', 'varchar', 'smartCenter,caption=Група');
		}
	
		return $fld;
	}
	
	
    /**
	 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
	 *
	 * @param stdClass $rec  - записа
	 * @param stdClass $dRec - чистия запис
	 * @return stdClass $row - вербалния запис
	 */
	protected function detailRecToVerbal($rec, &$dRec)
	{
		$isPlain = Mode::is('text', 'plain');
		$Int = cls::get('type_Int');
		$Date = cls::get('type_Date');
		$Double = cls::get('type_Double');
		$Double->params['decimals'] = 2;
		$groArr  = array();
		$row = new stdClass();


		if(isset($dRec->num)) {
		    $row->num = $Int->toVerbal($dRec->num);
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
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
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
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
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
}
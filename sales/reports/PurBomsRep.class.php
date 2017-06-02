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
 * @title     Продажби » Договори, чакащи за задание
 */
class sales_reports_PurBomsRep extends frame2_driver_TableData
{                  
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'cat,ceo,sales,purchase';
    
    
    /**
     * Дилърите
     *
     * @var array
     */
    private static $dealers = array();
    
    
    /**
     * Полета от таблицата за скриване, ако са празни
     *
     * @var int
     */
    protected $filterEmptyListFields = 'deliveryTime';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     * @var varchar
     */
    protected $hashField = 'containerId';
    
    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
     */
    protected $newFieldToCheck = 'containerId';
    
    
    /**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
	    $fieldset->FLD('dealers', 'keylist(mvc=core_Users,select=nick)', 'caption=Търговци,after=title,single=none');
	    $fieldset->FLD('precision', 'percent(min=0,max=1)', 'caption=Авансово платено,unit=и нагоре,after=dealers,remember');
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
		
		// Всички активни потебители
		$uQuery = core_Users::getQuery();
		$uQuery->where("#state = 'active'");
		$uQuery->orderBy("#names", 'ASC');
		$uQuery->show('id');
		
		// Които са търговци
		$roles = core_Roles::getRolesAsKeylist('ceo,sales');
		$uQuery->likeKeylist('roles', $roles);
		$allDealers = arr::extractValuesFromArray($uQuery->fetchAll(), 'id');
		
		// Към тях се добавят и вече избраните търговци
		if(isset($form->rec->dealers)){
			$dealers = keylist::toArray($form->rec->dealers);
			$allDealers = array_merge($allDealers, $dealers);
		}
		
		// Вербализират се
		$suggestions = array();
		foreach ($allDealers as $dealerId){
			$suggestions[$dealerId] = core_Users::fetchField($dealerId, 'nick');
		}
		
		// Задават се като предложение
		$form->setSuggestions('dealers', $suggestions);
		
		// Ако текущия потребител е търговец добавя се като избран по дефолт
		if(haveRole('sales') && empty($form->rec->id)){
			$form->setDefault('dealers', keylist::addKey('', core_Users::getCurrent()));
		}
		
		$form->setDefault('precision', "0.95");
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
		$salArr = array();
		$Sales = cls::get('sales_Sales');
		$dealers = keylist::toArray($rec->dealers);
	    $count = 1;
		
		// Всички чакащи и активни продажби на избраните дилъри
		$sQuery = sales_Sales::getQuery();
		$sQuery->where("#state = 'active'");
		 
		if(count($dealers)){
			$sQuery->in('dealerId', $dealers);
		}

		// За всяка
		while($sRec = $sQuery->fetch()){

			// Взимане на договорените и експедираните артикули по продажбата (събрани по артикул)
			$dealerId = ($sRec->dealerId) ? $sRec->dealerId : (($sRec->activatedBy) ? $sRec->activatedBy : $sRec->createdBy);
			$dealInfo = $Sales->getAggregateDealInfo($sRec);

			$delTime = (!empty($sRec->deliveryTime)) ? $sRec->deliveryTime : (!empty($sRec->deliveryTermTime) ?  dt::addSecs($sRec->deliveryTermTime, $sRec->valior) : NULL);
			if(empty($delTime)){
				$delTime = $Sales->getMaxDeliveryTime($sRec->id);
				$delTime = ($delTime) ? dt::addSecs($delTime, $sRec->valior) : $sRec->valior;
			}

			// Колко е очакваното авансово плащане
			$downPayment = $dealInfo->agreedDownpayment;
			// Колко е платено
			$downpayment = $dealInfo->downpayment;
			//$downpayment = $dealInfo->amountPaid;
		
			// ако имаме зададено авансово плащане
			// дали имаме поне 95% авансово плащане
			if(isset($rec->precision)) {
			    if($downpayment < $downPayment * $rec->precision)  continue;
			} else {
			    if($downpayment < $downPayment * 0.95)  continue;
			}
			 
			// артикулите
			$agreedProducts = $dealInfo->get('products');

			$d = NULL;
	
			// За всеки договорен артикул
			foreach ($agreedProducts as $pId => $pRec){ 
				// ако е нестандартен
				$productRec = cat_Products::fetch($pId, 'canManifacture,isPublic');

				if($sRec->closedDocuments != NULL) {
				
				    $newKeylist = keylist::addKey($sRec->closedDocuments, $sRec->id);
				
				    $salesArr = keylist::toArray($newKeylist);
				    $salesSrt = implode(',', $salesArr);
				}		
			
				// Ако артикула е нестандартен и няма задание по продажбата
				// артикула да е произведим
				if($productRec->isPublic == 'no' && $productRec->canManifacture == 'yes'){ 
				    if(is_array($salesArr)) { 
    				    if(in_array($sRec->id, $salesArr)) { 
    				        $jobId = planning_Jobs::fetchField("#productId = {$pId} AND #saleId IN ({$salesSrt})");
    				       
    				    } else { 
				          $jobId = planning_Jobs::fetchField("#productId = {$pId} AND #saleId = {$sRec->id} ");
				       
    				    }
				    
				    } else { 
				        $jobId = planning_Jobs::fetchField("#productId = {$pId} AND #saleId = {$sRec->id}");
				    }

				    $jobState = NULL;
				    $jobQuantity = NULL;
					if(isset($jobId)) {
						$jobState = planning_Jobs::fetchField("#id = {$jobId}",'state');
						$jobQuantity = planning_Jobs::fetchField("#id = {$jobId}",'quantity');
					}
					
					if (!$jobId || ($jobState == 'draft' || $jobState == 'rejected') || $jobQuantity < $pRec->quantity * 0.90) {  
					    
					    $index = $sRec->id . "|" . $pId;
						$d = (object) array ("num" => $count,
											  "containerId" => $sRec->containerId,
								              "pur" => $sRec->id,
								              "purDate" => $sRec->valior,
								              "deliveryTime" => $delTime,
								              "article" => $pId,
								              "dealerId" => $dealerId,
								              "quantity"=>$pRec->quantity);
						
						$count++;
						
					}
					
					if($d != NULL) {
					    $index = $sRec->id . "|" . $pId;
					    if($pId == $d->article) {
    					    
    					    $recs[$index] = $d;
					    } 
					}
				}
			}
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
			$fld->FLD('pur', 'varchar', 'caption=Договор->№');
	    	$fld->FLD('purDate', 'varchar', 'caption=Договор->Дата');
		    $fld->FLD('dealerId', 'varchar', 'smartCenter,caption=Търговец');
		    $fld->FLD('article', 'varchar', 'caption=Артикул');
	    	$fld->FLD('quantity', 'varchar', 'smartCenter,caption=К-во');
	    	$fld->FLD('deliveryTime', 'varchar', 'caption=Доставка');
		} else {
			$fld->FLD('num', 'varchar','caption=№');
       		$fld->FLD('pur', 'varchar','caption=Договор->№');
       		$fld->FLD('purDate', 'varchar','caption=Договор->Дата');
        	$fld->FLD('dealerId', 'varchar','caption=Търговец');
        	$fld->FLD('article', 'varchar','caption=Артикул');
       		$fld->FLD('quantity', 'varchar','caption=Количество');
        	$fld->FLD('deliveryTime', 'varchar','caption=Доставка');
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
		$row = new stdClass();

		// Линк към дилъра
		if(!array_key_exists($dRec->dealerId, self::$dealers)){
			self::$dealers[$dRec->dealerId] = crm_Profiles::createLink($dRec->dealerId);
		}
		
		if(isset($dRec->dealerId)) {
		    $row->dealerId = self::$dealers[$dRec->dealerId];
		}
		
		if($isPlain){
			$row->dealerId = strip_tags(($row->dealerId instanceof core_ET) ? $row->dealerId->getContent() : $row->dealerId);
		}

		if(isset($dRec->num)) {
		    $row->num = $Int->toVerbal($dRec->num);
		}

		//if(isset($dRec->deliveryTime)) {
		    $row->deliveryTime = ($isPlain) ? frame_CsvLib::toCsvFormatData($dRec->deliveryTime) : dt::mysql2verbal($dRec->deliveryTime);
		//}
		
		if(isset($dRec->pur)) {
			$row->pur = ($isPlain) ? sales_Sales::getTitleById($dRec->pur) : sales_Sales::getLink($dRec->pur, 0);
		}
		
		if(isset($dRec->purDate)) {
		    $row->purDate = $Date->toVerbal($dRec->purDate);
		}
		
		if(isset($dRec->article)) {
			$row->article = ($isPlain) ? cat_Products::getTitleById($dRec->article, FALSE) : cat_Products::getShortHyperlink($dRec->article);
		}
		
		if(isset($dRec->quantity)) {
			$row->quantity = ($isPlain) ? frame_CsvLib::toCsvFormatDouble($dRec->quantity) : $Int->toVerbal($dRec->quantity);
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
        if(isset($rec->precision) && $rec->precision != 1){
            $row->precision .= " " . tr('+');
        }
        
        $dealers = keylist::toArray($rec->dealers);
        foreach ($dealers as $userId => &$nick) {
            $nick = crm_Profiles::createLink($userId)->getContent();
        }
    
        $row->dealerId = implode(', ', $dealers);
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
							    <small><div><!--ET_BEGIN dealers-->|Търговци|*: [#dealers#]<!--ET_END dealers--></div></small></fieldset><!--ET_END BLOCK-->"));
      
        if(isset($data->rec->dealers)){
            $fieldTpl->append($data->row->dealerId, 'dealers');
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
}
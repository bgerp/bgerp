<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_BalanceReportImpl
{
    
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectSource = 'ceo, acc';
    
	
	/**
	 * Заглавие
	 */
    public $title = 'Подробен баланс';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    public function prepareReportForm($form)
    {
    	$form->FLD('accountId', 'acc_type_Account', 'caption=Сметка,mandatory');
    	$form->FLD('from', 'date', 'caption=От,mandatory');
    	$form->FLD('to', 'date', 'caption=До,mandatory');
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    public function checkReportForm($form)
    {
    	if($form->isSubmitted()){
    		if($form->rec->to < $form->rec->from){
    			$form->setError('to, from', 'Началната дата трябва да е по малка от крайната');
    		}
    	}
    }
    
    
    /**
     * Подготвя хедърите на заглавията на таблицата
     */
    private function prepareListFields(&$data)
    {
    	$accInfo = acc_Accounts::getAccountInfo($data->rec->accountId);
    	$bShowQuantities = ($accInfo->isDimensional === TRUE) ? TRUE : FALSE;
    	
    	$data->listFields = array();
    	if(count($accInfo->groups)){
    		$data->listFields = array('id' => '№', 'entries' => ' ');
    	}
    	
    	if ($bShowQuantities) {
    		$data->listFields += array(
    				'baseQuantity' => 'Начално салдо->ДК->К-во',
    				'baseAmount' => 'Начално салдо->ДК->Сума',
    				'debitQuantity' => 'Обороти->Дебит->К-во',
    				'debitAmount' => 'Обороти->Дебит->Сума',
    				'creditQuantity' => 'Обороти->Кредит->К-во',
    				'creditAmount' => 'Обороти->Кредит->Сума',
    				'blQuantity' => 'Крайно салдо->ДК->К-во',
    				'blAmount' => 'Крайно салдо->ДК->Сума',
    		);
    	} else {
    		$data->listFields += array(
    				'baseAmount' => 'Салдо->Начално',
    				'debitAmount' => 'Обороти->Дебит',
    				'creditAmount' => 'Обороти->Кредит',
    				'blAmount' => 'Салдо->Крайно',
    		);
    	}
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    public function prepareReportData($filter)
    {
    	$data = new stdClass();
    	$data->rec = $filter;
    	
    	$accSysId = acc_Accounts::fetchField($data->rec->accountId, 'systemId');
    	$Balance = new acc_ActiveShortBalance(array('from' => $data->rec->from, 'to' => $data->rec->to));
    	$data->recs = $Balance->getBalance($accSysId);
    	 
    	$this->prepareListFields($data);
    	
    	// Подготвяме страницирането
    	$Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listItemsPerPage));
    	$Pager->itemsCount = count($data->recs);
    	$Pager->calc();
    	$data->pager = $Pager;
    	
    	$start = $data->pager->rangeStart;
    	$end = $data->pager->rangeEnd - 1;
    	
    	$data->hideQuantities = FALSE;
    	if(count($data->recs)){
    		$count = 0;
    		foreach ($data->recs as $id => $rec){
    			
    			// Показваме само тези редове, които са в диапазона на страницата
    			if($count >= $start && $count <= $end){
    				$rec->id = $count + 1;
    				$row = $this->recToVerbal($rec);
    				if($row->blAmount != $row->blQuantity){
    					$data->hideQuantities = TRUE;
    				}
    				
    				$data->rows[$id] = $row;
    			}
    			
    			$count++;
    		}
    	}
    	
    	return $data;
    }
    
    
    /**
     * Вербалното представяне на ред от таблицата
     */
    public function recToverbal($rec)
    {
    	$Varchar = cls::get('type_Varchar');
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	$Int = cls::get('type_Int');
    	
    	$row = new stdClass();
    	$row->id = $Int->toVerbal($rec->id);
    	
    	foreach (array('baseAmount', 'debitAmount', 'creditAmount', 'blAmount', 'baseQuantity', 'debitQuantity', 'creditQuantity', 'blQuantity') as $fld){
    		$row->$fld = $Double->toVerbal($rec->$fld);
    		$row->$fld = (($rec->$fld) < 0) ? "<span style='color:red'>{$row->$fld}</span>" : $row->$fld;
    	}
    	
    	if($rec->baseAmount == 0) unset($row->baseAmount);
    	if($rec->baseQuantity == 0) unset($row->baseQuantity);
    	
    	foreach (array(1 => 'ent1Id', 2 =>  'ent2Id', 3 => 'ent3Id') as $id => $fld){
    		if(isset($rec->$fld)){
    			$row->entries .= "<div>{$id} . " . acc_Items::getVerbal($rec->$fld, 'titleLink') . "</div>";
    		}
    	}
    	
    	$row->ROW_ATTR['class'] = ($rec->id % 2 == 0) ? 'zebra1' :'zebra0';
    	
    	return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    public function renderReportData($filter, $data)
    {
    	if(empty($data)) return;
    	
    	$tpl = getTplFromFile('acc/tpl/ReportDetailedBalance.shtml');
    	$filter->accountId = acc_Balances::getAccountLink($data->rec->accountId, NULL, TRUE, TRUE);
    	$tpl->placeObject($filter);
    	
    	$tableMvc = new core_Mvc;
    	$tableMvc->FLD('baseQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('baseAmount', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('debitQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('debitAmount', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('creditQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('creditAmount', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('blQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('blAmount', 'int', 'tdClass=accCell');
    	
    	if(!$data->hideQuantities){
    		unset($data->listFields['baseQuantity'], $data->listFields['debitQuantity'], $data->listFields['creditQuantity'], $data->listFields['blQuantity']);
    	}
    	$table = cls::get('core_TableView', array('mvc' => $tableMvc));
    	
    	$tpl->append($table->get($data->rows, $data->listFields), 'DETAILS');
    	 
    	if($data->pager){
    		$tpl->append($data->pager->getHtml(), 'PAGER_BOTTOM');
    		$tpl->append($data->pager->getHtml(), 'PAGER_TOP');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    function canSelectSource($userId = NULL)
    {
    	 return core_Users::haveRole($this->canSelectSource, $userId);
    }
}
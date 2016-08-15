<?php



/**
 * Помощен детайл подготвящ и обединяващ заедно търговските
 * детайли на фирмите и лицата
 *
 * @category  bgerp
 * @package   crm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class crm_PersonsDetails extends core_Manager
{
	
	
	/**
	 * Подготвя ценовата информация за артикула
	 */
	public function preparePersonsDetails($data)
	{
		$data->TabCaption = 'Лични данни';
		expect($data->masterMvc instanceof crm_Persons);
		
		$employeeId = crm_Groups::getIdFromSysId('employees');
		if(keylist::isIn($employeeId, $data->masterData->rec->groupList)){
			$data->Codes = cls::get('crm_ext_Employees');
			$data->TabCaption = 'HR';
		}
		
		$eQuery = hr_EmployeeContracts::getQuery();
		$eQuery->where("#personId = '{$data->masterId}'"); 
		
		$dQuery = hr_Departments::getQuery();
		while($eRec = $eQuery->fetch()){
		    $data->masterId = $eRec->departmentId;
		    $data->Cycles = cls::get('hr_WorkingCycles');
		   
		}
		
		$data->Cards = cls::get('crm_ext_IdCards');
		$data->Cards->prepareIdCard($data);
		
		if(isset($data->Cycles)){
			$data->Cycles->prepareGrafic($data);
			$data->TabCaption = 'HR';
		}
		
		if(isset($data->Codes)){
		    $data->Codes->prepareData($data);
		    if(crm_ext_Employees::haveRightFor('add', (object)array('personId' => $data->masterId))){
		        $data->addResourceUrl = array('crm_ext_Employees', 'add', 'personId' => $data->masterId, 'ret_url' => TRUE);
		    }
		}
	}
	
	
	/**
	 * Подготвя ценовата информация за артикула
	 */
	public function renderPersonsDetails($data)
	{
		$tpl = getTplFromFile('crm/tpl/PersonsData.shtml');
		
		$cardTpl = $data->Cards->renderIdCard($data);
		$cardTpl->removeBlocks();
		$tpl->append($cardTpl, 'IDCARD');
		
		if(isset($data->Codes)){
			$resTpl = $data->Codes->renderData($data);
			$resTpl->removeBlocks();
			$tpl->append($resTpl, 'CODE');
		}
		
		if(isset($data->Cycles)){
		    $resTpl = $data->Cycles->renderGrafic($data);
		    $resTpl->removeBlock('legend');
		    $resTpl->removeBlocks();
		    $tpl->append($resTpl, 'CYCLES');
	
		    if (crm_Persons::haveRightFor('single', (object)array('personId' => $data->masterId))) {
    		    // правим url  за принтиране
                $url = array('hr_WorkingCycles', 'Print', 'Printing'=>'yes', 'masterId' => $data->masterId, 'cal_month'=>$data->Cycles->month, 'cal_year' =>$data->Cycles->year);
                $efIcon = 'img/16/printer.png';
                $link = ht::createLink('', $url, FALSE, "title=Печат,ef_icon={$efIcon}");                
                $tpl->append($link, 'print');
		    }
		}

		return $tpl;
	}
}
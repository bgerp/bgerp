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
			$ResourceCodes = cls::get('crm_ext_ResourceCodes');
			
			//@TODO да го махна като инсталирам модела
			$db = new core_Db();
			if($db->tableExists($ResourceCodes->dbTableName)){
				$data->ResourceCodes = $ResourceCodes;
			}
		}
		
		$data->Cards = cls::get('crm_ext_IdCards');
		if(isset($data->ResourceCodes)){
			$data->ResourceCodes->prepareData($data);
			if(crm_ext_ResourceCodes::haveRightFor('add', (object)array('personId' => $data->masterId))){
				$data->addResourceUrl = array('crm_ext_ResourceCodes', 'add', 'personId' => $data->masterId, 'ret_url' => TRUE);
			}
		}
		
		
		
		$data->Cards->prepareIdCard($data);
		
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
		
		if(isset($data->ResourceCodes)){
			$resTpl = $data->ResourceCodes->renderData($data);
			$resTpl->removeBlocks();
			$tpl->append($resTpl, 'CODE');
		}
		
		return $tpl;
	}
}
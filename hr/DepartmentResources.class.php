<?php 


/**
 * Структура
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_DepartmentResources extends core_Manager
{
	
	
	/**
	 * Подготвя ценовата информация за артикула
	 */
	public function prepareResources($data)
	{
		$data->TabCaption = 'Ресурси';
		$data->assetRecs = $data->employeeRecs = $data->employeeRows = $data->assetRows = array();
		
		$pQuery = crm_ext_Employees::getQuery();
		$pQuery->like("departments", "|{$data->masterId}|");
		while($pRec = $pQuery->fetch()){
			$data->employeeRecs[$pRec->id] = $pRec;
			$data->employeeRows[$pRec->id] = crm_ext_Employees::recToVerbal($pRec);
		}
		
		$aQuery = planning_AssetResources::getQuery();
		$aQuery->like("departments", "|{$data->masterId}|");
		while($aRec = $aQuery->fetch()){
			$data->assetRecs[$aRec->id] = $aRec;
			$data->assetRows[$aRec->id] = planning_AssetResources::recToVerbal($aRec);
		}
	}
	
	
	/**
	 * Подготвя ценовата информация за артикула
	 */
	public function renderResources($data)
	{
		$tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
		$tpl->append(tr('Ресурси на департамента'), 'title');
		
		if(count($data->employeeRows)){
			foreach ($data->employeeRows as $eRow){
				$tpl->append("{$eRow->code} ({$eRow->personId})<br>", 'content');
			}
			
			//$table = cls::get('core_TableView', array('mvc' => cls::get('crm_ext_Employees')));
			//$eTpl = $table->get($data->employeeRows, 'personId=Служител,code=Код');
			//$tpl->append($eTpl, 'content');
		}
		
		if(count($data->assetRows)){
			$table = cls::get('core_TableView', array('mvc' => cls::get('planning_AssetResources')));
			$aTpl = $table->get($data->assetRows, 'name=Оборудване,protocolId,quantity=Количество');
			$tpl->append($aTpl, 'content');
		}
		
		return $tpl;
	}
}
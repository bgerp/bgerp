<?php



/**
 * Клас 'store_plg_StoreFilter'
 * Плъгин за филтър по склад и състояние на складови документи
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_plg_StoreFilter extends core_Plugin
{
	
	
	/**
	 *  Подготовка на филтър формата
	 */
	public static function on_AfterPrepareListFilter($mvc, $data)
	{
		setIfNot($mvc->filterStoreFields, 'storeId');
		$storeFields = $mvc->filterStoreFields;
		
		if(!Request::get('Rejected', 'int')){
			$data->listFilter->FNC('store', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,input,silent');
			$data->listFilter->FNC('dState', 'enum(all=Всички, pending=Заявка, draft=Чернова, active=Контиран)', 'caption=Състояние,input,silent');
			$data->listFilter->showFields .= ',store,dState';
			$data->listFilter->input();
			$data->listFilter->setDefault('dState', 'all');
			 
			if($rec = $data->listFilter->rec){
	
				// Филтър по състояние
				if($rec->dState){
					if($rec->dState != 'all'){
						$data->query->where("#state = '{$rec->dState}'");
					}
				}
				 
				// Филтър по склад
				if($rec->store){
					$fields = arr::make($storeFields, TRUE);
					$where = '';
					foreach ($fields as $fld){
						$where .= (($where) ? ' OR ' : '') . "#{$fld} = {$rec->store}";
					}
					
					$data->query->where($where);
				}
			}
		}
	}
}
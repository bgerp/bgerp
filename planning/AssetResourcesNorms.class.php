<?php



/**
 * Мениджър на ресурсите свързани с обекти
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_AssetResourcesNorms extends core_Master
{
	
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	public function description()
	{
		$this->FLD('groupId', 'key(mvc=planning_AssetGroups,select=name,allowEmpty)', 'caption=Група,mandatory,silent');
		$this->FLD("productId", 'key(mvc=cat_Products,select=name)', 'silent,mandatory,caption=Артикул');
		$this->FLD("indTime", 'time(noSmart)', 'caption=Норма->Време,smartCenter');
		 
		$this->setDbUnique('groupId,productId');
	}
}
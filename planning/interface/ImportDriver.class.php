<?php



/**
 * Баща за импортиране на драйверите за производветните документи
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class planning_interface_ImportDriver extends import2_AbstractDriver 
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	protected $canSelectDriver = 'ceo,planning,store';
	
	
	/**
	 * Интерфейси, поддържани от този мениджър
	 */
	public $interfaces = 'planning_interface_ImportDetailIntf';
	
	
	/**
	 * Връща записите, подходящи за импорт в детайла.
	 * Съответстващия 'importRecs' метод, трябва да очаква
	 * същите данни (@see import_DestinationIntf)
	 *
	 * @see import_DriverIntf
	 * @param object $rec
	 * 		o xmlFile        - ид на файл от филеман
	 *
	 * @return void
	 */
	public function doImport(core_Manager $mvc, $rec)
	{
		if(!is_array($rec->importRecs)) return;
		
		foreach ($rec->importRecs as $rec){
			expect($rec->productId, 'Липсва продукт ид');
			expect(cat_Products::fetchField($rec->productId), 'Няма такъв артикул');
			expect($rec->packagingId, 'Няма опаковка');
			expect(cat_UoM::fetchField($rec->packagingId), 'Несъществуваща опаковка');
			expect($rec->{$mvc->masterKey}, 'Няма мастър кей');
			expect($mvc->Master->fetch($rec->{$mvc->masterKey}), 'Няма такъв запис на мастъра');
			expect($mvc->haveRightFor('add', (object)array($mvc->masterKey => $rec->{$mvc->masterKey})), 'Към този мастър не може да се добавя артикул');
			 
			if(!$mvc->isUnique($rec, $fields, $exRec)){
				core_Statuses::newStatus('Записа, не е импортиран защото имa дублаж');
				continue;
			}
			 
			$mvc->save($rec);
		}
	}
}
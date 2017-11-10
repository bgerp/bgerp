<?php



/**
 * Интерфейс за импортиране на данни в детайл
 *
 *
 * @category  bgerp
 * @package   import
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за импортиране на данни в мениджър
 */
class import_plg_Detail extends core_Plugin
{
	
	
	/**
	 * Извиква се след описанието на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		$mvc->declareInterface('import_DestinationIntf');
	}
	
	
	/**
	 * Извиква се преди изпълняването на екшън
	 */
	public static function on_BeforeAction($mvc, &$res, $action)
	{
		if ($action != 'importrecs') return;
		
		$mvc->requireRightFor('importrec');
		$form = cls::get('core_Form');
		$rec = &$form->rec;
		$form->FLD('driverClass', 'int', 'silent,removeAndRefreshForm,caption=Действие');
		$form->FLD($mvc->masterKey, 'int', 'silent,input=hidden');
		$form->input('', 'silent');
		$mvc->requireRightFor('importrec', (object)array($mvc->masterKey => $rec->{$mvc->masterKey}));
		$title = $mvc->Master->getFormTitleLink($rec->{$mvc->masterKey});
		$form->title = "Импорт на записи в|* <b>" . $title . "</b>";
		
		// Извличане на записите
		$drivers = $mvc->getDriverOptions($rec->{$mvc->masterKey});
		$form->setOptions('driverClass', $drivers);
		$form->setDefault('driverClass', key($drivers));
		
		// Ако има избран драйвер
		if(isset($rec->driverClass)){
			$Driver = cls::get($rec->driverClass);
			
			// Добавят се полетата от него
			$Driver->addImportFields($mvc, $form);
			$refreshFields = arr::make(array_keys($form->selectFields()), TRUE);
			unset($refreshFields['driverClass'], $refreshFields['noteId']);
			$refreshFieldsString = implode('|', $refreshFields);
			$form->setField('driverClass', "removeAndRefreshForm={$refreshFieldsString}");
			
			// Инпут и проверка на формата
			$form->input();
			$Driver->checkImportForm($mvc, $form);
		}
		
		// Ако е събмитната формата
		if($form->isSubmitted()){
			if($Driver = cls::get($rec->driverClass)){
				
				// Опит за подготовка на записите за импорт
				$recs = $Driver->getImportRecs($mvc, $rec);
				if(!count($recs)){
					$form->setError(implode(',', $refreshFields), 'Има проблем при подготовката на записите за импорт');
				}
				
				if(!$form->gotErrors()){
					try{
						// Опит за импортиране на записите в детайла
						$mvc->importRecs($recs);
						$msg = 'Записите са добавени';
						$type = 'notice';
					} catch(core_exception_Expect $e){
						reportException($e);
						$msg = 'Проблем при добавяне на записите';
						$type = 'error';
					}
					
					followRetUrl(NUll, $msg, $type);
				}
			}
		}
		
		// Добавяне на бутони
		$form->toolbar->addSbBtn('Импорт', 'save', 'ef_icon = img/16/star_2.png, title=Импорт');
		$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
		
		// Рендиране на формата
		$res = $mvc->renderWrapping($form->renderHtml());
		core_Form::preventDoubleSubmission($res, $form);
			
		// ВАЖНО: спираме изпълнението на евентуални други плъгини
		return FALSE;
	}
	
	
	/**
	 * Изпълнява се след подготвянето на тулбара в листовия изглед
	 */
	protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
	{
		// Бутон за импорт в лист изгледа
		if($mvc->haveRightFor('importrec', (object)array($mvc->masterKey => $data->masterId))){
			$data->toolbar->addBtn('Импорт', array($mvc, 'importrecs', $mvc->masterKey => $data->masterId, 'ret_url' => TRUE), NULL, "title=Импортиране на записи,ef_icon=img/16/import.png");
		}
	}
	
	
	
	/**
	 * Връща възможните за избор драйвери
	 * 
	 * @param core_Manager $mvc
	 * @param array|NULL $res
	 * @param stdClass|NULL $rec
	 * @param int|NULL  $limit
	 * @return void
	 */
	public static function on_AfterGetDriverOptions($mvc, &$res, $rec = NULL, $limit = NULL)
	{
		$count = 1;
		$options = array();
		$drivers = core_Classes::getOptionsByInterface('import_DriverIntf', 'title');
		if(is_array($drivers)){
			foreach ($drivers as $driverId => $driverClass){
				$Driver = cls::get($driverId);
				
				if($Driver->canSelectDriver($mvc, $rec)){
					$options[$driverId] = $driverClass;
					if(isset($limit) && $limit == $count) break;
					$count++;
				}
			}
		}
		
		$res = $options;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'importrec'){
			$requiredRoles = $mvc->getRequiredRoles('add', $rec, $userId);
		}
		
		// Може да се импортира само ако може да се избере поне един драйвер
		if($action == 'importrec' && isset($rec)){
			$drivers = $mvc->getDriverOptions($rec->{$mvc->masterKey}, 1);
			if(!count($drivers)){
				$requiredRoles = 'no_one';
			}
		}
	}
}
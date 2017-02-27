<?php



/**
 * Клас 'doc_plg_TransferDocs'
 *
 * Плъгин за за прехвурляне на документи от проектна папка към папка на потребител
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class doc_plg_TransferDoc extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		// Ако има уточнено поле към което да се трансферира, проверка дали е валидно
		if(isset($mvc->transferFolderField)){
			expect($Type = $mvc->getFieldType($mvc->transferFolderField));
			expect($Type instanceof type_Key);
			
			// Полето трябва да е от тип ключ към корица на папка
			$typeMvc = $mvc->getFieldTypeParam($mvc->transferFolderField, 'mvc');
			expect(cls::haveInterface('doc_FolderIntf', $typeMvc));
		}
	}
	
	
	/**
	 * Преди запис на документ
	 */
	public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
	{
		if(empty($rec->id)){ 
		    $person = crm_Profiles::fetchField("#personId = {$rec->personId}", 'userId');
		    $alternatePerson = crm_Profiles::fetchField("#personId = '{$rec->alternatePerson}'", 'userId');
			
			if($mvc->getField('sharedUsers', FALSE)){
				$cu = core_Users::getCurrent();
				$rec->sharedUsers = keylist::addKey($rec->sharedUsers, $cu);
				$rec->sharedUsers = keylist::addKey($rec->sharedUsers, $person);
				$rec->sharedUsers = keylist::addKey($rec->sharedUsers, $alternatePerson);
			}
		}
	}
	
	
	/**
	 * Ако в документа няма код, който да рутира документа до папка/тред,
	 * долния код, рутира документа до "Несортирани - [заглавие на класа]"
	 */
	protected static function on_BeforeRoute($mvc, &$res, $rec)
	{
		// Ако е събмитнато поле към което да се трансферира
		if(isset($rec->{$mvc->transferFolderField})) {
			$coverId = $rec->{$mvc->transferFolderField};
			
			// Форсира се папката на обекта, документа ще се създаде там
			$CoverMvc = cls::get($mvc->getFieldTypeParam($mvc->transferFolderField, 'mvc'));
			$rec->folderId = $CoverMvc->forceCoverAndFolder($coverId);
		}
	}
	
	
	/**
	 * Изпълнява се след създаване на нов запис
	 */
	public static function on_AfterCreate($mvc, $rec)
	{
		// Споделяме текущия потребител със нишката на документа, за всеки случай
		$cu = core_Users::getCurrent();
		doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $cu);
	}
}
<?php



/**
 * Плъгин за документи, които може да ги създават партньори
 * 
 * @category  bgerp
 * @package   colab
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class colab_plg_CreateDocument extends core_Plugin
{
	
	
	/**
	 * Какви роли са необходими за качване или сваляне?
	 */
	public static function on_BeforeGetRequiredRoles($mvc, &$roles, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'add' || $action == 'edit')){
			$addContractor = FALSE;
			
			// Ако документа е от тези, които може да се създават от партньори
			if(core_Users::isContractor($userId)){
				$documents = colab_Setup::get('CREATABLE_DOCUMENTS_LIST');
				if(keylist::isIn($mvc->getClassId(), $documents)){
					$addContractor = TRUE;
				}
			}
			
			if(isset($rec)){
				if($action == 'edit'){
					if($rec->createdBy != $userId){
						$addContractor = FALSE;
					}
				} elseif($action == 'add') {
					$sharedFolders = colab_Folders::getSharedFolders($userId);
					if(!$rec->folderId || !in_array($rec->folderId, $sharedFolders)){
						$addContractor = FALSE;
					}
				}
			}
			
			// Добавяне към правата ,че и партньор може да редактира/добавя
			if($addContractor === TRUE){
				$property = ucfirst($action);
				$property = "can{$property}";
				$mvc->{$property} .= ",contractor";
			}
		}
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		// Ако не е контрактор 
		if(!core_Users::isContractor()) return;
		$form = &$data->form;
		
		// Полетата, които не са за контрактор се скриват
		$hideFields = $form->selectFields("#notChangeableByContractor");
		if(is_array($hideFields)){
			foreach ($hideFields as $name => $field){
				$form->setField($name, 'input=hidden');
			}
		}
		
		$mvc->currentTab = 'Нишка';
		plg_ProtoWrapper::changeWrapper($mvc, 'colab_Wrapper');
		
		// Контракторите да не могат да споделят потребители
		if (core_Users::isContractor()) {
		    $data->form->setField('sharedUsers', 'input=none');
		}
	}
	
	
	/**
	 * 
	 * 
	 * @param core_Mvc $mvc
	 * @param stdObject $res
	 * @param stdObject $data
	 */
	function on_AfterPrepareEditToolbar($mvc, &$res, $data)
	{
	    // Контрактора да не може да създава чернова, а директно да активира
	    if (core_Users::isContractor()) {
	        $data->form->toolbar->removeBtn('save');
	    }
	}
}


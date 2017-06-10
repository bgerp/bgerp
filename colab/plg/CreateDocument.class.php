<?php



/**
 * Плъгин за документи, които може да ги създават партньори
 * 
 * @category  bgerp
 * @package   colab
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
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
			if(core_Users::haveRole('partner', $userId)){
				$documents = colab_Setup::get('CREATABLE_DOCUMENTS_LIST');
				if(keylist::isIn($mvc->getClassId(), $documents)){
					$addContractor = TRUE;
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
            }

			// Добавяне към правата ,че и партньор може да редактира/добавя
			if($addContractor === TRUE){
				$property = ucfirst($action);
				$property = "can{$property}";
				
				// Ако не са зададени специфични роли за външни потребители се взима по дефолт партньор
				$externalRoles = isset($mvc->canWriteExternal) ? $mvc->canWriteExternal : 'partner';
				$mvc->{$property} .= ",{$externalRoles}";
			}
		}
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		// Ако не е контрактор 
		if(!core_Users::haveRole('partner')) return;
		
		$form = &$data->form;
		
		// Полетата, които не са за контрактор се скриват
		$hideFields = $form->selectFields("#notChangeableByContractor");
		if(is_array($hideFields)){
			foreach ($hideFields as $name => $field){
				$form->setField($name, 'input=hidden');
			}
		}
		
		$mvc->currentTab = 'Нишка';
		plg_ProtoWrapper::changeWrapper($mvc, 'cms_ExternalWrapper');
		
		// Контракторите да не могат да споделят потребители
		if (core_Users::haveRole('partner')) {
			if($mvc->getField('sharedUsers', FALSE)){
				$data->form->setField('sharedUsers', 'input=none');
			}
		}
	}
	
	
	/**
	 * След вземане на състоянието на треда
	 * 
	 * @param core_Mvc $mvc
	 * @param string|NULL $res
	 * @param integer $id
	 */
	public static function on_AfterGetThreadState($mvc, &$res, $id)
	{
	    $rec = $mvc->fetch($id);
	    
	    if (core_Users::haveRole('partner', $rec->createdBy)) {
	        $res = 'opened';
	    } elseif (core_Users::isPowerUser($rec->createdBy) && $mvc->isVisibleForPartners($rec)) {
	        $res = 'closed';
	    }
	}
}


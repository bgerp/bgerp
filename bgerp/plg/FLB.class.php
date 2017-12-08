<?php



/**
 * Плъгин за обекти, които могат да имат Материално отговорни лица
 *
 * $canActivateUserFld - поле в което е оказано, кои потребители могат да контират документи с обекта
 * $canActivateRoleFld - поле в което е оказано, кои роли могат да контират документи с обекта
 * $canSelectUserFld   - поле в което е оказано, кои потребители могат да избират обекта в документи
 * $canSelectRoleFld   - поле в което е оказано, кои роли могат да избират обекта в документи
 * $canActivate        - кой може да контира документи, в които е избран обекта
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_FLB extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 */
	public static function on_AfterDescription($mvc)
	{
		setIfNot($mvc->canActivate, 'ceo');
		setIfNot($mvc->canActivateRoleFld, 'activateRoles');
		setIfNot($mvc->canSelectUserFld, 'selectUsers');
		setIfNot($mvc->canSelectRoleFld, 'selectRoles');
		
		// Поле, в които се указват ролите, които могат да контират документи с обекта
		if(!$mvc->getField($mvc->canActivateRoleFld, FALSE)){
			$mvc->FLD($mvc->canActivateRoleFld, 'keylist(mvc=core_Roles,select=role,groupBy=type,orderBy=orderByRole)', "caption=Контиране на документи->Екипи,after={$mvc->canActivateUserFld}");
		}
		
		// Поле, в които се указват потребителите, които могат да контират документи с обекта
		if(!$mvc->getField($mvc->canSelectUserFld, FALSE)){
			$mvc->FLD($mvc->canSelectUserFld, "userList", "caption=Използване в документи->Потребители,after={$mvc->canActivateRoleFld}");
		}
		
		// Поле, в които се указват rolite, които могат да избират обекта в документи
		if(!$mvc->getField($mvc->canSelectRoleFld, FALSE)){
			$mvc->FLD($mvc->canSelectRoleFld, 'keylist(mvc=core_Roles,select=role,groupBy=type,orderBy=orderByRole)', "caption=Използване в документи->Екипи,after={$mvc->canSelectUserFld}");
		}
		
		// Трябва да е към корица
		expect($mvc->hasPlugin('doc_FolderPlg'));
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$form->setField($mvc->canActivateUserFld, 'mandatory');
		
		$roles = array();
		$rQuery = core_Roles::getQuery();
		$rQuery->where("#type = 'team'");
		while($rRec = $rQuery->fetch()){
			$roles[$rRec->id] = $rRec->role;
		}
		
		$form->setSuggestions($mvc->canSelectRoleFld, $roles);
		$form->setSuggestions($mvc->canActivateRoleFld, $roles);
		
		// Отговорника на папката трябва да има нужните роли, или да е админ
		$roles = arr::make($mvc->canActivate);
		$roles[] = 'admin';
		$roles = implode('|', $roles);
		$form->setFieldType('inCharge', "user(roles={$roles}, rolesForAll=officer)");
	}
	
	
	/**
	 * Помощна ф-я връщаща дали потребителя може да активира корицата или да я избира
	 * 
	 * @param core_Master $mvc
	 * @param stdClass $rec
	 * @param int $userId
	 * @param activate|select $action
	 * @return boolean
	 */
	public static function canUse($mvc, $rec, $userId, $action = 'activate')
	{
		// Инстанциране на класа при нужда
		if(!is_object($mvc)){
			$mvc = cls::get($mvc);
		}
		
		// Дали ще се проверява за избиране или за активиране
		expect(in_array($action, array('activate', 'select')));
		$action = ucfirst($action);
		$userFld = $mvc->{"can{$action}UserFld"};
		$roleFld = $mvc->{"can{$action}RoleFld"};
		
		$rec = $mvc->fetchRec($rec);
		
		// Ако потребителя е ceo винаги има достъп
		if(core_Users::haveRole('ceo', $userId)) return TRUE;
		
		// Отговорника на папката винаги може да прави всичко с нея
		if($rec->inCharge == $userId) return TRUE;
		
		// Ако потребителя е изрично избран че може да селектира или активира
		if($rec->{$userFld}){
			if(keylist::isIn($userId, $rec->{$userFld})) return TRUE;
		}
		
		// Ако потребителя има роля която има достъп до действието
		if(isset($rec->{$roleFld})){
			if(core_Users::haveRole($rec->{$roleFld}, $userId)) return TRUE;
		}
		
		// При проверка за избиране, ако потребителя не е оказан, се проверява дали може да активира обекта
		// Ако може да активира обекта той винаги може и да го избира
		if($action == 'Select'){
			return self::canUse($mvc, $rec, $userId, 'activate');
		}
		
		// Ако нищо от горното не е изпълнено потребителя няма права
		return FALSE;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		if($res == 'no_one') return;
		
		// Ако се проверява за избор, допълнително се проверява
		// дали потребителя може да контира документи с обекта
		if($action == 'select' && isset($rec)){
			if(self::canUse($mvc, $rec, $userId, 'activate')){
				$res = $mvc->canActivate;
			} else {
				$res = 'no_one';
			}
		}
	}
	
	
	/**
	 * Премахва от резултатите скритите от менютата за избор
	 */
	public static function on_AfterMakeArray4Select($mvc, &$res, $fields = NULL, &$where = "", $index = 'id'  )
	{
		$cu = core_Users::getCurrent();
		if(haveRole('ceo', $cu)) return;
		
		// Ако потребителя не може да избира обекта от списъка се маха
		if(is_array($res)){
			foreach ($res as $id => $title){
				if(!self::canUse($mvc, $id, $cu, 'select')){
					unset($res[$id]);
				}
			}
		}
	}
	
	
	/**
	 * Подготовка на филтър формата
	 */
	public static function on_AfterPrepareListFilter($mvc, &$data)
	{
		$data->listFilter->view = 'horizontal';
		$data->listFilter->FLD('users', "users(rolesForAll=ceo|admin,rolesForTeams=officer|admin)", 'caption=Потребител,silent,autoFilter,remember');
		$data->listFilter->showFields = 'users';
		$data->listFilter->input('users', 'silent');
		
		$default = $data->listFilter->getField('users')->type->fitInDomain('all_users');
		$data->listFilter->setDefault('users', $default);
		
		// Скриване на записите до които няма достъп
		if($selectedUsers = $data->listFilter->rec->users) {
			self::addUserFilterToQuery($mvc, $data->query, $selectedUsers);
		}
	}
	
	
	/**
	 * Добавя филтър към заявката
	 * 
	 * @param core_Master $mvc
	 * @param core_Query $query
	 * @param int|NULL $users
	 * @param boolean $onlyActivate
	 * @return void
	 */
	public static function addUserFilterToQuery($mvc, core_Query &$query, $users = NULL, $onlyActivate = FALSE)
	{
		$users = ($users) ? $users : core_Users::getCurrent();
		$users = (keylist::isKeylist($users)) ? keylist::toArray($users) : arr::make($users);
		
		$count = 0;
		$cond = '';
		
		$mvc = cls::get($mvc);
		expect($mvc->hasPlugin('bgerp_plg_FLB'));
		
		foreach ($users as $userId){
			$beginning = ($count == 0) ? ' ' : " OR ";
			$userRoles = core_Users::fetchField($userId, 'roles');
			$cond .= "{$beginning}LOCATE('|{$userId}|', #{$mvc->canActivateUserFld})";
			
			$roles = keylist::toArray($userRoles);
			
			if($onlyActivate === FALSE){
				$cond .= " OR LOCATE('|{$userId}|', #{$mvc->canSelectUserFld})";
			}
			
			foreach ($roles as $r){
				$cond .= " OR LOCATE('|{$r}|', #{$mvc->canActivateRoleFld})";
				
				if($onlyActivate === FALSE){
					$cond .= " OR LOCATE('|{$r}|', #{$mvc->canSelectRoleFld})";
				}
			}
			
			$cond .= " OR #inCharge = {$userId} ";
			$count++;
		}
		
		$query->where($cond);
	}
	
	
	/**
	 * Преди форсиране на обекта
	 * 
	 * @param core_Mvc $mvc
	 * @param core_Query $query
	 */
	public static function on_BeforeSelectByForce($mvc, &$query)
	{
		// Само ако потребителя не е ceo, се филтрира по полетата
		if(!haveRole('ceo')){
			self::addUserFilterToQuery($mvc, $query, NULL, TRUE);
		}
	}
}
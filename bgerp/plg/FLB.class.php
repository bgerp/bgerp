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
			$mvc->FLD($mvc->canActivateRoleFld, 'keylist(mvc=core_Roles,select=role,groupBy=type)', "caption=Контиране на документи->Роли,after={$mvc->canActivateUserFld}");
		}
		
		// Поле, в които се указват потребителите, които могат да контират документи с обекта
		if(!$mvc->getField($mvc->canSelectUserFld, FALSE)){
			$mvc->FLD($mvc->canSelectUserFld, "userList", "caption=Използване в документи->Потребители,after={$mvc->canActivateRoleFld}");
		}
		
		// Поле, в които се указват rolite, които могат да избират обекта в документи
		if(!$mvc->getField($mvc->canSelectRoleFld, FALSE)){
			$mvc->FLD($mvc->canSelectRoleFld, 'keylist(mvc=core_Roles,select=role,groupBy=type)', "caption=Използване в документи->Роли,after={$mvc->canSelectUserFld}");
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
		
		$roles = arr::make($mvc->canActivate);
		
		// Отсяване само на потребителите с избраните роли
		$inCharge = array();
		foreach ($roles as $role){
			$roleId = core_Roles::fetchByName($role);
			$usersWithRole = core_Users::getByRole($roleId);
			foreach ($usersWithRole as $userId){
				$inCharge[$userId] = core_Users::fetchField($userId, 'nick');
			}
		}
		
		// Задаване на потребители по подразбиране за отговорници
		$form->setOptions('inCharge', $inCharge);
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
		//if(core_Users::haveRole('ceo')) return TRUE;
		
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
		//if(haveRole('ceo', $cu)) return;
		
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
		$allowEmpty = (haveRole('ceo')) ? 'allowEmpty' : '';
		$data->listFilter->view = 'horizontal';
		$data->listFilter->FLD('user', "user(rolesForAll=ceo|admin,rolesForAll=ceo|manager|admin,{$allowEmpty})", 'caption=Потребител,silent,autoFilter,remember');
		$data->listFilter->showFields = 'user';
		$data->listFilter->input('user', 'silent');
		
		if((!haveRole('ceo'))){
			$data->listFilter->setDefault('user', core_Users::getCurrent());
		}
		
		// Скриване на записите до които няма достъп
		if($selectedUser = $data->listFilter->rec->user){
			self::addUserFilterToQuery($mvc, $data->query, $selectedUser);
		}
	}
	
	
	/**
	 * Добавя филтър към заявката
	 * 
	 * @param core_Master $mvc
	 * @param core_Query $query
	 * @param int|NULL $userId
	 * @return void
	 */
	private static function addUserFilterToQuery($mvc, core_Query &$query, $userId = NULL)
	{
		$userId = ($userId) ? $userId : core_Users::getCurrent();
		
		$query->likeKeylist($mvc->canActivateUserFld, $userId);
		$query->orLikeKeylist($mvc->canActivateRoleFld, $userId);
		$query->orLikeKeylist($mvc->canSelectUserFld, $userId);
		$query->orLikeKeylist($mvc->canSelectRoleFld, $userId);
		$query->orWhere("#inCharge = {$userId}");
	}
	
	
	/**
	 * Преди форсиране на обекта
	 */
	public static function on_BeforeSelectByForce($mvc, &$res)
	{
		$query = $mvc->getQuery();
		$query->where("#state != 'rejected' || #state != 'closed'");

		// Само ако потребителя не е ceo, се филтрира по полетата
		if(!haveRole('ceo')){
			self::addUserFilterToQuery($mvc, $query);
		}
		
		// Ако има точно един обект, който потребителя може да избере се избира автоматично
		if($query->count() == 1) {
			$rec = $query->fetch();
			if($id = $mvc->selectCurrent($rec)) {
				$res = $id;
			}
		}
	}
}
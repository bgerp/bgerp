<?php
/**
 * Помощен клас с функции за рутиране
 *
 * @category  bgerp
 * @package   marketing
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 */
class marketing_Router
{
	
	
	/**
	 * Намира кой ще е отговорника на папката, в следния ред
	 * 
	 * 1. Ако има папка "Несортирани - <име на град>", взимаме нейния отговорник
	 * 2. Ако има папка "Несортирани - <държава>", взимаме нейния отговорник
	 * 3. Ако има корпоративен имейл и има папка за този имейл, взимаме нейния отговорник
	 * 4. Първия регистриран потребител с роля 'ceo'
	 * 
	 * @param string $city   - град
	 * @param int $countryId - ид на държава
	 * @return int $inCharge - ид на потребител
	 */
	public static function getInChargeUser($city, $countryId)
	{
		$conf = core_Packs::getConfig('email');
		
		// Ако има град
		if($city){
			
			// Проверка имали несортирана папка с името на града
			$city = preg_replace('/\s+/', ' ', $city);
			$city = str::mbUcfirst($city);
			$unsortedName = sprintf($conf->EMAIL_UNSORTABLE_COUNTRY, $city);
			$inCharge = doc_UnsortedFolders::fetchField(array("#name = '[#1#]'", $unsortedName), 'inCharge');
		
			// Ако има такава папка, взимаме и отговорника
			if($inCharge) return $inCharge;
		}
		
		if($countryId){
			
			// Проверяваме имали несортирана папка с името на държавата
			$country = drdata_Countries::fetchField($countryId, 'commonNameBg');
			$unsortedName = sprintf($conf->EMAIL_UNSORTABLE_COUNTRY, $country);
			$inCharge = doc_UnsortedFolders::fetchField(array("#name = '[#1#]'", $unsortedName), 'inCharge');
			
			// Ако има, взимаме нейния отговорник
			if($inCharge) return $inCharge;
		}
		
		// Проверяваме имали корпоративна сметка
		$corpAcc = email_Accounts::getCorporateAcc();
		if($corpAcc){
			
			// Намираме отговорника на папката с корица кутията на корпоративния акаунт
			$corpAccId = email_Inboxes::fetchField("#email = '{$corpAcc->email}'");
			$inboxClassId = email_Inboxes::getClassId();
			$inCharge = doc_Folders::fetchField("#coverClass = {$inboxClassId} AND #coverId = {$corpAccId}", 'inCharge');
			
			// Ако има, взимаме нейния отговорник
			if($inCharge) return $inCharge;
		}
		
		// Ако няма нищо, намираме всички с роля 'ceo'
		$ceoRoleId = core_Roles::fetchByName('ceo');
		$ceos = core_users::getByRole($ceoRoleId);
		ksort($ceos);
		
		// Връщаме този с най-малко ид от тях
		return reset($ceos);
	}

	
	/**
	 * Рутира в папка на фирма с подадения имейл
	 * 
	 * @param string $email - имейл
	 * @param int $inCharge - отговорника на папката
	 * @return int - ид на папката
	 */
	public static function routeByCompanyEmail($email, $inCharge)
	{
		$companyRec = crm_Companies::fetch(array("#email LIKE '%[#1#]%'", $email));
		
		if($companyRec){
			$emails = type_Emails::toArray($companyRec->email);
			if(in_array($email, $emails)){
				$rec = (object)array('id' => $companyRec->id, 'inCharge' => $inCharge);
					
				return crm_Companies::forceCoverAndFolder($rec);
			}
		}
		
		return FALSE;
	}
	
	
	/**
	 * Рутира в папка на лице с подадения имейл
	 * 
	 * @param string $email - имейл
	 * @param int $inCharge - отговорника на папката
	 * @return int - ид на папката
	 */
	public static function routeByPersonEmail($email, $inCharge)
	{
		$personRec = crm_Persons::fetch(array("#email LIKE '%[#1#]%'", $email));
		
		if($personRec){
			$emails = type_Emails::toArray($personRec->email);
			
			if(in_array($email, $emails)){
				$rec = (object)array('id' => $personRec->id, 'inCharge' => $inCharge);
					
				return crm_Persons::forceCoverAndFolder($rec);
			}
		}
		
		return FALSE;
	}
	
	
	/**
	 * Рутира в папка, намерена от имейл-рутера, само ако е от посочените корици
	 * 
	 * @param string $email - Имейл
	 * @param enum(contragent,company,person) $allowedCover - разрешена корица
	 * @return int - ид на папка
	 */
	public static function routeByEmail($email, $allowedCover)
	{
		$folderId = email_Router::getEmailFolder($email);
		if(empty($folderId)) return;
		
		$coverClassId = doc_Folders::fetchCoverClassId($folderId);
		$personsClassId = crm_Persons::getClassId();
		$companyClassId = crm_Companies::getClassId();
		
		switch ($allowedCover){
			case 'contragent':
				$res = ($coverClassId == $personsClassId || $coverClassId == $companyClassId);
				break;
			case 'person':
				$res = ($coverClassId == $personsClassId);
				break;
			case 'company':
				$res = ($coverClassId == $companyClassId);
				break;
		}
		
		return ($res) ? $folderId : NULL;
	}
	
	
	/**
	 * Рутира в папка на лице с подобно име от същата държава
	 * 
	 * @param string $name - име на лице
	 * @param int $countryId - ид на държава
	 * @return int - ид на папка
	 */
	public static function routeByPerson($name, $countryId, $inCharge)
	{
		$nameArr = explode(' ', $name);
		
		if(count($nameArr) == 1) return;
		
		$name = preg_replace('/\s+/', ' ', $name);
		
		$conf = core_Packs::getConfig('crm');
		$query = crm_Persons::getQuery();
		$query->where(array("#name = '[#1#]'", $name));
		$query->where("#country = {$countryId} AND #state != 'closed' AND #state != 'rejected'");
		
		$ownCountryId = drdata_Countries::fetchField("#commonName = '{$conf->BGERP_OWN_COMPANY_COUNTRY}'");
		if($ownCountryId == $countryId){
			$query->orWhere("#country IS NULL");
		}
		
		if($person = $query->fetch()){
			try{
				expect($person, $person);
			} catch(core_exception_Expect $e){
				reportException($e);
			}
			
			return crm_Persons::forceCoverAndFolder((object)array('id' => $person->id, 'inCharge' => $inCharge));
		}
	}
	
	
	/**
	 * Форсиране на папка на лице с подадените адресни данни
	 * 
	 * @param string $name    - име
	 * @param string $email   - имейл
	 * @param int $country    - държава
	 * @param string $tel     - телефон
	 * @param string $pCode   - п. код
	 * @param string $place   - населено място
	 * @param string $address - адрес
	 * @param int $inCharge   - отговорник
	 * @return int            - ид на папка
	 */
	public static function forcePersonFolder($name, $email, $country, $tel, $pCode, $place, $address, $inCharge)
	{
		$rec = new stdClass();
		foreach (array('name', 'email', 'country', 'tel', 'pCode', 'place', 'address', 'inCharge') as $param){
			$rec->{$param} = ${$param};
		}
		
		try{
			expect($rec->name, $rec);
		} catch(core_exception_Expect $e){
			reportException($e);
		}
		
		$folderId = crm_Persons::forceCoverAndFolder($rec);
		crm_Persons::forceGroup($rec->id, 'customers');
		
		return $folderId;
	}
	
	
	/**
	 * Форсиране на папка на фирма с подадените адресни данни
	 * 
	 * @param string $name    - име
	 * @param string $email   - имейл
	 * @param int $country    - държава
	 * @param string $tel     - телефон
	 * @param string $pCode   - п. код
	 * @param string $place   - населено място
	 * @param string $address - адрес
	 * @param int $inCharge   - отговорник
	 * @return int            - ид на папка
	 */
	public static function forceCompanyFolder($name, $email, $country, $tel, $pCode, $place, $address, $inCharge)
	{
		$rec = new stdClass();
		foreach (array('name', 'email', 'country', 'tel', 'pCode', 'place', 'address', 'inCharge') as $param){
			$rec->$param = ${$param};
		}
		
		try{
			expect($rec->name, $rec);
		} catch(core_exception_Expect $e){
			reportException($e);
		}
		
		$folderId = crm_Companies::forceCoverAndFolder($rec);
		crm_Companies::forceGroup($rec->id, 'customers');
		
		return $folderId;
	}
	
	
	/**
	 * Рутира в папка на лице с подобно име от същата държава
	 * 
	 * @param string $name - име на лице
	 * @param int $countryId - ид на държава
	 * @return int - ид на папка
	 */
	public static function routeByCompanyName($name, $countryId, $inCharge)
	{
		$companies = self::getCompaniesByCountry($countryId);
		$normalizedName = self::normalizeCompanyName($name);
		$flipped = array_flip($companies);
		
		if(array_key_exists($normalizedName, $flipped)){
			if($companyId = $flipped[$normalizedName]){
				return crm_Companies::forceCoverAndFolder((object)array('id' => $companyId, 'inCharge' => $inCharge));
			}
		}
		
		return NULL;
	}
	
	
	/**
	 * Рутиране по БРИД на запиътване
	 * 
	 * @param string $brid
	 * @param int|NULL $folderId
	 */
	public static function routeByBrid($brid)
	{
		$contragentClasses = core_Classes::getOptionsByInterface('crm_ContragentAccRegIntf');
		
		// Опит за намиране на последното запитване със същия брид в папка на фирма/лице
		$mQuery = marketing_Inquiries2::getQuery();
		$mQuery->EXT('coverClass', 'doc_Folders', 'externalName=coverClass,externalKey=folderId');
		$mQuery->EXT('fState', 'doc_Folders', 'externalName=state,externalKey=folderId');
		$mQuery->where("#brid IS NOT NULL AND #fState != 'rejected' AND #fState != 'closed' AND #state != 'rejected'");
		$mQuery->where(array("#brid = '[#1#]'", $brid));
		$mQuery->in("coverClass", array_keys($contragentClasses));
		$mQuery->show('folderId');
		$mQuery->orderBy('createdOn', 'DESC');
		
		return $mQuery->fetch()->folderId;
	}
	
	
	/**
	 * Нормализира името на фирмата
	 * 
	 * @param string $name  - името на фирмата
	 * @return string $name - нормализираното име на фирмата
	 */
	public static function normalizeCompanyName($name)
	{
		$name = str::utf2ascii($name);
		$name = strtolower($name);
		$name = preg_replace('/[^\w]/', ' ', $name);
		$name = trim($name);
		
		$companyTypes = getFileContent('drdata/data/companyTypes.txt');
		$companyTypesArr = explode("\n", $companyTypes);
	
		if(is_array($companyTypesArr)){
			foreach ($companyTypesArr as $type){
				$type = trim($type, '|');
				$name = str_replace($type, '', $name);
			}
		}
		
		return $name;
	}
		
	
	/**
	 * Връща всички нормализирани всички фирми от същата държава
	 * 
	 * @param int|NULL $countryId - ид на държава или NULL за всички
	 * @return array $normalized  - нормализирани имена на фирмите
	 */
	public static function getCompaniesByCountry($countryId = NULL)
	{
		$normalized = array();
		$query = crm_Companies::getQuery();
		if(isset($countryId)){
			$query->where("#country = {$countryId}");
		}
		
		while($cRec = $query->fetch()){
			$normalized[$cRec->id] = self::normalizeCompanyName($cRec->name);
		}
		
		return $normalized;
	}
}
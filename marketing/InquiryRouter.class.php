<?php



/**
 * Помощен клас за рутиране на запитвания
 *
 * @category  bgerp
 * @package   marketing
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 */
class marketing_InquiryRouter extends core_Manager
{
	
	
	/**
	 * Рутиране на запитване
	 * 
	 * @param stdClass $rec - запис на запитване
	 * @return int - ид на папка
	 */
	public static function route($company, $personNames, $email, $tel, $countryId, $pCode, $place, $address, $brid)
	{
		// Ако е от колаборатор към първата споделена папка на колаборатор
		if(core_Packs::isInstalled('colab')){
			$firstFolderId = colab_FolderToPartners::getLastSharedCompanyFolder();
			if(!empty($firstFolderId)) return $firstFolderId;
		}
		
		// Ако има компания
		if(empty($company)){
			
			// Рутиране на запитване от лице
			$folderId = static::routeInquiryFromPerson($company, $personNames, $email, $tel, $countryId, $pCode, $place, $address, $brid);
		} else {
			
			// Рутиране на запитване от фирма
			$folderId = static::routeInquiryFromCompany($company, $personNames, $email, $tel, $countryId, $pCode, $place, $address, $brid);
		}
		
		// Трябва да е намерена папка
		expect($folderId);
		
		return $folderId;
	}
	
	
	/**
	 * Рутира запитване от лице
	 * 
	 * 1.Която е от тип фирма и във визитката има същия имейл, като посочения в запитването;
	 * 2.Която е от тип "Лице" и във визитката има същия имейл като посочения в запитването.
	 * 3.Търсим папка, по зададения имейл, чрез метода на имейл-рутера, но само от тип "Фирма" или "Лице".
	 *   Ако намерената папка не е от посочения тип - това правило пропада.
	 * 4.Която е от тип "Лице" и има същото име на човек и е от същата държава. Това правило сработва, само ако имаме посочени поне две имена на лицето.
	 * 5.Ако нито едно от горните не сработва, създаваме нова папка, с корица "Лице" с данните от запитването.
	 * 
	 * @param string $company
	 * @param string $personNames
	 * @param string $email
	 * @param string $tel
	 * @param int $countryId
	 * @param string $pCode
	 * @param string $place
	 * @param string $address
	 * @param string $brid
	 * @return int $folderId
	 */
	private static function routeInquiryFromPerson($company, $personNames, $email, $tel, $countryId, $pCode, $place, $address, $brid)
	{
		// Дефолтния отговорник
		$inCharge = marketing_Router::getInChargeUser($place, $countryId);
		
		// Ако има папка на фирма с този имейл
		$folderId = marketing_Router::routeByCompanyEmail($email, $inCharge);
		if($folderId) return $folderId;
		
		// Ако има папка на лице с този имейл
		$folderId = marketing_Router::routeByPersonEmail($email, $inCharge);
		if($folderId) return $folderId;
		
		// Ако има папка на контрагент с този имейл
		$folderId = marketing_Router::routeByEmail($email, 'contragent');
		if($folderId) return $folderId;
		
		// Ако има лице във визитника от същата държава
		$folderId = marketing_Router::routeByPerson($personNames, $countryId, $inCharge);
		if($folderId) return $folderId;
		
		// Опит за рутиране по БРИД
		$folderId = marketing_Router::routeByBrid($brid);
		if($folderId) return $folderId;
		
		// Форсиране на папка и запис във визитника на лице с посочените данни
		return marketing_Router::forcePersonFolder($personNames, $email, $countryId, $tel, $pCode, $place, $address, $inCharge);
	}
	
	
	/**
	 * Рутиране на запитване от фирма
	 * 
	 * 1 Която има визитка тип "Фирма" и в нея има същия имейл, като от запитването
	 * 2 Търсим папка (но само от тип "Фирма"), по зададения имейл, чрез метода на имейл-рутера. Ако намерената папка не е "Фирма" - това правило пропада.
	 * 3 Която е от тип "Фирма" и има същото (приблизително) име и държава, като от запитването;
	 * 4 Ако нито едно от горните не сработва, създаваме нова папка, с корица "Фирма" с данните от запитването.
	 * 
	 * @param string $company
	 * @param string $personNames
	 * @param string $email
	 * @param string $tel
	 * @param int $countryId
	 * @param string $pCode
	 * @param string $place
	 * @param string $address
	 * @param string $brid
	 * @return int $folderId
	 */
	private static function routeInquiryFromCompany($company, $personNames, $email, $tel, $countryId, $pCode, $place, $address, $brid)
	{
		// Дефолтния отговорник
		$inCharge = marketing_Router::getInChargeUser($place, $countryId);
		
		// Намираме папка на компания с този имейл
		$folderId = marketing_Router::routeByCompanyEmail($email, $inCharge);
		if($folderId) return $folderId;
		
		// Рутиране според имейла, взимаме папката ако корицата и е фирма
		$folderId = marketing_Router::routeByEmail($email, 'company');
		if($folderId) return $folderId;
		
		// Рутираме в папка на фирма със същото име от същата държава
		$folderId = marketing_Router::routeByCompanyName($company, $countryId, $inCharge);
		if($folderId) return $folderId;
		
		// Опит за рутиране по БРИД
		$folderId = marketing_Router::routeByBrid($brid);
		if($folderId) return $folderId;
		
		// Форсиране на папка и визитка на фирма с въведените данни
		return marketing_Router::forceCompanyFolder($company, $email, $countryId, $tel, $pCode, $place, $address, $inCharge);
	}
}
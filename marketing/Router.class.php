<?php
/**
 * Помощен клас с функции за рутиране
 *
 * @category  bgerp
 * @package   marketing
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 *
 */
class marketing_Router
{
    /**
     * Работен кеш
     */
    public static $companyTypes = array();
    
    
    /**
     * Намира кой ще е отговорника на папката, в следния ред
     *
     * 1. Ако има папка "Несортирани - <име на град>", взимаме нейния отговорник
     * 2. Ако има папка "Несортирани - <държава>", взимаме нейния отговорник
     * 3. Ако има корпоративен имейл и има папка за този имейл, взимаме нейния отговорник
     * 4. Първия регистриран потребител с роля 'ceo'
     *
     * @param string $city      - град
     * @param int    $countryId - ид на държава
     * @param int $domainId     - домейн
     * 
     * @return int $inCharge - ид на потребител
     */
    public static function getInChargeUser($city, $countryId, $domainId)
    {
        if(isset($domainId)){
            $settings = cms_Domains::getSettings($domainId);
            if(isset($settings->dealerId)){
                if(haveRole('sales', $settings->dealerId)){
                    
                    return $settings->dealerId;
                }
            }
        }
        $conf = core_Packs::getConfig('email');
        
        // Ако има град
        if ($city) {
            
            // Проверка имали несортирана папка с името на града
            $city = preg_replace('/\s+/', ' ', $city);
            $city = str::mbUcfirst($city);
            $unsortedName = sprintf($conf->EMAIL_UNSORTABLE_COUNTRY, $city);
            $inCharge = doc_UnsortedFolders::fetchField(array("#name = '[#1#]'", $unsortedName), 'inCharge');
            
            // Ако има такава папка, взимаме и отговорника
            if ($inCharge) {
                
                return $inCharge;
            }
        }
        
        if ($countryId) {
            
            // Проверяваме имали несортирана папка с името на държавата
            $country = drdata_Countries::fetchField($countryId, 'commonNameBg');
            $unsortedName = sprintf($conf->EMAIL_UNSORTABLE_COUNTRY, $country);
            $inCharge = doc_UnsortedFolders::fetchField(array("#name = '[#1#]'", $unsortedName), 'inCharge');
            
            // Ако има, взимаме нейния отговорник
            if ($inCharge) {
                
                return $inCharge;
            }
        }
        
        // Проверяваме имали корпоративна сметка
        $corpAcc = email_Accounts::getCorporateAcc();
        if ($corpAcc) {
            
            // Намираме отговорника на папката с корица кутията на корпоративния акаунт
            $corpAccId = email_Inboxes::fetchField("#email = '{$corpAcc->email}'");
            $inboxClassId = email_Inboxes::getClassId();
            $inCharge = doc_Folders::fetchField("#coverClass = {$inboxClassId} AND #coverId = {$corpAccId}", 'inCharge');
            
            // Ако има, взимаме нейния отговорник
            if ($inCharge) {
                
                return $inCharge;
            }
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
     * @param string $email    - имейл
     * @param int    $inCharge - отговорника на папката
     *
     * @return int - ид на папката
     */
    public static function routeByCompanyEmail($email, $inCharge)
    {
        $companyRec = crm_Companies::fetch(array("#email LIKE '%[#1#]%' AND #state = 'active'", $email));
        
        if ($companyRec) {
            $emails = type_Emails::toArray($companyRec->email);
            if (in_array($email, $emails)) {
                $rec = (object) array('id' => $companyRec->id, 'inCharge' => $inCharge);
                
                return crm_Companies::forceCoverAndFolder($rec);
            }
        }
        
        return false;
    }
    
    
    /**
     * Рутира в папка на лице с подадения имейл
     *
     * @param string $email    - имейл
     * @param int    $inCharge - отговорника на папката
     *
     * @return int - ид на папката
     */
    public static function routeByPersonEmail($email, $inCharge)
    {
        $personRec = crm_Persons::fetch(array("#email LIKE '%[#1#]%' AND #state = 'active'", $email));
        
        if ($personRec) {
            $emails = type_Emails::toArray($personRec->email);
            
            if (in_array($email, $emails)) {
                $rec = (object) array('id' => $personRec->id, 'inCharge' => $inCharge);
                
                return crm_Persons::forceCoverAndFolder($rec);
            }
        }
        
        return false;
    }
    
    
    /**
     * Рутира в папка, намерена от имейл-рутера, само ако е от посочените корици
     *
     * @param string                          $email        - Имейл
     * @param string $allowedCover - разрешена корица
     *
     * @return int - ид на папка
     */
    public static function routeByEmail($email, $allowedCover)
    {
        $folderId = email_Router::getEmailFolder($email);
        if (empty($folderId)) {
            
            return;
        }
        
        $coverClassId = doc_Folders::fetchCoverClassId($folderId);
        $personsClassId = crm_Persons::getClassId();
        $companyClassId = crm_Companies::getClassId();
        
        switch ($allowedCover) {
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
        
        return ($res) ? $folderId : null;
    }
    
    
    /**
     * Форсиране на папка на лице с подадените адресни данни
     *
     * @param string $name     - име
     * @param string $email    - имейл
     * @param int    $country  - държава
     * @param string $tel      - телефон
     * @param string $pCode    - п. код
     * @param string $place    - населено място
     * @param string $address  - адрес
     * @param string $vatId    - ДДС номер
     * @param string $uicId    - Нац. номер
     * @param int    $inCharge - отговорник
     *
     * @return int - ид на папка
     */
    public static function forcePersonFolder($name, $email, $country, $tel, $pCode, $place, $address, $vatId, $uicId, $inCharge)
    {
        $rec = new stdClass();
        foreach (array('name', 'email', 'country', 'tel', 'pCode', 'place', 'address', 'inCharge', 'vatId', 'egn') as $param) {
            $value = ${$param};
            if($param == 'egn'){
                $value = $uicId;
            }
            
            $rec->{$param} = $value;
        }
        
        try {
            expect($rec->name, $rec);
        } catch (core_exception_Expect $e) {
            reportException($e);
        }
        
        $folderId = crm_Persons::forceCoverAndFolder($rec);
        crm_Persons::forceGroup($rec->id, 'customers');
        
        return $folderId;
    }
    
    
    /**
     * Форсиране на папка на фирма с подадените адресни данни
     *
     * @param string $name     - име
     * @param string $email    - имейл
     * @param int    $country  - държава
     * @param string $tel      - телефон
     * @param string $pCode    - п. код
     * @param string $place    - населено място
     * @param string $address  - адрес
     * @param string $vatId    - ДДС номер
     * @param string $uicId    - Нац. номер
     * 
     * 
     * @param int    $inCharge - отговорник
     *
     * @return int - ид на папка
     */
    public static function forceCompanyFolder($name, $email, $country, $tel, $pCode, $place, $address, $vatId, $uicId, $inCharge)
    {
        $rec = new stdClass();
        foreach (array('name', 'email', 'country', 'tel', 'pCode', 'place', 'address', 'inCharge', 'vatId', 'uicId') as $param) {
            $rec->$param = ${$param};
        }
        
        try {
            expect($rec->name, $rec);
        } catch (core_exception_Expect $e) {
            reportException($e);
        }
        
        $folderId = crm_Companies::forceCoverAndFolder($rec);
        crm_Companies::forceGroup($rec->id, 'customers');
        
        return $folderId;
    }
    
    
    /**
     * Рутира в папка на лице с подобно име от същата държава
     *
     * @param string $name      - име на лице
     * @param int    $countryId - ид на държава
     *
     * @return int - ид на папка
     */
    public static function routeByCompanyName($name, $countryId, $inCharge)
    {
        $companies = self::getCompaniesByCountry($countryId);
        $normalizedName = self::normalizeCompanyName($name);
        
        if ($companyId = array_search($normalizedName, $companies)) {
            
            return crm_Companies::forceCoverAndFolder((object) array('id' => $companyId, 'inCharge' => $inCharge));
        }
    }
    
    
    /**
     * Рутиране по БРИД на запиътване
     *
     * @param string   $brid
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
        $mQuery->in('coverClass', array_keys($contragentClasses));
        $mQuery->show('folderId');
        $mQuery->orderBy('createdOn', 'DESC');
        
        return $mQuery->fetch()->folderId;
    }
    
    
    /**
     * Нормализира името на фирмата
     *
     * @param string $name - името на фирмата
     *
     * @return string $name - нормализираното име на фирмата
     */
    public static function normalizeCompanyName($name)
    {
        $name = plg_Search::normalizeText($name);
        $nameL = "#{$name}#";
        
        // Кеширане на думите, които трябва да се премахнат
        if (!countR(self::$companyTypes)) {
            $companyTypes = getFileContent('drdata/data/companyTypes.txt');
            self::$companyTypes = explode("\n", $companyTypes);
            
            // Сортиране от дългите към късите символи
            usort(self::$companyTypes, function ($a, $b) {
                
                return strlen($a) < strlen($b);
            });
        }
        
        // За всяка дума ако е в началото или края на името се маха
        foreach (self::$companyTypes as $word) {
            $word = trim($word, '|');
            $nameL = str_replace(array("#{$word} ", " {$word}#"), array('', ''), $nameL);
        }
        
        $name = trim(str_replace('#', '', $nameL));
        
        return $name;
    }
    
    
    /**
     * Връща всички нормализирани всички фирми от същата държава
     *
     * @param int|NULL $countryId - ид на държава или NULL за всички
     *
     * @return array $normalized  - нормализирани имена на фирмите
     */
    public static function getCompaniesByCountry($countryId = null)
    {
        // Проверяваме имали кеш
        $key = "normalizedNames|{$countryId}|";
        $normalized = core_Cache::get('crm_Companies', $key);
        
        if (!is_array($normalized)) {
            $normalized = array();
            $query = crm_Companies::getQuery();
            $query->EXT('last', 'doc_Folders', 'externalKey=folderId');
            $query->orderBy('#last', 'DESC');
            $query->show('folderId,name,last');
            if (isset($countryId)) {
                $query->where("#country = {$countryId} AND #state = 'active'");
            }
            
            // Подредба по последно използване
            while ($cRec = $query->fetch()) {
                $normalized[$cRec->id] = self::normalizeCompanyName($cRec->name);
            }
            
            core_Cache::set('crm_Companies', $key, $normalized, 10080, array('crm_Companies'));
        }
        
        return $normalized;
    }
    
    
    /**
     * Рутиран по уникален номер
     * 
     * @param string $vatId
     * @param string $field
     * @param mixed $class
     * @param int $inCharge
     * 
     * @return int|null
     */
    public static function routeByUniqueId($vatId, $field, $class, $inCharge)
    {
        $Class = cls::get($class);
        expect(cls::haveInterface('crm_ContragentAccRegIntf', $Class));
        if($id = $Class->fetchField(array("#{$field} = '[#1#]' AND #state != 'rejected'", $vatId))){
            return $Class->forceCoverAndFolder((object) array('id' => $id, 'inCharge' => $inCharge));
        }
        
        return null;
    }
    
    
    /**
     * Рутиран по личен телефон
     *
     * @param string $tel
     * @param boolean $onlyMobile
     * 
     * @return int|null
     */
    public static function routeByPersonTel($tel, $onlyMobile = false)
    {
        // Намиране на всички визитки с подобен телефон, подредени по последна промяна
        $normalized1 = drdata_PhoneType::getNumberStr($tel);
        $pQuery = crm_Persons::getQuery();
        $pQuery->where("#tel IS NOT NULL AND #state = 'active'");
        $pQuery->where(array("#tel LIKE '%[#1#]%' OR #tel LIKE '%[#2#]%'", $tel, $normalized1));
        $pQuery->show('tel,name');
        $pQuery->orderBy('modifiedOn', 'DESC');
        
        while($pRec = $pQuery->fetch()){
            
            // Парсират се телефоните им
            $telArr = drdata_PhoneType::toArray($pRec->tel);
            foreach ($telArr as $telData){
                
                // Връщане на папката на лицето с първия мачнат телефон
                $normalized2 = drdata_PhoneType::getNumberStr($telData->original);
                if($normalized1 == $normalized2){
                    if($onlyMobile === false || ($onlyMobile === true && $telData->mobile === true)){
                        
                        return crm_Persons::forceCoverAndFolder($pRec->id);
                    }
                }
            }
        }
        
        return null;
    }
}

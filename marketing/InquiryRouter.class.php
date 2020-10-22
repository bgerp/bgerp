<?php


/**
 * Помощен клас за рутиране на запитвания
 *
 * @category  bgerp
 * @package   marketing
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 *
 */
class marketing_InquiryRouter extends core_Manager
{
    /**
     * Рутиране на запитване
     *
     * @param stdClass $rec - запис на запитване
     *
     * @return int - ид на папка
     */
    public static function route($company, $personNames, $email, $tel, $countryId, $pCode, $place, $address, $brid, $vatId = null, $uicId = null, &$explained = null, $domainId = null)
    {
        // Ако е от колаборатор към първата споделена папка на колаборатор
        if (core_Packs::isInstalled('colab') && core_Users::isContractor()) {
            if ($companyFolderId = core_Mode::get('lastActiveContragentFolder')) {
                
                return $companyFolderId;
            }
        }
        
        // Ако има компания
        if (empty($company)) {
            
            // Рутиране на запитване от лице
            $folderId = static::routeInquiryFromPerson($company, $personNames, $email, $tel, $countryId, $pCode, $place, $address, $brid, $vatId, $uicId, $explained, $domainId);
        } else {
            
            // Рутиране на запитване от фирма
            $folderId = static::routeInquiryFromCompany($company, $personNames, $email, $tel, $countryId, $pCode, $place, $address, $brid, $vatId, $uicId, $explained, $domainId);
        }
        
        // Трябва да е намерена папка
        expect($folderId);
        
        return $folderId;
    }
    
    
    /**
     * Рутиране в папка на лице
     *
     * 1. Рутиране по личен имейл на лице от визитника
     * 2. Рутиране по  ДДС № или ЕГН, на лице от визитника
     * 3. Рутиране по личен телефон на лице от визитника
     * 4. Рутиране по БРИД
     * 5. Ако нито едно от горните не сработва, създаваме нова папка и визитка на лице.
     * 
     * @param string $company
     * @param string $personNames
     * @param string $email
     * @param string $tel
     * @param int    $countryId
     * @param string $pCode
     * @param string $place
     * @param string $address
     * @param string $brid
     * @param string $vatId
     * @param string $uicId
     * @param string $explained
     * @param string $domainId
     *
     * @return int $folderId
     */
    private static function routeInquiryFromPerson($company, $personNames, $email, $tel, $countryId, $pCode, $place, $address, $brid, $vatId = null, $uicId = null, &$explained, $domainId)
    {
        $inCharge = marketing_Router::getInChargeUser($place, $countryId, $domainId);
        
        foreach (array('vatId' => $vatId, 'egn' => $uicId) as $field => $value){
            if(!empty($value)){
                $folderId = marketing_Router::routeByUniqueId($value, $field, 'crm_Persons', $inCharge);
                if ($folderId) {
                    $explained = "Рутиране на лице по " . strtoupper($field);
                    
                    return $folderId;
                }
            }
        }
        
        // Ако има папка на лице с този имейл
        $folderId = marketing_Router::routeByPersonEmail($email, $inCharge);
        if ($folderId) {
            $explained = 'Рутиране на лице по личен имейл';
           
            return $folderId;
        }
        
        if(!empty($tel)){
            $folderId = marketing_Router::routeByPersonTel($tel, true);
            if ($folderId) {
                $explained = "Рутиране на лице по мобилен телефон";
               
                return $folderId;
            }
        }
        
        // Опит за рутиране по БРИД
        $folderId = marketing_Router::routeByBrid($brid);
        if ($folderId) {
            $explained = "Рутиране на лице по БРИД";
            
            return $folderId;
        }
        
        // Форсиране на папка и запис във визитника на лице с посочените данни
        $folderId = marketing_Router::forcePersonFolder($personNames, $email, $countryId, $tel, $pCode, $place, $address, $vatId, $uicId, $inCharge);
        colab_FolderToPartners::force($folderId);
        $explained = "Рутиране на лице към нова папка на лице";
        
        return $folderId;
    }
    
    
    /**
     * Рутиране в папка на фирма
     * 
     * 1. Рутиране по ДДС № или ЕИК на фирма
     * 2. Ако има ДДС №/ЕИК и (1.) не е сработило създава се нова фирма с тези ДДС №/ЕИК
     * 3. Която има визитка тип "Фирма" и в нея има същия имейл
     * 4. Търсим папка (но само от тип "Фирма"), по зададения имейл, чрез метода на имейл-рутера. Ако намерената папка не е "Фирма" - това правило пропада.
     * 5. Която е от тип "Фирма" и има същото (приблизително) име и държава
     * 6. Рутиране по БРИД
     * 7. Рутиране в папка на нова фирма
     * 
     * @param string $company
     * @param string $personNames
     * @param string $email
     * @param string $tel
     * @param int    $countryId
     * @param string $pCode
     * @param string $place
     * @param string $address
     * @param string $brid
     * @param string $vatId
     * @param string $uicId
     * @param string $explained
     * @param string $domainId
     * 
     * @return int $folderId
     */
    private static function routeInquiryFromCompany($company, $personNames, $email, $tel, $countryId, $pCode, $place, $address, $brid, $vatId = null, $uicId = null, &$explained, $domainId)
    {
        // Дефолтния отговорник
        $inCharge = marketing_Router::getInChargeUser($place, $countryId, $domainId);
        
        // Ако има въведен уникален код на фирма
        if(!empty($vatId) || !empty($uicId)){
            
            // И има вече фирма с него, рутира се към нея
            foreach (array('vatId' => $vatId, 'uicId' => $uicId) as $field => $value){
                if(!empty($value)){
                    $folderId = marketing_Router::routeByUniqueId($value, $field, 'crm_Companies', $inCharge);
                    if ($folderId) {
                        $explained = "Рутиране на фирма по {$field}";
                       
                        return $folderId;
                    }
                }
            }
            
            // Ако няма, създава се нова фирма с този уникален номер
            $folderId = marketing_Router::forceCompanyFolder($company, $email, $countryId, $tel, $pCode, $place, $address, $vatId, $uicId, $inCharge);
            colab_FolderToPartners::force($folderId);
            $explained = 'Рутиране към нова папка на фирма според уникален номер';
            
            return $folderId;
        }
        
        // Намираме папка на компания с този имейл
        $folderId = marketing_Router::routeByCompanyEmail($email, $inCharge);
        if ($folderId) {
            $explained = 'Рутиране на фирма по фирмен имейл';
            
            return $folderId;
        }
        
        // Рутиране според имейла, взимаме папката ако корицата и е фирма
        $folderId = marketing_Router::routeByEmail($email, 'company');
        if ($folderId) {
            $explained = 'Рутиране на фирма по имейл на фирма';
            
            return $folderId;
        }
        
        // Рутираме в папка на фирма със същото име от същата държава
        $folderId = marketing_Router::routeByCompanyName($company, $countryId, $inCharge);
        if ($folderId) {
            $explained = 'Рутиране на фирма по име на фирма';
            
            return $folderId;
        }
        
        // Опит за рутиране по БРИД
        $folderId = marketing_Router::routeByBrid($brid);
        if ($folderId) {
            $explained = 'Рутиране на фирма по БРИД';
            
            return $folderId;
        }
        
        // Форсиране на папка и визитка на фирма с въведените данни
        $folderId = marketing_Router::forceCompanyFolder($company, $email, $countryId, $tel, $pCode, $place, $address, $vatId, $uicId, $inCharge);
        colab_FolderToPartners::force($folderId);
        $explained = 'Рутиране на фирма към нова папка на фирма';
        
        return $folderId;
    }
}

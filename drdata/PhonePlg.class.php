<?php 


/**
 * При вербализиране на телефонния номер използва кода на държавата, вместо COUNTRY_PHONE_CODE
 * 
 * @category  vendors
 * @package   vendors
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class drdata_PhonePlg extends core_Plugin
{
    
    
    /**
     * Преди вземане на вербалната стойност
     * 
     * За определяне на държавата на даден номер използва държавата от записа.
     * $mvc->phoneCountryField - Полета, в което се записва държавата
     * $mvc->phoneFields - Полетата, които ще се преобразуват
     * 
     * @param core_Mvc $mvc
     * @param string $tel
     * @param object $rec
     * @param string $field
     */
    static function on_BeforeGetVerbal($mvc, &$phone, &$rec, $field=NULL)
    {
        // Ако не е подадено име на поле
        if (!$field) return ;
        
        // Името на полето за държавата
        $countryField = ($mvc->phoneCountryField) ? $mvc->phoneCountryField : 'country';
        
        // Ако не е подадена държавата
        if (!$rec->{$countryField}) return ;
        
        // Ако полето е празно
        if (!$rec->{$field}) return ;
        
        // Името на полетата за номерата
        $phoneFields = ($mvc->phoneFields) ? $mvc->phoneFields : 'tel, fax, mobile';
        
        // Масив с имената на полетата
        $phoneFieldsArr = arr::make($phoneFields, TRUE);
        
        // Ако полета, за което се прави обработката не съществува в масива
        if (!$phoneFieldsArr[$field]) return ;
        
        // Запис за държавата
        $countryRec = drdata_Countries::fetch($rec->{$countryField});
        
        // Ако няма телефонен код или няма запис за държавата
        if (!$countryRec || !$countryRec->telCode) return ;

        // Инстанция на класа
        $PhoneTypeInst = cls::get('drdata_PhoneType');
        
        // Вземаме стойността, която е зададена
        $countryPhoneCode = $PhoneTypeInst->params['countryPhoneCode'];
        
        // Задаваме новата сойност
        $PhoneTypeInst->params['countryPhoneCode'] = $countryRec->telCode;
        
        // Вземаме вербалната стойност на телефона
        $phone = $PhoneTypeInst->toVerbal($rec->{$field});
        
        // Връщаме старата стойност
        $PhoneTypeInst->params['countryPhoneCode'] = $countryPhoneCode;
        
        return FALSE;
    }
}

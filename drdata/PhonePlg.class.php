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
     * Ако не са дефинирани, ще се използват всички, които са drdata_PhoneType
     *
     * @param core_Mvc $mvc
     * @param string   $tel
     * @param object   $rec
     * @param string   $field
     */
    public static function on_BeforeGetVerbal(&$mvc, &$phone, &$rec, $field = null)
    {
        // Ако не е подадено име на поле
        if (!$field) {
            return ;
        }
        
        // Името на полето за държавата
        $countryField = ($mvc->phoneCountryField) ? $mvc->phoneCountryField : 'country';
        
        // Ако не е подадена държавата
        if (!$rec->{$countryField}) {
            return ;
        }
        
        // Ако полето е празно
        if (!$rec->{$field}) {
            return ;
        }
        
        // Ако не е дефинирано преди
        // За да не се взема всеки път
        if (is_null($mvc->phoneFields)) {
            
            // Маси с данните
            $mvc->phoneFields = array();
            
            // Обхождаме всички полета
            foreach ($mvc->fields as $fieldName => $mvcField) {
                
                // Ако са инстанция за телефони
                if ($mvcField->type instanceof drdata_PhoneType) {
                    
                    // Добавяме в масива
                    $mvc->phoneFields[$fieldName] = $mvcField->type;
                }
            }
        } elseif (!is_array($mvc->phoneFields)) {
            
            // За случаи когато полетата са дефинирани в модела, като стринг -  за да не се вземат всички полета
            
            // Масив със зададените полета
            $fieldsArr = arr::make($mvc->phoneFields);
            
            // Името на полето
            $mvc->phoneFields = array();
            
            // Добавяме името в полето
            foreach ($fieldsArr as $fieldName) {
                if (!$mvc->fields[$fieldName] ||
                    (!$mvc->fields[$fieldName]->type instanceof drdata_PhoneType)) {
                    continue;
                }
                    
                $mvc->phoneFields[$fieldName] = $mvc->fields[$fieldName]->type;
            }
        }
        
        // Ако полета, за което се прави обработката не съществува в масива
        if (!$mvc->phoneFields[$field]) {
            return ;
        }
        
        // Запис за държавата
        $countryRec = drdata_Countries::fetch($rec->{$countryField});
        
        // Ако няма телефонен код или няма запис за държавата
        if (!$countryRec || !$countryRec->telCode) {
            return ;
        }

        // Инстанция на класа
        $PhoneTypeInst = $mvc->phoneFields[$field];
        
        // Вземаме стойността, която е зададена
//        $countryPhoneCode = $PhoneTypeInst->params['countryPhoneCode'];
        
        // Задаваме новата сойност
        $PhoneTypeInst->params['countryPhoneCode'] = $countryRec->telCode;
        
        // За България приемаме, че по-подразбиране телефоните са в София
        if ($PhoneTypeInst->params['countryPhoneCode'] == '359') {
            $PhoneTypeInst->params['areaPhoneCode'] = '2';
        }

        // Вземаме вербалната стойност на телефона
        $phone = $PhoneTypeInst->toVerbal($rec->{$field});
        
        // Връщаме старата стойност
//        $PhoneTypeInst->params['countryPhoneCode'] = $countryPhoneCode;
        
        return false;
    }
}

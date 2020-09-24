<?php


/**
 * Допълнителни телефонни номера
 * 
 * @category  bgerp
 * @package   callcenter
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class callcenter_AdditionalNumbersPlg extends core_Plugin
{
    
    
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->FLD('additionalTel', 'drdata_PhoneType(type=tel)', 'caption=Допълнителни комуникации->Телефони,input=none,single=none');
        $mvc->FLD('additionalMobile', 'drdata_PhoneType(type=tel)', 'caption=Допълнителни комуникации->Мобилен,input=none,single=none');
        $mvc->FLD('additionalFax', 'drdata_PhoneType(type=fax)', 'caption=Допълнителни комуникации->Факс,input=none,single=none');
    }
    
    
    /**
     * 
     * @param core_Mvc $mvc
     * @param null $res
     * @param stdClass $rec
     * @param string $mobile
     * @param string $tel
     * @param string $fax
     */
    public static function on_AfterAddAddtionalNumber($mvc, &$res, $rec, $mobile, $tel, $fax)
    {
        if (!$mobile && !$tel && !$fax) {
            
            return ;
        }
        
        $saveFName = '';
        foreach (array('mobile', 'tel', 'fax') as $fName) {
            $val = ${$fName};
            if (!$val) {
                continue;
            }
            
            $phoneParams = array();
            if ($rec->country) {
                $phoneParams['countryPhoneCode'] = drdata_Countries::fetchField($rec->country, 'telCode');
            }
            $numberDetArr = drdata_PhoneType::toArray($val, $phoneParams);
            
            $numStr = '';
            
            foreach ($numberDetArr as $numberDetObj) {
                $numStr = drdata_PhoneType::getNumStrFromObj($numberDetObj);
                
                // Този номер вече е бил добавен към потребител
                if (callcenter_Numbers::fetch(array("#number = '[#1#]'", $numStr))) {
                    
                    continue ;
                }
            }
            
            if ($numStr) {
                $fRecName = 'additional' . ucfirst($fName);
                if (strpos($rec->{$fRecName}, $numStr) === false) {
                    $rec->{$fRecName} .= $rec->{$fRecName} ? ', ' : '';
                    $rec->{$fRecName} .= $numStr;
                    $saveFName .= $saveFName ? ',' : '';
                    $saveFName .= $fRecName;
                }
            }
        }
        
        if ($saveFName) {
            $mvc->save($rec, $saveFName);
        }
    }
    
    
    /**
     * След добавяне на запис в модела
     *
     * @param core_Mvc      $mvc
     * @param int|null      $id
     * @param stdClass      $rec
     * @param string|NULL   $saveFileds
     */
    protected static function on_AfterSave($mvc, &$id, $rec, $saveFileds = null)
    {
        // Подсигуряваме се, че е целия запис
        $rec = $mvc->fetch($rec->id);
        
        $mvc->updateNumbers($rec);
    }
    
    
    /**
     * Обновява номерата след запис
     * 
     * @param core_Mvc $mvc
     * @param null|array $res
     * @param stdClass $rec
     * @param array $fArr
     */
    static function on_AfterUpdateNumbers($mvc, &$res, $rec)
    {
        if (!$rec->id) {
            
            return ;
        }
        
        $fArr = $mvc->updateNumMap;
        $fArr['additionalTel'] = 'tel';
        $fArr['additionalMobile'] = 'mobile';
        $fArr['additionalFax'] = 'fax';
        
        $sNumArr = array();
        
        $numbersArr = array();
        foreach ($fArr as $fName => $nName) {
            if (!$rec->{$fName}) {
                
                continue;
            }
            
            // Предпазва от повторно вкарване
            if ($sNumArr[$nName][$rec->{$fName}]) {
                
                continue;
            }
            
            if (strpos($fName, 'additional') === 0) {
                
                $phoneParams = array();
                if ($rec->country) {
                    $phoneParams['countryPhoneCode'] = drdata_Countries::fetchField($rec->country, 'telCode');
                }
                $numberDetArr = drdata_PhoneType::toArray($rec->{$fName}, $phoneParams);
                
                $addNumber = '';
                foreach ($numberDetArr as $numberDetObj) {
                    $numStr = drdata_PhoneType::getNumStrFromObj($numberDetObj);
                    
                    // Този номер вече е бил добавен към потребител
                    if (callcenter_Numbers::fetch(array("#number = '[#1#]' AND (( #classId != '[#2#]' AND #contragentId != '[#3#]') OR (#classId = '[#2#]' AND #contragentId != '[#3#]'))", $numStr, $mvc->getClassId(), $rec->id))) {
                        
                        continue ;
                    }
                    
                    $addNumber .= $addNumber ? ', ' : '';
                    $addNumber .= $numStr;
                }
            } else {
                $addNumber = $rec->{$fName};
            }
            
            if ($addNumber) {
                $sNumArr[$nName][$addNumber] = $addNumber;
                $numbersArr[$nName][] = $addNumber;
            }
        }
        
        $res = callcenter_Numbers::addNumbers($numbersArr, $mvc->getClassId(), $rec->id, $rec->country);
    }
    
    
    /**
     * Промяна на данните от таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = null)
    {
        if ($fields['-single'] && haveRole('debug')) {
            $numStrArr = array();
            foreach (array('mobile', 'tel', 'fax') as $fName) {
                $val = ${$fName};
                $fName = $fRecName = 'additional' . ucfirst($fName);
                
                if ($rec->{$fName}) {
                    $numStrArr[] = $mvc->recToVerbal($rec, $fName)->{$fName};
                }
            }
            if (!empty($numStrArr)) {
                $row->info .= tr('Други номера') . ': ' . implode($numStrArr, ', ');
                
            }
        }
    }
}

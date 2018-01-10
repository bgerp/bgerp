<?php


/**
 * Клас 'spcheck_Plugin'
 * Плъгин за маркиране на грешните думи в черновите документи
 * 
 * @category  vendors
 * @package   spcheck
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class spcheck_Plugin extends core_Plugin
{
    
    
    /**
     * Над колко символо да се проверява за правописни грешки
     */
    protected static $minLenForCheck = 64;
    
    
    /**
     * 
     * 
     * @param core_Master $mvc
     * @param stdObject $res
     * @param stdObject $data
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        if (Mode::isReadOnly() || Mode::is('text', 'plain')) return ;
        
        // Чернова документите и документите, които са променяни последните 10 мин може да се проверяват
        $date = ($data->rec->modifiedOn) ? $data->rec->modifiedOn : $data->rec->createdOn;
        $spcheckInterval = ($mvc->spcheckInterval) ? $mvc->spcheckInterval : 600; // 10 мин
        if ($data->rec && (($data->rec->state != 'draft') && (dt::secsBetween(dt::now(), $date) >= $spcheckInterval))) return ;
        
        // Документите създадени/редактирани от текущия потребител мога да се проверяват
        $cu = core_Users::getCurrent();
        if ($data->rec && ($data->rec->createdBy != $cu && ($data->rec->modifiedBy != $cu))) return ;

        if (!core_Users::isPowerUser($cu)) return ;
        
        if (!cls::haveInterface('doc_DocumentIntf', $mvc)) return ;
        
        $Setup = cls::get('spcheck_Setup');
        $confRes = $Setup->checkConfig();
        if (isset($confRes)) {
            
            $mvc->logWarning($confRes);
            
            return ;
        }
        
        foreach ((array)$mvc->fields as $fName => $field) {
            if ($field->spellcheck == 'no') continue;
            
            if (($field->type instanceof type_Richtext) || ($field->type instanceof type_Text) || ($field->type instanceof type_Varchar)) {
                
                $fName = $field->name;
                
                if (mb_strlen($data->row->{$fName}) < self::$minLenForCheck) continue;
                
                if ($data->rec->containerId) {
                    $lg = doc_Containers::getLanguage($data->rec->containerId);
                    
                    if (!$lg) {
                        $lg = doc_Threads::getLanguage($data->rec->threadId);
                    }
                    
                    if (!$lg) {
                        $lg = doc_Folders::getLanguage($data->rec->folderId);
                    }
                    
                    if (!$lg) {
                        $lg = core_Lg::getCurrent();
                    } else {
                        
                        // Ако езика не е един от позволените
                        if (!core_Lg::isGoodLg($lg)) {
                            $lg = 'en';
                        }
                    }
                }
                
                $data->row->{$fName} = spcheck_Dictionary::highliteWrongWord($data->row->{$fName}, $lg);
            }
        }
    }
}

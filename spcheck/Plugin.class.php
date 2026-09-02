<?php


/**
 * Клас 'spcheck_Plugin'
 * Плъгин за маркиране на грешните думи в черновите документи
 *
 * @category  vendors
 * @package   spcheck
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
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
     * @param stdClass    $res
     * @param stdClass    $data
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        if (Mode::isReadOnly() || Mode::is('text', 'plain') || Mode::is('text', 'xhtml')) {

            return ;
        }

        if (!cls::haveInterface('doc_DocumentIntf', $mvc)) {

            return ;
        }

        if (($mvc->checkSpell ?? null) === false) {

            return ;
        }

        // Чернова документите и документите, които са променяни последните 10 мин може да се проверяват
        $date = ($data->rec->modifiedOn) ? $data->rec->modifiedOn : $data->rec->createdOn;
        $spcheckInterval = !empty($mvc->spcheckInterval) ? $mvc->spcheckInterval : 600; // 10 мин
        if ($data->rec && (($data->rec->state != 'draft') && (dt::secsBetween(dt::now(), $date) >= $spcheckInterval))) {
            
            return ;
        }
        
        // Документите създадени/редактирани от текущия потребител мога да се проверяват
        $cu = core_Users::getCurrent();
        if ($data->rec && ($data->rec->createdBy != $cu && ($data->rec->modifiedBy != $cu))) {
            
            return ;
        }
        
        if (!core_Users::isPowerUser($cu)) {
            
            return ;
        }
        
        $Setup = cls::get('spcheck_Setup');
        $confRes = $Setup->checkConfig();
        if (isset($confRes)) {
            $mvc->logWarning($confRes);
            
            return ;
        }
        
        foreach ((array) $mvc->fields as $fName => $field) {
            if (($field->spellcheck ?? null) == 'no') {
                continue;
            }
            
            if (($field->type instanceof type_Richtext) || ($field->type instanceof type_Text) || ($field->type instanceof type_Varchar)) {
                $fName = $field->name;

                $fieldValue = $data->row->{$fName} ?? null;
                if (!isset($fieldValue) || mb_strlen((string) $fieldValue) < self::$minLenForCheck) {
                    continue;
                }

                $lg = core_Lg::getCurrent();
                if (!empty($data->rec->containerId)) {
                    $lg = doc_Containers::getLanguage($data->rec->containerId);
                    
                    if (!$lg && !empty($data->rec->threadId)) {
                        $lg = doc_Threads::getLanguage($data->rec->threadId);
                    }
                    
                    if (!$lg && !empty($data->rec->folderId)) {
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
                
                $data->row->{$fName} = spcheck_Dictionary::highliteWrongWord($fieldValue, $lg);
            }
        }
    }
}

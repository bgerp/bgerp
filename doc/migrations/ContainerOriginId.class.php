<?php
/**
 * Клас 'doc_migrations_ContainerOriginId'
 *
 * Миграционен клас - попълва с коректно стойности полето doc_Containers::originId
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_migrations_ContainerOriginId extends schema_Migration
{
    /**
     * Дата и час на създаване на миграцията
     * 
     * @var string MySQL формат: Y-m-d H:i:s
     */
    public static $time = '2013-05-07 18:48';

    
    /**
     * Кога се прилага тази миграция
     * 
     * @var string
     */
    public static $when = 'afterSetup';
    
    
    /**
     * Попълва полето doc_Containers::originId със стойности, извлечени от съотв. документи
     * 
     * @return string лог от изпълнението на миграционната логика 
     */
    public static function apply()
    {
        /* @var $Containers doc_Containers */
        $Containers = cls::get('doc_Containers');
        
        /* @var $query core_Query */
        $query = $Containers->getQuery();
        $query->show('id, originId, docClass, docId');
        
        $html = '';

        while ($cRec = $query->fetch()) {
            try {
                $doc = doc_Containers::getDocument($cRec);
            } catch (core_exception_Expect $ex) {
                $html .= "<li class=\"error\">Проблемен контейнер #{$cRec->id}</li>";
            }
            
            if ($doc && ($originId = $doc->fetchField('originId')) && $originId != $cRec->originId) {
                $oldOriginId = $cRec->originId;
                $cRec->originId = $originId;
                if ($Containers->save_($cRec)) {
                    $html .= "<li class=\"success\">Обновен originId на контейнер #{$cRec->id}: {$oldOriginId} => {$originId}</li>";
                } else {
                    $html .= "<li class=\"error\">Проблем при обновяване на контейнер #{$cRec->id}</li>";
                }
            }
            
            unset($cRec);
            unset($doc);
        }
        
        if (!empty($html)) {
            $html = "<ul>{$html}</ul>";
        }
        
        return $html;
    }
}
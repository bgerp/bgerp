<?php

/**
 * Клас 'fileman_Setup' - Начално установяване на пакета 'fileman'
 *
 * @category   Experta Framework
 * @package    fileman
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class fileman_Setup extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  Контролер на връзката от менюто core_Packs
     */
    var $startCtr = 'fileman_Files';
    
    
    /**
     *  Екшън на връзката от менюто core_Packs
     */
    var $startAct = 'default';
    
    /**
     * Описание на модула
     */
    var $info = "Мениджър на файлове: качване, съхранение и използване";
    
    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        // Установяваме папките;
        $Buckets = cls::get('fileman_Buckets');
        $html .= $Buckets->setupMVC();
        
        // Установяваме файловете;
        $Files = cls::get('fileman_Files');
        $html .= $Files->setupMVC();
        
        // Установяваме версиите;
        $Versions = cls::get('fileman_Versions');
        $html .= $Versions->setupMVC();
        
        // Установяваме даните;
        $Data = cls::get('fileman_Data');
        $html .= $Data->setupMVC();
        
        // Установяваме свалянията;
        $Download = cls::get('fileman_Download');
        $html .= $Download->setupMVC();
        
        // Установяваме вземанията от URL;
        // $Get = cls::get('fileman_Get');
        // $html .= $Get->setupMVC();
        
        // Установяваме MIME-типовете;
        $Mime2Ext = cls::get('fileman_Mime2Ext');
        $html .= $Mime2Ext->setupMVC();

        // Конвертира старите имена, които са на кирилица
        $query = $Files->getQuery();
        while($rec = $query->fetch()) {
            if(STR::utf2ascii($rec->name) != $rec->name) {
                $rec->name = $Files->getPossibleName($rec->name, $rec->bucketId);
                $Files->save($rec, 'name');
            }
        }
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "<h4>Пакета fileman е деинсталиран</h4>";
    }
}
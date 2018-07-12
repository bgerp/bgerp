<?php


/**
 * Установяване на пакета replace
 *
 * @category  vendors
 * @package   oembed
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class replace_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Заместване на текст в richtext';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'replace_Dictionary';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        // 'OEMBED_MAX_WIDTH' => array ('int', 'caption=Максимална широчина на вградените елементи->Размер в пиксели'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $Dictionary = cls::get('replace_Dictionary');
        $html .= $Dictionary->setupMVC();
        
        $Groups = cls::get('replace_Groups');
        $html .= $Groups->setupMVC();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за работа с документи от системата
        // Замества handle' ите на документите с линк към документа
        $html .= $Plugins->installPlugin('Заместване на текст в RT', 'replace_Plugin', 'type_Richtext', 'private');
        
        return $html;
    }
    
    
    public function deinstall()
    {
        $html = parent::deinstall();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за работа с документи от системата
        // Замества handle' ите на документите с линк към документа
        $Plugins->deinstallPlugin('replace_Plugin');
        $html .= '<li>Деинсталиране на replace_Plugin';
        
        return $html;
    }
}

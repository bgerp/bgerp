<?php

/** Версия на браузърния клиент; досегашният CDN остава default. */
defIfNot('CONVERSEJS_VERSION', 'legacy');


/**
 * Адрес на BOSH услугата за XMPP връзката
 */
defIfNot('CONVERSEJS_BOSH_SERVICE_URL', 'https://conversejs.org/http-bind/');


/**
 * Клас 'conversejs_Setup' - Предава по добър изглед на keylist полетата
 *
 *
 * @category  bgerp
 * @package   conversejs
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class conversejs_Setup extends core_ProtoSetup
{
    /**
     * Необходими пакети
     */
    public $depends = 'remote=0.1';
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Уеб чат за XMPP протокол';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'CONVERSEJS_VERSION' => array('enum(legacy=Досегашен CDN,14.0.1=14.0.1)', 'caption=Converse.js->Версия'),
        'CONVERSEJS_BOSH_SERVICE_URL' => array('url', 'caption=BOSH_SERVICE->Url'),
    
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $html .= $Plugins->forcePlugin('ConverseJS Chat', 'conversejs_Plugin', 'core_page_InternalModern', 'private');
        
        return $html;
    }
}

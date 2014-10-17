<?php


/**
 * По подразбиране всички компоненти ще се форматират
 */
defIfNot('COMMUNICATION_FORMAT', 'tel,fax,mob,email,icq,social,web');


/**
 * Клас 'communicationformat_Setup' -
 *
 *
 * @category  bgerp
 * @package   communicationformat
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class commformat_Setup extends core_ProtoSetup 
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = '';
    
    
    /**
     * Описание на модула
     */
    var $info = "Форматиране на ICQ, Skype, tel. и други форми за комуникация";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        
             'COMMUNICATION_FORMAT'   => array 
    												('set(tel=Телефон,
                                                     fax=Факс,
                                                     mob=Мобилен телефон,
                                                     email=Имейл,
                                                     icq=ICQ,
                                                     social=AIM|YIM|MSNIM|MSN|XMPP|Jabber|Skype,
                                                     web=Уеб адреси
                                                     )', 'caption=Форматиране на адреси за комуникация->Услуги'),
    
             );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('Форматиране на комуникацията', 'commformat_Plugin', 'type_Richtext', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    	$html = parent::deinstall();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Date полета
        $Plugins->deinstallPlugin('commformat_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'commformat_Plugin'";
        
        return $html;
    }
}
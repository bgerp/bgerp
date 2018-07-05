<?php


/**
 *  Път до ImageMagic командата 'identify'
 */
defIfNot('IMAGICS_IDENTIFY_FILE_COMMAND', core_Os::isWindows() ? '' : 'identify');


/**
 * Клас 'imagics_Setup'
 *
 * @category  vendors
 * @package   imagics
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class imagics_Setup extends core_ProtoSetup
{
    
    
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
    public $info = 'Адаптер за ImageMagick: конвертиране на графични формати';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за аватари
        $html .= $Plugins->installPlugin('IM identify', 'imagics_Identify', 'fileman_Files', 'private', 'active', true);
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        $html = parent::deinstall();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $Plugins->deinstallPlugin('imgagics_Identify');
        
        return $html;
    }
}

<?php


/**
 * Клас 'tinymce_Setup' - Редактор за HTML текстови полета
 *
 *
 * @category  bgerp
 * @package   tinymce
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see       https://www.tinymce.com/
 */
class tinymce_Setup extends core_ProtoSetup
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
    public $info = 'Wysiwyg редактор за HTML данни използващ tinyMCE';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'tinymce_import_Html',
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталиране към всички полета, но без активиране
        $html .= $Plugins->installPlugin('tinyMCE', 'tinymce_Plugin', 'type_Html', 'private');
        
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
        
        $Plugins->deinstallPlugin('tinyMCE');
        
        return $html;
    }
}

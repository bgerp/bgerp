<?php


/**
 * Широчина по подразбиране
 * 480 - старата стойност
 */
defIfNot('GDOCS_DEFAULT_WIDTH', 640);


/**
 * Височина по подразбиране
 * 389 - старата стойност
 */
defIfNot('GDOCS_DEFAULT_HEIGHT', 480);


/**
 * Инсталатор на плъгин за добавяне на бутона за преглед на документи в gdocs.com
 * Разширения: doc,docx,xls,xlsx,ppt,pptx,pdf,pages,ai,tiff,dxf,svg,eps,ps,ttf,xps,zip,rar
 *
 * @category  vendors
 * @package   gdocs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class gdocs_Setup extends core_ProtoSetup
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
    public $info = 'Преглед на документи с docs.google.com';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'GDOCS_DEFAULT_WIDTH' => array('int', 'caption=Широчина по подразбиране->Размер в пиксели'),
        'GDOCS_DEFAULT_HEIGHT' => array('int', 'caption=Височина по подразбиране->Размер в пиксели'),
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
        $html .= $Plugins->forcePlugin('Преглед на документи с gdocs', 'gdocs_Plugin', 'fileman_Files', 'private');
        
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
        
        // Премахваме от type_Keylist полета
        $Plugins->deinstallPlugin('gdocs_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'gdocs_Plugin'";
        
        return $html;
    }
}

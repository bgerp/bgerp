<?php


defIfNot('MARKDOWN_VERSION', 'extra-1.2.5');


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с пакета markdown
 *
 * @category  vendors
 * @package   markdown
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class markdown_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Конвертиране от markdown текст към HTML';


    /** Изборът на библиотека важи само за конкретната инсталация. */
    public $configDescription = array(
        'MARKDOWN_VERSION' => array('enum(1.0.1,extra-1.2.5,2.0.0)', 'mandatory,caption=PHP Markdown->Версия'),
    );


    /** Показва несъвместимия PHP по стандартния начин в core_Packs. */
    public function checkConfig()
    {
        if (self::get('VERSION') === '2.0.0' && PHP_VERSION_ID < 70400) {
            return 'PHP Markdown 2.0.0 изисква PHP 7.4 или по-нова версия.';
        }
    }
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за конвертиране от markdown
        $html .= $Plugins->installPlugin('Markdown', 'markdown_RichTextPlg', 'type_Richtext', 'private');
        
        return $html;
    }
}

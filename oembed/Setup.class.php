<?php


/**
 * Максимална широчина на ембеднатите неща
 */
defIfNot('OEMBED_MAX_WIDTH', 600);


/**
 * Списък с услуги на които по подразбиране се ембедват връзките към тяхно съдържание
 */
defIfNot('OEMBED_SERVICES', 'Flickr Photos,Hulu,Scribd,Vimeo,YouTube,Picasa,Slideshare,Vbox7,Cacco,GoogleDrive');


/**
 * Установяване на пакета oembed
 *
 * @link http://www.oembed.com
 *
 * @category  vendors
 * @package   oembed
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class oembed_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Вграждане на външни обекти в текст чрез линкове';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'oembed_Cache';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'OEMBED_MAX_WIDTH' => array('int', 'caption=Максимална широчина на вградените елементи->Размер в пиксели'),
        'OEMBED_SERVICES' => array('set(blip.tv,Dailymotion,Flickr Photos,Hulu,Qik Video,Revision3,
           Scribd,Viddler Video,Vimeo,YouTube,dotSUB.com,YFrog,Clikthrough,Photobucket,Picasa,Slideshare,Vbox7,Cacco,Embed.ly,GoogleDrive)',
            'caption=Услуги на които по подразбиране се вграждат връзките към тяхно съдържание->Списък')
    
    );
    
    
    // Инсталиране на мениджърите
    public $managers = array(
        'oembed_Cache',
        'migrate::removeLongUrl',
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за работа с документи от системата
        // Замества handle' ите на документите с линк към документа
        $html .= $Plugins->installPlugin('oEmbed връзки', 'oembed_Plugin', 'type_Richtext', 'private');
        
        return $html;
    }
    
    
    /**
     * Миграция за изтриване на дългите линкове
     */
    public static function removeLongUrl()
    {
        return oembed_Cache::delete('CHAR_LENGTH(#url) = 128');
    }
}

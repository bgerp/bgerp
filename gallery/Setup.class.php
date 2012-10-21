<?php



/**
 * Клас 'gallery_Setup' -
 *
 *
 * @category  vendors
 * @package   gallery
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class gallery_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'gallery_Groups';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Картинки и групи от картинки с възможност за поставяне в Richtext";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'gallery_Groups',
            'gallery_Images',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('gallery_Pictures', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '6MB', 'user', 'every_one');
        

        // Замества абсолютните линкове с титлата на документа
        core_Plugins::installPlugin('Галерии и картинки в RichText', 'gallery_RichTextPlg', 'type_Richtext', 'private');
        $html .= "<li>Закачане на gallery_RichTextPlg към полетата за RichEdit - (Активно)";
        
        // Закачане в основното меню
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(3, 'Сайт', 'Галерия', 'gallery_Images', 'default', "cms, admin");

        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "";
    }
}
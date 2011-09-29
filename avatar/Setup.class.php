<?php


/**
 * Клас 'avatar_Setup' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    avatar
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class avatar_Setup extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = '';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = '';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $depends = 'fileman=0.1';
    
    /**
     * Описание на модула
     */
    var $info = "Аватари или gravatar-и за потребителите";
    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за аватари
        $Plugins->installPlugin('Аватари', 'avatar_Plugin', 'core_Users', 'private');
        
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Avatars', 'Икони на продуктови групи', 'jpg,gif,jpeg', '3MB', 'user', 'every_one');
        
        $Users = cls::get('core_Users');
        $AvatarPlg = cls::get('avatar_Plugin');
        
        $AvatarPlg->on_AfterDescription($Users);
        
        $html .= $Users->setupMVC();
        
        $html .= "<li>Потребителите имат вече аватари";
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $Plugins->deinstallPlugin('avatar_Plugin');
        $html .= "<li>Махнати са аватарите на потребителите";
        
        return $html;
    }
}
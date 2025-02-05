<?php


/**
 * Икона по подразбиране в gravatar - identicon, monsterid, wavatar, retro, blank, mysteryperson, robohash, mp, 404
 */
defIfNot('AVATAR_DEFAULT_ICON_TYPE', 'wavatar');


/**
 * Икона, която да се използва при липса на изображение
 */
defIfNot('AVATAR_NO_IMAGE_ICON', '');


/**
 * Клас 'avatar_Setup' -
 *
 *
 * @category  vendors
 * @package   avatar
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class avatar_Setup extends core_ProtoSetup
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
     * Необходими пакети
     */
    public $depends = 'fileman=0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Аватари или gravatar-и за потребителите';
    
    
    /**
     * Плъгини, които трябва да се инсталират
     */


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'AVATAR_DEFAULT_ICON_TYPE' => array('enum(wavatar=Wavatar, identicon=Идентификация, mysteryperson=Мистериозен човек, mp=Мистериозен човек 2, 404=Само наличните, robohash=Робот, monsterid=Чудовище, retro=Ретро, blank=Празно)', 'caption=Икона по подразбиране в gravatar->Избор'),
        'AVATAR_NO_IMAGE_ICON' => array('fileman_FileType(bucket=pictures,focus=none)', 'caption=Икона за липсващо изображение->Избор'),
    );

    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за аватари
        $html .= $Plugins->forcePlugin('Аватари', 'avatar_Plugin', 'core_Users', 'private');
        
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Avatars', 'Аватари на потребители', 'jpg,jpeg,png,gif,webp,image/*', '15MB', 'user', 'every_one');
        
        $Users = cls::get('core_Users');
        $AvatarPlg = cls::get('avatar_Plugin');
        
        $AvatarPlg->on_AfterDescription($Users);
        
        $html .= $Users->setupMVC();
        
        $Register = cls::get('avatar_Gravatar');
        $html .= $Register->setupMVC();
        
        $html .= '<li>Потребителите имат вече аватари';
        
        return $html;
    }
}

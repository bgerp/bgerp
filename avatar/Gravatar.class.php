<?php



/**
 * Клас 'avatar_Register' - Регистър на аватарите
 *
 * Поддържа информация за аватарите на вътрешни и външни лица
 *
 *
 * @category  vendors
 * @package   avatar
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class avatar_Gravatar extends core_Manager
{
    
    
    /**
     * Заглавие на модула
     */
    public $title = 'Аватари от Gravatar';
    
    
    /**
     * Списък плъгини за зареждане
     */
    public $loadList = 'plg_Created';
    
    
    /**
     * Връща URL към аватара на посочения имейл
     */
    public static function getUrl($email, $width = 100)
    {
        $md5 = md5(strtolower(trim($email)));
        
        $imgUrl = "http://www.gravatar.com/avatar/{$md5}?d=wavatar&s={$width}";
        
        $thmb = new thumb_Img($imgUrl, $width, $width, 'url');
        
        return $thmb->getUrl();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function act_Avatarco()
    {
        expect($name = Request::get('name', 'identifier'));
        
        expect($width = Request::get('width', 'int'));
        
        $av = cls::get('avatar_Avatarco');
        
        $av->init($name, $width);
        
        $av->showPicture();
        
        die;
    }
}

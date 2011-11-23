<?php

/**
 * Клас 'avatar_Register' - Регистър на аватарите
 * 
 * Поддържа информация за аватарите на вътрешни и външни лица
 *
 * @category   vendors
 * @package    avatar
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 * @since      v 0.1
 */
class avatar_Gravatar extends core_Manager {
    
    
    /**
     *  Заглавие на модула
     */
    var $title = 'Аватари от Gravatar';
    

    /**
     * Списък плъгини за зареждане
     */
    var $loadList = 'plg_Created';

     /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        // хеш на съдържанието на файла
        $this->FLD("file", "varchar(32)", array('caption' => 'Файл') );
        
        // Дължина на файла в байтове 
        $this->FLD("md5", "varchar(32)", array( 'caption' => 'Ключ'));

        // Кога за последно е проверяван този Gravatar
        $this->FLD("lastCheck", "datetime", 'caption=Проверка');
        
         
        $this->setDbUnique('md5');
        
    }

    
    /**
     * 
     */
    function getLink($email, $width = 100)
    {
        $md5 = md5(strtolower(trim($email)));

        $imgLink = "http://www.gravatar.com/avatar/{$md5}?d=wavatar&s={$width}";
        
       // $imgLink = toUrl(array('avatar_Gravatar', 'avatarco', 'name' => 'a' . $md5, 'width' => $width));

        return $imgLink;
    }



    /**
     *
     */
    function act_Avatarco()
    {
        expect($name = Request::get('name', 'identifier'));
        
        expect($width = Request::get('width', 'int'));

        $av = cls::get('avatar_Avatarco');

        $av->init($name, $width);

        $av->showPicture();

        die;
    }

    /**
     * Създаваме папката, където ще слагаме умалените изображения
     */
    function on_AfterSetupMVC($mvc, $result)
    {
        if(!is_dir(AVATARS_DIR)) {
            mkdir(AVATARS_DIR, 0777, TRUE);
            $result .= "<li style='color:green;'> Създадена папка аватари: " . AVATARS_DIR;
        } else {
            $result .= "<li> Папката за аватари от преди: " . AVATARS_DIR;
        }
    }

}
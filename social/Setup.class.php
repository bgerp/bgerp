<?php



/**
 * class dec_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със социалните мрежи
 *
 *
 * @category  bgerp
 * @package   social
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class social_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'social_Sharings';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Бутони за споделяне и проследяване в социални мрежи";

    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
   var $managers = array(
            'social_Sharings',
			'social_Followers',
   			'social_SharingCnts',
  
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'social';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
//     var $menuItems = array(
//             array(3.9, 'Сайт', 'Соц', 'social_Sharings', 'list', "cms, social, admin, ceo"),
//         );

        
    /**
     * Инсталиране на пакета
     */
    function install()
    {  
    	$html = parent::install(); 
    	 
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('social', 'Прикачени файлове в социални мрежи', 'png,gif,ico,bmp,jpg,jpeg,image/*', '1MB', 'user', 'powerUser');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }

}
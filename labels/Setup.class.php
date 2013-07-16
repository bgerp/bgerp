<?php


/**
 * Инсталиране/Деинсталиране на мениджъри свързани с inks модула
 *
 * @category  bgerp
 * @package   inks
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class labels_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'labels_Default';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Етикети";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
	       'labels_Default',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'labels';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.15, 'Търговия', 'Етикети', 'labels_Default', 'default', "labels"),
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	        
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Labels', 'Прикачени файлове в етикети', NULL, '300 MB', 'user', 'user');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}
<?php



/**
 * Транспорт
 *
 *
 * @category  bgerp
 * @package   passage
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class passage_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'passage_Texts';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Модул за съхраняваме откъси от текст";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'passage_Texts',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'ceo, admin';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(4.1, 'Система', 'Дефиниции', 'passage_Texts', 'default', "admin, ceo"),
        );

	
	/**
	 * Път до css файла
	 */
//	var $commonCSS = 'trans/tpl/LineStyles.css';
	
	
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
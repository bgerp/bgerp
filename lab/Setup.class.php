<?php



/**
 * class lab_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра на лабораторията
 *
 *
 * @category  bgerp
 * @package   lab
 * @author    Milen Georgiev <milen@download.bg>
 *            Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class lab_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'lab_Tests';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Лаборатория: методи, тестове и стандарти";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'lab_Tests',
            'lab_Parameters',
            'lab_Methods',
            'lab_TestDetails'
        );

        
    /**
     * Роли за достъп до модула
     */
   //  var $roles ='lab';

     var $roles = array(
     		array('lab'),
     		array('masterLab', 'lab'),
     );
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.45, 'Обслужване', 'Лаб', 'lab_Tests', 'default', "lab, ceo"),
        );

        
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
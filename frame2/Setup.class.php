<?php



/**
 * Какъв да е десетичният разделител на числата при експорт в csv
 */
defIfNot('FRAME2_CSV_DEC_POINT', '&#44;');


/**
 * Как да е форматирана датата
*/
defIfNot('FRAME2_CSV_DATE_MASK', 'd.m.Y');


/**
 * class frame2_Setup
 *
 * Инсталиране/Деинсталиране на пакета frame2
 *
 *
 * @category  bgerp
 * @package   frame2
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class frame2_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    var $depends = '';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    var $startCtr = 'frame2_Reports';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Динамични справки";

    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'frame2_Reports',
    		'frame2_ReportVersions',
    		'frame2_AllReports',
    );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'report,dashboard';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
    		array(2.56, 'Обслужване', 'Отчети', 'frame2_Reports', 'default', "report, ceo, admin"),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    		'FRAME2_CSV_DATE_MASK' => array ('enum(d.m.Y=|*22.11.1999, d-m-Y=|*22-11-1999, d/m/Y=|*22/11/1999, m.d.Y=|*11.22.1999, m-d-Y=|*11-22-1999, m/d/Y=|*11/22/1999, d.m.y=|*22.11.99, d-m-y=|*22-11-99, d/m/y=|*22/11/99, m.d.y=|*11.22.99, m-d-y=|*11-22-99, m/d/y=|*11/22/99)', 'caption=Екпорт във CSV->Дата, customizeBy=user'),
    		'FRAME2_CSV_DEC_POINT' => array ('enum(.=Точка,&#44;=Запетая)', 'caption=Екпорт във CSV->Дробен знак, customizeBy=user'),
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

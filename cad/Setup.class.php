<?php


/**
 * Максимален размер на чертежа
 */
defIfNot('CAD_MAX_CANVAS_SIZE', 10000);
 

/**
 * class 'cad_Setup' - Начално установяване на пакета 'cad'
 *
 *
 * @category  vendors
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cad_Setup extends core_ProtoSetup {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cad_Drawer';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'test';
   
     
    /**
     * Описание на модула
     */
    var $info = "Параметрично чертаене";

    /**
     * Роли за достъп до модула
    */
    public $roles = 'cad';
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
    		array(3.70, 'Производство', 'CAD', 'cad_Drawer', 'test', "cad, ceo, admin"),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        'CAD_MAX_CANVAS_SIZE' => array('int', 'caption=Чертожна дъска->Максималнен размер(+-mm),  width=100%'),

        );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {

        $shapes = array(
		            'cad_Circle',
		            'cad_RoundTo',
		            'cad_Rectangle',
		            'cad_Test',
		            'cad_MeasureLine',
		            'cad_ArcTo'
        );

        foreach($shapes as $cls) {
            $res .= core_Classes::add($cls);
        }

    	$res .= parent::install();
    	
        return $res;
    }
}
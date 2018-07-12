<?php


/**
 * Максимален размер на чертежа
 */
defIfNot('CAD2_MAX_CANVAS_SIZE', 10000);


/**
 * class 'cad_Setup' - Начално установяване на пакета 'cad'
 *
 *
 * @category  vendors
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cad2_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'cad2_Drawings';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Параметрично чертаене';
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'cad';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.70, 'Производство', 'CAD2', 'cad2_Drawings', '', 'cad, ceo, admin'),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CAD2_MAX_CANVAS_SIZE' => array('int', 'caption=Чертожна дъска->Максималнен размер(+-mm),  width=100%'),
    
    );
    
    
    /**
     * Модели
     */
    public $managers = array('cad2_Drawings');
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $shapes = array(
            'cad2_Circle',
            'cad2_Ellipse',
            'cad2_JaggedLine',
            'cad2_MeasureLine',
            'cad2_Rectangle',
            'cad2_ArcTo',
        );
        
        foreach ($shapes as $cls) {
            $res .= core_Classes::add($cls);
        }
        
        $res .= parent::install();
        
        return $res;
    }
}

<?php



/**
 * class draw_Setup
 *
 * Инсталиране/Деинсталиране на пакета draw
 *
 *
 * @category  bgerp
 * @package   draw
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class draw_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    public $depends = '';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    public $startCtr = 'draw_Designs';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Параметрични дизайни';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'draw_Designs',
            'draw_Pens',
        );
    

    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('draw'),
        array('drawMaster', 'draw'),
    );
     
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}

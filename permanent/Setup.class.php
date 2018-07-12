<?php


/**
 * Какъв е максималния размер на некомпресираните данни в байтове
 */
defIfNot('DATA_MAX_UNCOMPRESS', 10000);


/**
 * Клас 'permanent_Setup' - Съхранява параметри и показания на обекти
 *
 *
 * @category  vendors
 * @package   permanent
 *
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class permanent_Setup extends core_ProtoSetup
{
    /**
     * Версия
     */
    public $version = '0.1';
    
    
    /**
     * Контролер на връзката от менюто core_Packs
     */
    public $startCtr = 'permanent_Data';
    
    
    /**
     * Екшън на връзката от менюто core_Packs
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Перманентни данни за различни обекти';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        // Какъв е максималния размер на некомпресираните данни в байтове
        'DATA_MAX_UNCOMPRESS' => array('fileman_FileSize', 'mandatory, caption=Какъв е максималният размер на некомпресираните данни->Размер в байтове, suggestions=10 kB|20 kB|30 kB'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'permanent_Data'
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

<?php


/**
 * Пътя до директорията с временните файлове
 */
defIfNot('FCONV_TEMP_PATH', EF_TEMP_PATH . '/fconv');


/**
 * Убиване на увиснали скриптове
 */
defIfNot('FCONV_TIME_LIMIT', 'timelimit -t 3600');


/**
 * Дали да се използва скрипт за убиване на увиснали програми
 */
defIfNot('FCONV_USE_TIME_LIMIT', 'no');


/**
 * "Подправка" за кодиране на fconv
 */
defIfNot('FCONV_SALT', md5(EF_SALT . '_FCONV'));


/**
 * Конвертиране на файлове
 *
 * @category  vendors
 * @package   fconv
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fconv_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'fconv_Processes';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Конвертиране на файлове';
        
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'fconv_Processes',
            'fconv_Remote',
        );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'FCONV_TIME_LIMIT' => array('varchar', 'caption=Убиване на увиснали скриптове->Скрипт'),
        'FCONV_USE_TIME_LIMIT' => array('enum(no=Не, yes=Да)', 'caption=Дали да се използва скрипт за убиване на увиснали програми->Избор'),
        'FCONV_SALT' => array('varchar', 'caption=Ключ за отдалечено конвертиране->Ключ'),
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

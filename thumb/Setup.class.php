<?php


/**
 * Име на под-директория  в sbg/EF_APP_NAME, където се намират умалените изображения
 */
defIfNot('THUMB_IMG_DIR', '_tb_');


/**
 * Пълен път до директорията, където се съхраняват умалените картинки
 */
defIfNot('THUMB_IMG_PATH', EF_INDEX_PATH . '/' . EF_SBF . '/' . EF_APP_NAME . '/' . THUMB_IMG_DIR);


/**
 * Кои външни програми - оптимизатори да се използват за картинките
 */
defIfNot('THUMB_OPTIMIZATORS', '');

/**
 * Дали всички картинки да се създават като webp, ако браузърът поддържа този формат
 */
defIfNot('THUMB_WEBP', 'no');


/**
 * Качество на webp и jpeg картинките
 */
defIfNot('THUMB_QUALITY', '80');


/**
 * Дали да се оправят обърнатите изображения
 */
defIfNot('THUMB_FIX_ORIENTATION', 'yes');


/**
 * Клас 'thumb_Setup'
 *
 *
 * @category  bgerp
 * @package   minify
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class thumb_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'THUMB_OPTIMIZATORS' => array('set(jpegoptim/jpg,jpegtran/jpg,optipng/png,pngquant/png)', 'caption=Оптимизатори за графични файлове->Избор'),
        'THUMB_WEBP' => array('enum(no,yes)', 'caption=Когато е възможно използвай webp формат->Избор'),
        'THUMB_QUALITY' => array('enum(65=65%,70=70%,75=75%,80=80%,85=85%,90=90%,95=95%)', 'caption=Качество за jpeg и webp->Избор'),
        'THUMB_FIX_ORIENTATION' => array('enum(yes=Да, no=Не)', 'caption=Оправяне на обърнатите изображения->Избор'),
    );
    
    
    /**
     * Описание на системните действия
     */
    public $systemActions = array(
        array('title' => 'Изтриване', 'url' => array('thumb_M', 'clear', 'ret_url' => true), 'params' => array('title' => 'Изтриване на кешираните изображения'))
    );
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Скалиране на картинки';
    
    
    /**
     * Дали пакета е системен
     */
    public $isSystem = true;
    
    
    protected $folders = THUMB_IMG_PATH;
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
}

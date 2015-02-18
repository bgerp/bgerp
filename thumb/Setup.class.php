<?php

/**
 * Име на под-директория  в sbg/EF_APP_NAME, където се намират умалените изображения
 */
defIfNot('THUMB_IMG_DIR', '_tb_');


/**
 * Пълен път до директорията, където се съхраняват умалените картинки
 */
defIfNot('THUMB_IMG_PATH',  EF_INDEX_PATH . '/' . EF_SBF . '/' . EF_APP_NAME . '/' . THUMB_IMG_DIR);



/**
 * Клас 'thumb_Setup'
 *
 *
 * @category  vendors
 * @package   minify
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class thumb_Setup extends core_ProtoSetup {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = '';
    
    
    /**
     * Описание на модула
     */
    var $info = "Скалиране на картинки";
    
    
    /**
     * Дали пакета е системен
     */
    public $isSystem = TRUE;
    
        
    protected $folders = THUMB_IMG_PATH;    
    
}
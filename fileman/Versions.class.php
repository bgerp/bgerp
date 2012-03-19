<?php



/**
 * Клас 'fileman_Versions' -
 *
 *
 * @category  all
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_Versions extends core_Manager {
    
    
    /**
     * Заглавие на модула
     */
    var $title = 'Версии';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Файлов манипулатор - уникален 8 символен низ от малки лат. букви и цифри
        // Генериран случайно, поради което е труден за налучкване
        $this->FLD("fileHnd", "varchar(8)", array('notNull' => TRUE, 'caption' => 'Манипулатор'));
        
        // Версия на данните на файла
        $this->FLD("dataId", "key(mvc=file_Data)", array('caption' => 'Данни Id'));
        
        // От кога са били валидни тези данни
        $this->FLD("from", "datetime", array('caption' => 'Валидност->от'));
        
        // До кога са били валидни тези данни
        $this->FLD("to", "datetime", array('caption' => 'Валидност->до'));
        
        // Състояние на файла
        $this->FLD("state", "enum(draft=Чернова,active=Активен,rejected=Оттеглен)", array('caption' => 'Състояние'));
        
        // Кой е изпратил тази версия в историята
        $this->load('plg_Created,fileman_Wrapper');
        
        $this->setDbIndex('fileHnd');
    }
}
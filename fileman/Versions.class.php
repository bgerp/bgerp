<?php


/**
 * Клас 'fileman_Versions' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    fileman
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class fileman_Versions extends core_Manager {
    
    
    /**
     *  Заглавие на модула
     */
    var $title = 'Версии';
    
    
    /**
     *  Описание на модела (таблицата)
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
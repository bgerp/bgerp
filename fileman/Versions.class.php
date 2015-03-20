<?php



/**
 * Клас 'fileman_Versions' -
 *
 *
 * @category  vendors
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
	 * Кой може да го разглежда?
	 */
	var $canList = 'debug';
	
	
	/**
	 * 
	 */
	var $canAdd = 'no_one';
	
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Файлов манипулатор - уникален 8 символен низ от малки лат. букви и цифри
        // Генериран случайно, поради което е труден за налучкване
        $this->FLD("fileHnd", "varchar(8)", array('notNull' => TRUE, 'caption' => 'Манипулатор'));
        
        // Версия на данните на файла
        $this->FLD("dataId", "key(mvc=fileman_Data)", array('caption' => 'Данни Id'));
        
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
    
    
    /**
     * Създава нова версия на файла
     * 
     * @param string $fileHnd - Манипулатора на файла
     * @param fileman_Data - id на данните
     * 
     * @return fileman_Versions $id - id' то на записа
     */
    public static function createNew($fileHnd, $dataId)
    {
        // Проверяваме дали има запис
        $rec = static::fetch(array("#fileHnd = '[#1#]' AND #dataId = '[#2#]'", $fileHnd, $dataId));
        
        // Ако същетстува запис връщаме резултата
        if ($rec) return $rec->id;
        
        // Създаваме нов запис
        $nRec = new stdClass();
        $nRec->fileHnd = $fileHnd;
        $nRec->dataId = $dataId;
        $nRec->state = 'active';
        
        $id = static::save($nRec);
        
        // Увеличаваме брой на файловете, към които сочат данните
        fileman_Data::increaseLinks($dataId);
        
        return $id;
    }
}
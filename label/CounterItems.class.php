<?php 


/**
 * Детайл на броячите.
 * Показва кой брояч в кой етикет е използван и до кой номер е стигнал
 *
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_CounterItems extends core_Detail
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Запис в броячи';
    
    
    /**
     * 
     */
    var $singleTitle = 'Записи';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'label, admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'label, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'label, admin, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'label_Wrapper, plg_Created, plg_Modified';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'counterId';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
//    var $rowToolsSingleField = 'id';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
//    var $fetchFieldsBeforeDelete = '';
    
    
    /**
     * 
     */
//    var $listFields = 'id, name, description, maintainers';
    
    
    /**
     * 
     */
//    var $currentTab = '';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('counterId', 'key(mvc=label_Counters, select=name)', 'caption=Брояч, mandatory');
        $this->FLD('labelId', 'key(mvc=label_Labels, select=title)', 'caption=Етикет, mandatory');
        $this->FLD('number', 'int', 'caption=Номер');
    }
    
    
    /**
     * Връща най - голямата стойност за брояча
     * 
     * @param id $counterId - id на брояча
     */
    static function getMax($counterId)
    {
        // Вземаме най - голямата стойност на номера за съответния брояч
        $query = static::getQuery();
        $query->XPR('maxVal', 'int', 'MAX(#number)');
        $query->groupBy('counterId');
        $query->where(array("#counterId = '[#1#]'", $counterId));
        
        $rec = $query->fetch();
        
        // Връщаме максималната стойност
        return $rec->maxVal;
    }
    
    
    /**
     * Обновяваме брояча
     * 
     * @param integer $counterId - id на брояча
     * @param integer $labelId - id на етикета
     * @param integer $number - Стойността на брояча
     * 
     * @return integer - id на записа
     */
    static function updateCounter($counterId, $labelId, $number)
    {
        // Вземаме записа
        $rec = static::fetch(array("#counterId = '[#1#]' AND #labelId = '[#2#]'", $counterId, $labelId));
        
        // Ако няма запис
        if (!$rec) {
            
            // Създаваме нов
            $rec = new stdClass();
            $rec->counterId = $counterId;
            $rec->labelId = $labelId;
        }
        
        // Добавяме номера
        $rec->number = $number;
        
        // Записваме
        return static::save($rec);
    }
}

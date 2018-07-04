<?php
/**
 * Клас 'acc_Operations'
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Operations extends core_Manager
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'acc_ContoReasons';
    
    
    /**
     * Заглавие в множествено число
     *
     * @var string
     */
    public $title = 'Счетоводни операции';
    
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'acc_WrapperSettings';
    
    
    /**
     * Активен таб на менюто
     *
     * @var string
     */
    public $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Кой има право да чете?
     *
     * @var string|array
     */
    public $canRead = 'debug';
    
    
    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,title';
    

    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Основания за счетоводна транзакция';
    
    
    /**
     * Описание на модела на нишките от контейнери за документи
     */
    public function description()
    {
        $this->FLD('title', 'varchar(255,ci)', 'caption=Основание,mandatory');
        
        $this->setDbUnique('title');
    }
    
    
    /**
     * Връща ид-то отговарящо на  основанието, ако няма такова се създава
     *
     * @param  string $title - текст на основанието
     * @return int    $id - ид на основанието
     */
    public static function getIdByTitle($title)
    {
        // Ако има запис с това име, връщаме му ид-то
        if ($id = static::fetchField(array("#title = '[#1#]'", $title), 'id')) {
            return $id;
        }
        
        $rec = (object) array('title' => $title);
        
        // Ако няма добавяме текста на основанието към модела
        return static::save($rec);
    }
}

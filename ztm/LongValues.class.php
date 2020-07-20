<?php


/**
 * Клас 'ztm_LongValues' - Кеш на дългите стойностти на регистрите
 *
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class ztm_LongValues extends core_Manager
{
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'ztm_RegisterLongValues';
    
    
    /**
     * Заглавие
     */
    public $title = 'Дълги стойностти на регистрите';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'ztm_Wrapper';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('hash', 'varchar', 'mandatory,input=none,caption=Хеш');
        $this->FLD('value', 'blob(serialize, compress)', 'mandatory,input=none,caption=Стойност');
        
        $this->setDbUnique('hash');
    }
}
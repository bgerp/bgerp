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
     * Заглавие
     */
    public $title = 'Дълги стойностти на регистрите';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'ztm_Wrapper,plg_RowTools2';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
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
        $this->FLD('hash', 'varchar(32)', 'mandatory,caption=Хеш');
        $this->FLD('value', 'blob', 'mandatory,caption=Стойност');
        
        $this->setDbUnique('hash');
    }
    
    
    /**
     * Връща стойността при нужда
     *
     * @param mixed $var
     *
     * @return mixed
     */
    public static function getValueByHash($var)
    {
        $value = ztm_LongValues::fetchField(array("#hash = '[#1#]'", $var), 'value');

        return isset($value) ? $value : $var;
    }


    /**
     * Изтриване на старите неизползвани регистри
     *
     * @return string
     */
    public function cron_DeleteUnusedRegisterValues()
    {
        // Всички регистри, които имат стойности
        $query = $this->getQuery();
        $query->EXT('vHash', 'ztm_RegisterValues', 'externalName=value');
        $query->where("#vHash = #hash");
        $query->show('id');
        $existKeysArr = array_keys($query->fetchAll());

        // Изтриваме останалите
        $query = $this->getQuery();
        $query->notIn('id', $existKeysArr);
        $query->show('id');
        $dCnt = 0;
        while ($rec = $query->fetch()) {
            $this->delete($rec->id);
            $dCnt++;
        }

        return "Изтрити са $dCnt неизползвани стойности на регистрите";
    }
}

<?php


/**
 * Клас 'recently_Values'
 *
 * Поддържа база данни с дефолти за комбо-боксовете
 * дефолтите са въведените данни от потребителите
 * при предишни сесии
 *
 *
 * @category  bgerp
 * @package   recently
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class recently_Values extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Последно въвеждани стойности';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,plg_RowTools2,recently_Wrapper';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име');
        $this->FLD('value', 'varchar', 'caption=Стойност');
        
        $this->setDbIndex('name,value,createdBy');
        $this->setDbIndex('name,createdBy');
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $data->form->title = (isset($data->form->rec->id)) ? 'Редактиране на опция' : 'Добавяне на опция';
    }
    
    
    /**
     * Връща предложенията за посоченото поле
     */
    public static function fetchSuggestions($name, $maxSuggestion = null, $maxKeepingDays = null)
    {
        $conf = core_Packs::getConfig('recently');
        
        setIfNot($maxSuggestion, $conf->RECENTLY_MAX_SUGGESTION);
        setIfNot($maxKeepingDays, $conf->RECENTLY_MAX_KEEPING_DAYS);
        
        $query = self::getQuery();
        $query->orderBy('#createdOn=DESC');
        $query->limit($maxSuggestion);
        $query->where(array("#createdOn > '[#1#]'", dt::addDays(-$maxKeepingDays)));
        
        $opt = array('' => '');
        
        if ($cu = core_Users::getCurrent()) {
            while ($rec = $query->fetch(array(
                "#name = '[#1#]' AND #createdBy = [#2#]",
                $name,
                $cu
            ))) {
                $value = $rec->value;
                $opt[$value] = $value;
            }
        }
        
        return countR($opt) > 1 ? $opt : array();
    }
    
    
    /**
     * Добавя стойност към определено име и потребител
     */
    public static function add($name, $value)
    {
        $cu = core_Users::getCurrent();
        $value = mb_substr($value, 0, 255);
        $name = str::convertToFixedKey($name, 64);
        
        $rec = self::fetch(array(
            "#name = '[#1#]' AND #createdBy = '{$cu}' AND #value = '[#2#]'",
            $name,
            $value
        ));
        
        if ($rec) {
            $rec->createdOn = dt::verbal2mysql();
            self::save($rec, 'createdOn');
        } else {
            $rec = new stdClass();
            $rec->name = $name;
            $rec->value = $value;
            self::save($rec);
        }
    }

    /**
     * Изтриване на стари стойности
     *
     * @param datetime $olderThan
     * @return void
     */
    private function deleteOldValues()
    {
        if($olderThan = recently_Setup::get('MAX_KEEPING_DAYS')){

            // Всички движения преди X време
            $createdBefore = dt::addDays(-1 * $olderThan);

            Mode::push('valuesDeleteByCron', true);
            recently_Values::delete("#createdOn <= '{$createdBefore}'");
            Mode::pop('valuesDeleteByCron');
        }

    }



    /**
     * Преди да се извлекат записите за листови изглед,
     * задава подреждане от най-новите към по-старите
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#createdOn=DESC');
    }

    /**
     * Изтриване на стари движения по разписание
     */
    public function cron_DeleteOldValues()
    {
        // Изтриване на старите стойности
        $this->deleteOldValues();

    }
}

<?php



/**
 * Максимален брой за предложенията за последно използвани стойности на поле
 */
defIfNot(RECENTLY_MAX_SUGGESTION, 20);


/**
 * Максимален брой дни за запазване на стойност след нейната последна употреба
 */
defIfNot(RECENTLY_MAX_KEEPING_DAYS, 60);


/**
 * Клас 'recently_Values'
 *
 * Поддържа база данни с дефолти за комбо-боксовете
 * дефолтите са въведените данни от потребителите
 * при предишни сесии
 *
 *
 * @category  vendors
 * @package   recently
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class recently_Values extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Последно въвеждани стойности';
    
    /**
     * @todo Чака за документация...
     */
    var $loadList = 'plg_Created,plg_RowTools,recently_Wrapper';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име');
        $this->FLD('value', 'varchar(128)', 'caption=Стойност');
        
        $this->setDbUnique('name,value,createdBy');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($invoker, $data)
    {
        if (Request::get('id', 'int')) {
            $data->form->title = 'Редактиране на опция';
        } else {
            $data->form->title = 'Добавяне на опция';
        }
    }
    
    
    /**
     * Връща предложенията за посоченото поле
     */
    function getSuggestions($name)
    {
        $query = $this->getQuery();
        
        $query->orderBy("#createdOn=DESC");
        
        $query->limit(RECENTLY_MAX_SUGGESTION);
        
        $query->where(array("#createdOn > '[#1#]'", dt::addDays(-RECENTLY_MAX_KEEPING_DAYS)));
        
        $opt = array('' => '');
        
        $cu = core_Users::getCurrent();
        
        while ($rec = $query->fetch("#name = '{$name}' AND #createdBy = {$cu}")) {
            
            $value = $rec->value;
            
            $opt[$value] = $value;
        }
        
        return count($opt) > 1 ? $opt : array();
    }
    
    
    /**
     * Добавя стойност към определено име и потребител
     */
    function add($name, $value)
    {
        $cu = core_Users::getCurrent();
        $value = str::convertToFixedKey($value, 64);
        $rec = $this->fetch(array(
                "#name = '[#1#]' AND #value = '[#2#]' AND #createdBy = {$cu}",
                $name,
                $value
            ));
        
        if ($rec) {
            $rec->createdOn = dt::verbal2mysql();
            $this->save($rec, 'createdOn');
        } else {
            $rec = new stdClass();
            $rec->name = $name;
            $rec->value = $value;
            $this->save($rec);
        }
    }
    
    
    /**
     * Преди да се извлекат записите за листови изглед,
     * задава подреждане от най-новите към по-старите
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy("#createdOn=DESC");
    }
}
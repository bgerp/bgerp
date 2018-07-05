<?php

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
    public $title = 'Последно въвеждани стойности';
    
    /**
     * @todo Чака за документация...
     */
    public $loadList = 'plg_Created,plg_RowTools2,recently_Wrapper';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име');
        $this->FLD('value', 'varchar', 'caption=Стойност');
        
        $this->setDbUnique('name,value,createdBy');
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
    public function getSuggestions($name)
    {
        $conf = core_Packs::getConfig('recently');

        $query = $this->getQuery();
        
        $query->orderBy('#createdOn=DESC');
        
        $query->limit($conf->RECENTLY_MAX_SUGGESTION);
        
        $query->where(array("#createdOn > '[#1#]'", dt::addDays(-$conf->RECENTLY_MAX_KEEPING_DAYS)));
        
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
        
        return count($opt) > 1 ? $opt : array();
    }
    
    
    /**
     * Добавя стойност към определено име и потребител
     */
    public function add($name, $value)
    {
        $cu = core_Users::getCurrent();
        $value = mb_substr($value, 0, 255);
        $name = str::convertToFixedKey($name, 64);

        $rec = $this->fetch(array(
                "#name = '[#1#]' AND #value = '[#2#]' AND #createdBy = '{$cu}'",
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
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#createdOn=DESC');
    }
}

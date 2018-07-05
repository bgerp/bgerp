<?php

/**
 * Линкове
 *
 *
 * @category  bgerp
 * @package   blogm
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blogm_Links extends core_Manager
{
    
    
    /**
     * Заглавие на страницата
     */
    public $title = 'Препратки';
    
    
    /**
     * Зареждане на необходимите плъгини
     */
    public $loadList = 'plg_RowTools, plg_State2, blogm_Wrapper, plg_Created, plg_Modified';
    

    /**
     * Полета за листов изглед
     */
    public $listFields = ' id, name, url, state';
    

    /**
     * Кой може да листва линковете
     */
    public $canRead = 'cms, ceo, admin, blog';
    
    
    /**
     * Кой може да добявя,редактира или изтрива линк
     */
    public $canWrite = 'cms, ceo, admin, blog';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,cms, blog';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,cms, blog';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(50)', 'caption=Наименование, mandatory, notNull');
        $this->FLD('url', 'url', 'caption=Адрес, mandatory, notNull');
        
        // Уникални полета
        $this->setDbUnique('name');
        $this->setDbUnique('url');
    }
    
    
    /**
     * Метод за извличане на всички Линкове и съхраняването им в масив от обекти
     */
    public static function prepareLinks(&$data)
    {
        // Взимаме Заявката към Линковете
        $query = static::getQuery();
        
        // Избираме само активните линкове
        $query->where("#state = 'active'");
        
        // За всеки запис създаваме обект, който натрупваме в масива $data
        while ($rec = $query->fetch()) {
            $link = new stdClass();
            $link->name = static::getVerbal($rec, 'name');
            $link->url = $rec->url;
            
            // Добавяме линка като нов елемент на $data
            $data->links[$rec->id] = $link;
        }
    }
    
    
    /**
     *  Метод за рендиране на линковете
     */
    public static function renderLinks($data)
    {
        $tpl = new ET();
        
        if ($data->links) {
            foreach ($data->links as $link) {
                // Създаваме линк от заглавието и урл-то
                $name = ht::createLink($link->name, $link->url, null, 'target=_blank,class=out');
                $name = ht::createElement('div', array('class' => 'level2'), $name);
                
                // Добавяме линка към шаблона
                $tpl->append($name);
            }
        }
        
        return $tpl;
    }
}

<?php

/**
 * Модул Пасаж
 *
 * @category  bgerp
 * @package   cond
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_Groups extends core_Manager
{


    /**
     * Заглавие
     */
    public $title = "Групи";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, cond_Wrapper";


    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,admin';


    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,admin';


    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,admin';


    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,admin';


    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,admin';


    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,admin';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar', 'caption=Група');
        $this->setDbUnique('title');
    }

    static function on_AfterSetupMVC($mvc, &$res)
    {
        $rec = new stdClass();

        $rec->title = 'Общи';
        
        $rec->id = $mvc->fetchField("#title = '{$rec->title}'");
        
        $id = $mvc->save($rec, 'title', 'IGNORE');

        $res .= $id ? "<li>Добавен е един запис успешно!</li>" : "<li>Не е добавено ново поле</li>";

    }
}
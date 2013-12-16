<?php


/**
 * Добавки от код към публичната страница
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Includes extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Добавки към публичната страница";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Modified, plg_State2, plg_RowTools, plg_Printing, cms_Wrapper';
    
         
     
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('place', 'varchar(32)', 'caption=Място,mandatory,suggestions=HTTP_HEADER|HEAD|JS|CSS|META_DESCRIPTION|META_KEYWORDS|STYLES|PAGE_CONTENT|SCRIPTS');
        $this->FLD('mode', 'enum(append, prepend, replace, push)', 'caption=Метод');
        $this->FLD('code', 'text', 'caption=Код,mandatory,width=100%');
    }


    /**
     * Добавя кодовете в посочения шаблон
     */
    static function insert($tpl)
    {
        $query = self::getQuery();
        $query->where("#state = 'active'");
        while($rec = $query->fetch()) { 
            $rec->code = "\n" . $rec->code;
            switch($rec->mode) {
                case 'append':
                    $tpl->append($rec->code, $rec->place);
                    break;
                case 'prepend':
                    $tpl->prepend($rec->code, $rec->place);
                    break;
                case 'replace':
                    $tpl->replace($rec->code, $rec->place);
                    break;
                case 'push':
                    $tpl->push($rec->code, $rec->place);
                    break;
            }
        }
    }

}

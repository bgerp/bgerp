<?php

/**
 * Речник за текстовете за заместване
 *
 * @category  vendors
 * @package   replace
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class replace_Dictionary extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,plg_RowTools2,plg_State2,replace_Wrapper';
    
    
    /**
     * Заглавие
     */
    var $title = "Речник на заместванията";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    // var $listFields = '';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Масив за заместване
     */
    static $replace;
    

    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('from' , 'richtext(rows=3,bucket=Notes)', 'caption=Текст->Оригинал');
        $this->FLD('to' , 'richtext(rows=3,bucket=Notes)', 'caption=Текст->Заместване');
        $this->FLD('groupId' , 'key(mvc=replace_Groups,select=name)', 'caption=Групи');
    }
    

    /**
     * Връща заместванията дефинирани от една група
     */
    public static function getTexts($groups)
    {
        if(!self::$replace) {
            self::$replace = array();
            $query = self::getQuery();
            while($rec = $query->fetch("#state = 'active'")) {
                $gRec = replace_Groups::fetch($rec->groupId);
                self::$replace[strtolower($gRec->name)][$rec->from] = $rec->to;
            }
        }

        $groups = arr::make($groups);

        foreach($groups as $groupName) {
            $groupName = strtolower($groupName);
            if(is_array(self::$replace[$groupName])) {
                foreach(self::$replace[$groupName] as $from => $to) {
                    $res[$from] = $to;
                }
            }
        }
 
        return $res;
    }
}
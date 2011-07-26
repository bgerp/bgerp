<?php


/**
 * Клас 'drdata_DialCodes' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    drdata
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class drdata_DialCodes extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Телефонни кодове';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'drdata_Wrapper';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        // Декларираме полетата
        $this->FLD('country', 'varchar', 'caption=Страна->Наименование');
        $this->FLD('countryCode', 'varchar(8)', 'caption=Страна->Код,notNull');
        $this->FLD('area', 'varchar', 'caption=Регион->Наименование');
        $this->FLD('areaCode', 'varchar(16)', 'caption=Регион->Код,notNull');
        
        // Декларираме индексите
        $this->setDbUnique('countryCode,areaCode', 'code');
        $this->setDbIndex('country');
    }
    
    
    /**
     *  Извиква се след SetUp-а на таблицата за модела
     */
    function on_AfterSetupMVC(&$mvc, &$html)
    {
        // Увеличаваме паметта на PHP
        ini_set('memory_limit', '1000000000');
        
        // Изтриваме съдържанието й
        $mvc->db->query("TRUNCATE TABLE  `{$this->dbTableName}`");
        
        // Намираме директорията, където е текущия файл
        $dir = dirname(__FILE__);
        
        // Вкарваме първия източник на данни
        $file = file_get_contents($dir ."/data/DialingCodes.dat");
        
        // Парсираме CSV съдържанието
        $lines = explode("\n", $file);
        
        $cnt = 0;
        
        // Ред по ред го вкарваме в таблицата
        foreach($lines as $row) {
            
            if(!strpos($row, "|")) continue;
            
            $rec = NULL;
            
            list($rec->country, $rec->countryCode, $rec->area, $rec->areaCode) = explode('|', $row);
            
            $rec->areaCode = trim($rec->areaCode);
            
            $mvc->save($rec);
            
            $cnt++;
        }
        
        $html .= "<li>Imported $cnt rows";
        
        return $html;
    }
}
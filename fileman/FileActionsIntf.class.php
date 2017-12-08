<?php



/**
 * Клас 'fileman_FileActionsIntf' - Интерфейсен метод за действия с даден файл
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейсен метод за създаване на входящи документи
 */
class fileman_FileActionsIntf
{
    
    
    /**
     * Интерфейсен метод на fileman_FileActionsIntf
     * 
     * Връща масив с действия, които могат да се извършат с дадения файл
     * 
     * @param stdObject $fRec - Обект са данни от модела
     * 
     * @return array $arr - Масив с данните
     * $arr['url'] - array URL на действието
     * $arr['title'] - Заглавието на бутона
     * $arr['icon'] - Иконата
     */
    function getActionsForFile($rec)
    {
        $this->class->getActionsForFile($rec);
    }
}

<?php



/**
 * Клас 'incoming_CreateDocumentIntf' - Интерфейсен метод за създаване на входящи документи
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class incoming_CreateDocumentIntf
{
    
    
    /**
     * Връща масив, от който се създава бутона за създаване на входящ документ
     * 
     * @param fileman_Files $rec - Обект са данни от модела
     * 
     * @return array $arr - Масив с данните
     * $arr['class'] - Името на класа
     * $arr['action'] - Екшъна
     * $arr['title'] - Заглавието на бутона
     * $arr['icon'] - Иконата
     */
    function canCreate($rec)
    {
        
        $this->class->canCreate($rec);
    }
}

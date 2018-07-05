<?php



/**
 * Плъгин за документи, които могат да бъдат изпращани по имейл
 *
 * Плъгина не е задължително условие за да може един документ да се изпрати по имейл
 * Той предоставя реализация по подразбиране на някои от методите на интерфейса
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       email_DocumentIntf
 */
class email_plg_Document extends core_Plugin
{
    
    
    /**
     * HTML или plain text изгледа на документ при изпращане по емайл.
     *
     * Използва single view на мениджъра на документа.
     *
     * @param core_Mvc $mvc  мениджър на документа
     * @param int      $id   първичния ключ на документа - key(mvc=$mvc)
     * @param string   $mode `plain` или `html`
     * @access private
     */
    public function getDocumentBody($mvc, $id, $mode)
    {
        expect($mode == 'plain' || $mode == 'html');
        
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Трябва да има $rec за това $id
        expect($data->rec = $mvc->fetch($id));
        
        // Запомняме стойността на обкръжението 'text'
        $textMode = Mode::get('text');
        
        // Задаваме `text` режим според $mode. singleView-то на $mvc трябва да бъде генерирано
        // във формата, указан от `text` режима (plain или html)
        Mode::set('text', $mode);
        
        // Подготвяме данните за единичния изглед
        $mvc->prepareSingle($data);
        
        // Рендираме изгледа
        $res = $mvc->renderSingle($data)->removePlaces();
        
        Mode::set('text', $textMode);
        
        return $res;
    }
}

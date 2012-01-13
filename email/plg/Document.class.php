<?php

/**
 * Плъгин за документи, които могат да бъдат изпращани по емаил.
 *
 * Плъгина не е задължително условие за да може един документ да се изпрати по емаил. Той
 * предоставя реализация по подразбиране на някои от методите на интерфейса
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       email_DocumentIntf
 */
class email_plg_Document extends core_Plugin
{
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод email_DocumentIntf::getEmailHtml()
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int $id key(mvc=$mvc)
     */
    public function on_AfterGetEmailHtml($mvc, $res, $id)
    {
        $res = $this->getDocumentBody($mvc, $id, 'html');
    }
    
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод email_DocumentIntf::getEmailText()
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int $id key(mvc=$mvc)
     */
    public function on_AfterGetEmailText($mvc, $res, $id)
    {
        $res = $this->getDocumentBody($mvc, $id, 'plain');
    }
    
    
    
    /**
     * HTML или plain text изгледа на документ при изпращане по емайл.
     *
     * Използва single view на мениджъра на документа.
     *
     * @param core_Mvc $mvc мениджър на документа
     * @param int $id първичния ключ на документа - key(mvc=$mvc)
     * @param string $mode `plain` или `html`
     * @access private
     */
    function getDocumentBody($mvc, $id, $mode)
    {
        expect($mode == 'plain' || $mode == 'html');
        
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Трябва да има $rec за това $id
        expect($data->rec = $mvc->fetch($id));
        
        // Запомняме стойността на обкръжението 'printing' и 'text'
        $isPrinting = Mode::get('printing');
        $textMode = Mode::get('text');
        
        // Емулираме режим 'printing', за да махнем singleToolbar при рендирането на документа
        Mode::set('printing', TRUE);
        
        // Задаваме `text` режим според $mode. singleView-то на $mvc трябва да бъде генерирано
        // във формата, указан от `text` режима (plain или html)
        Mode::set('text', $mode);
        
        // Подготвяме данните за единичния изглед
        $mvc->prepareSingle($data);
        
        // Рендираме изгледа
        $res = $mvc->renderSingle($data)->removePlaces();
        
        // Връщаме старата стойност на 'printing'
        Mode::set('printing', $isPrinting);
        Mode::set('text', $textMode);
        
        return $res;
    }
}

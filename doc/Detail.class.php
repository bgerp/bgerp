<?php


/**
 * Клас 'doc_Detail'
 *
 * абстрактен клас за детайл на документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class doc_Detail extends core_Detail
{
    
    
    
    /**
     * Подготвя формата за редактиране
     */
    function prepareEditForm_($data)
    {
        parent::prepareEditForm_($data);
        
        // Добавяме клас, за да може формата да застане до привюто на документа/файла
        if(Mode::is('screenMode', 'wide') ) {
            $data->form->class .= " floatedElement ";
        }
        
        $cId = $this->Master->fetchField($data->form->rec->{$this->masterKey}, 'containerId');
        
        // Рендираме формата
        doc_Linked::showLinkedInForm($data->form, $cId);
        
        return $data;
    }
}
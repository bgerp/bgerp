<?php


/**
 * Клас 'doc_Detail'
 *
 * абстрактен клас за детайл на документи
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class doc_Detail extends core_Detail
{
    /**
     * Подготвя формата за редактиране
     */
    public function prepareEditForm_($data)
    {
        parent::prepareEditForm_($data);
        
        // Добавяме клас, за да може формата да застане до привюто на документа/файла
        if (Mode::is('screenMode', 'wide')) {
            $data->form->class .= ' floatedElement ';
        }
        
        if ($this->Master && $this->masterKey && $data->form->rec->{$this->masterKey}) {
            $cId = $this->Master->fetchField($data->form->rec->{$this->masterKey}, 'containerId');
            
            // Рендираме формата
            doc_Linked::showLinkedInForm($data->form, $cId);
        }
        
        return $data;
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass     $res
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        // Ако е казано да се рендира мастъра под формата
        if($mvc->renderMasterBellowForm !== true) return;
        
        // Ако има контейнер мастъра на детайла
        if($masterContainerId = $mvc->Master->fetchField($data->form->rec->{$mvc->masterKey}, 'containerId')){
            $masterDocument = doc_Containers::getDocument($masterContainerId);
            
            // Рендира се под формата за добавяне/редактиране
            if ($masterDocument->haveRightFor('single')) {
               
                $className = (Mode::is('screenMode', 'wide')) ? ' floatedElement ' : '';
                $data->form->layout = $data->form->renderLayout();
                $tpl = new ET("<div class='preview-holder {$className}'><div class='scrolling-holder'>[#DOCUMENT#]</div></div><div class='clearfix21'></div>");
                $tpl->append($masterDocument->getInlineDocumentBody(), 'DOCUMENT');
                $data->form->layout->append($tpl);
            }
        }
    }
}

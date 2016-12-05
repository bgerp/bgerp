<?php


/**
 * Клас 'incoming_CreateDocumentPlg'
 *
 * Плъгин за добавяне на бутона за създаване на входящи документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class incoming_CreateDocumentPlg extends core_Plugin
{
    
    
    /**
     * Добавя бутон за създаване на входящ документ
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, &$data)
    {
        // id от fileman_Data
        $dataId = fileman_Files::fetchByFh($data->rec->fileHnd, 'dataId');

        // Проверяваме дали има вече създаден документ от файла
//        if ($dRec = incoming_Documents::fetch("#dataId='{$dataId}'")) {
//            
//            if (incoming_Documents::haveRightFor('single', $dRec)) {
//                
//                // Добавяме бутон за разглеждане на single'a на документа, ако имаме права
//                $viewDocUrl = toUrl(array('incoming_Documents', 'single', $dRec->id, 'ret_url' => TRUE), FALSE);
//                $data->toolbar->addBtn('Документ', $viewDocUrl, 'id=btn-docIncomings', 'order=50', 'ef_icon = img/16/page_attach.png');
//            }
//        } else {
//            
            // Добавяме бутон за създаване на входящ документ
//            $createDocUrl = toUrl(array('incoming_Documents', 'add', 'fh' => $data->rec->fileHnd, 'ret_url' => TRUE), FALSE);
//            
//        }

        // Вземаме всички класове, които имплементират интерфейса
        $classesArr = core_Classes::getOptionsByInterface('incoming_CreateDocumentIntf');
    
        // Обхождаме всички класове, които наследяват интерфейса
        foreach ($classesArr as $className) {
            
            // Ако има клас, който може да създава входящ документ
            $arr = $className::canCreate($data->rec);
            if (is_array($arr) && count($arr)) {
                
                // Сетваме стойността
                $canCreate = TRUE;
                
                // Прекъсваме по нататъчното изпълнение
                break;
            }    
        }
      
        // Ако е намерен поне един клас, който да имплементира интерфейса
        if ($canCreate) {
            
            // Създаваме URL за съзваване на бутон
            $createDocUrl = toUrl(array('incoming_Documents', 'showDocMenu', 'fh' => $data->rec->fileHnd, 'ret_url' => TRUE), FALSE);
            
            // Създаваме бутона за създаване на документ
            $data->toolbar->addBtn('Действие', $createDocUrl, 'id=btn-New,order=50,ef_icon=img/16/bullet_arrow_right.png');
        }
    }
}
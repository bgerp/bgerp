<?php 


/**
 * Добавяне на документи към ричтекст
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Log extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'doc_DialogWrapper';
    
    
    /**
     * Заглавие
     */
    public $title = 'Избор на документ';
    
    
    /**
     * Екшън за добавяне на документ от диалогов прозорец
     */
    public function act_AddDocDialog()
    {
        // Очакваме да е логнат потребител
        requireRole('user');
        
        // Задаваме врапера
        Mode::set('wrapper', 'page_Dialog');
        
        // Обект с данните
        $data = new stdClass();
        
        // Вземаме променливите
        $data->callback = $this->callback = Request::get('callback', 'identifier');
        $data->PerPage = Request::get('PerPage', 'int');
        
        // Подготваме страницирането
        $this->prepareAddDocDialogPager($data);
        
        // Подготвяме данните
        $this->prepareAddDocDialog($data);
        
        // Рендираме диалоговия прозорец
        $tpl = $this->renderAddDocDialog($data);
        
        return $this->renderDialog($tpl);
    }
    
    
    /**
     * Подготвя навигацията по страници
     *
     * @param object $data
     */
    public function prepareAddDocDialogPager(&$data)
    {
        // Ако е сетнат
        if ($perPage = $data->PerPage) {
            
            // Трябва да е между 0-100
            if ($perPage > 100) {
                $perPage = 100;
            } elseif ($perPage < 0) {
                $perPage = 0;
            }
        }
        
        // Ако няма
        if (!$perPage) {
            
            // Задаваме стойността
            $perPage = ($this->dialogItemsPerPage) ? $this->dialogItemsPerPage : 8;
        }
        
        // Ако има зададен брой
        if ($perPage) {
            
            // Ако все още не е сетнат
            if (!$data->dialogPager) {
                
                // Сетваме пейджъра
                $data->dialogPager = & cls::get('core_Pager', array('pageVar' => 'P_' . get_called_class()));
            }
            // Добавяме страниците към пейджъра
            $data->dialogPager->itemsPerPage = $perPage;
        }
    }
    
    
    /**
     * Подготвя необходимите данни
     *
     * @param object $data
     */
    public function prepareAddDocDialog(&$data)
    {
        // Вземаме документите от последните 3 нишки за съответния потребител
        $threadsArr = bgerp_Recently::getLastThreadsId(3);
        
        // Вземаме всички документи от нишката, до които потебителя има достъп
        $docThreadIdsArr = doc_Containers::getAllDocIdFromThread($threadsArr, null, 'DESC');
        
        // Масив с документите
        $docIdsArr = array();
        
        // Обхождаме масива с нишките
        foreach ($threadsArr as $threadId => $dummy) {
            
            // Добавяме към документите
            $docIdsArr += (array) $docThreadIdsArr[$threadId];
        }
        
        // Задаваме броя на документите
        $data->itemsCnt = count((array) $docIdsArr);
        
        // Зададаваме лимита за странициране
        $this->setLimitAddDocDialogPager($data);
        
        // Брояча
        $c = 0;
        
        // Масив с всички документи
        $resArr = array();
        
        // Обхождаме документите
        foreach ((array) $docIdsArr as $docId => $docRec) {
            
            // Ако сме в границита на брояча
            if (($c >= $data->dialogPager->rangeStart) && ($c < $data->dialogPager->rangeEnd)) {
                
                // Увеличаваме брояча
                $c++;
            } else {
                
                // Увеличаваме брояча
                $c++;
                
                // Ако сме достигнали горната граница, да се прекъсне
                if ($data->dialogPager->rangeEnd < $c) {
                    break;
                }
                
                // Прескачаме
                continue;
            }
            
            try {
                
                // Вземаме документа
                $document = doc_Containers::getDocument($docId, 'doc_DocumentIntf');
                
                // Вземаме полетата
                $docRow = $document->getDocumentRow();
                
                // Манипулатор на докуемнта
                $handle = '#' . $document->getHandle();
                
                $handleArr = doc_RichTextPlg::parseHandle($handle);
                
                // Ако има само нули, да не се показва
                $hndId = trim($handleArr['id'], '0');
                if (!strlen($hndId)) {
                    $c--;
                    continue;
                }
                
                // Масив за вземане на уникалното id
                $attrId = array();
                
                // Вземаме уникалното id
                ht::setUniqId($attrId);
                
                // id на реда
                $resArr[$docId]['ROW_ATTR']['id'] = $attrId['id'];
                
                // Заглавие на документа
                $resArr[$docId]['title'] = str::limitLen($docRow->title, 55);
                
                // Данни за създаването на документа
                $resArr[$docId]['createdOn'] = doc_Containers::getVerbal($docRec, 'createdOn');
                $resArr[$docId]['createdBy'] = doc_Containers::getVerbal($docRec, 'createdBy');
                $resArr[$docId]['created'] = $resArr[$docId]['createdOn'] . "\n" . $resArr[$docId]['createdBy'];
                $resArr[$docId]['created'] = "<div class='upload-doc-created'>" . $resArr[$docId]['created'] . '</div>';
                
                // Атрибутите на линковете
                $attr = array('onclick' => "flashDocInterpolation('{$attrId['id']}'); if(window.opener.{$data->callback}('{$handle}') != true) self.close(); else self.focus();", 'class' => 'file-log-link');
                
                // Името на документа да се добави към текста, при натискане на линка
                $resArr[$docId]['handle'] = ht::createLink($handle, '#', null, $attr);
                
                // Документа
                $resArr[$docId]['document'] = $resArr[$docId]['handle'] . "\n<div class='addDocSubTitle'>{$resArr[$docId]['title']}</div>";
            } catch (core_exception_Expect $e) {
                continue;
            }
        }
        
        // Добавяме резултатите
        $data->docIdsArr = $resArr;
    }
    
    
    /**
     * Задаваме броя на всички елементи
     *
     * @param oject $data
     */
    public function setLimitAddDocDialogPager(&$data)
    {
        // Задаваме броя на страниците
        $data->dialogPager->itemsCount = $data->itemsCnt;
        
        // Изчисляваме
        $data->dialogPager->calc();
    }
    
    
    /**
     * Извиква се след рендиране на диалоговия прозорец
     *
     * @param object $data
     *
     * @retun core_Et
     */
    public function renderAddDocDialog($data)
    {
        // Вземаме шаблона
        $tpl = getTplFromFile('doc/tpl/DialogAddDoc.shtml');
        
        // Инстанция на класа за създаване на таблици
        $inst = cls::get('core_TableView');
        
        // Полета в таблицата
        $tableCaptionArr = array('document' => 'Документ', 'created' => 'Създадено');
        
        // Вземаме таблицата с попълнени данни
        $tableTpl = $inst->get($data->docIdsArr, $tableCaptionArr);
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($tableTpl, 'tableContent');
        
        // Добавяме заглавието
        $tpl->append(tr($this->title), 'title');
        
        // Заместваме страницирането
        $tpl->append($this->RenderDialogAddDocPager($data), 'pager');
           
        // Добавяме клас към бодито
        $tpl->append('dialog-window', 'BODY_CLASS_NAME');
        
        return $tpl;
    }
    
    
    /**
     * Рендира  навигация по страници
     *
     * @param object $data
     *
     * @return core_ET
     */
    public function renderDialogAddDocPager($data)
    {
        // Ако има странициране
        if ($data->dialogPager) {
            
            // Рендираме
            $res = $data->dialogPager->getHtml();
        }
        
        return $res;
    }
}

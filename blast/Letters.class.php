<?php 


/**
 * Циркулярни писма
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 * @see       https://github.com/bgerp/bgerp/issues/148
 */
class blast_Letters extends core_Master
{
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Циркулярни писма";
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/letters.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'LET';
    
    
    /**
     * Полето "Заглавие" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'subject';
    
    
    /**
     * Заглавие
     */
    var $title = "Циркулярни писма";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, blast';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, blast';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, blast';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin, blast';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, blast';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да праша информационните съобщения?
     */
    var $canBlast = 'admin, blast';
    
    
    /**
     * Какви интерфайси поддържа този мениджър
     */
    var $interfaces = 'email_DocumentIntf';
    
    
    /**
     * Плгънитите и враперите, които ще се използват
     */
    var $loadList = 'blast_Wrapper, plg_State, plg_RowTools, plg_Rejected, plg_Printing, doc_DocumentPlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, subject, listId, date, outNumber, numLetters';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'blast_LetterDetails';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'blast/tpl/SingleLayoutLetters.shtml';
    
    
    /**
     * Данните на получателя
     */
    var $userDetails = NULL;
    
    
    /**
     * Шаблона на писмото
     */
    var $letterTemp = NULL;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('listId', 'key(mvc=blast_Lists, select=title)', 'caption=Списък за разпращане');
        $this->FLD('subject', 'varchar', 'caption=Заглавие, width=100%, mandatory');
        $this->FLD('sender', 'varchar', 'caption=Адресант, width=100%, mandatory');
        $this->FLD('date', 'date', 'caption=Дата');
        $this->FLD('outNumber', 'varchar', 'caption=Изходящ номер, input=none');   //манипулатора на документа //TODO да се реализира
        $this->FLD('text', 'richtext', 'caption=Текст');
        $this->FLD('numLetters', 'int(min=1, max=100)', 'caption=Печат едновременно, mandatory, value=3');
        $this->FLD('template', 'enum(default=По подразбиране, triLeft=3 сгъвания - ляво,
        	triRight=3 сгъвания - дясно)', 'caption=Шаблон');
    }
    
    
    /**
     * Изпълнява се след подготвяне на формата за редактиране
     */
    function on_AfterPrepareEditForm($mvc, $res, &$data)
    {
        //Добавя в лист само списъци на с имейли
        $query = blast_Lists::getQuery();
        $query->where("#keyField = 'names' OR #keyField = 'company'");
        
        while ($rec = $query->fetch()) {
            $files[$rec->id] = $rec->title;
        }
        
        //Ако няма нито един запис, тогава редиректва към станицата за добавяне на списъци.
        if (!$files) {
            
            return new Redirect(array('blast_Lists', 'add'), tr("Нямате добавен списък за циркулярни писма. Моля добавете."));
        }
        
        $form = $data->form;
        
        if (!$form->rec->id) {
            //Слага state = draft по default при нов запис
            $form->setDefault('state', 'draft');
            
            //Ако добавяме нов показваме всички списъци
            $form->setOptions('listId', $files, $form->rec->id);
        } else {
            //Ако редактираме, показваме списъка, който го редактираме
            $file[$form->rec->listId] = $files[$form->rec->listId];
            $form->setOptions('listId', $file, $form->rec->id);
        }
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $subject = $this->getVerbal($rec, 'subject');
        
        //Ако заглавието е празно, тогава изписва сътоветния текст
        if(!trim($subject)) {
            $subject = '[' . tr('Липсва заглавие') . ']';
        }
        
        //Заглавие
        $row->title = $subject;
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        return $row;
    }
    
    
    /**
     * Екшън за принтиране
     */
    function act_Print()
    {
        //Права за работа с екшъна
        requireRole('blast, admin');
        
        //Вземаме id'то на детайла на писмото
        expect($id = Request::get('id', 'int'));
        
        //Вземаме детайла на писмото
        expect($letterDetail = blast_LetterDetails::fetch($id));
        
        //Променяме мода за принтиране
        Mode::set('wrapper', 'tpl_PrintPage');
        Mode::set('printing');
        
        //Преобразуваме keylist полето в масив
        $lettersDetArr = type_Keylist::toArray($letterDetail->listDetailsId);
        
        //Променяме статуса на детайла на затвоворен  и добавяме дата на принтиране
        $newLetterDetail = new stdClass();
        $newLetterDetail->id = $letterDetail->id;
        $newLetterDetail->state = 'closed';
        $newLetterDetail->printedDate = dt::verbal2mysql();
        blast_LetterDetails::save($newLetterDetail);
        
        $letterId = $letterDetail->letterId;
        
        //Проверяваме дали има други непринтирани писма, и ако няма сменяме състоянито на затворено
        $this->closeLetter($letterId);
        
        if (count($lettersDetArr)) {
            
            //Сетва шаблона на писмото
            $this->setTemplates($letterId);
            
            foreach ($lettersDetArr as $letDetId) {
                
                //Сетва детайла за потребителя
                $this->setUserDetails($letDetId);
                
                //Името на мастер шаблона
                $templateFile = ucfirst($this->letterTemp->template);
                
                //Пътя до мастер шаблона
                $fullPath = getFullPath("blast/tpl/{$templateFile}LettersTemplate.shtml");
                
                //Проверява дали е файл
                if (!is_file($fullPath)) {
                    
                    $link = array('doc_Containers', 'list', 'threadId' => $this->letterTemp->threadId);
                    
                    return new Redirect($link, tr("Файлът на шаблона не може да се намери. Моля изберете друг шаблон."));
                }
                 
                //Вземаме съдържанието на мастър шаблона
                $tpl = new ET(tr(file_get_contents($fullPath)));
                
                //Заместваме данните за потребителя в мастър шаблона и ги присоява на променливата
                $allLetters .= $this->tplReplace($tpl);
            }
        }
        
        //Връща резултата
        return $allLetters;
    }
    
    
    /**
     * Взема шаблона на писмото
     */
    function setTemplates($id)
    {
        $this->letterTemp = blast_Letters::fetch("#id = $id");
    }
    
    
    /**
     * Сетваме детайла за потребителите
     */
    function setUserDetails($id)
    {
        if ($this->userDetails['id'] != $id) {
            $listDetails = blast_ListDetails::fetch("#id = $id");
            $this->userDetails['id'] = $id;
            $this->userDetails['data'] = unserialize($listDetails->data);
            
            $this->replace();
        }
    }
    
    
    /**
     * Заместваме данните за потребителя
     */
    function replace()
    {
        expect($this->letterTemp);
        $this->userDetails['text'] = $this->letterTemp->text;
        
        if (count($this->userDetails['data'])) {
            foreach ($this->userDetails['data'] as $key => $value) {
                $this->userDetails['text'] = str_ireplace('[#' . $key . '#]', $value, $this->userDetails['text']);
            }
        }
        
        //Изчистваме richtext' а, и го преобразуваме в чист текстов вид
        $Rich = cls::get('type_Richtext');
        $this->userDetails['text'] = $Rich->richtext2text($this->userDetails['text']);
    }
    
    
    /**
     * Заместваме плейсхолдерите в шаблона
     */
    function tplReplace($tpl)
    {
        //Заместваме текстовата част в мастер шаблона
        $tpl->replace($this->userDetails['text'], 'textPart');
        
        //Заместваме частта за потребителските данни в мастер шаблона
        if (count($this->userDetails['data'])) {
            foreach ($this->userDetails['data'] as $key => $value) {
                $tpl->replace($value, $key);
            }
        }
        
        //Заместваме данните за изпращача в мастър шаблона
        $tpl->replace($this->letterTemp->subject, 'subject');
        $tpl->replace($this->letterTemp->sender, 'sender');
        $tpl->replace($this->letterTemp->date, 'date');
        $tpl->replace($this->letterTemp->outNumber, 'outNumber');
        $tpl->replace(dt::mysql2verbal($this->letterTemp->modifiedOn, "d-m-Y"), 'date');
        //Връщаме шаблона
        return $tpl;
    }
    
    
    /**
     * Ако няма повече записи за принтиране сменяме състоянието на писмото на "спряно"
     */
    function closeLetter($id)
    {
        $details = blast_LetterDetails::fetch("#letterId = '$id' AND #printedDate IS NULL");
        
        //Ако няма нито един запис
        if ($details === FALSE) {
            $newLetter = new stdClass();
            $newLetter->id = $id;
            $newLetter->state = 'stopped';
            blast_Letters::save($newLetter);
        }
    }
    
    
    /**
     * Добавя сътоветени бътони в тулбара, в зависимост от състоянието
     */
    function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $id = $data->rec->id;
        $state = $data->rec->state;
        
        if (($state == 'draft') || ($state == 'stopped')) {
            //Добавяме бутона Активирай, ако състоянието е чернова или спряно
            $data->toolbar->addBtn('Активиране', array($mvc, 'Activation', $id), 'class=btn-activation');
        } elseif ($state == 'active') {
            //Добавяме бутона Спри, ако състояноето е активно или изчакване
            $data->toolbar->addBtn('Спиране', array($mvc, 'Stop', $id), 'class=btn-cancel');
        }
    }
    
    
    /**
     * Екшън за активиране
     */
    function act_Activation()
    {
        //Права за работа с екшъна
        requireRole('blast, admin');
        
        // Очакваме да има такъв запис
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        // Очакваме потребителя да има права за синхронизиране
        $this->haveRightFor('activation', $rec);
        
        ($rec->numLetters) ? $numLetters = $rec->numLetters : $numLetters = 1;
        
        $exist = '';
        
        //Променяме статуса на активен
        $recList = new stdClass();
        $recList->id = $rec->id;
        $recList->state = 'active';
        blast_Letters::save($recList);
        
        //Вземаме всички записи, които са добавени от предишното активиране в детайлите на писмото
        $queryLetterDetail = blast_LetterDetails::getQuery();
        $queryLetterDetail->where("#letterId = '$rec->id'");
        
        while ($recLetterDetail = $queryLetterDetail->fetch()) {
            $exist .= $recLetterDetail->listDetailsId;
        }
        
        //Вземаме всички детайли на листа, които са към избраното писмо
        $queryListDetails = blast_ListDetails::getQuery();
        $queryListDetails->where("#listId = '$rec->listId'");
        
        while ($recListDetail = $queryListDetails->fetch()) {
            
            //Ако нямаме запис с id'то в модела, тогава го добавяме към масива
            if (!type_Keylist::isIn($recListDetail->id, $exist)) {
                $allNewId[$recListDetail->id] = $recListDetail->id;
            }
        }
        
        //Ако имаме поне един нов запис
        if (count($allNewId)) {
            
            //Сортираме масива, като най - отгоре са записити с най - голямо id
            arsort($allNewId);
            
            //Групираме записите по максималния брой, който ще се печатат заедно
            for ($i = 0; $i < count($allNewId); $i = $i + $numLetters) {
                $slicedNewId = array_slice($allNewId, $i, $numLetters, TRUE);
                $keylist = type_Keylist::fromArray($slicedNewId);
                
                //Добавяме новите записи в модела
                $newLetterDetail = new stdClass();
                $newLetterDetail->letterId = $rec->id;
                $newLetterDetail->listDetailsId = $keylist;
                blast_LetterDetails::save($newLetterDetail);
            }
        }
        
        //След като приключи операцията редиректваме към същата страница, където се намирахме
        $link = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
        
        return new Redirect($link, tr("Успешно активирахте писмото."));
    }
    
    
    /**
     * Екшън за спиране
     */
    function act_Stop()
    {
        //Права за работа с екшъна
        requireRole('blast, admin');
        
        //Очаква да има въведено id
        expect($id = Request::get('id', 'int'));
        
        //Очакваме да има такъв запис
        expect($rec = $this->fetch($id));
        
        // Очакваме потребителя да има права за спиране
        $this->haveRightFor('stop', $rec);
        
        //Променяме статуса на спрян
        $recUpd = new stdClass();
        $recUpd->id = $rec->id;
        $recUpd->state = 'stopped';
        blast_Letters::save($recUpd);
        
        $link = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
        
        return new Redirect($link, tr("Успешно спряхте писмото."));
    }
    
    
    /**
     * Добавяне на филтър
     * Сортиране на записите
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        //Добавя филтър за търсене по "Тема" и "Време на започване"
        $data->listFilter->FNC('filter', 'varchar', 'caption=Търсене,input, width=100%, 
                hint=Търсене по "Заглавие" или "Дата"');
        
        $data->listFilter->showFields = 'filter';
        
        $data->listFilter->view = 'horizontal';
        
        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        $filterInput = trim($data->listFilter->input()->filter);
        
        if($filterInput) {
            $data->query->where(array("#subject LIKE '%[#1#]%' OR #date LIKE '%[#1#]%'", $filterInput));
        }
        
        // Сортиране на записите по състояние и дата на създаване
        $data->query->orderBy('state', 'ASC');
        $data->query->orderBy('createdOn', 'DESC');
    }
}
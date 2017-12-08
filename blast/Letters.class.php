<?php 


/**
 * Циркулярни писма
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/148
 */
class blast_Letters extends core_Master
{
    
    
    /**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
    var $defaultFolder = 'Циркулярни писма';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Циркулярно писмо";
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/letter.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Let';
    
    
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
    var $canRead = 'ceo, blast';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo, blast';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo, blast';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo, blast';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo, blast';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo, blast';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да праша информационните съобщения?
     */
    var $canBlast = 'ceo, blast';
    
    
    /**
     * Кой може да променя активирани записи
     */
    var $canChangerec = 'blast, ceo, admin';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'email_DocumentIntf';
    
    
    /**
     * Плъгините и враперите, които ще се използват
     */
    var $loadList = 'blast_Wrapper, plg_State, plg_RowTools, doc_DocumentPlg, bgerp_plg_Blank, change_Plugin, plg_Printing, plg_Clone';
    

    /**
     * Кой може да оттелгя имейла
     */
    protected $canReject = 'ceo, blast';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, subject, listId, numLetters';
    
    
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
     * Групиране на документите
     */
    var $newBtnGroup = "2.3|Циркулярни";
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'ceo, blast';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('listId', 'key(mvc=blast_Lists, select=title)', 'caption=Списък, mandatory');
        $this->FLD('group', 'enum(company=Фирми, personBiz=Лица (Бизнес данни), person=Лица (Частни данни))', 'caption=Група, mandatory, input=none');
        $this->FLD('subject', 'richtext(rows=3, bucket=Blast)', 'caption=Заглавие, mandatory, changable');
        $this->FLD('body', 'richtext(bucket=Blast)', 'caption=Текст, oldFieldName=text, mandatory, changable');
        $this->FLD('numLetters', 'int(min=1, max=100)', 'caption=Печат, mandatory, input=none, hint=Колко писма ще се печатат едновременно');
        $this->FLD('template', 'enum(triLeft=3 части - ляво,
            triRight=3 части - дясно, oneRightUp = 1 част горе - дясно)', 'caption=Шаблон, mandatory, changable');
        
        $this->FLD('attn', 'varchar', 'caption=Адресат->Име, width=100%, changable');
        $this->FLD('position', 'varchar', 'caption=Адресат->Длъжност, width=100%, changable');
        $this->FLD('recipient', 'varchar', 'caption=Адресат->Фирма, width=100%, changable');
        $this->FLD('address', 'varchar', 'caption=Адресат->Адрес, width=100%, changable');
        $this->FLD('pcode', 'varchar', 'caption=Адресат->П. код, width=100%, changable');
        $this->FLD('place', 'varchar', 'caption=Адресат->Град/с, width=100%, changable');
        $this->FLD('country', 'varchar', 'caption=Адресат->Държава, width=100%, changable');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param int $folderId - id на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        // Името на класа
        $coverClassName = strtolower(doc_Folders::fetchCoverClassName($folderId));
        
        // Ако не е папка проект или контрагент, не може да се добави
        if (($coverClassName != 'doc_unsortedfolders') && ($coverClassName != 'crm_groups')) return FALSE;
    }
    
    
    /**
     * Изпълнява се след подготвяне на формата за редактиране
     */
    static function on_AfterPrepareEditForm($mvc, &$res, &$data)
    {
        $form = $data->form;
        
        // Ако има папка
        if ($form->rec->folderId) {
            
            // Корицата на папката
            $coverClassName = doc_Folders::fetchCoverClassName($form->rec->folderId);
            
            // Ако корицата е група
            if (strtolower($coverClassName) == 'crm_groups') {
                
                // Сетваме стойността
                $isGroup = TRUE;
                
                // Задаваме да се показва групата
                $form->setField('group', 'input=input');
                
                // Задаваме да не се показва листа
                $form->setField('listId', 'input=none');
                
                // Вземаме id на корицата
                $coverId = doc_Folders::fetchCoverId($form->rec->folderId);
                
                // Инстация на класа
                $coverClassInst = cls::get($coverClassName);
                
                // Вземаме записа
                $coverRec = $coverClassInst->fetch($coverId);
                
                // Ако няма лица и фирми
                if (!$coverRec->companiesCnt && !$coverRec->personsCnt) {
                    
                    // Редиректваме към групата
                    redirect(array('crm_Groups', 'single', $coverId), FALSE, "|Нямате добавени лица или фирми в групата");
                }
            }
        }
        
        // Ако не е група
        if (!$isGroup) {
            
            //Добавя в лист само списъци на лица и фирми
            $query = blast_Lists::getQuery();
            $query->where("#keyField = 'names' OR #keyField = 'company' OR #keyField = 'uniqId'");
            $query->orderBy("createdOn", 'DESC');
            
            $files = array();
            while ($rec = $query->fetch()) {
                $files[$rec->id] = blast_Lists::getVerbal($rec, 'title');
            }
            
            //Ако няма нито един запис, тогава редиректва към страницата за добавяне на списъци.
            if (!$files) {
                
                redirect(array('blast_Lists', 'add'), FALSE, "|Нямате добавен списък за циркулярни писма");
            }
            
            if (!$form->rec->id) {
                
                //Ако добавяме нов показваме всички списъци
                $form->setOptions('listId', $files, $form->rec->id);
            } else {
                
                $file = array();
                //Ако редактираме, показваме списъка, който го редактираме
                $file[$form->rec->listId] = $files[$form->rec->listId];
                $form->setOptions('listId', $file, $form->rec->id);
            }
        }
        
        //Ако създаваме нов, тогава попълва данните за адресата по - подразбиране
        $rec = $data->form->rec;
        
        // Ако създаваме нов
        if (!$rec->id) {
            
            //Слага state = draft по подразбиране при нов запис
            $form->setDefault('state', 'draft');
        }
        
        // Ако създваме
        if ((!$rec->id) && ($data->action != 'clone')) {
            
            // Задваме стойности по подразбиране
            $rec->recipient = '[#company#]';
            $rec->attn = '[#names#]';
            $rec->country = '[#country#]';
            $rec->pcode = '[#pCode#]';
            $rec->place = '[#place#]';
            $rec->address = '[#address#]';
            $rec->position = '[#position#]';
        }
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        // Вземаме стойността
        $subject = $this->getVerbal($rec, 'subject');
        
        // Превръщаме в стринг
        $subject = strip_tags($subject);
        
        // Максимална дължина
        $subject = str::limitLen($subject, 70);
        
        //Ако заглавието е празно, тогава изписва съответния текст
        if(!trim($subject)) {
            $subject = '[' . tr('Липсва заглавие') . ']';
        }
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = $subject;
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $rec->subject;
        
        return $row;
    }
    
    
    /**
     * Екшън за принтиране
     */
    function act_Print()
    {
        //Вземаме id'то на детайла на писмото
        expect($detailId = Request::get('detailId', 'int'));
        
        //Вземаме детайла на писмото
        expect($letterDetail = blast_LetterDetails::fetch($detailId));
        
        // Шаблона, който ще връщаме
        $tpl = new ET();
        
        // Масив с листовете използвани в детайла
        $listDetIdsArr = type_Keylist::toArray($letterDetail->listDetailsId);
        
        // Обхождаме масива
        foreach ($listDetIdsArr as $listDet) {
            
            // Опции за документа
            $options = new stdClass();
            
            // Вземаме мастера на детайла
            $options->rec = static::fetch($letterDetail->letterId);
            
            // Добавяме listId до кого е
            $options->__toListId = $listDet;
            
            // Пушваме екшъна
            doclog_Documents::pushAction(array('data' => array('toListId' => $listDet, 'listId' => $options->rec->listId)));
            
            // Вземаме документа в xhtml формат
            $res = $this->getDocumentBody($options->rec->id, 'xhtml', $options);
            
            // Добавяме към шаблона
            $tpl->append($res);
            
            // Попваме съответния екшън
            doclog_Documents::popAction();
        }
        
        // Ако състоянито на детайла не е затворен
        // За да запишем датата на първото отпечтване
        if ($letterDetail->state != 'closed') {
            
            //Променяме статуса на детайла на затворен  и добавяме дата на принтиране
            $newLetterDetail = new stdClass();
            $newLetterDetail->id = $letterDetail->id;
            $newLetterDetail->state = 'closed';
            $newLetterDetail->printedDate = dt::verbal2mysql();
            blast_LetterDetails::save($newLetterDetail);
            
            //Проверяваме дали има други непринтирани писма, и ако няма сменяме състоянието на затворено
            $this->closeLetter($letterDetail->letterId);
        }
        
        return $tpl;
    }
    
    
    /**
     * Преди да подготвим данните за имейла, подготвяме rec
     */
    function on_BeforeGetDocumentBody($mvc, &$res, $id, $mode = 'html', $options = NULL)
    {
        
        // Ако има id
        if ($id) {
            
            // Вземаме данните от базата
            $options->rec = static::fetch($id);
        }
        
        // Намираме преполагаемия език на писмото
        core_Lg::push(static::getLanguage($options->rec->body));
        
        // Ако име, тогава да се рендира
        if ($options->__toListId) {
            
            // Ако е лист
            if ($options->rec->listId) {
                
                // Фетчваме детайла за съответния лист
                $detailRec = blast_ListDetails::fetch($options->__toListId);
                
                // Десериализираме данните
                $data = unserialize($detailRec->data);
            } elseif ($options->rec->group) {
                
                // Ако е група
                
                // Ако групата е фирма
                if ($options->rec->group == 'company') {
                    
                    $group = 'company';
                } elseif ($options->rec->group == 'person') {
                    
                    // Ако групата е лице
                    
                    $group = 'person';
                }  elseif ($options->rec->group == 'personBiz') {
                    
                    // Ако групата е бизнес данни от лице
                    
                    $group = 'personBiz';
                }
                
                // Вземаме масива с плейсхолдерите, които ще се заместват
                $data = static::getDataFor($group, $options->__toListId);
            }
            
            // Обхождаме масива с данните
            foreach ((array)$data as $key => $value) {
                
                // Какво ще заместваме
                $search = "[#{$key}#]";
                
                //Заместваме данните
                $options->rec->body = str_ireplace($search, $value, $options->rec->body);
                $options->rec->subject = str_ireplace($search, $value, $options->rec->subject);
                $options->rec->recipient = str_ireplace($search, $value, $options->rec->recipient);
                $options->rec->attn = str_ireplace($search, $value, $options->rec->attn);
                $options->rec->country = str_ireplace($search, $value, $options->rec->country);
                $options->rec->pcode = str_ireplace($search, $value, $options->rec->pcode);
                $options->rec->place = str_ireplace($search, $value, $options->rec->place);
                $options->rec->address = str_ireplace($search, $value, $options->rec->address);
                $options->rec->position = str_ireplace($search, $value, $options->rec->position);
            }
            
            // Добавяме, че разглеждаме детайла
            $options->rec->__detail = TRUE;
        }
    }
    
    
    /**
     * След подготвените данни за имейла, подготвяме rec
     */
    function on_AfterGetDocumentBody($mvc, &$res, $id, $mode = 'html', $options = NULL)
    {
        // Връщаме стария език
        core_Lg::pop();
    }
    
    
    /**
     * Функция която скрива бланката с логото на моята фирма
     * при принтиране ако документа е базиран на
     * "приходен банков документ"
     */
    function renderSingleLayout_(&$data)
    {
        // Ако разглеждаме детайла
        if ($data->rec->__detail) {
            
            // Името на шаблона
            $templateFile = ucfirst($data->rec->template);
            
            // Пътя до файла от пакета
            $filePath = "blast/tpl/{$templateFile}LettersTemplate.shtml";
            
            // Целия път до шаблона
            $fullPath = getFullPath($filePath);
            
            //Проверява дали е файл
            if (!is_file($fullPath)) {
                
                // Редиректваме към сингъла
                redirect(array('blast_Letters', 'single', $data->rec->id), FALSE, "|Файлът на шаблона не може да се намери. Моля изберете друг шаблон");
            }
            
            // Вземаме шаблона
            $tpl = getTplFromFile($filePath);
            
            return $tpl;
        }
        
        // Ако има лист
        if ($data->rec->listId) {
            
            // Добавяме линк към листа
            $data->row->ListLink = ht::createLink($data->row->listId, array('blast_Lists', 'single', $data->rec->listId));
        } elseif ($data->rec->group) {
            
            // Ако е група
            
            // Вземаме корицата
            $coverObj = doc_Folders::getCover($data->rec->folderId);
            
            // Инстанцията на документа
            $docInst = $coverObj->instance;
            
            // id' на документа
            $docId = $coverObj->that;
            
            // Запис на документа
            $docRec = $docInst->fetch($docId);
            
            // Името на групата
            $name = $docInst->getVerbal($docRec, 'name');
            
            // Ако имаме права за сингъл на групата
            if ($docInst->haveRightFor('single', $docRec)) {
                
                // Създаваме бутон към сигъла на групата
                $data->row->GroupLink = ht::createLink($name, array($docInst, 'single', $docId));
            }
        }
        
        // Превръщаме в стринг заглавието
        $data->row->subject = strip_tags($data->row->subject);
        
        // Ако не е детайл рендираме шаблона по подразбиране
        return getTplFromFile($this->singleLayoutFile);
    }
    
    
    /**
     * Ако няма повече записи за принтиране сменяме състоянието на писмото на "затворено"
     */
    function closeLetter($id)
    {
        // Вземаме детайла на писмото което не е принтирано
        $details = blast_LetterDetails::fetch("#letterId = '$id' AND #printedDate IS NULL");
        
        //Ако няма нито един запис
        if ($details === FALSE) {
            
            // Сменяме състоянието на затворено
            $newLetter = new stdClass();
            $newLetter->id = $id;
            $newLetter->state = 'closed';
            blast_Letters::save_($newLetter);  // Ако е прекъсваема, отбелязва с 1 повече принтиране в историята
        }
    }
    
    
    /**
     * Добавя съответните бутони в лентата с инструменти, в зависимост от състоянието
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $id = $data->rec->id;
        $state = $data->rec->state;
        
        if (($state == 'draft') || ($state == 'stopped')) {
            //Добавяме бутона Активирай, ако състоянието е чернова или спряно
            $data->toolbar->addBtn('Активиране', array($mvc, 'Activation', $id, 'ret_url' => TRUE), 'ef_icon = img/16/lightning.png');
        } elseif ($state == 'active') {
            //Добавяме бутона Спри, ако състоянието е активно или изчакване
            $data->toolbar->addBtn('Спиране', array($mvc, 'Stop', $id, 'ret_url' => TRUE),  'ef_icon = img/16/gray-close.png');
        }
    }
    
    
    /**
     * Екшън за активиране
     */
    function act_Activation()
    {
        // Права за работа с екшън-а
        requireRole('blast, ceo');
        
        // Очакваме да има такъв запис
        expect($id = Request::get('id', 'int'));
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Въвеждаме id-то (и евентуално други silent параметри, ако има)
        $form->input(NULL, 'silent');
        
        // Очакваме да имаме такъв запис
        expect($rec = static::fetch($form->rec->id));
        
        // Очакваме потребителя да има права за активиране
        $this->haveRightFor('activation', $rec);
        
        // Въвеждаме съдържанието на полетата
        $form->input('numLetters');
        
        // По подразбиране да е избрана стойността от записа
        $form->setDefault('numLetters', $rec->numLetters);
        
        // Вземаме ret_url
        $retUrl = getRetUrl();
        
        // URL' то където ще се редиректва при отказ
        $retUrl = ($retUrl) ? ($retUrl) : (array($this, 'single', $id));
        
        // Ако формата е изпратена без грешки, то активираме, ... и редиректваме
        if($form->isSubmitted()) {
            
            // Ако броя на отпечатваният не отговаря на броя записан в модела
            if ($rec->numLetters && $rec->numLetters != $form->rec->numLetters) {
                
                // Вземаме вербалната стойност
                $numLetters = static::getVerbal($rec, 'numLetters');
                
                // Сетваме предупреждение
                $form->setWarning('numLetters', "Ще важи само за новите записи. За старите е: {$numLetters}");
            }
        }
        
        $coverArr = array();
        
        // Ако формата е изпратена успешно
        if($form->isSubmitted()) {
            
            // Броя на пимсмата, които ще се печатат едновремнно
            $numLetters = $form->rec->numLetters;
            
            // Очакваме да е зададение
            expect($numLetters);
            
            // Записваме новия брой
            $nRec = new stdClass();
            $nRec->id = $form->rec->id;
            $nRec->numLetters = $numLetters;
            static::save($nRec, 'numLetters');
            
            //Променяме статуса на активен
            $recList = new stdClass();
            $recList->id = $rec->id;
            $recList->state = 'active';
            blast_Letters::save($recList, 'id, state');
            
            // Вземаме всички записи, които са добавени от предишното активиране в детайлите на писмото
            $queryLetterDetail = blast_LetterDetails::getQuery();
            $queryLetterDetail->where("#letterId = '$rec->id'");
            
            while ($recLetterDetail = $queryLetterDetail->fetch()) {
                
                // Добавяме keylist'а към стринга
                $exist .= $recLetterDetail->listDetailsId;
            }
            
            $allNewId = array();
            
            // Ако е лист
            if ($rec->listId) {
                
                // Вземаме всички детайли на листа, които са към избраното писмо и не са спрени
                $queryListDetails = blast_ListDetails::getQuery();
                $queryListDetails->where("#listId = '$rec->listId'");
                $queryListDetails->where("#state != 'stopped'");
                $queryListDetails->orderBy('id', 'ASC');
                
                // Обхождаме откритите резултата
                while ($recListDetail = $queryListDetails->fetch()) {
                    
                    // Ако нямаме запис с id'то в модела
                    if (!keylist::isIn($recListDetail->id, $exist)) {
                        
                        // Добавяме към масива
                        $allNewId[$recListDetail->id] = $recListDetail->id;
                    }
                }
            } elseif ($rec->group) {
                
                // Ако е група
                
                // id на корицата
                $coverId = doc_Folders::fetchCoverId($rec->folderId);
                
                // Добавяме в масив
                $coverArr[$coverId] = $coverId;
                
                // Ако е фирма
                if ($rec->group == 'company') {
                    
                    // Извличаме записите за фирмата
                    $gQuery = crm_Companies::getQuery();
                } else {
                    
                    // Ако е лице
                    
                    // Извличаме записите за лицето
                    $gQuery = crm_Persons::getQuery();
                }
                
                // Всички, които са от тази група и не са оттеглени
                $gQuery->likeKeylist('groupList', $coverArr);
                $gQuery->where("#state != 'rejected'");
                
                // Обхождаме откритите резултати
                while ($gRec = $gQuery->fetch()) {
                    
                    // Ако нямаме запис с id'то в модела
                    if (!keylist::isIn($gRec->id, $exist)) {
                        
                        // Добавяме към масива
                        $allNewId[$gRec->id] = $gRec->id;
                    }
                }
            }
            
            $cntAllNewId = count($allNewId);
            
            // Ако имаме поне един нов запис
            if ($cntAllNewId) {
                
                // Сортираме масива, като най - отгоре са записити с най - малко id
                asort($allNewId);
                
                // Групираме записите по максималния брой, който ще се печатат заедно
                for ($i = 0; $i < $cntAllNewId; $i = $i + $numLetters) {
                    $slicedNewId = array_slice($allNewId, $i, $numLetters, TRUE);
                    $keylist = keylist::fromArray($slicedNewId);
                    
                    //Добавяме новите записи в модела
                    $newLetterDetail = new stdClass();
                    $newLetterDetail->letterId = $rec->id;
                    $newLetterDetail->listDetailsId = $keylist;
                    blast_LetterDetails::save($newLetterDetail);
                }
            }
            
            // След като приключи операцията редиректваме към същата страница, където се намирахме
            return new Redirect($retUrl, "|Успешно активирахте писмото");
        }
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'numLetters';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png');
        
        // Добавяме титлата на формата
        $form->title = "Активиране на писмо за печат";
        $subject = $this->getVerbal($rec, 'subject');
        
        // Превръщаме в стринг
        $subject = strip_tags($subject);
        
        // Вземаме датата
        $date = dt::mysql2verbal($rec->createdOn);
        
        // Добавяме във формата информация, за да знаем за кое писмо става дума
        $form->info = new ET ('[#1#]', tr("|*<b>|Писмо|*<i style='color:blue'>: {$subject} / {$date}</i></b>"));
        
        // Опциите за създаване на тялот
        $options = new stdClass();
        
        // Ако е листа
        if ($rec->listId) {
            
            // Вземаме всички детайли, които не са спряни от съответния лист
            $query = blast_ListDetails::getQuery();
            $query->where("#listId = '{$rec->listId}'");
            $query->where("#state != 'stopped'");
            
            // Обхождаме получените резултати
            while ($lRec = $query->fetch()) {
                
                // Ако имаме права за single
                if (blast_ListDetails::haveRightFor('single', $lRec)) {
                    
                    // Добавяме listId до кого е
                    $options->__toListId = $lRec->id;
                    
                    // Спираме по нататъшното изпълнение
                    break;
                }
            }
        } elseif ($rec->group) {
            
            // Ако е група
            
            // Вземаме id на корицата
            $coverId = doc_Folders::fetchCoverId($rec->folderId);
            
            // Добавяме в масива
            $coverArr[$coverId] = $coverId;
            
            // Ако групата е фирма
            if ($rec->group == 'company') {
                
                // Вземаме записите за фирмата
                $gQuery = crm_Companies::getQuery();
            } else {
                
                // Ако е лице
                
                // Вземаме записите за лицето
                $gQuery = crm_Persons::getQuery();
            }
            
            // Вземаме всички заиси от групата, които не са оттеглени
            $gQuery->likeKeylist('groupList', $coverArr);
            $gQuery->where("#state != 'rejected'");
            
            // Обхождаме получените резултати
            while ($gRec = $gQuery->fetch()) {
                
                // Ако имаме права за сингула на документа
                if ($gQuery->mvc->haveRightFor('single', $gRec)) {
                    
                    // Добавяме listId до кого е
                    $options->__toListId = $gRec->id;
                    
                    // Прекратяваме изпълнението на програмата
                    break;
                }
            }
        }
        
        // Записите
        $options->rec = $rec;
        
        // Вземаме документа в xhtml формат
        $res = $this->getDocumentBody($options->rec->id, 'xhtml', $options);
        
        // Получаваме изгледа на формата
        $tpl = $form->renderHtml();
        
        // Добавяме превю на първото писмо, което ще печатаме
        $preview = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Примерно писмо") . "</b></div><div class='scrolling-holder'>[#BLAST_LET#]</div></div>");
        
        // Добавяме към шаблона
        $preview->append($res, 'BLAST_LET');
        
        // Добавяме изгледа към главния шаблон
        $tpl->append($preview);
        
        // Рендираме шаблона и връщаме резултата
        return static::renderWrapping($tpl);
    }
    
    
    /**
     * Екшън за спиране
     */
    function act_Stop()
    {
        // Права за работа с екшън-а
        requireRole('blast, ceo');
        
        // Очаква да има въведено id
        expect($id = Request::get('id', 'int'));
        
        // Очакваме да има такъв запис
        expect($rec = $this->fetch($id));
        
        // Очакваме потребителя да има права за спиране
        $this->haveRightFor('stop', $rec);
        
        // Променяме статуса на спрян
        $recUpd = new stdClass();
        $recUpd->id = $rec->id;
        
        // Състоянието да е спряно
        $recUpd->state = 'stopped';
        
        // Ако записа е успешен
        if (blast_Letters::save($recUpd, 'state')) {
            
            // Вземаме детайлите, които не са печатани в съответното писмо
            $dQuery = blast_LetterDetails::getQuery();
            $dQuery->where("#letterId = '$id'");
            $dQuery->where("#printedDate IS NULL");
            
            // Обикаляме резултатите
            while ($dRec = $dQuery->fetch()) {
                
                // Изтриваме записите
                blast_LetterDetails::delete($dRec->id);
            }
        }
        
        // Вземаме ret_url
        $retUrl = getRetUrl();
        
        // URL' то където ще се редиректва при отказ
        $retUrl = ($retUrl) ? ($retUrl) : (array($this, 'single', $id));
        
        // След като приключи операцията редиректваме към същата страница, където се намирахме
        return new Redirect($retUrl, "|Успешно спряхте писмото");
    }
    
    
    /**
     * Добавяне на филтър
     * Сортиране на записите
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        //Добавя филтър за търсене по "Тема" и "Време на започване"
        $data->listFilter->FNC('filter', 'varchar', 'caption=Търсене, input, width=100%, 
                hint=Търсене по "Заглавие"');
        
        $data->listFilter->showFields = 'filter';
        
        $data->listFilter->view = 'horizontal';
        
        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $filterInput = trim($data->listFilter->input()->filter);
        
        if($filterInput) {
            $data->query->where(array("#subject LIKE '%[#1#]%'", $filterInput));
        }
        
        // Сортиране на записите по състояние и дата на създаване
        $data->query->orderBy('state', 'ASC');
        $data->query->orderBy('createdOn', 'DESC');
    }
    
    
    /**
     * Изпълнява се след въвеждането на даните от формата
     * Проверява дали сме въвели несъществуващ шаблон
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        // Ако формата е изпраена успешно
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            // Ако е група
            if (isset($rec->group)) {
                
                // Ако има папка
                if ($rec->folderId) {
                    
                    // Сетваме, че е група
                    $isGroup = TRUE;
                    
                    // Вземаме id на корицата
                    $coverId = doc_Folders::fetchCoverId($rec->folderId);
                    
                    // Името на корицата
                    $coverClassName = doc_Folders::fetchCoverClassName($form->rec->folderId);
                    
                    // Инстанция на корицата
                    $coverClassInst = cls::get($coverClassName);
                    
                    // Записа на корицата
                    $coverRec = $coverClassInst->fetch($coverId);
                    
                    // Ако е групата е фирма
                    if ($rec->group == 'company') {
                        
                        // Ако няма фирми в групата
                        if (!$coverRec->companiesCnt) {
                            
                            // Сетваме грешка
                            $form->setError('group', 'Няма въведени фирми в групата');
                        }
                    } else {
                        
                        // Ако е лице
                        
                        // Ако няма лица в групата
                        if (!$coverRec->personsCnt) {
                            
                            // Сетваме грешка
                            $form->setError('group', 'Няма въведени лица в групата');
                        }
                    }
                }
            }
        }
        
        // Ако сме субмитнали формата
        if ($form->isSubmitted()) {
            
            // Масив с всички записи
            $recArr = (array)$form->rec;
            
            if (!$isGroup) {
                
                // id' то на листа, от който се вземат данните на потребителя
                if (!$listId = $form->rec->listId) {
                    
                    // Вземаме от записа
                    $listId = $mvc->fetchField($form->rec->id, 'listId');
                }
                
                // Вземаме всички полета, които ще се заместват
                $listsRecAllFields = blast_Lists::fetchField($listId, 'allFields');
                
                //Вземаме всички имена на полетата на данните, които ще се заместват
                preg_match_all('/(^)([^=]+)/m', $listsRecAllFields, $allFieldsArr);
                
                // Вземаме плейсхолдерите
                $onlyAllFieldsArr = $allFieldsArr[2];
            } else {
                
                // Вземаме плейсхолдерите за групата
                $onlyAllFieldsArr = static::getGroupPlaceholders($rec->group);
            }
            
            // Вземаме Относно и Съобщение
            $bodyAndSubject = $recArr['body'] . ' ' . $recArr['subject'];
            
            // Масив с данни от плейсхолдера
            $nRecArr = array();
            $nRecArr['recipient'] = $recArr['recipient'];
            $nRecArr['attn'] = $recArr['attn'];
            $nRecArr['country'] = $recArr['country'];
            $nRecArr['pcode'] = $recArr['pcode'];
            $nRecArr['place'] = $recArr['place'];
            $nRecArr['address'] = $recArr['address'];
            $nRecArr['position'] = $recArr['position'];
            
            // Обикаляме всички останали стойности в масива
            foreach ($nRecArr as $field) {
                
                // Всички данни ги записваме в една променлива
                $allRecsWithPlaceHolders .= ' ' . $field;
            }
            
            // Създаваме шаблон
            $tpl = new ET($allRecsWithPlaceHolders);
            
            // Вземаме всички шаблони, които се използват
            $allPlaceHolder = $tpl->getPlaceHolders();
            
            // Шаблон на Относно и Съобщение
            $bodyAndSubTpl = new ET($bodyAndSubject);
            
            // Вземаме всички шаблони, които се използват
            $bodyAndSubPlaceHolder = $bodyAndSubTpl->getPlaceHolders();
            
            $fieldsArr = array();
            
            // Обхождаме масива с плейсхолдерите
            foreach ((array)$onlyAllFieldsArr as $field) {
                
                // Тримваме полето
                $field = trim($field);
                
                // Името в долен регистър
                $field = strtolower($field);
                
                // Добавяме в масива
                $fieldsArr[$field] = $field;
            }
            
            // Премахваме дублиращите се плейсхолдери
            $allPlaceHolder = array_unique($allPlaceHolder);
            
            $warningPlaceHolderArr = array();
            
            //Търсим всички полета, които сме въвели, но ги няма в полетата за заместване
            foreach ($allPlaceHolder as $placeHolder) {
                
                // Плейсхолдера в долен регистър
                $placeHolderL = strtolower($placeHolder);
                
                // Ако плейсхолдера го няма във листа
                if (!$fieldsArr[$placeHolderL]) {
                    
                    // Добавяме към съобщението за предупреждение
                    $warning .= ($warning) ? ", {$placeHolder}" : $placeHolder;
                    
                    // Стринг на плейсхолдера
                    $placeHolderStr = "[#" . $placeHolder . "#]";
                    
                    // Добавяме го в масива
                    $warningPlaceHolderArr[$placeHolderStr] = $placeHolderStr;
                }
            }
            
            // Премахваме дублиращите се плейсхолдери
            $bodyAndSubPlaceHolder = array_unique($bodyAndSubPlaceHolder);
            
            //Търсим всички полета, които сме въвели, но ги няма в полетата за заместване
            foreach ($bodyAndSubPlaceHolder as $placeHolder) {
                
                // Плейсхолдера в долен регистър
                $placeHolderL = strtolower($placeHolder);
                
                // Ако плейсхолдера го няма във листа
                if (!$fieldsArr[$placeHolderL]) {
                    
                    // Добавяме към съобщението за грешка
                    $error .= ($error) ? ", {$placeHolder}" : $placeHolder;
                }
            }
            
            // Показваме грешка, ако има шаблони, които сме въвели в повече в Относно и Съощение
            if ($error) {
                $form->setError('*', "|Шаблоните, които сте въвели ги няма в БД|*: {$error}");
            }
            
            // Показваме предупреждение за останалите шаблони
            if ($warning) {
                
                // Сетваме грешката
                $form->setWarning('*', "|Шаблоните, които сте въвели ги няма в БД|*: {$warning}");
                
                // При игнориране на грешката
                if (!$form->gotErrors()) {
                    
                    // Обхождаме масива с стойност
                    foreach ($nRecArr as $field => $val) {
                        
                        // Премахваме всички плейсхолдери, които не се използват
                        $val = str_ireplace((array)$warningPlaceHolderArr, '', $val);
                        
                        // Добавяме към записа
                        $form->rec->{$field} = $val;
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща масив с плейсхолдери за съответната група
     *
     * @param sting $group - Групата
     *
     * @return array $arr - Масив с плейсхолдери
     */
    static function getGroupPlaceholders($group)
    {
        $arr = array();
        
        switch ($group) {
            
            // Ако е фирма
            case 'company' :
                $arr['name'] = 'company';
                $arr['country'] = 'country';
                $arr['pCode'] = 'pCode';
                $arr['place'] = 'place';
                $arr['address'] = 'address';
                break;
                
                // Ако е бизнес данни от лице
            case 'personBiz' :
                
                $arr['salutation'] = 'salutation';
                $arr['name'] = 'names';
                $arr['buzCompanyId'] = 'company';
                $arr['buzPosition'] = 'position';
                $arr['company_country'] = 'country';
                $arr['company_pCode'] = 'pCode';
                $arr['company_place'] = 'place';
                $arr['company_address'] = 'address';
                
                break;
                
                // Ако е лични данни от лице
            case 'person' :
                
                $arr['salutation'] = 'salutation';
                $arr['name'] = 'names';
                $arr['country'] = 'country';
                $arr['pCode'] = 'pCode';
                $arr['place'] = 'place';
                $arr['address'] = 'address';
                
                break;
            
            default :
            ;
            break;
        }
        
        return $arr;
    }
    
    
    /**
     * Връща масив с данни за заместване за съответното писмо
     *
     * @param string $group - Групата
     * @param integer $id - id' то на записа от съответната група
     *
     * @return array $data - Масив с данни
     */
    static function getDataFor($group, $id)
    {
        // Вземама масива с плейсхолдерите за съответната група
        $placeArr = static::getGroupPlaceholders($group);
        
        // Ако е фирма
        if ($group == 'company') {
            
            // Вземаме данните за фирмата
            $rec = crm_Companies::fetch($id);
            
            // Класа на групата
            $groupClass = 'crm_Companies';
        } else {
            
            //Ако е лице
            
            // Вземаме данните за лицето
            $rec = crm_Persons::fetch($id);
            
            // Класа на групата
            $groupClass = 'crm_Persons';
        }
        
        $data = array();
        
        // Обхождаме масива с плейсхолдерите
        foreach ((array)$placeArr as $field => $place) {
            
            // Позициата на долната черта
            $pos = mb_stripos($field, '_');
            
            // Ако няма долна черта
            if ($pos === FALSE) {
                
                // Добавяме в масива плейсхолдера и стойността
                $data[$place] = $groupClass::getVerbal($rec, $field);
            } else {
                
                // Типа
                $type =  mb_substr($field, 0, $pos);
                
                // Полето
                $nField = mb_substr($field, $pos + 1);
                
                // Ако е фирма
                if ($type = 'company') {
                    
                    // Ако има бизнес данни
                    if ($rec->buzCompanyId && $companyRec->id != $rec->buzCompanyId) {
                        
                        // Вземаме записите за фирмата
                        $companyRec = crm_Companies::fetch($rec->buzCompanyId);
                    }
                    
                    // Ако има фирма
                    if ($companyRec) {
                        
                        // Вземаме стойността на съответното поле
                        $placeVal = crm_Companies::getVerbal($companyRec, $nField);
                    }
                    
                    // Добавяме стойността в полето
                    $data[$place] = $placeVal;
                }
            }
        }
        
        return $data;
    }
    
    
    /**
     * Намира предполагаемия език на текста
     *
     * @param text $body - Текста, в който ще се търси
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език
     */
    static function getLanguage($body)
    {
        // Вземаме езиак
        $lg = i18n_Language::detect($body);
        
        //Ако езика не е добър
        if (!$lg || !core_Lg::isGoodLg($lg)) {
            
            // Използваме английски
            $lg = 'en';
        }
        
        return $lg;
    }
    
    
    /**
     * @param blast_Letters $mvc
     * @param array $res
     * @param integer $id
     * @param integer $userId
     * @param object $data
     */
    public static function on_BeforeGetLinkedDocuments($mvc, &$res, $id, $userId = NULL, $data = NULL)
    {
        $toListId = $data->toListId;
        
        if (!$toListId) return ;
        
        // Вземаме данните от базата
        $letterRec = $mvc->fetch($id);
        
        expect($letterRec);
        
        $data = array();
        
        // Ако е лист
        if ($letterRec->listId) {
            
            // Фетчваме детайла за съответния лист
            $detailRec = blast_ListDetails::fetch($toListId);
            
            // Десериализираме данните
            $data = unserialize($detailRec->data);
        } elseif ($letterRec->group) {
            
            // Ако е група
            
            $group = $letterRec->group;
            
            // Вземаме масива с плейсхолдерите, които ще се заместват
            $data = static::getDataFor($group, $toListId);
        }
        
        // Ако не е зададено id използваме текущото id на потребите (ако има) и в краен случай id на активиралия потребител
        if (!$userId) {
            $userId = core_Users::getCurrent();
            
            if ($userId <= 0) {
                $userId = $mvc->getContainer($id)->activatedBy;
            }
        }
        
        core_Users::sudo($userId);
        
        // За всички полета опитваме да извлечем прикаченте файлове
        foreach ((array)$data as $name => $value) {
            $attachedDocs = (array)doc_RichTextPlg::getAttachedDocs($value);
            
            if (count($attachedDocs)) {
                $attachedDocs = array_keys($attachedDocs);
                $attachedDocs = array_combine($attachedDocs, $attachedDocs);
                
                $res = array_merge($attachedDocs, (array)$res);
            }
        }
        
        core_Users::exitSudo();
    }
}

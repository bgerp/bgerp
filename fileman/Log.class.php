<?php

/**
 * 
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Log extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Последни файлове";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'fileman_Wrapper, fileman_DialogWrapper, plg_Sorting';
    
    
    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'lastOn';
    
    
    /**
     * Броя на записите при странициране в диалоговия прозорец
     */
    const DIALOG_LIST_ITEMS_PER_PAGE = 5;
    
    
    /**
     * 
     */
    var $listFields = 'fileId=Линк, action, lastOn';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('fileId', 'key(mvc=fileman_Files, select=fileHnd)', 'caption=Манипулатор,notNull');
        $this->FLD('fileName', 'varchar', 'caption=Файл');
        $this->FLD('fileSize', "fileman_FileSize", 'caption=Размер');
        $this->FLD('action', 'enum(upload=Качване, preview=Разглеждане, rename=Преименуване)', 'caption=Действие');
        $this->FLD('userId', 'user', 'caption=Потребител');
        $this->FLD('lastOn', 'datetime(format=smartTime)', 'caption=Последно');
        
        $this->FLD("dataId", "key(mvc=fileman_Data)", 'caption=Данни');
        
        $this->setDbUnique('dataId,userId');
        
        $this->setDbIndex('lastOn');
        $this->setDbIndex('userId,fileId');
    }
    
    
    /**
     * Обновява информацията за използването на файла
     * 
     * @param mixed $fileHnd - Запис от fileman_Files или манипулатор на файла
     * @param string $action - Съответнотното действие: upload, preview
     * @param integer $userId - id на потребитля
     */
    static function updateLogInfo($fileHnd, $action, $userId=NULL)
    {
        // Ако не е подадено id на потребител
        if (!$userId) {
            
            // Вземаме id' то на текущия потребител
            $userId = core_Users::getCurrent();
        }
        
        // Ако е подаден запис всмето манипулатор
        if (is_object($fileHnd)) {
            
            // Използваме го
            $fRec = $fileHnd;
        } else {
            
            // Ако е подаден манипулатор
            
            // Вземаме записа
            $fRec = fileman_Files::fetchByFh($fileHnd);
        }
        
        // Обновяваме времето на последно използване на данните
        fileman_Data::updateLastUse($fRec->dataId);

        // Ако системния потребител, връщаме
        if ($userId < 1) return FALSE;
        
        // Ако няма манипулатор на файла
        if (!$fRec->fileHnd) return FALSE;
        
        // Вземаме предишния запис
        $nRec = static::fetch(array("#dataId = '[#1#]' AND #userId=[#2#]", $fRec->dataId, $userId));
        
        // Ако този файл не е бил използван от съответния потребител
        if (!$nRec) {
            
            // Създаваме обект
            $nRec = new stdClass();
            
            // Добавяме id на потребителя
            $nRec->userId = $userId;
        }
        
        // Добавяме id на файла
        $nRec->fileId = $fRec->id;
        
        // Вземаме meta данните
        $meta = fileman::getMeta($fRec->fileHnd);
        
        // Добавяме размера
        $nRec->fileSize = $meta['size'];
        
        // Добавяме съответното действие
        $nRec->action = $action;
        
        // Добавяме текущото време
        $nRec->lastOn = dt::now();
        
        // Добавяме името на файла
        $nRec->fileName = $fRec->name;
        
        // Добавяме данните за файла
        $nRec->dataId = $fRec->dataId;
        
        // Упдейтваме записа
        static::save($nRec, NULL, 'UPDATE');
        
        // Връщаме записа
        return $nRec;
    }
    
    
    /**
     * Екшъна за показване на диалоговия прозорец за добавяне на файл 
     */
    function act_Dialog()
    {
        // Вземаме id' то на кофата
        $bucketId = Request::get('bucketId', 'int');
        
        // Вземаме callBack'а
        $callback = Request::get('callback', 'identifier');
        
        // Сетваме нужните променливи
        Mode::set('dialogOpened', TRUE);
        Mode::set('callback', $callback);
        Mode::set('bucketId', $bucketId);
        
        // Вземаме шаблона
        $tpl = $this->getTpl();
        
        // Рендираме диалоговия прозорец
//        return $this->renderDialog($tpl);
        
        // Връщаме шаблона
        return $tpl;
    }
    
    
    /**
     * Връща шаблона за диалоговия прозорец
     * 
     * @param Core_Et $tpl
     */
    function renderDialog_($tpl)
    {
        
        return $tpl;
    }
    
    
    /**
     * Връща шаблона за добавяне на файл
     */
    function getTpl()
    {
        // Вземаме шаблона за листовия изглед
        return $this->act_List();
    }
    
    
    /**
     * Извиква се преди подготовката на колоните ($data->listFields)
     * 
     * @param core_Mvc $mvc
     * @param object $res
     * @param object $data
     */
    static function on_BeforePrepareListFields($mvc, &$res, $data)
    {
        // Ако е отворен в диалоговия прозорец
        if (Mode::get('dialogOpened')) {
            
            // Нулираме, ако е имало нещо
            $data->listFields = array();
            
            // Задаваме, кои полета да се показва
            $data->listFields['FileIcon'] = '-';
            $data->listFields['Name'] = 'Име';
            
            // Да не се извикат останалите
            return FALSE;
        } else {
            
            // Ако сме в листовия изглед
            // Добавяме плъгина
            $mvc->load('plg_GroupByDate');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     * 
     * @param core_Mvc $mvc
     * @param object $data
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $query = $data->query;
        
        // Ако е задеден потребител
        if (($userId = core_Users::getCurrent()) && ($userId > 0)) {
            
            // Вземаме записите само за избрания потребител
            $query->where("#userId = '{$userId}'");
        }
        
        // По - новите да са по - напред
        $query->orderBy("#lastOn", 'DESC');
        
        // Ако е отворен в диалоговия прозорец
        if (Mode::get('dialogOpened')) {
            
            // Ако има кофа
            if ($bucketId = Mode::get('bucketId')) {
                
                // Вземаме записите за кофата
                $bucketRec = fileman_Buckets::fetch($bucketId);
                
                // Вземаме максималния размер за файл в кофата
                $query->where("#fileSize < '{$bucketRec->maxSize}'");
                
                // Ако има позволени разширения в кофата
                if ($extensions = $bucketRec->extensions) {
                    
                    // Вземаме масива с позволените разширения в кофата
                    $extArr = fileman_Buckets::getAllowedExtensionArr($extensions);
                    
                    // Ако има масив
                    if ($extArr) {
                        
                        // Флаг, който указва, че за първи път сме в цикъла
                        $first = TRUE;
                        
                        // Обхождаме масива
                        foreach ((array)$extArr as $ext) {
                            
                            // Добавяме условие, да се вземат всички файлове, които завършавт с посоченото разширение
                            $query->where(array("LOWER(#fileName) LIKE '%.[#1#]'", $ext), !$first);
                            
                            // Ако сме за първи път
                            if ($first) {
                                
                                // Сваляме флага
                                $first = FALSE;
                            }
                        }
                    }
                }
            }
        }
        
        // Ако не е отворен диалоговия прозорец
        if (!Mode::get('dialogOpened') || Mode::is('screenMode', 'wide')) {
            
            // Вземаме формата за филтриране
            $form = $data->listFilter;
            
            // Добавяме променливата, която се взема от URL' то
            // Необходимо е, защото не се предава при филтриране
            $form->FNC('Protected', 'varchar', 'input=hidden, silent');
            
            // В хоризонтален вид
            $form->view = 'horizontal';
            
            // Добавяме бутон за филтриране
            $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
            
            $form->FNC('search', 'varchar', 'caption=Търсене,input,silent,recently');
            
            // Показваме полетата
            $form->showFields = 'search, Protected';
            
            // Инпутваме стойностите
            $form->input('search, Protected', 'silent');
            
            // Последно избраното търсене, да е по-подразбиране
            if (is_null($form->rec->search)) {
                if (Mode::get('dialogOpened')) {
                    $form->rec->search = Mode::get('filemanLogFileLogSearch');
                }
            } else {
                Mode::setPermanent('filemanLogFileLogSearch', $form->rec->search);
            }
            
            // Ако има текст за търсене
            if($search = trim($form->rec->search)) {
                $data->query->EXT('searchKeywords', 'fileman_Data', 'externalKey=dataId');
                plg_Search::applySearch($search, $data->query, 'searchKeywords');
            }
        }
    }
    
    
    /**
     * Изпълнява се преди подготвяне на страниците
     * 
     * @param core_Mvc $mvc
     * @param object $res
     * @param object $data
     */
    function on_BeforePrepareListPager($mvc, &$res, $data)
    {
        // Ако е отворен в диалоговия прозорец
        if (Mode::get('dialogOpened')) {
            
            // Задаваме броя на елементите в страница
            $mvc->listItemsPerPage = static::DIALOG_LIST_ITEMS_PER_PAGE;
        }
    }
    
    
    /**
     * Изпълнява се преди подготвяне на страниците
     * 
     * @param core_Mvc $mvc
     * @param object $res
     * @param object $data
     */
    function on_AfterPrepareListPager($mvc, &$res, $data)
    {
        // Ако е отворен в диалоговия прозорец
        if (Mode::get('dialogOpened')) {
            
            // Последно избраната страница да е отворена по-подразбиране
            
            $pageVar = $data->pager->getPageVar(get_called_class());
            
            if ($pageVar) {
                $page = Request::get($pageVar);
                
                if (is_null($page)) {
                    Request::push(array($pageVar => Mode::get('filemanLogLastOpenedPage')));
                } else {
                    Mode::setPermanent('filemanLogLastOpenedPage', $page);
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     * 
     * @param core_Mvc $mvc
     * @param object $data
     */
    static function on_AfterPrepareListRows($mvc, $res, &$data)
    {
        // Ако е отворен в диалоговия прозорец
        if (Mode::get('dialogOpened')) {
            
            // Масива с дейсвията
            $actionArr = array('upload' => 'Качен', 'preview' => 'Разгледан');
            
            $callback = Mode::get('callback');
            
            // Обхождаме записите
            foreach ((array)$data->recs as $key => $rec) {
                
                // Взеамем текста на съответното действие
                $action = tr($actionArr[$data->recs[$key]->action]);
                
                // Вземаме вербалната дата
                $date = $mvc->getVerbal($data->recs[$key], 'lastOn');
                
                // Името на файла
                $fileName = $rec->fileName;
                
                // Манипулатора на файла
                $fh = fileman_Files::fetchField($data->recs[$key]->fileId, 'fileHnd');
                
                // Вербалното име на файла
                $fileNameVerb = static::getVerbal($rec, 'fileName');
                
                // Съкращаваме дължината на името
                $fileNameVerb = str::limitLen($fileNameVerb, 30);
                
                // Хифенираме името на файла
                $fileNameVerb = hyphen_Plugin::getHyphenWord($fileNameVerb);
                
                // Масив за вземане на уникалното id
                $attrId = array();
                
                // Вземаме уникалното id
                ht::setUniqId($attrId);
                
                // Атрибутите на линковете
                $attr = array('onclick' => "flashDocInterpolation('{$attrId['id']}'); if(window.opener.{$callback}('{$fh}','{$fileName}') != true) self.close(); else self.focus();", "class" => "file-log-link");
                
                // Името на файла да е линк с посочените атрибути
                $fileNameLink = ht::createLink($fileNameVerb, '#', NULL, $attr); 
                
                // Взеамаме иконата с линка за съответния файл
                $img = static::getIcon($fh);
                
                // Добавяме иконата
                $data->rows[$key]->FileIcon = ht::createLink($img, '#', NULL, $attr);
                
                // Взeмаме иконата с линка към сингъла на файла
                $nameStr = $fileNameLink . "<br /><span class='fileman-log-action-date'>" . $action . ' ' . $date . "</span>";
                
                // Взemame иконата с линка към сингъл на файла
                $nameLink = ht::createLinkRef($nameStr, array('fileman_Files', 'single', $fh), NULL, array('target' => '_blank', 'title' => 'Към изгледа на файла'));
                
                // Добавяме името на файла
                $data->rows[$key]->Name = $nameLink;
                
                // Добавяме id в атрибутите на файла
                $data->rows[$key]->ROW_ATTR['id'] = $attrId['id'];
            }
            
            // Да не се извикат останалите
            return FALSE;
        } else {
            
            // Обхождаме масива
            foreach ((array)($data->recs) as $id => $rec) {
            	
                // Вземаме линка към сингъла на файла
                $data->rows[$id]->fileId = fileman_Files::getLinkById($rec->fileId);
            }
        }
    }


    /**
     * Връща иконата за подадения файл
     * 
     * @param fileHnd $fh - Манупулатор на файла
     * @param integer $size - Размер на файла
     */
    static function getIcon($fh, $size=48)
    {
         // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fh);
        
        // Вземаме името на файла
        $fileName = $fRec->name;
        
        // Вземаме разширението на файла
        $ext = mb_strtolower(fileman_Files::getExt($fileName));
        
        // Ако може да се генерира thumbnail
        if (thumb_Img::isAllowedForThumb($fh)) {
            
            $imgInst = new thumb_Img($fh, $size, $size, 'fileman');
            
            // Вземаме файла
            $img = $imgInst->createImg();
        } else {
            
            //Иконата на файла, в зависимост от разширението на файла
            $icon = "fileman/icons/{$size}/{$ext}.png";
            
            //Ако не можем да намерим икона за съответното разширение
            if (!is_file(getFullPath($icon))) {
                
                // Използваме иконата по подразбиране
                $icon = "fileman/icons/{$size}/default.png";
            }
            
            // Вземаме изображението
            $img = ht::createElement('img', array('src' => sbf($icon, '', TRUE), 'width' => $size, 'height' => $size));
        }
        
        return $img;
    }
    
    
    /**
     * Извиква се преди подготовката на титлата в списъчния изглед
     * 
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param object $data
     */
    static function on_BeforeRenderListTitle($mvc, &$tpl, $data)
    {
        // Ако е отворен в диалоговия прозорец
        if (Mode::get('dialogOpened')) {
            
            // Да не се слага заглавието
            
            // Да не се извикат останалите
            return FALSE;
        }
    }
    
    
    /**
     * След рендиране на шаблона
     * 
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param object $data
     */
    static function on_AfterRenderListLayout($mvc, $tpl, $data)
    {
        // Ако е отворен в диалоговия прозорец
        if (Mode::get('dialogOpened')) {
            
            // Заменяме плейсхолдера за страницира с празен стринг
            $tpl->replace('', 'ListPagerBottom');
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката' на мениджъра
     * 
     * @param core_Mvc $mvc
     * @param string $res
     * @param core_Et $tpl
     * @param object $data
     */
    function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data=NULL)
    { 
        // Ако е отворен в диалоговия прозорец
        if (Mode::get('dialogOpened')) {
            
            // Рендираме опаковката от друго място
            $res = $mvc->renderDialog($tpl);
            
            // Да не се извикат останалите и да не се рендира "опаковката"
            return FALSE;
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     * 
     * За съвместимост със стари бази
     * @todo Може да се премахне
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // Вземаме всички записи, които няма dataId
        $query = $mvc->getQuery();
        $query->where("#dataId IS NULL");
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            
            // Флаг, дали да се премахне
            $delete = FALSE;
            
            // Ако има fileId вземаме dataId
            if (($rec->fileId) && ($dataId = fileman_Files::fetchField($rec->fileId, 'dataId'))) {
                
                // Ако няма такъв запис в този модел за съответния потребител
                if (!static::fetch("#dataId = {$dataId} AND #userId = {$rec->userId}")) {
                    
                    // Добавяме dataId
                    $rec->dataId = $dataId;
                    static::save($rec);
                    
                    // Увеличаваме брояча
                    $cnt++;
                } else {
                    
                    // Да се изтрие ако записа няма да е уникален
                    $delete = TRUE;
                }
            } else {
                
                // Да се изтрие, ако няма да има такива данни
                $delete = TRUE;
            }
            
            // Ако флага е вдигнат
            if ($delete) {
                
                // Изтриваме
                static::delete($rec->id);
            }
        }
        
        // Ако има добавени записи
        if ($cnt) {
            
            // Показваме броя им
            $res .= "<li>Добавени {$cnt} 'dataId'";
        }
    }
 }
 

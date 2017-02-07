<?php


/**
 * Клас 'cms_GalleryImages' - картинки в галерията
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_GalleryImages extends core_Manager
{
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'user';
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'user';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'user';
    
    
    /**
     * Заглавие
     */
    var $title = 'Картинки в Галерията';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Картинка от галерията";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = "plg_RowTools2,cms_Wrapper,plg_Created, cms_GalleryTitlePlg, plg_Search, cms_GalleryDialogWrapper";
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'fileman_GalleryImages';
    
    
    /**
     * Полета за изглед
     */
    var $listFields = "id,title=Код,src,groupId,createdOn,createdBy";
    
    
    /**
     * Името на полето, което ще се използва от плъгина
     * @see cms_GalleryTitlePlg
     */
    var $galleryTitleFieldName = 'title';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 20;
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, src';
    
    
    /**
     * Брой елементи при показване на страница в диалогов прозорец
     */
    var $galleryListItemsPerPage = 5;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('src', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Изображение,mandatory, width=100%');
        $this->FLD('groupId', 'key(mvc=cms_GalleryGroups,select=title)', 'caption=Група,mandatory, width=100%');
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие, width=100%');
        $this->FLD('style', 'varchar(128)', 'caption=Стил, width=100%');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Ако създаваме нов
        if (!$data->form->rec->id) {
            
            // id на групата по подразбиране
            $grId = cms_GalleryGroups::getDefaultGroupId();
            
            // Да е избрана
            $data->form->setDefault('groupId', $grId);
        }
    }
    
    
    /**
     * допълнение към подготовката на вербално представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields)
    {
        $tArr = array(128, 128);
        $mArr = array(600, 450);
            
        $Fancybox = cls::get('fancybox_Fancybox');
        
        if($rec->src) {
            $row->src = $Fancybox->getImage($rec->src, $tArr, $mArr, $rec->title);
        }
        
        $row->{$mvc->galleryTitleFieldName} = "[img=#" . $rec->{$mvc->galleryTitleFieldName} . "]";
    }
    
    
    /**
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('groupSearch', 'key(mvc=cms_GalleryGroups,select=title, allowEmpty)', 'caption=Група,input,silent,autoFilter');
        $data->listFilter->FNC('usersSearch', 'users(rolesForAll=user, rolesForTeams=user)', 'caption=Потребител,input,silent,autoFilter');
        
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search, usersSearch, groupSearch';
        
        $data->listFilter->input('groupSearch, usersSearch', 'silent');
        
        // По - новите добавени да са по - напред
        $data->query->orderBy("#createdOn", "DESC");
        
        // Ако не е избран потребител по подразбиране
        if(!$data->listFilter->rec->usersSearch) {
            
            // Да е текущия
            $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
        }
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
            // Ако филтъра е по потребители
            if($filter->usersSearch) {
                
    			// Ако се търси по всички и има права ceo
    			if ((strpos($filter->usersSearch, '|-1|') !== FALSE) && (haveRole('ceo'))) {
    			    // Търсим всичко
                } else {
                    
                    // Масив с потребителите
                    $usersArr = type_Keylist::toArray($filter->usersSearch);
                    
                    // Търсим по създатели
                    $data->query->orWhereArr('createdBy', $usersArr);
                }
    		}
    		
    		// Ако се търси по група
    		if ($filter->groupSearch) {
    		    
    		    // Търсим групата
    		    $data->query->where(array("#groupId = '[#1#]'", $filter->groupSearch));
    		}
        }
        
        $orToPrevious = FALSE;
        
        // Да се показват групите, които са споделени до някоя от групите на потребителя
        if (cms_GalleryGroups::restrictRoles($data->query, $orToPrevious, 'groupRoles')) {
            
            // Външно поле за ролите към групата
            $data->query->EXT('groupRoles', 'cms_GalleryGroups', 'externalName=roles,externalKey=groupId');
            $orToPrevious = TRUE;
        }
        
        // Или до потребителя
        if (cms_GalleryGroups::restrictSharedTo($data->query, $orToPrevious, 'groupSharedTo')) {
            
            // Външно поле за споределените потребителие към групата
            $data->query->EXT('groupSharedTo', 'cms_GalleryGroups', 'externalName=sharedTo,externalKey=groupId');
            $orToPrevious = TRUE;
        }
        
        // Или са създадени от потребителя
        if (cms_GalleryGroups::restrictCreated($data->query, $orToPrevious, 'groupCreatedBy')) {
            
            // Външно поле за създателите на групата
            $data->query->EXT('groupCreatedBy', 'cms_GalleryGroups', 'externalName=createdBy,externalKey=groupId');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако има запис и потребител
        if ($rec->id && $userId) {
            
            // Ако редактираме, изтриваме, добавяме или разглеждаме сингъла
            if ($action == 'edit' || $action == 'delete') {
                
                // Ако не е 'ceo', трябва да има достъп до групата, за да редактира картина от нея
                if (!haveRole('ceo')) {
                    
                    if ($rec->groupId && !cms_GalleryGroups::haveRightFor('usegroup', $rec->groupId)) {
                        $requiredRoles = 'no_one';
                    } else if ($action == 'delete') {
                        
                        // Всеки потребител може да изтрива само създадените от него
                        if ($rec->createdBy != $userId) {
                            $requiredRoles = 'no_one';
                        }
                    }
                }
            }
        }
    }
    
    
	/**
     * Връща URL за добавяне на документи
     * 
     * @param core_Mvc $mvc
     * @param core_Et $res
     * @param string $callback
     */
    static function getUrLForAddImg($callback)
    {
        // Защитаваме променливите
        Request::setProtected('callback');
        
        // Създаваме URL' то
        return toUrl(array('cms_GalleryImages', 'addImg', 'callback' => $callback));
    }
    
    
    /**
     * Екшън за редирект към необходимия екшън за добавяне на изображение в ричтекст поле
     */
    function act_AddImg()
    {
        // Избрания текст
        $selText = Request::get('selText');
        
        // Други допълнителни данни
        $callback = Request::get('callback');
        
        // Защитаваме променливите
        Request::setProtected('callback');
        
        // Ако има избран текст
        if ($selText) {
            
            // Шаблон за намиране на изображение към галерията
            $pattern = "/^\[img=\#(?'text'[^\]]*)\]\s*$/i";
            
            preg_match($pattern, $selText, $match);
            
            // Ако има окрит вид на галерията
            if($match['text']) {
                
                // Текста за търсене
                $searchText = $match['text'];
            } else {
                
                // Ако щаблона не открие текста, но все пак има текст
                // Премахваме последната скоба, ако има такава
                $searchText = rtrim($selText, ']');
            }
            
            // Опитваме се да вземем id на записа от вида
            $id = $this->fetchField(array("#{$this->galleryTitleFieldName} = '[#1#]'", $searchText));
        } 
        
        // Ако има id и имаме права за редакция
        if ($id && $this->haveRightFor('edit', $id)) {
            
            // URL-то да сочи към диалоговия прозорец за редактиране на изображението
            $url = array($this, 'addImgDialog', $id, 'callback' => $callback);
        } else {
            
            // Името на класа
            $class = 'cms_GalleryImages';
            
            // Вземаме екшъна
            $act = 'addImgDialog';
            
            // URL-то да сочи към съответния екшън и клас
            $url = array($class, $act, 'callback' => $callback);
        }
        
        return new Redirect($url);
    }
    
    
    /**
     * Екшън за добавяне на изображение в ричтекст поле
     */
    function act_AddImgDialog()
    {
        // Очакваме да е има права за добавяне
        $this->requireRightFor('add');
        
        // id на записа
        $id = Request::get('id', 'int');
        
        // Ако има id
        if ($id) {
            
            // Изискваме да има права за редактиране
            $this->requireRightFor('edit', $id);
            
            // Вземаме записа
            $rec = static::fetch($id);
            
            // Очакваме да има такъв запис
            expect($rec);
        }
        
        // Задаваме врапера
        Mode::set('wrapper', 'page_Dialog');
        
        // Обект с данните
        $data = new stdClass();
        
        // Вземаме променливите
        $callback = $this->callback = Request::get('callback', 'identifier');
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Добавяме нужните полета
        $form->FNC('imgFile', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Изображение, mandatory");
        $form->FNC('imgGroupId', 'key(mvc=cms_GalleryGroups,select=title)', 'caption=Група, mandatory');
        $form->FNC('imgTitle', 'varchar(128)', 'caption=Заглавие');
        
        // Ако има запис
        if ($rec) {
            
            // Сетваме стойностите на променливите
            $form->setDefault('imgFile', $rec->src);
            $form->setDefault('imgGroupId', $rec->groupId);
            $form->setDefault('imgTitle', $rec->title); 
        } else {
            
            // По подразбиране да е избрана id на група
            $grId = cms_GalleryGroups::getDefaultGroupId();
            $form->setDefault('imgGroupId', $grId);
        }
        
        // Въвеждаме полето
        $form->input('imgFile, imgGroupId, imgTitle, id', TRUE);
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
            
            // Манипулатор на файла
            $fileHnd = $form->rec->imgFile;
            
            // Ако няма запис
            if (!$rec) {
                
                $rec = new stdClass();
            } else if ($rec->id) {
                
                // Ако сме променили нещо при редактирането
                if (($rec->title != $form->rec->imgTitle) || ($rec->groupId != $form->rec->imgGroupId) || ($rec->src != $form->rec->imgFile)) {
                    
                    $rec = new stdClass();
                }
            }
            
            // Добавяме стойностите
            $rec->title = $form->rec->imgTitle;
            $rec->groupId = $form->rec->imgGroupId;
            $rec->src = $form->rec->imgFile;
            
            // Записваме
            $this->save($rec);
            
            // Вземаме полето
            $title = $this->galleryTitleFieldName;
            
            // Очакваме да има стойност
            expect($rec->$title);
            
            // Създаваме шаблона
            $tpl = new ET();
            
            // Добавяме скрипта, който ще добави надписа и ще затвори прозореца
            $tpl->append("if(window.opener.{$callback}('{$rec->$title}') == true) self.close(); else self.focus();", 'SCRIPTS');
            
            return $tpl;
        }
        
        // Заглавие на шаблона
        $form->title = "Добавяне на картинка";
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'imgFile, imgGroupId, imgTitle';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Добави', 'save', 'ef_icon = img/16/add.png');
        
        // URL за връщане
        $retUrl = getRetUrl();
        
        if ($retUrl) {
            
            // При отказ да се върне към предишния запис
            $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png');
        } else {
            
            // При отказ да се затвори прозореца
            $form->toolbar->addFnBtn('Отказ', 'self.close();', 'ef_icon = img/16/close-red.png');
        }
        
        // Рендираме формата
        $tpl = $form->renderHtml();
        
        // Добавяме бутона за затваряне
        $tpl->append("<button onclick='javascript:window.close();' class='dialog-close'>X</button>");
        
        // Рендираме опаковката
        $tpl = $this->renderDialog($tpl);
        
        return $tpl;
    }
    
    
    /**
     * Екшън за показване на диалогов прозорец за с изображенията в галерията
     */
    function act_GalleryDialog()
    {
        // Очакваме да е има права за добавяне
        $this->requireRightFor('add');
        
        // Обект с необходомите данни
        $data = new stdClass();
        
        // Създаваме заявката
        $data->query = $this->getQuery();
        
        // Подготвяме филтъра
        $this->prepareListFilter($data);
        
        // По - новите добавени да са по - напред
        $data->query->orderBy("#createdOn", "DESC");
        
        // Функцията, която ще се извика
        $data->callback = $this->callback = Request::get('callback', 'identifier');
        
        // Титлата на формата
        $data->title = 'Изображения в галерия';
        
        // Брой елементи на страница
        $this->listItemsPerPage = $this->galleryListItemsPerPage;
        
        // Подготвяме навигацията по страници
        $this->prepareListPager($data);
        
        // Подготвяме записите за таблицата
        $this->prepareListRecs($data);
        
        // Подготвяме редовете на таблицата за диалога
        $this->prepareGalleryDialogListRows($data);
        
        // Рендираме изгледа
        $tpl = $this->renderGalleryDialogList($data);
        
        // Задаваме врапера
        Mode::set('wrapper', 'page_Dialog');
        
        // Добавяме бутона за затваряне
        $tpl->append("<button onclick='javascript:window.close();' class='dialog-close'>X</button>");
        
        // Рендираме опаковката
        $tpl = $this->renderDialog($tpl);
        
        return $tpl;
    }
    
    
    /**
     * Подготвя вербалните стойности на записите, които ще се показват в диалоговия прозорец на галерията
     * 
     * @param stdObject $data
     */
    function prepareGalleryDialogListRows($data)
    {   
        // Защитаваме променливите
        Request::setProtected('callback');
        
        // Ако има записи
        if($data->recs && count($data->recs)) {
            
            // Обхождаме записите
            foreach($data->recs as $id => $rec) {
                
                // Добавяме вербалните представяния
                $data->rows[$id] = $this->recToVerbal($rec, 'src, groupId');
                // Защитаваме променливите
                
                // Ако има права за редактиране
                if ($this->haveRightFor('edit', $data->recs[$id])) {
                    
                    // Изображение за редактиране
                    $img = ht::createElement('img', array('src' => sbf('img/16/edit-icon.png', '')));
                    
                    // Линк, който сочи към добавяне на изображения в диалов прозрец, с данните на това изображение
                    $data->rows[$id]->tools = ht::createLink($img, array($this, 'addImgDialog', $id, 'callback' => $data->callback, 'ret_url' => TRUE));
                }
                
                if ($data->recs[$id]->{$this->galleryTitleFieldName}) {
                    
                    // Добавяме id на реда
                    $idRow = 'rowGallery' . $id;
                    $data->rows[$id]->ROW_ATTR['id'] = $idRow;
                    
                    // Атрибутите на линковете
                    $attr = array('onclick' => "flashDocInterpolation('{$idRow}'); if(window.opener.{$data->callback}('{$data->recs[$id]->{$this->galleryTitleFieldName}}') != true) self.close(); else self.focus();", "class" => "file-log-link");
                    
                    // Изображение за добавяне
                    $imgAdd = ht::createElement('img', array('src' => sbf('img/16/add1-16.png', '')));
                    
                    // Линк, който добавя изображението в рич едита
                    $data->rows[$id]->tools .= ht::createLink($imgAdd, '#', NULL, $attr);
                }
            }
        }
        
        return $data;
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    function renderGalleryDialogList($data)
    {
        // Рендираме общия лейаут
        $tpl = new ET("
          <div>
                [#ListTitle#]
                
                <div class='top-pager'> 
                	[#ListPagerTop#]
                </div>
                <div class='galleryListTable'>
                	[#ListTable#]
        		</div>
            </div>
          ");
        
        // Попълваме титлата
        $tpl->append($this->renderListTitle($data), 'ListTitle');
        
        // Попълваме горния страньор
        $tpl->append($this->renderListPager($data), 'ListPagerTop');
        
        // Попълваме таблицата с редовете
        $tpl->append($this->renderGalleryDialogListTable($data), 'ListTable');
        
        return $tpl;
    }
    
    
    /**
     * Рендира таблицата за показване в диалоговия прозорец на галерията
     * 
     * @param stdObject $data
     */
    function renderGalleryDialogListTable($data)
    {
        // Инстанция на класа
        $table = cls::get('core_TableView', array('mvc' => $this));
        
        // Полетата, които ще се показва
        $listFields = array('tools' => '✍', 'src' => 'Картинка', 'groupId' => 'Група');    
        
        // Рендираме таблицата
        $tpl = $table->get($data->rows, $listFields);
        
        return new ET("<div class='listRows'>[#1#]</div>", $tpl);
    }
    
    
    /**
     * Подготвя полето за заглавие
     * 
     * @param object $rec
     * @see cms_GalleryTitlePlg
     */
    function prepareRecTitle(&$rec)
    {
        // Името на полето
        $titleField = $this->galleryTitleFieldName;
        
        // Ако не е зададено заглавието
        if (!$rec->{$titleField} && $rec->src) {
            
            // Определяме заглавието от името на файла
            $rec->{$titleField} = fileman_Files::fetchByFh($rec->src, 'name');
        }
    }
}

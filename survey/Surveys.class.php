<?php


/**
 * Модел "Анкети"
 *
 *
 * @category  bgerp
 * @package   survey
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class survey_Surveys extends core_Master
{
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, cms_ObjectSourceIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Анкети';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, survey_Wrapper, plg_Printing,plg_Clone,
     	  doc_DocumentPlg, bgerp_plg_Blank, doc_ActivatePlg, cms_ObjectPlg, doc_plg_SelectFolder';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Анкета';
    
    
    /**
     * Икона на единичния обект
     */
    public $singleIcon = 'img/16/text_list_bullets.png';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     *  Брой елементи на страница
     */
    public $listItemsPerPage = '15';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'survey,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'survey,ceo';
    
    
    /**
     * Кой има право да чете?
     */
    public $canSummarise = 'user';
    
    
    /**
     * Детайли на анкетата
     */
    public $details = 'survey_Alternatives';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Ank';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'survey, ceo';
    
    
    /**
     * Файл за единичен изглед
     */
    public $singleLayoutFile = 'survey/tpl/SingleSurvey.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '18.2|Други';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title,enddate,summary,state';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие, mandatory');
        $this->FLD('description', 'text(rows=2)', 'caption=Описание, mandatory');
        $this->FLD('enddate', 'date(format=d.m.Y)', 'caption=Краен срок,mandatory');
        $this->FLD('summary', 'enum(internal=Вътрешно,personal=Персонално,public=Публично)', 'caption=Обобщение,mandatory');
        $this->FLD('state', 'enum(draft=Чернова,active=Публикувана,rejected=Оттеглена)', 'caption=Състояние,input=none');
        $this->FLD('userBy', 'enum(browser=Браузър,ip=IP)', 'caption=Разграничаване на потребителите->Признак');
    }
    
    
    /**
     * Модификации по формата
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Премахваме бутона за активация от формата ! за да не активираме
        // анкета без въпроси
        $data->form->toolbar->removeBtn('activate');
    }
    
    
    /**
     * Обработки след като изпратим формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $today = dt::now();
            if ($form->rec->enddate <= $today) {
                $form->setError('enddate', 'Крайния срок на анкетата не е валиден');
            }
            
            $form->rec->state = 'draft';
        }
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->number = static::getHandle($rec->id);
        
        if ($fields['-single']) {
            if (static::isClosed($rec->id)) {
                $row->closed = tr('Анкетата е затворена');
            }
        }
        
        if ($fields['-list']) {
            if (static::isClosed($rec->id)) {
                $row->title = $row->title . " - <span style='color:darkred'>" .tr('затворена'). '</span>';
            }
            
            $txt = explode("\n", $rec->description);
            if (countR($txt) > 1) {
                $row->description = $txt[0] . ' ...';
            }
        }
    }
    
    
    /**
     * Метод проверяващ дали дадена анкета е отворена
     *
     * @param int id - id на анкетата
     *
     * @return bool $res - затворена ли е анкетата или не
     */
    public static function isClosed($id)
    {
        expect($rec = static::fetch($id), 'Няма такъв запис');
        ($rec->enddate <= dt::now()) ? $res = true : $res = false;
        
        return $res;
    }
    
    
    /**
     * Модификация на ролите, които могат да видят избраната тема
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        //  Кой може да обобщава резултатите
        if ($action == 'summarise' && isset($rec->id)) {
            
            //Можем да Обобщим резултатите само ако анкетата не е чернова
            if ($rec->state == 'active' && !static::isClosed($rec->id)) {
                switch ($rec->summary) {
                    case 'internal':
                        $res = $mvc->canSummarise;
                        break;
                    case 'personal':
                        if ($rec->createdBy != core_Users::getCurrent()) {
                            $res = 'no_one';
                        }
                        break;
                    case 'public':
                        $res = 'every_one';
                        break;
                }
            } else {
                $res = 'no_one';
            }
        }
        
        if ($action == 'activate' && isset($rec)) {
            if (static::alternativeCount($rec->id) == 0 ||
                $rec->enddate <= dt::now()) {
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * Обработка на SingleToolbar-a
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $summary = Request::get('summary');
        $url = getCurrentUrl();
        if ($mvc::haveRightFor('summarise', $data->rec->id) && !$summary) {
            $url['summary'] = 'ok';
            $data->toolbar->addBtn('Обобщение', $url, 'ef_icon=img/16/chart_pie.png, title=Виж резултатите от анкетата');
        }
        
        if ($summary && $data->rec->state == 'active') {
            unset($url['summary']);
            $data->toolbar->addBtn('Анкета', $url, 'ef_icon=img/16/text_list_bullets.png, title=Обратно към анкетата');
            $printBtnName = 'btnPrint_' . get_called_class() . '_' . $data->rec->id;
            if ($data->toolbar->buttons[$printBtnName]) {
                $data->toolbar->buttons[$printBtnName]->url['summary'] = $summary;
            }
        }
        
        if ($data->rec->state != 'draft' && survey_Votes::haveRightFor('read')) {
            $votesUrl = array('survey_Votes', 'list', 'surveyId' => $data->rec->id);
            $data->toolbar->addBtn('Гласувания', $votesUrl, null, array('title' => 'Преглед на гласовете', 'ef_icon' => 'img/16/Business-Survey-icon.png'));
        }
    }
    
    
    /**
     * Колко въпроса има дадена анкета
     *
     * @param int $id
     *
     * @return int - Броя въпроси които има анкетата
     */
    public static function alternativeCount($id)
    {
        expect(static::fetch($id), 'Няма такава анкета');
        $altQuery = survey_Alternatives::getQuery();
        $altQuery->where(array('#surveyId = [#1#]', $id));
        
        return $altQuery->count();
    }
    
    
    /**
     * Пушваме css и js файла
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        $tpl->push('survey/tpl/css/styles.css', 'CSS', true);
        $tpl->push(('survey/js/scripts.js'), 'JS', true);
        jquery_Jquery::run($tpl, 'surveyActions();', true);
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        $title = $this->recToverbal($rec, 'title')->title;
        $row = new stdClass();
        $row->title = $this->singleTitle . ' "' . $title . '"';
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $rec->title;
        
        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public static function getHandle($id)
    {
        $rec = static::fetch($id);
        $self = cls::get(get_called_class());
        
        return $self->abbr . $rec->id;
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        if (Mode::is('printing') || Mode::is('text', 'xhtml')) {
            $tpl->removeBlock('header');
        }
    }
    
    
    /**
     * След извличане на името на документа за показване в RichText-а
     */
    protected static function on_AfterGetDocNameInRichtext($mvc, &$docName, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        $docName = tr($mvc->singleTitle) . ': "' . str::limitLen($rec->title, 64) . '"';
    }
    
    
    /**
     * Генерираме ключа за кеша
     * Интерфейсен метод
     *
     * @param survey_Surveys          $mvc
     * @param NULL|FALSE|string $res
     * @param NULL|int          $id
     * @param object            $cRec
     *
     * @see doc_DocumentIntf
     */
    public static function on_AfterGenerateCacheKey($mvc, &$res, $id, $cRec)
    {
        if ($res !== false) {
            $res = md5($res . '|' . Request::get('summary'));
        }
    }
}

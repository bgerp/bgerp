<?php


/**
 * Коментари на статиите
 *
 *
 * @category  bgerp
 * @package   blogm
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class blogm_Comments extends core_Detail
{
    /**
     * Заглавие на страницата
     */
    public $title = 'Блог коментари';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Коментар';
    
    
    /**
     * Зареждане на необходимите плъгини
     */
    public $loadList = 'plg_RowTools2, plg_Created, blogm_Wrapper, plg_State, plg_Sorting, plg_LastUsedKeys, plg_RowNumbering, plg_Rejected';
    
    
    /**
     * Поле за лентата с инструменти
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Полета за изглед
     */
    public $listFields = 'name, email, web, ip, brid, userDelay, spamRate, comment=@, createdOn=Създаване||Created';
    
    
    /**
     * Кой може да изтрива коментари
     */
    public $canRead = 'cms, ceo, admin, blog';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin, cms, blog';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, admin, cms, blog';
    
    
    /**
     * Кой има достъп до Спосъка с коментати
     */
    public $canWrite = 'cms, ceo, admin,blog';
    
    
    /**
     * Кой има достъп до Спосъка с коментати
     */
    public $canDelete = 'cms, ceo, admin,blog';


    /**
     * Кой може да публикува чакащ коментар
     */
    public $canActivate = 'cms, ceo, admin, blog';
    
    
    /**
     * Мастър ключ към статиите
     */
    public $masterKey = 'articleId';
    
    
    /**
     * По колко реда от резултата да показва на страница в детайла на документа
     * Стойност '0' означава, че детайла няма да се странира
     */
    public $listItemsPerPage = 200;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('articleId', 'key(mvc=blogm_Articles, select=title)', 'caption=Тема, input=hidden, silent');
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory, width=65%,placeholder=Името ви (задължително)');
        $this->FLD('email', 'email(64)', 'caption=Имейл, mandatory, width=65%,placeholder=Имейлът ви (задължително)');
        $this->FLD('web', 'url(72)', 'caption=Сайт, width=65%,placeholder=Вашият сайт или блог');
        $this->FLD('comment', 'richtext(bucket=' . blogm_Articles::FILE_BUCKET . ')', 'caption=Коментар,mandatory,placeholder=Въведете вашия коментар тук');
        $this->FLD('state', 'enum(pending=Чакащ,active=Публикуван,closed=Затворен,rejected=Оттеглен)', 'caption=Състояние,mandatory');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none, oldFieldName=browserId');
        $this->FLD('ip', 'ip', 'caption=IP,input=none');
        $this->FLD('userDelay', 'time', 'caption=Спам->Закъснение,input=none');
        $this->FLD('spamRate', 'int', 'caption=Спам->Рейтинг,input=none');
        
        $this->setDbIndex('ip');
        $this->setDbIndex('brid');
        $this->setDbIndex('state,createdOn');
    }
    
    
    /**
     * Моделна функиця за подготовка на данните, необходими за показването на
     * коментарите към дадена статия и форма за добавянето на нов
     */
    public function prepareComments_($data)
    {
        $query = self::getQuery();
        
        $me = cls::get(get_called_class());
        $fields = $me->selectFields('');
        $fields['-article'] = true;

        // Нерегистриран посетител не вижда профилите и документите от системата
        $isAnonymous = !core_Users::getCurrent('id', false);
        if ($isAnonymous) {
            $me->setFieldTypeParams('comment', array('hndToLink' => 'no', 'nickToLink' => 'no'));
        }

        // Търсим brid в сесията
        $data->brid = log_Browsers::getBrid();
        
        $query->where(array("#articleId = {$data->articleId} AND (#state = 'active' OR #brid = '[#1#]')", $data->brid));

        $data->commentsRecs = $data->commentsRows = array();
        while ($rec = $query->fetch()) {
            $data->commentsRecs[$rec->id] = $rec;
            // Иконата за отсъствие след ника издава статуса на служителя
            $vRec = $rec;
            if ($isAnonymous) {
                $vRec = clone $rec;
                $vRec->comment = preg_replace_callback(rtac_Plugin::$pattern, function ($m) {
                    return $m['pre'] . $m['nick'];
                }, $rec->comment ?? '');
            }
            $data->commentsRows[$rec->id] = self::recToVerbal($vRec, $fields);
            
            // Непубликуваните коментари се виждат само от автора им, с етикет за състоянието
            $statuses = array('pending' => 'Чака одобрение', 'rejected' => 'Отхвърлен', 'closed' => 'Затворен');
            $state = $data->commentsRecs[$rec->id]->state;
            if (isset($statuses[$state])) {
                $data->commentsRows[$rec->id]->status = tr($statuses[$state]);
                $data->commentsRows[$rec->id]->stateClass = "blogm-comment-{$state}";
            }
            
            $data->commentsRows[$rec->id]->name = str::limitLen($data->commentsRows[$rec->id]->name, 32);
            
            if ($data->commentsRows[$rec->id]->web) {
                $data->commentsRows[$rec->id]->name = ht::createLink(
                    $data->commentsRows[$rec->id]->name,
                    $rec->web,
                    null,
                    'target=_blank,rel=external nofollow'
                );
            }
            
            // Аватара на коментиращия
            $data->commentsRows[$rec->id]->avatar = avatar_Plugin::getImg(0, $rec->email, 50);
        }
        
        // Към статията може ли да има форма за коментари?
        $cRec = (object) array('articleId' => $data->articleId);
        if (self::haveRightFor('add', $cRec)) {
            $data->commentForm = self::getForm();
            $data->commentForm->formAttr['id'] = 'postingForm';
            $data->commentForm->setField('state', 'input=none');
            $data->commentForm->setHidden('articleId', $data->articleId);
            
            $Crypt = cls::get('core_Crypt');
            $key = Mode::getPermanentKey();
            $now = $Crypt->encodeVar(time(), $key);
            $data->commentForm->setHidden('renderOn', $now);
            
            $data->commentForm->toolbar->addSbBtn('Изпращане');
        }
    }
    
    
    /**
     * Рендира коментарите и формата за нов коментар в шаблона на статията
     */
    public static function renderComments_($data, $layout)
    {
        if (countR($data->commentsRows)) {
            foreach ($data->commentsRows as $row) {
                $commentTpl = $layout->getBlock('COMMENT');
                $commentTpl->placeObject($row);
                $commentTpl->append2master();
            }
        } else {
            $layout->removeBlock('COMMENTS');
        }
        
        if ($data->commentForm ?? null) {
            $formTpl = getTplFromFile('blogm/tpl/CommentForm.shtml');
            $data->commentForm->fieldsLayout = $formTpl->getBlock('FORM_FIELDS');
            $data->commentForm->layout = $formTpl;
            $layout->replace($data->commentForm->renderHtml(), 'COMMENT_FORM');
        }
        
        // Връщаме шаблона
        return $layout;
    }
    
    
    /**
     * Всички нови коментари, направени през формата в единичния
     * изглед на статията се създават в състояние "чакъщ"
     */
    public static function on_BeforeSave($mvc, &$id, &$rec, $fields = null)
    {
        if (empty($rec->id)) {
            if (!haveRole('cms,ceo,admin') || empty($rec->state) || $rec->state == 'draft') {
                $artRec = $mvc->Master->fetch($rec->articleId);
                $rec->state = ($artRec->commentsMode == 'enabled') ? 'active' : 'pending';
            }
            
            $rec->ip = core_Users::getRealIpAddr();
            $rec->brid = log_Browsers::getBrid();
            
            // Часът на зареждане идва само от публичната форма под статията
            if (!empty($rec->renderOn)) {
                $Crypt = cls::get('core_Crypt');
                $key = Mode::getPermanentKey();
                $renderOn = $Crypt->decodeVar($rec->renderOn, $key);
                if (is_numeric($renderOn)) {
                    $rec->userDelay = time() - $renderOn;
                }
            }

            // Да се записва само при нов запис и и когато няма регистриран потребител
            log_Browsers::setVars(array('name' => $rec->name ?? null, 'email' => $rec->email ?? null, 'web' => $rec->web ?? null));
        }
        
        // Начален рейтинг
        $sr = 0;
        
        // Ако потребителя е посочил уеб-сайт +1
        if ($rec->web) {
            ++$sr;
        }
        
        // Ако има файлови окончания +1
        $sr += self::hasWord($rec->web, '.pdf,.html,.htm,.doc,.xls,.ppt,#') ? 1 : 0;
        
        // Ако в името на сайта има sex, xxx, porn, cam, teen, adult, cheap, sale, xenical, pharmacy, pills, prescription, опционы
        $sr += self::hasWord($rec->web, 'sex,xxx,porn,cam,teen,adult,cheap,sale,xenical,pharmacy,pills,prescription,опционы');
        
        // Ако в името на сайта има директория
        $sr += countR(explode('/', rtrim($rec->web ?? '', '/'))) > 3 ? 1 : 0;
        
        // Ако има линкове в описанието
        $sr += self::hasWord($rec->comment, array('href=', 'src='));
        
        // Ако в името на сайта има sex, xxx, porn, cam, teen, adult, cheap, sale, xenical, pharmacy, pills, prescription, опционы
        $sr += self::hasWord($rec->comment, 'sex,xxx,porn,cam,teen,adult,cheap,sale,xenical,pharmacy,pills,prescription,опционы,bit.ly');
        
        // Ако в коментара има http://
        $sr += self::hasWord($rec->comment, 'http://');
        
        // Ако в коментара има линк
        $sr += self::hasWord($rec->comment, array('[link=')) ? 2 : 0;
        
        // Ако е написано за под 50 секунди
        if (isset($rec->userDelay) && $rec->userDelay < 20) {
            ++$sr;
        }
        
        // Ако е написано за под 10 секунди
        if (isset($rec->userDelay) && $rec->userDelay < 10) {
            ++$sr;
        }
        
        // Ако е написано за под 65 секунди
        if (isset($rec->userDelay) && $rec->userDelay < 65) {
            ++$sr;
        }
        
        // Ако е написано за над 24 часа
        if (isset($rec->userDelay) && $rec->userDelay > 24 * 3600) {
            $sr += round($rec->userDelay / (24 * 3600));
        }
        
        // Изключваме текущия запис, ако е записан
        if (!empty($rec->id)) {
            $idCond = " AND #id != {$rec->id}";
        } else {
            $idCond = '';
        }
        
        // Има ли от същото IP
        $query = self::getQuery();
        $query->limit(28);
        $cnt = $query->count(array("#state != 'active' AND #state != 'closed' AND #ip = '[#1#]'" . $idCond, $rec->ip));
        if ($cnt > 1) {
            $sr += pow($cnt, 1 / 3);
        }
        
        // Има ли от същия brid?
        $query = self::getQuery();
        $query->limit(28);
        $cnt = $query->count(array("#state != 'active' AND #state != 'closed' AND #brid = '[#1#]'" . $idCond, $rec->brid));
        if ($cnt > 1) {
            $sr += pow($cnt, 1 / 3);
        }
        
        $rec->spamRate = (int) $sr;
        
        if (empty($rec->id) && $rec->spamRate <= 3) {
            $artRec = $mvc->Master->fetch($rec->articleId);
            $title = $mvc->Master->getVerbal($artRec, 'title');
            bgerp_Notifications::add(
                "Нов коментар към \"{$title}\"", // съобщение
                array($mvc->Master, 'single', $rec->articleId), // URL
                $artRec->createdBy
            );
        }
        
        // Да не се обновява мастера, ако коментара не става видим или се премахва от видимите
        if ($rec->state != 'active') {
            $stopMasterUpdate = false;
            if (empty($rec->id)) {
                $stopMasterUpdate = true;
            } else {
                $oRec = $mvc->fetch($rec->id);
                if ($oRec->state != 'active') {
                    $stopMasterUpdate = true;
                }
            }
            if ($stopMasterUpdate) {
                Mode::set("stopMasterUpdate{$rec->{$mvc->masterKey}}", true);
                $mvc->lastUsedKeys = 'createdBy';
            }
        }
    }
    
    
    /**
     * Проверка дали стринг съдържа дума от подаден списък.
     * caseinsensitive
     */
    public static function hasWord($str, $words)
    {
        $words = arr::make($words);
        
        foreach ($words as $w) {
            if (stripos($str ?? '', $w) !== false) {
                
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * Махаме articleId когато показваме списък коментари към конкретна статия
     */
    public function on_AfterPrepareListFields($mvc, $data)
    {
        // Извън статията се показва и към коя статия е коментарът
        if (!isset($data->masterMvc)) {
            arr::insert($data->listFields, 'name', array('articleId' => 'Статия'));
        } else {
            
            // В нишката данните за подателя са под името, за да се събере таблицата
            unset($data->listFields['email'], $data->listFields['web'], $data->listFields['ip'], $data->listFields['brid'], $data->listFields['userDelay']);
            $data->listFields['spamRate'] = 'Спам';
        }
        
        $data->query->orderBy('#createdOn', 'DESC');
    }


    /**
     * В статията коментарите се странират по 10
     */
    protected static function on_AfterPrepareListPager($mvc, $data)
    {
        if (isset($data->masterMvc, $data->pager)) {
            $data->pager->itemsPerPage = 10;
        }
    }

    
    /**
     * В нишката събира данните за подателя в колоната с името
     */
    protected static function on_AfterPrepareListRows($mvc, $data)
    {
        if (!isset($data->masterMvc) || !countR($data->rows)) {
            
            return;
        }
        
        foreach ($data->rows as $id => $row) {
            $rec = $data->recs[$id];
            $contacts = array();
            foreach (array('email', 'web') as $fld) {
                if (!empty($rec->{$fld})) {
                    $contacts[] = $row->{$fld} ?? $mvc->getVerbal($rec, $fld);
                }
            }
            
            $name = "<b>{$row->name}</b>";
            if (countR($contacts)) {
                $name .= "<div class='small'>" . implode(' &middot; ', $contacts) . '</div>';
            }
            $row->name = $name . "<div class='small' style='margin-top:2px'>{$row->ip} {$row->brid}</div>";
            
            if (!empty($rec->userDelay)) {
                $row->spamRate = ht::createHint($row->spamRate, 'Закъснение|*: ' . $mvc->getVerbal($rec, 'userDelay'));
            }
        }
    }
    
    
    /**
     *  Ако статията не може да бъде коментираме, премахваме правото за добавяне на нов коментар
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        // Конфигурацията на пакета 'blogm'
        static $conf;
        
        if (!$conf) {
            $conf = core_Packs::getConfig('blogm');
        }
        
        // Проверяваме имаме ли запис и дали екшъна е 'add'
        if ($action == 'add') {
            $artRec = isset($rec->articleId) ? $mvc->Master->fetch($rec->articleId) : null;
            if (is_object($artRec)) {

                // Срокът за коментиране тече от публикуването, не от последната редакция
                $publishedOn = !empty($artRec->publishedOn) ? $artRec->publishedOn : $artRec->createdOn;
                
                // Коментира се само активна статия, с разрешени или потвърждавани коментари, преди срока
                if (!in_array($artRec->commentsMode ?? null, array('enabled', 'confirmation'), true) ||
                    ($artRec->state ?? null) != 'active' ||
                    dt::addSecs($conf->BLOGM_MAX_COMMENT_DAYS, $publishedOn) < dt::now()) {
                    $res = 'no_one'; // Коментарите са забранени
                } else {
                    $res = 'every_one';  // Коментарите са разрешени
                }
            } else {
                $res = 'no_one'; // Коментарите са забранени
            }
        }

        // Активната статия не се редактира, а се променя - коментарите ѝ следват правото за промяна
        if ($action == 'write' && isset($rec->{$mvc->masterKey})) {
            $artRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
            if (($artRec->state ?? null) == 'active') {
                $res = $mvc->Master->getRequiredRoles('changerec', $artRec, $userId);
            }
        }

        // Публикуват се само чакащите коментари
        if ($action == 'activate' && ($rec->state ?? null) != 'pending') {
            $res = 'no_one';
        }

        // Могат да се изтриват само оттеглените
        if ($action == 'delete' && isset($rec) && $rec->state != 'rejected' && ((!stripos($rec->comment, '<a ')) || $rec->state == 'active')) {
            $res = 'no_one';
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, true);
        
        if (isset($fields['-list']) && !empty($rec->articleId)) {
            $row->articleId = blogm_Articles::getHyperlink($rec->articleId, true);
        }
        
        $row->brid = log_Browsers::getLink($rec->brid);

        if (isset($row->_rowTools) && $mvc->haveRightFor('activate', $rec)) {
            $row->_rowTools->addLink('Активиране', array($mvc, 'activate', $rec->id, 'ret_url' => true), array('ef_icon' => 'img/16/lightning.png', 'alwaysShow' => true, 'title' => 'Публикуване на коментара'));
        }
    }


    /**
     * Публикуване на чакащ коментар
     */
    public function act_Activate()
    {
        $id = Request::get('id', 'int');
        expect($rec = $this->fetch($id));
        $this->requireRightFor('activate', $rec);

        $rec->state = 'active';
        $this->save($rec, 'state');
        $this->Master->logWrite('Публикуване на коментар', $rec->{$this->masterKey});

        followRetUrl(array('blogm_Articles', 'single', $rec->articleId));
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     * Форма за търсене по дадена ключова дума
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->showFields = 'ip, brid';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Като детайл филтърът се изпраща към нишката на статията
        $threadId = $data->masterData->rec->threadId ?? null;
        if (!empty($threadId)) {
            $data->listFilter->FNC('threadId', 'int', 'input=hidden,silent');
            $data->listFilter->setDefault('threadId', $threadId);
            $data->listFilter->showFields .= ', threadId';
        }
        $data->listFilter->input($data->listFilter->showFields, 'silent');
        
        if ($ip = ($data->listFilter->rec->ip ?? null)) {
            $ip = str_replace('*', '%', $ip);
            $data->query->where(array("#ip LIKE '[#1#]'", $ip));
        }

        if ($brid = ($data->listFilter->rec->brid ?? null)) {
            $data->query->where(array("#brid LIKE '[#1#]'", $brid));
        }
        
        $data->query->orderBy('#createdOn=DESC');
    }
    
    
    /**
     * Изтрива спам коментарите
     */
    public function cron_DeleteSPAM()
    {
        // Изтриваме всички чакъщи коментари, които имат спам рейтинг над 10 и са по-стари от 1 ден
        // Изтриваме всички чакъщи коментари, които имат спам рейтинг над 5 и са по-стари от 7 дни
        // Изтриваме всички чакъщи коментари, които имат спам рейтинг над 3 и са по-стари от 10 дни
        
        $before25m = dt::addSecs(-25 * 60);
        $before5d = dt::addDays(-5);
        $before14d = dt::addDays(-14);
        $res = null;
        $deleteCnt = 0;
        $rejectedCnt = 0;
        
        // Оттегляме, всички, които по-голям рейтинг от 5 и са на повече от 25 минути или имат по-голям рейтинг от 3 и са от преди повече от 5 дни
        $query = $this->getQuery();
        $query->where("#state = 'pending' AND ((#spamRate > 5 AND #createdOn < '{$before25m}') OR (#spamRate > 3 AND #createdOn < '{$before5d}'))");
        while ($rec = $query->fetch()) {
            $rec->state = 'rejected';
            $this->save_($rec, 'state');
            $rejectedCnt++;
        }
        
        $deleteCnt = $this->delete("#state = 'rejected' AND #createdOn < '{$before14d}'");
        
        if ($rejectedCnt + $deleteCnt) {
            $res = "Бяха оттеглени {$rejectedCnt} и изтрити {$deleteCnt} СПАМ коментара от блога.";
        }
        
        return $res;
    }
}

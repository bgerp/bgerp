<?php


/**
 * Модул 'Пасажи'
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_Texts extends core_Manager
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'passage_Texts';
    
    
    /**
     * Заглавие
     */
    public $title = 'Пасажи';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Sorting, plg_RowTools2, cond_Wrapper, plg_Search, cond_DialogWrapper';
    
    
    /**
     * Избор на полетата, по които може да се осъществи търсенето
     */
    public $searchFields = 'title, body';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'powerUser';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title, body, createdOn,createdOn,createdBy, access, group';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(256)', 'caption=Име, mandatory');
        $this->FLD('body', 'richtext(rows=10,bucket=Comments, passage)', 'caption=Описание, mandatory');
        $this->FLD('access', 'enum(private=Персонален,public=Публичен)', 'caption=Достъп, mandatory');
        $this->FLD('lang', 'enum(bg,en)', 'caption=Език');
        $this->FLD('group', 'keylist(mvc=cond_Groups,select=title)', 'caption=Група, silent');
        $this->FNC('Protected', 'varchar', 'input=hidden, silent');
    }
    
    
    /**
     * Екшъна за показване на диалоговия прозорец за добавяне на пасаж
     */
    public function act_Dialog()
    {
        Request::setProtected('groupName, callback');
        
        Mode::set('wrapper', 'page_Dialog');

        $this->title = 'Добавяне на пасажи';

        // Вземаме callBack'а
        $callback = Request::get('callback', 'identifier');
        
        // Сетваме нужните променливи
        Mode::set('dialogOpened', true);
        Mode::set('callback', $callback);
        
        // Вземаме шаблона
        $tpl = $this->act_List();
        
        // Връщаме шаблона
        return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $res
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     *
     * @internal param string $requiredRoles
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'add') {
            if (Mode::get('dialogOpened')) {
                $res = 'no_one';
            }
        }
        
        if ($action == 'edit') {
            if (Mode::get('dialogOpened')) {
                $res = 'no_one';
            }
        }

        if ($rec) {
            if ($action == 'edit' || $action == 'delete') {
                if ($rec->createdBy != $userId) {
                    if (!haveRole('ceo, admin')) {
                        $res = 'no_one';
                    }
                }
            }
        }

        if (Mode::get('dialogOpened')) {
            if ($action == 'delete') {
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката' на мениджъра
     *
     * @param core_Mvc $mvc
     * @param string   $res
     * @param core_Et  $tpl
     * @param object   $data
     *
     * @return bool
     */
    public function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data = null)
    {
        // Ако е отворен в диалоговия прозорец
        if (Mode::get('dialogOpened')) {
            
            // Рендираме опаковката от друго място
            $res = $mvc->renderDialog($tpl);
            
            // Да не се извикат останалите и да не се рендира "опаковката"
            return false;
        }
    }
    
    
    /**
     * Връща шаблона за диалоговия прозорец
     *
     * @param Core_Et $tpl
     *
     * @return core_ET $tpl
     */
    public function renderDialog_($tpl)
    {
        return $tpl;
    }
    
    
    /**
     * Промяна да дължината на заглавието
     *
     * @param $mvc
     * @param $id
     * @param $rec
     * @param null $fields
     */
    public static function on_BeforeSave($mvc, &$id, &$rec, $fields = null)
    {
        if (empty($rec->title)) {
            list($title, ) = explode('/n', $rec->body);
            $rec->title = str::limitLen($title, 100);
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        if (!haveRole('admin, ceo')) {
            unset($data->form->fields['access']->type->options['public']);
        }
    }

    
    /**
     *
     * Поставянето на полета за търсене
     *
     * @param $mvc
     * @param $data
     *
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $form = $data->listFilter;
        $form->FLD('author', 'users(roles=powerUser, rolesForTeams=powerUser, rolesForAll=powerUser)', 'caption=Автор, autoFilter');
        $form->FLD('langWithAllSelect', 'enum(,bg,en)', 'caption=Език, placeholder=Всички');

        Request::setProtected('groupName, callback');
        $group = Request::get('groupName');

        $cu = core_Users::getCurrent();
        if (!$form->cmd) {
            if ($lastFilter = core_Permanent::get("condLastFilter{$cu}")) {
                if ($lastFilter['group']) {
                    $form->setDefault('group', $lastFilter['group']);
                }

                if ($lastFilter['author']) {
                    $form->setDefault('author', $lastFilter['author']);
                }

                if ($lastFilter['langWithAllSelect']) {
                    $form->setDefault('langWithAllSelect', $lastFilter['langWithAllSelect']);
                }
            }

            if (isset($group)) {
                $groupId = cond_Groups::fetchField(array("#title = '[#1#]'", $group), 'id');
                $default = type_Keylist::fromArray(array($groupId => $groupId));
                $form->setDefault('group', $default);
            }
        }

        $form->setDefault('author', $cu);

        $form->showFields = 'search, author, group, langWithAllSelect';
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $form->view = 'vertical';
        $form->class = 'simpleForm';
        
        $form->input(null, 'silent');

        $rec = $form->rec;

        if ($rec->author) {
            $authArr = type_Keylist::toArray($rec->author);
            if (mb_stripos($rec->author, '|-1|') === false) {
                $data->query->in('createdBy', type_Keylist::toArray($rec->author));
            }

            if (!haveRole('admin, ceo', $cu)) {
                $data->query->where("#access = 'public'");
                $data->query->orWhere(array("#createdBy = '[#1#]'", $cu));
            }
        }
        if ($rec->langWithAllSelect) {
            $data->query->where(array("#lang = '[#1#]'", $rec->langWithAllSelect));
        }

        if ($rec->group) {
            $data->query->likeKeylist('group', $rec->group);
        }

        core_Permanent::set("condLastFilter{$cu}", array('group' => $rec->group, 'author' => $rec->author, 'langWithAllSelect' => $rec->langWithAllSelect), 24 * 60 * 100);

        $data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
     * Променяне на вида на прозореца при отварянето му като диалог
     *
     * @param $mvc
     * @param $row
     * @param $rec
     * @param null $fields
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = null)
    {
        if (Mode::get('dialogOpened')) {

            $callback = Mode::get('callback');
            $str = json_encode($rec->body);
            
            $attr = array('onclick' => "if(window.opener.{$callback}(${str}) != true) self.close(); else self.focus();", 'class' => 'file-log-link');

//            $attr = array('onclick' => "console.log('test');", "class" => "file-log-link");
            $title = ht::createLink($rec->title, '#', false, $attr);
            
            $string = str_replace(array("\r", "\n"), array('', ' '), str::limitLen($rec->body, 200));
            
            Mode::push('text', 'plain');
            
            $string = $mvc->fields['body']->type->toVerbal($string);
            
            Mode::pop('text');
            $rec->title = str::limitLen($title, 100);
            
            $string = substr_replace($string, '[hide]', 0, 0);
            $string = substr_replace($string, '[/hide]', strlen($string), 0);
            $string = $mvc->fields['body']->type->toVerbal($string);

            $row->body = "<span class='passageHolder'>" . $title . $string . '</span>';
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        $data->listTableMvc->FLD('created', 'varchar', 'tdClass=createdInfo');
    }


    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        if (Mode::get('dialogOpened')) {
            $data->listFields['body'] = $data->listFields['title'];
            unset($data->listFields['title']);
        }
    }
}

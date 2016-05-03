<?php


/**
 * Модул Пасаж
 *
 * @category  bgerp
 * @package   passage
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class passage_Texts extends core_Manager
{


    /**
     * Заглавие
     */
    public $title = "Фрагменти";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, cond_Wrapper, plg_Search, passage_DialogWrapper";


    /**
     * Избор на полетата, по които може да се осъществи търсенето
     */
    public $searchFields = "title, body";


    /**
     * Време за опресняване информацията при лист на събитията
     */
    var $refreshRowsTime = 5000;


    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,admin';


    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,admin';


    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,admin';


    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,admin';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,admin';


    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,admin';


    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,admin,trans';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(256)', 'caption=Заглавие, oldFieldName = name');
        $this->FLD('body', 'richtext(rows=10,bucket=Comments)', 'caption=Описание, mandatory');
        $this->FLD('access', 'enum(private=Персонален,public=Публичен)', 'caption=Достъп, mandatory');
        $this->FLD('lang', 'enum(bg,en)', 'caption=Език на пасажа');
    }

    /**
     * Екшъна за показване на диалоговия прозорец за добавяне на пасаж
     */
    function act_Dialog()
    {
        Mode::set('wrapper', 'page_Dialog');

        // Вземаме callBack'а
        $callback = Request::get('callback', 'identifier');

        // Сетваме нужните променливи
        Mode::set('dialogOpened', TRUE);
        Mode::set('callback', $callback);
      // Mode::set('bucketId', $bucketId);

        // Вземаме шаблона
        $tpl = $this->act_List();

        // Връщаме шаблона
        return $tpl;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $res
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     * @internal param string $requiredRoles
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'add') {
            if (Mode::get('dialogOpened')) {
                $res = 'no_one';
            }
        }

    }
    /**
     * Извиква се преди рендирането на 'опаковката' на мениджъра
     *
     * @param core_Mvc $mvc
     * @param string $res
     * @param core_Et $tpl
     * @param object $data.
     *
     * @return boolean
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
     * Връща шаблона за диалоговия прозорец
     *
     * @param Core_Et $tpl
     *
     * @return core_ET $tpl
     */
    function renderDialog_($tpl)
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
    static function on_BeforeSave($mvc, &$id, &$rec, $fields = NULL)
    {
        if(empty($rec->title)){
            list($title,) = explode("/n", $rec->body);
            $rec->title =   str::limitLen($title, 100);
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
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $form = $data->listFilter;
        $form->FLD('author' , 'users(roles=powerUser, rolesForTeams=manager|ceo|admin, rolesForAll=ceo|admin)', 'caption=Автор, autoFilter');
        $form->FLD('langWithAllSelect', 'enum(,bg,en)', 'caption=Език на пасажа, placeholder=Всичко');
        $form->showFields = 'search,author,langWithAllSelect';
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $form->view = 'vertical';
        $form->class = 'simpleForm';

        $form->input();
        if($form->isSubmitted()){
            $rec = $form->rec;
            if($rec->author){
                $data->query->where("'{$rec->author}' LIKE CONCAT ('%|', #createdBy , '|%')");
            }
            if($rec->langWithAllSelect){
                $data->query->where(array("#lang = '[#1#]'", $rec->langWithAllSelect));
            }
        }
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
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = NULL)
    {
        if (Mode::get('dialogOpened')) {
            $callback = Mode::get('callback');
            $str = json_encode($rec->body);

            $attr = array('onclick' => "if(window.opener.{$callback}($str) != true) self.close(); else self.focus();", "class" => "file-log-link");
//            $attr = array('onclick' => "console.log('test');", "class" => "file-log-link");
            $title = ht::createLink($rec->title, '#', FALSE, $attr);

            $string = str_replace(array("\r", "\n"), array('', ' '), $rec->body);

            Mode::set('text', 'plain');

            $string =  $mvc->fields['body']->type->toVerbal($string);
            Mode::push('text');
            $rec->title = str::limitLen($title, 100);

            $string = substr_replace($string, "[hide=Още]", 0, 0);
            $string = substr_replace($string, "[/hide]", strlen($string), 0);
            $string =  $mvc->fields['body']->type->toVerbal($string);
            $createdOn = $mvc->getVerbal($rec, 'createdOn');
            $createdBy = $mvc->getVerbal($rec, 'createdBy');

            $row->body = $title . "<br>" . $string  . $createdOn . ' - ' . $createdBy;
        }
    }


    /**
    * Извиква се преди подготовката на колоните ($data->listFields)
    *
    * @param core_Mvc $mvc
    * @param object $res
    * @param object $data
     *
     * @return bool false
    */
    static function on_BeforePrepareListFields($mvc, &$res, $data)
    {
        // Ако е отворен в диалоговия прозорец
        if (Mode::get('dialogOpened')) {

            // Нулираме, ако е имало нещо
            $data->listFields = array();

            // Задаваме, кои полета да се показва
            $data->listFields['body'] = "Пасаж";

            // Да не се извикат останалите
            return FALSE;
        }
    }
}
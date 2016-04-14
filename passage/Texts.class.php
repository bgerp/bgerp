<?php


/**
 * Модел "Изчисляване на налва"
 *
 *
 * @category  bgerp
 * @package   trans
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
    public $title = "Фрафменти";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, cond_Wrapper, plg_Search";

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


    static function on_BeforeSave($mvc, &$id, &$rec, $fields = NULL)
    {
        if(empty($rec->title)){
            list($title,) = explode("/n", $rec->body);
            $rec->title =   str::limitLen($title, 100);
        }
    }

    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $form = $data->listFilter;
        $form->FLD('author' , 'users(roles=powerUser, rolesForTeams=manager|ceo|admin, rolesForAll=ceo|admin)', 'caption=Автор, autoFilter');
        $form->FLD('langWithAllSelect', 'enum(,bg,en)', 'caption=Език на пасажа, placeholder=Всичко');

        $form->showFields = 'search,author,langWithAllSelect';

        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        $form->view = 'horizontal';

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

}
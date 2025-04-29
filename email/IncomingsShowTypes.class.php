<?php 


/**
 * Изглед на имейлите в сингъла им
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_IncomingsShowTypes extends core_Manager
{

    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'email_Wrapper, plg_Created, plg_Sorting';
    
    
    /**
     * Заглавие
     */
    public $title = 'Изглед на имейлите';
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'debug';
    
    
    /**
     * Кой може да добавя
     */
    protected $canAdd = 'no_one';
    
    
    /**
     * Кой може да го редактира
     */
    protected $canEdit = 'no_one';


    /**
     * @return void
     */
    public function description()
    {
        $this->FLD('emailId', 'key(mvc=email_Incomings, select=subject)', 'caption=Имейл');
        $this->FLD('userId', 'user', 'caption=Потребител');
        $this->FLD('showType', 'enum(text=Текст,html=HTML)', 'caption=Изглед');

        $this->setDbUnique('emailId, userId');
    }


    /**
     * Задава текущия изглед на имейла
     *
     * @param $emailId
     * @param $showType
     * @param $userId
     *
     * @return mixed
     */
    public static function setCurrentState($emailId, $showType, $userId = null)
    {
        setIfNot($userId, core_Users::getCurrent());

        if ($userId <= 0) {

            return false;
        }

        $rec = new stdClass();
        $rec->emailId = $emailId;
        $rec->showType = $showType;
        $rec->userId = $userId;

        return self::save($rec, NULL, 'REPLACE');
    }


    /**
     * Връща текущия изглед на имейла
     *
     * @param $emailId
     * @param $userId
     * @return string|NULL - NULL, html, text
     */
    public static function getCurrentState($emailId, $userId = null)
    {
        setIfNot($userId, core_Users::getCurrent());

        if ($userId <= 0) {

            return null;
        }

        $res = self::fetchField(array("#emailId = '[#1#]' AND #userId = '[#2#]'", $emailId, $userId), 'showType');

        return $res;
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Да се показва полето за търсене
        $data->listFilter->showFields = 'userId';

        $data->listFilter->view = 'horizontal';

        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        $data->listFilter->setDefault('userId', core_Users::getCurrent());

        $data->listFilter->input();

        $data->listFilter->setFieldTypeParams('userId', array('allowEmpty' => 'allowEmpty'));

        if ($data->listFilter->rec->userId) {
            $data->query->where(array("#userId = '[#1#]'", $data->listFilter->rec->userId));
        }

        $data->listFilter->setField("userId", "refreshForm");

        $data->query->orderBy('createdOn', 'DESC');
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if (email_Incomings::haveRightFor('single', $rec->emailId)) {
            $row->emailId = email_Incomings::getLinkToSingle($rec->emailId, 'subject');
        }
    }
}

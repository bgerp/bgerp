<?php


/**
 * Логове на движения
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_Logs extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Логове на движения';


    /**
     * Еденично заглавие
     */
    public $singleTitle = 'Лог';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, rack_Wrapper, plg_SelectPeriod, plg_Search, plg_Sorting';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,rack';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,rack';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Кои полета ще се виждат в листовия изглед
     */
    public $listFields = 'message,createdBy=От,createdOn=На';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'movementId,position,message';


    /**
     * Дефолтни предложения за търсене
     */
    const SEARCH_SUGGESTIONS = array('Създаване', 'Започване', 'Отказване', 'Връщане', 'Приключване', 'Ревизия');


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('movementId', 'key(mvc=rack_Movements,select=id)', 'caption=Движение');
        $this->FLD('position', 'rack_PositionType', 'caption=Позиция');
        $this->FLD('message', 'text', 'caption=Текст');
    }


    /**
     * Добавя Лог на историята на движението
     *
     * @param $position   - позиция
     * @param $movementId - ид на движение
     * @param $message    - текст
     */
    public static function add($position, $movementId, $message)
    {
        $Movements = cls::get('rack_Movements');
        $movementRec = rack_Movements::fetchRec($movementId);
        $rec = (object)array('position' => $position, 'message' => $message);

        if(is_object($movementRec)){
            $rec->movementId = $movementRec->id;
            $description = strip_tags($Movements->getMovementDescription($movementRec, false, false));
            $rec->message .= " / {$description}";
        }

        static::save($rec);
    }


    /**
     * След обработка на лист филтъра
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('createdOn=DESC');

        if($movementId = Request::get('movementId', 'int')){
            $data->query->where("#movementId = {$movementId}");
        }

        $data->listFilter->FLD('from', 'date', 'caption=От');
        $data->listFilter->FLD('to', 'date', 'caption=До');
        $data->listFilter->showFields = 'selectPeriod, from, to, createdBy, search';
        $data->listFilter->setField('createdBy', 'caption=Потребител,placeholder=Всички');
        $data->listFilter->setFieldTypeParams('createdBy', array('allowEmpty' => 'allowEmpty'));
        $users = core_Users::getUsersByRoles('ceo,rack,store');
        $users = array(core_Users::SYSTEM_USER => core_Users::getTitleById(core_Users::SYSTEM_USER, 'nick')) + $users;
        $data->listFilter->setOptions('createdBy', $users);
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));

        $searchSuggestions = array_combine(static::SEARCH_SUGGESTIONS, static::SEARCH_SUGGESTIONS);
        $data->listFilter->setSuggestions('search', array('' => '') + $searchSuggestions);

        $data->listFilter->input();
        if($filterRec = $data->listFilter->rec){
            if(!empty($filterRec->from)){
                $data->query->where("#createdOn >= '{$filterRec->from} 00:00:00'");
            }

            if(!empty($filterRec->to)){
                $data->query->where("#createdOn <= '{$filterRec->to} 23:59:59'");
            }

            if(!empty($filterRec->createdBy)){
                $data->query->where("#createdBy = '{$filterRec->createdBy}'");
            }
        }

        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }
}
<?php


/**
 * Клас 'drdata_CanonizedStrings' - канонизации на стрингове
 *
 *
 * @category  bgerp
 * @package   drdata
 *
 * @author    Ivelin Dimov <ivelin.pdimov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class drdata_CanonizedStrings extends core_Manager
{

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'drdata_Wrapper, plg_RowTools2';


    /**
     * Заглавие
     */
    public $title = 'Канонизирани стрингове';


    /**
     * Кой  може да редактира
     */
    public $canEdit = 'debug';


    /**
     * Кой  може да добавя
     */
    public $canAdd = 'debug';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('string', 'varchar(128, ci)', 'caption=Оригинал->Стринг');
        $this->FNC('stringLen', 'int', 'caption=Оригинал->Дължина');
        $this->FLD('canonized', 'varchar(128, ci)', 'caption=Канонизиран->Стринг');
        $this->FNC('canonizedLen', 'int', 'caption=Канонизиран->Дължина');
        $this->FLD('type', 'enum(uic=Нац.номер,iban=IBAN)', 'caption=Тип');

        $this->setDbUnique('string,type');
        $this->setDbIndex('canonized');
        $this->setDbIndex('canonized,type');
    }


    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $row->stringLen = mb_strlen($rec->string);
        $row->canonizedLen = mb_strlen($rec->canonized);
    }


    /**
     * Връща стринга, отговарящ на канонизацията
     *
     * @param string $canonized
     * @param string $type
     * @return string $canonized
     */
    public static function getString($canonized, $type)
    {
        $query = static::getQuery();
        $query->where(array("#canonized = '[#1#]'", $canonized));
        $query->where("#type = '{$type}'");
        $query->orderBy('id=DESC');
        $query->limit(1);

        $rec = $query->fetch();

        if(is_object($rec)) return $rec->string;

        return $canonized;
    }


    /**
     * Канонизира стринга според типа
     *
     * @param $string            - стринг
     * @param $type              - тип
     * @param bool $save         - да се запише ли съответствието в модела
     * @return string $canonized - канонизиран стринг
     */
    public static function canonize($string, $type, $save = true)
    {
        $canonized = preg_replace('/[^a-z\d]/i', '', $string);
        $canonized = strtoupper($canonized);

        if($save){
            if(!self::fetch(array("#string = '[#1#]' AND #type = '[#2#]'", $string, $type))){
                $newRec = (object)array('string' => $string, 'canonized' => $canonized, 'type' => 'uic');
                static::save($newRec);
            }
        }

        return $canonized;
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->FLD('compare', 'enum(all=Всички,different=Различни)', 'placeholder=Състояние');
        $data->listFilter->showFields = 'string,compare,type';
        $data->listFilter->setDefault('compare', 'all');
        $data->listFilter->setDefault('type', 'uic');

        $data->listFilter->input();
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->query->orderBy('#id', 'DESC');

        if ($filter = $data->listFilter->rec) {
            if (isset($filter->type)) {
                $data->query->where("#type = '{$filter->type}'");
            }

            if (isset($filter->compare) && $filter->compare == 'different') {
                $data->query->where("#string != #canonized");
            }

            if (!empty($filter->string)) {
                $data->query->where("#string = '{$filter->string}'");
            }
        }
    }
}
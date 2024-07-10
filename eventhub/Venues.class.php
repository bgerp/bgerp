<?php


/**
 * Място на събитие
 *
 *
 * @category  bgerp
 * @package   eventhub
 *
 * @author    Ивета Мошева <ivetamosheva@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eventhub_Venues extends core_Master
{
    /**
     * Заглавие на страницата
     */
    public $title = 'Място на събитие';


    /**
     * Зареждане на необходимите плъгини
     */
    public $loadList = 'plg_RowTools2, plg_State2, plg_Printing, 
        plg_Search, plg_Created, plg_Modified, plg_Sorting,eventhub_Wrapper';


    /**
     * Полета за листов изглед
     */
    public $listFields = 'id, title, type, district, city, address, createdOn=Създаване||Created->На, createdBy=Създаване||Created->От||By, modifiedOn=Модифицирано||Modified->На, modifiedBy=Модифицирано||Modified->От||By,state';


    /**
     *  Полета по които ще се търси
     */
    public $searchFields = 'title';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, admin';


    /**
     * Кой може да добявя,редактира или изтрива статия
     */
    public $canWrite = 'ceo, admin';


    /**
     * Единично заглавие на документа
     */
    public $singleTitle = 'Място на събитие';


    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'eventhub/tpl/SingleLayoutVenues.shtml';

    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Наименование, mandatory');
        $this->setDbUnique('title');
        $this->FLD('type', 'enum(,hall=Зала, stadium=Стадион, square=Площад, building=Сграда)', 'caption=Вид,mandatory, placeholder=Изберете');
        $this->FLD('district', 'enum(,1=Благоевградска, 2=Бурсгаска, 3=Варненска, 4=Великотърновска, 5=Видинска, 6=Врачанска, 7=Габровска,
        8=Добричка, 9=Кърджалийска, 10=Кюстендилска, 11=Ловешка, 12=Монтанска, 13=Пазарджишка, 14=Пернишка, 15=Плевенска, 16=Пловдивска, 
        17=Разградска, 18=Русенска, 19=Силистренска, 20=Сливенска, 21=Смолянска, 22=София-град, 23=Софийска, 24=Старозагорска, 25=Търговищка, 
        26=Хасковска, 27=Шуменска, 28=Ямболска)', 'caption=Област,mandatory, placeholder=Изберете');
        $this->FLD('city', 'varchar(64)', 'caption=Нас. място, mandatory');
        $this->FLD('address', 'varchar(128)', 'caption=Адрес, mandatory');
        $this->FLD('geolocation', 'location_Type(geolocation=mobile)', 'caption=Геолокация');
    }

    /**
     * Вкарваме css файл за единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        $tpl->push('eventhub/tpl/css/style.css', 'CSS');
    }
}
<?php



/**
 * Мениджър на форма за CV
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Форма за CV
 */
class hr_FormCv extends core_Master
{

    /**
     * Кой има право да чете?
     */
    public $canRead = 'hr, ceo';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'hr,ceo';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'hr,ceo';


    /**
     * Кой има право да чете?
     */
    public $canSummarise = 'hr,ceo';



    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf';



    /**
     * Заглавие на мениджъра
     */
    var $title = "Форма CV";


    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "CV ";


//    /**
//     * Икона за единичния изглед
//     */
//    var $singleIcon = 'img/16/vcard.png';


    /**
     * Полета, които се показват в листови изглед
     */
    var $listFields = 'name,egn,place,mobile';


    /**
     * Полета за експорт
     */
    var $exportableCsvFields = 'name,egn,country,place,email,info,birthday,pCode,place,address,tel,mobile';

    /**
     * Абревиатура
     */
    public $abbr = "CVf";

    /**
     * Групиране на документите
     */
    public $newBtnGroup = "5.7|Човешки ресурси";


    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'doc_DocumentPlg, plg_RowTools2, crm_Wrapper, plg_Printing, plg_State, plg_PrevAndNext,doc_ActivatePlg';


    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {


        // Име на лицето
        // $this->FLD('salutation', 'enum(,mr=Г-н,mrs=Г-жа,miss=Г-ца)', 'caption=Обръщение,export=Csv');
        $this->FLD('name', 'varchar(255,ci)', 'caption=Информация->Имена,class=contactData,mandatory,remember=info,silent,export=Csv');
       // $this->FNC('nameList', 'varchar', 'sortingLike=name');

        // Единен Граждански Номер
        $this->FLD('egn', 'bglocal_EgnType', 'caption=ЕГН,export=Csv');

        //Снимка
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Фото,export=Csv');

        // Адресни данни
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Адресни данни->Държава,remember,class=contactData,mandatory,silent,export=Csv');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode,export=Csv');
        $this->FLD('place', 'varchar(64)', 'caption=Град,class=contactData,hint=Населено място: град или село и община,export=Csv');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,class=contactData,export=Csv');

        // Лични комуникации
        $this->FLD('email', 'emails', 'caption=Лични комуникации->Имейли,class=contactData,export=Csv');
        $this->FLD('tel', 'drdata_PhoneType(type=tel)', 'caption=Лични комуникации->Телефони,class=contactData,silent,export=Csv');
        $this->FLD('mobile', 'drdata_PhoneType(type=tel)', 'caption=Лични комуникации->Мобилен,class=contactData,silent,export=Csv');

        $period = '';$months = '';

        for($i=1989;$i<=2017;$i++){
                $period .= $i.'|';
        }

        $monthsArr = array("Ян", "Фев", "Мар", "Апр", "Май", "Юни", "Юли", "Авг", "Сеп", "Окт", "Ное", "Дек");
       foreach ($monthsArr as $m){
        $months .= $m.'|';
       }

        $this->FLD('workExperience', "table(columns=orgName|position|beginM|beginY|endM|endY,beginM_opt=$months,beginY_opt=$period,endM_opt=$months,endY_opt=$period,captions=Фирмa/Организация|Длъжност|ОТ мес|год|ДО мес|год,widths=20em|15em|4em|4em|4em|4em)", "caption=Трудов стаж||Extras->Месторабота||Additional,autohide,advanced,export=Csv");

        $this->FLD('education', 'table(columns=school|specility|begin|end,captions=Учебно заведение|Степен/Квалификация|Начало|Край,widths=20em|15em|5em|5em)', "caption=Образование||Extras->Обучение||Additional,autohide,advanced,export=Csv");

        $this->FLD('state', 'enum(draft=Чернова,active=Публикувана,rejected=Оттеглена)', 'caption=Състояние,input=none');


    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    }


    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        $title = $this->recToverbal($rec, 'name')->name;
        $row = new stdClass();
        $row->title = $this->singleTitle . ' -' . $title;
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





}
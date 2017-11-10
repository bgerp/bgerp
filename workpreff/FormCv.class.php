<?php



/**
 * Мениджър на форма за CV
 *
 *
 * @category  bgerp
 * @package   workpreff
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Форма за CV
 */
class workpreff_FormCv extends core_Master
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
    var $singleTitle = "CV";


    /**
     * Полета, които се показват в листови изглед
     */
    var $listFields = 'name,egn,place,mobile';


    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'workpreff/tpl/SingleLayoutCV.shtml';


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
    var $loadList = 'doc_DocumentPlg, plg_RowTools2,hr_Wrapper, plg_Printing, plg_State, plg_PrevAndNext,doc_ActivatePlg';


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
        $this->FLD('country', "key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,default=Bg)",
                    'caption=Адресни данни->Държава,remember,class=contactData,silent,export=Csv');
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

        $this->FLD('workpreff',"blob(compress,serialize)","caption = Предпочитания,input=none");

        $this->FLD('state', 'enum(draft=Чернова,active=Публикувана,rejected=Оттеглена)', 'caption=Състояние,input=none');
    }

    /**
     * Добавям поле "ПРЕДПОЧИТАНИЯ" във формата
     * @param $mvc
     * @param $form
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {

        $preferencesForWork = array();

        if ($form->isSubmitted()){

            $workpreff = new stdClass();

                foreach ($form->rec as $k => $v) {

                    if (substr($k, 0, 10) == 'workpreff_') {

                        $nameChoice =  workpreff_WorkPreff::getOptionsForChoice()[substr($k, 10)]->name;

                        $preferencesForWork[] = (object)array(

                            'id' => $nameChoice,

                            'value' => $v

                        );

                    }

                }
            $form->rec->workpreff = $preferencesForWork;
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */

    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;

        $form->setDefault('country', drdata_Countries::getIdByName('bul'));

        $options = workpreff_WorkPreff::getOptionsForChoice();

        if (is_array($options)) {

            foreach ($options as $v) {

                if ($v->type == 'enum') {

                    $form->FNC("workpreff_$v->id", "enum($v->parts)", "caption =$v->name->Избери,maxRadio=$v->count,input");

                }

                if ($v->type == 'set') {

                    $form->FNC("workpreff_$v->id", "set($v->parts)", "caption =$v->name->Маркирай,input");

                }

            }
        }

    }


    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        $title = $this->recToverbal($rec, 'name')->name;
        $row = new stdClass();
        $row->title = $this->singleTitle . ' - ' . $title;
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
     * Вербализиране на полето Предпочитания
     * @param $mvc
     * @param $row
     * @param $rec
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {


        if ($fields['-single']) {
            $row->singleTitle = 'CV - ' . $row->name;

            // Fancy ефект за картинката
            $Fancybox = cls::get('fancybox_Fancybox');

            $tArr = array(200, 150);
            $mArr = array(600, 450);

            if($rec->photo) {
                $row->image = $Fancybox->getImage($rec->photo, $tArr, $mArr);
            }

        }


        $prepare = '';

        if (is_array($rec->workpreff)) {

            foreach ($rec->workpreff as $v) {

                $printValues = explode(',', $v->value);

                $printValue = '';

                foreach ($printValues as $vp) {

                    $printValue .= "<div>" . $vp . "</div>";
                }

                $prepare .= "<tr><td class='aright'>" . $v->id . ": " . "</td><td class='aleft' colspan='2'>" . $printValue . "</td></tr>";

            }
        }

        $row->workpreff = "$prepare";

    }

}
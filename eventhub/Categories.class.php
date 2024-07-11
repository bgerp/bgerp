<?php


/**
 * Категории на събития
 *
 *
 * @category  bgerp
 * @package   eventhub
 *
 * @author    Svetlozar Trifonov <svetlozartrifonov60@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eventhub_Categories extends core_Manager
{
    /**
     * Заглавие на страницата
     */
    public $title = 'Категории на събития';


    /**
     * Зареждане на необходимите плъгини
     */
    public $loadList = 'plg_RowTools2, plg_State2, plg_Printing, 
        plg_Search, plg_Created, plg_Modified, plg_Sorting, eventhub_Wrapper,plg_TreeObject';


    /**
     * Полета за листов изглед
     */
    public $listFields = 'id, title, createdOn=Създаване||Created->На, createdBy=Създаване||Created->От||By, 
    modifiedOn=Модифицирано||Modified->На, modifiedBy=Модифицирано||Modified->От||By,state';


    /**
     *  Полета по които ще се търси
     */
    public $searchFields = 'title, parentId';


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
    public $singleTitle = 'Категории събития';


    /**
     * Единично заглавие на документа
     */
    public $nameField = 'title';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('title', 'varchar(32)', 'caption=Категория, mandatory');
        $this->FLD('parentId', 'key(mvc=eventhub_Categories, select=title, allowEmpty)',
            'caption=Подкатегория на');
        $this->setDbUnique('title,parentId');
    }


    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->form->setField('makeDescendantsFeatures', 'input=hidden');
    }


    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        // Подготвяме пътя до файла с данните
        $file = 'eventhub/data/Categories.csv';

        // Кои колонки ще вкарваме
        $fields = array(
            0 => 'title'
        );

        // Импортираме данните от CSV файла.
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($this, $file, $fields, null, null);

        // Записваме в лога вербалното представяне на резултата от импортирането
        $res = $cntObj->html;

        return $res;
    }
}
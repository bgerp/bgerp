<?php


/**
 *
 *
 * @category  bgerp
 * @package   deepl
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deepl_Cache extends core_Manager
{


    /**
     * Версия на външния код
     */
    const API_VERSION = 'V1';


    /**
     * Заглавие на мениджъра
     */
    public $title = 'Кешове';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, deepl_Wrapper, plg_Sorting, plg_Search, plg_RowTools2';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'deepl';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, deepl';


    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'debug';


    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    public $searchFields = 'key, sourceText, sourceLg, translatedText, translatedLg';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('sourceText', 'text', 'caption=Текст->Текст');
        $this->FLD('sourceLg', 'varchar(8)', 'caption=Текст->Език');
        $this->FLD('translatedText', 'text', 'caption=Превод->Текст');
        $this->FLD('translatedLg', 'varchar(8)', 'caption=Превод->Език');
        $this->FLD('key', 'identifier(32)', 'caption=Ключ, input=none');

        $this->setDbUnique('key');
    }


    /**
     * Връща съдържанието на кеша за посочения обект
     */
    public static function get($dArr)
    {
        $key = self::getKey($dArr);

        $rec = self::fetch(array("#key = '[#1#]'", $key));

        if (!$rec) {

            return false;
        }

        return $rec->translatedText;
    }


    /**
     * Записва обект в кеша
     *
     * @params array $dArr
     */
    public static function set($dArr)
    {
        expect($dArr);

        $key = self::getKey($dArr);

        $rec = self::fetch(array("#key = '[#1#]'", $key));

        if (!$rec) {
            $rec = new stdClass();
            $rec->key = $key;
        }

        $rec->sourceText = $dArr['sourceText'];
        $rec->sourceLg = $dArr['sourceLg'];
        $rec->translatedText = $dArr['translatedText'];
        $rec->translatedLg = $dArr['translatedLg'];

        self::save($rec);

        return $key;
    }


    /**
     * Помощна функция за генериране на ключ
     *
     * @param array $dArr
     *
     * @return string
     */
    public static function getKey($dArr)
    {
        $keyArr = array();

        if (is_scalar($dArr)) {

            return $dArr;
        }

        $keyArr['sourceText'] = $dArr['sourceText'];
        $keyArr['sourceLg'] = $dArr['sourceLg'];
        $keyArr['translatedLg'] = $dArr['translatedLg'];

        $keyArr['API_VERSION'] = self::API_VERSION;
        ksort($keyArr);

        $keyStr = serialize($keyArr);

        return md5($keyStr);
    }


    /**
     * Преди запис на документ
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if (!isset($rec->key)) {
            $rec->key = $mvc->getKey((array) $rec);
        }
    }


    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'search';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');

        $data->query->orderBy('createdOn', 'DESC');
        $data->query->orderBy('id', 'DESC');
    }


    /**
     * Добавяме бутон за import и export
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        // Бутон за изчистване на всички
        if (haveRole('admin') && haveRole('debug')) {
            if (BGERP_GIT_BRANCH == 'dev') {
                $data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искате ли да изчистите таблицата,ef_icon=img/16/sport_shuttlecock.png');
            }
        }
    }


    /**
     * Изчиства записите
     */
    public function act_Truncate()
    {
        requireRole('admin');
        requireRole('debug');

        expect(BGERP_GIT_BRANCH == 'dev');

        // Изчистваме записите от моделите
        self::truncate();

        return new Redirect(array($this, 'list'), '|Записите са изчистени успешно');
    }
}

<?php


/**
 *
 *
 * @category  bgerp
 * @package   openai
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class openai_Cache extends core_Manager
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
    public $loadList = 'plg_Created, openai_Wrapper, plg_Sorting, plg_Search, plg_RowTools2';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'openai, admin';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'openai, admin';


    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'openai, admin';


    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    public $searchFields = 'key, prompt, answer';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('prompt', 'text', 'caption=Въпрос, input=none');
        $this->FLD('answer', 'text', 'caption=Отговор, input=none');
        $this->FLD('promptParams', 'blob(serialize,compress)', 'caption=Параметри->Заявка, input=none');
        $this->FLD('answerData', 'blob(compress)', 'caption=Параметри->Отговор, input=none');
        $this->FLD('key', 'identifier(32)', 'caption=Ключ, input=none');

        $this->setDbUnique('key');
    }


    /**
     * Връща съдържанието на кеша за посочения обект
     */
    public static function get($keyObj)
    {
        $key = self::getKey($keyObj);

        $rec = self::fetch(array("#key = '[#1#]'", $key));

        if (!$rec) {

            return false;
        }

        return $rec->answerData;
    }


    /**
     * Записва обект в кеша
     */
    public static function set($keyObj, $response)
    {
        expect($keyObj);

        $key = self::getKey($keyObj);

        $rec = self::fetch(array("#key = '[#1#]'", $key));

        if (!$rec) {
            $rec = new stdClass();
            $rec->key = $key;
        }

        $rec->prompt = $keyObj['prompt'];
        $rec->answer = openai_Api::prepareRes($response);
        $rec->answer = $rec->answer->choices[0]->text;
        $rec->promptParams = $keyObj;
        $rec->answerData = $response;

        self::save($rec);

        return $key;
    }


    /**
     * Помощна функция за генериране на ключ
     *
     * @param array|string $keyObj
     *
     * @return string
     */
    public static function getKey($keyObj)
    {
        if (is_scalar($keyObj)) {

            return $keyObj;
        }

        $keyObj['prompt'] = mb_strtolower($keyObj['prompt']);
        $keyObj['prompt'] = preg_replace('/[\W]/ui', '', $keyObj['prompt']);

        $keyObj['API_VERSION'] = self::API_VERSION;

        setIfNot($keyObj['model'], openai_Setup::get('API_MODEL'));
        setIfNot($keyObj['temperature'], openai_Setup::get('API_TEMPERATURE'));
        setIfNot($keyObj['max_tokens'], openai_Setup::get('API_MAX_TOKENS'));
        setIfNot($keyObj['top_p'], openai_Setup::get('API_TOP_P'));
        setIfNot($keyObj['frequency_penalty'], openai_Setup::get('API_FREQUENCY_PENALTY'));
        setIfNot($keyObj['presence_penalty'], openai_Setup::get('API_PRESENCE_PENALTY'));

        ksort($keyObj);

        $keyObj = serialize($keyObj);

        return md5($keyObj);
    }


    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    public static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields)
    {
        $rec->answerData = @json_decode($rec->answerData);
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

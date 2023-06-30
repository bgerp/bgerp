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
    public $canAdd = 'openai, admin';


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
    public static function get($keyObj, &$cKey = null)
    {
        $cKey = self::getKey($keyObj);

        $rec = self::fetch(array("#key = '[#1#]'", $cKey));

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

        $rec->prompt = $keyObj['prompt'] ? $keyObj['prompt'] : $keyObj['messages'][0]['content'];
        $rec->answer = openai_Api::prepareRes($response);
        $rec->answer = $rec->answer->choices[0]->text ? $rec->answer->choices[0]->text : $rec->answer->choices[0]->message->content;
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

        $keyObj['API_VERSION'] = openai_Setup::get('VERSION');

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
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $fRecClone = clone $rec;
        if ($fRecClone->id) {
            $fRecClone = $mvc->fetch($fRecClone->id);
        }

        $form->FLD('_pModel', 'enum(GPT 3.5 TURBO, TEXT DAVINCI 003)', 'caption=Параметри->Модел, removeAndRefreshForm=__prompt|__mContentRole|__mContentSystem|__mContentAssistant');
        $form->FLD('__temperature', 'double', 'caption=Параметри->temperature');
        $form->FLD('__max_tokens', 'double', 'caption=Параметри->max_tokens');
        $form->FLD('__top_p', 'double', 'caption=Параметри->top_p');
        $form->FLD('__frequency_penalty', 'double', 'caption=Параметри->frequency_penalty');
        $form->FLD('__presence_penalty', 'double', 'caption=Параметри->presence_penalty');

        if (isset($rec->id)) {
            if ($fRecClone->promptParams) {
                $defModel = 'TEXT DAVINCI 003';

                foreach ($fRecClone->promptParams as $fName => $prompt) {
                    if (strpos($fName, '__') === 0) {
                        continue;
                    }
                    if ($fName == 'model') {
                        if ($prompt == 'gpt-3.5-turbo') {
                            $defModel = 'GPT 3.5 TURBO';
                        }

                        continue;
                    }

                    if (is_array($prompt) && $fName == 'messages' && ($defModel = 'GPT 3.5 TURBO')) {
                        foreach ($prompt as $p) {
                            if ($p['role'] == 'user') {
                                $form->setDefault('__mContentRole', $p['content']);
                            }

                            if ($p['role'] == 'system') {
                                $form->setDefault('__mContentSystem', $p['content']);
                            }

                            if ($p['role'] == 'assistant') {
                                $form->setDefault('__mContentAssistant', $p['content']);
                            }
                        }

                        continue;
                    }

                    $form->setDefault('__' . $fName, $prompt);
                }

                $form->setDefault('_pModel', $defModel);
            }
        } else {
            $form->setDefault('__temperature', openai_Setup::get('API_TEMPERATURE'));
            $form->setDefault('__max_tokens', openai_Setup::get('API_MAX_TOKENS'));
            $form->setDefault('__top_p', openai_Setup::get('API_TOP_P'));
            $form->setDefault('__frequency_penalty', openai_Setup::get('API_FREQUENCY_PENALTY'));
            $form->setDefault('__presence_penalty', openai_Setup::get('API_PRESENCE_PENALTY'));
        }

        $form->input('_pModel');

        if ($form->rec->_pModel == 'TEXT DAVINCI 003') {
            $form->FLD('__prompt', 'text', 'caption=Параметри->prompt (Текст), mandatory');

            if (isset($fRecClone->promptParams['messages'][0]['content'])) {
                $form->setDefault('__prompt', $fRecClone->promptParams['messages'][0]['content']);
            }
        } else {
            $form->FLD('__mContentRole', 'text', 'caption=Параметър USER (Въпросът|&#44; |*който задаваме)->Текст, mandatory');
            $form->FLD('__mContentSystem', 'text', 'caption=Параметър SYSTEM (Област на въпроса)->Текст');
            $form->FLD('__mContentAssistant', 'text', 'caption=Параметър ASSISTANT (Насочващ отговор)->Текст');

            if (isset($fRecClone->promptParams['prompt'])) {
                $form->setDefault('__mContentRole', $fRecClone->promptParams['prompt']);
            }
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;

        if ($form->isSubmitted()) {
            if ($rec->_pModel == 'GPT 3.5 TURBO') {
                $prompt = null;

                if ($rec->__prompt) {
                    $pArr['messages'] = array(array('role' => 'user', 'content' => $rec->__prompt));
                } else {
                    $pArr['messages'] = array(array('role' => 'user', 'content' => $rec->__mContentRole));
                }

                if ($rec->__mContentSystem) {
                    $pArr['messages'][] = array('role' => 'system', 'content' => $rec->__mContentSystem);
                }

                if ($rec->__mContentAssistant) {
                    $pArr['messages'][] = array('role' => 'assistant', 'content' => $rec->__mContentAssistant);
                }
            } else {
                $prompt = $rec->__prompt ? $rec->__prompt : $rec->__mContentRole;

                $pArr = array();
            }
            $pArr['temperature'] = $rec->__temperature;
            $pArr['max_tokens'] = $rec->max_tokens;
            $pArr['top_p'] = $rec->top_p;
            $pArr['frequency_penalty'] = $rec->frequency_penalty;
            $pArr['presence_penalty'] = $rec->presence_penalty;

            $cKey = null;

            try {
                if ($rec->_pModel == 'GPT 3.5 TURBO') {
                    $res = openai_Api::getChatRes($prompt, $pArr, true, 0, $cKey);
                } else {
                    $res = openai_Api::getRes($prompt, $pArr, true, 0, $cKey);
                }

                if ($cKey) {

                    redirect(array($mvc, 'list', 'cKey' => $cKey), false, 'Отговор: ' . $res);
                }
            } catch (openai_Exception $e) {
                $form->setError('_pModel', 'Грешка при изпращане на заявка');
            }

            if (!$cKey) {
                $form->setError('_pModel', 'Грешка при изпращане на заявка');
            }
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

        if ($cKey = Request::get('cKey')) {
            $data->query->where(array("#key = '[#1#]'", $cKey));
        }
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

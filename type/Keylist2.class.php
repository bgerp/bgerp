<?php


/**
 * Клас 'type_Keylist2' - Списък от ключове, избиран чрез Select2 (AJAX) интерфейс
 *
 * Работи като type_Keylist (съхранява keylist стринг вида |id1|id2|),
 * но рендира Select2 multi-select dropdown вместо чекбокси,
 * подобно на type_Key2 за единичен избор.
 *
 * @category  bgerp
 * @package   type
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class type_Keylist2 extends type_Keylist
{
    /**
     * Инициализиране на типа
     */
    public function init($params = array())
    {
        setIfNot($params['params']['savePrevSearch'], 'yes');
        $params['params']['callerType'] = 'keylist2';
        parent::init($params);
    }


    /**
     * Връща опциите, съответстващи на избраните параметри.
     * Ако suggestions са зададени директно (напр. чрез setSuggestions), ползва ги вместо MVC/AJAX заявка.
     */
    public function getOptions($limit = null, $search = '', $ids = null, $includeHiddens = false)
    {
        if (isset($this->suggestions)) {

            return $this->filterSuggestions($this->suggestions, $limit, $search, $ids);
        }

        $sLen = mb_strlen($search);
        $handler = null;

        if ($this->params['savePrevSearch'] == 'yes') {
            $params = $this->params;
            ksort($params);
            $handler = md5(serialize($params) . '|' . $includeHiddens . '|' . $limit . '|' . core_Users::getCurrent());

            if (($limit != 1) && (!$ids)) {
                if (!$sLen) {
                    $resArr = core_Cache::get('keylist2getOptions', $handler);
                    if ($resArr) {
                        $this->params['forceAjax'] = true;

                        return $resArr;
                    }
                }
            }
        }

        $haveSelectSourceArr = $this->params['selectSourceArr'] ?? null;
        if (!($this->params['selectSourceArr'] ?? null)) {
            if ($this->params['selectSource'] ?? null) {
                $this->params['selectSourceArr'] = explode('::', $this->params['selectSource']);
            } else {
                $this->params['selectSourceArr'] = array($this->params['mvc'], 'getSelectArr');
            }
        }

        $debugKey = 'KEYLIST2_SELECTSOURCE_';
        if (is_array($this->params['selectSourceArr'])) {
            $debugKey .= implode('::', $this->params['selectSourceArr']);
        } else {
            $debugKey .= $this->params['selectSourceArr'];
        }

        core_Debug::startTimer($debugKey);

        if (!($this->params['titleFld'] ?? null)) {
            $mvc = cls::get($this->params['mvc']);
            if ($mvc->getField('name', false)) {
                $this->params['titleFld'] = 'name';
            }
            if ($mvc->getField('title', false)) {
                $this->params['titleFld'] = 'title';
            }
        }

        $this->params['titleFld'] = $this->params['titleFld'] ?? $this->params['select'] ?? null;

        if (!$haveSelectSourceArr) {
            expect($this->params['titleFld'], $this);
        }

        $resArr = call_user_func($this->params['selectSourceArr'], $this->params, $limit, $search, $ids, $includeHiddens);

        if (!empty($handler)) {
            if ($sLen > 1 && $limit != 1) {
                if ($resArr) {
                    core_Cache::set('keylist2getOptions', $handler, $resArr, 10, array($this->params['mvc']));
                } else {
                    core_Cache::remove('keylist2getOptions', $handler);
                }
            }

            if ($sLen == 1) {
                core_Cache::remove('keylist2getOptions', $handler);
            }
        }

        core_Debug::stopTimer($debugKey);

        return $resArr;
    }


    /**
     * Рендира HTML поле за въвеждане на данни чрез форма (Select2 multiple)
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        if (defined('TEST_MODE') && TEST_MODE) {
            $this->params['maxSuggestions'] = 100000;
        }

        if (!($this->params['maxSuggestions'] ?? null)) {
            $maxSuggestions = $this->params['maxSuggestions'] = core_Setup::get('TYPE_KEY_MAX_SUGGESTIONS', true);
        } else {
            $maxSuggestions = $this->params['maxSuggestions'];
        }

        $options = array();
        if (defined('TEST_MODE') && TEST_MODE) {
            $this->params['forceAjax'] = false;
        }

        if (!core_Packs::isInstalled('select2')) {
            $this->params['forceAjax'] = false;
        }

        if (!($this->params['forceAjax'] ?? null)) {
            $options = $this->getOptions($maxSuggestions);
        }

        // Зареждаме текущо избраните елементи (нужно при forceAjax)
        $selectedIds = type_Keylist::toArray($value);
        if (!empty($selectedIds)) {
            $currentOpts = $this->getOptions(null, '', implode(',', array_keys($selectedIds)), true);
            if (is_array($currentOpts)) {
                foreach ($currentOpts as $key => $opt) {
                    if (!isset($options[$key])) {
                        $options[$key] = $opt;
                    }
                }
            }
        }

        $optionsCnt = countR($options);

        // Маркираме избраните опции
        foreach ($options as $key => $opt) {
            if (isset($selectedIds[$key])) {
                if (is_object($opt)) {
                    $opt = clone $opt;
                    if (!is_array($opt->attr ?? null)) {
                        $opt->attr = array();
                    }
                    $opt->attr['selected'] = 'selected';
                } else {
                    $opt = (object) array('title' => $opt, 'attr' => array('selected' => 'selected'));
                }
                $options[$key] = $opt;
            }
        }

        $attr['multiple'] = 'multiple';
        ht::setUniqId($attr);

        $tpl = ht::createSelect($name . '[]', $options, null, $attr);

        if (core_Packs::isInstalled('select2') && !Mode::is('javascript', 'no')) {
            $matchOnlyStartsWith = !(($this->params['find'] ?? null) == 'everywhere');

            $ajaxUrl = '';
            $handler = $this->getHandler();
            if (($this->params['forceAjax'] ?? null) || ($optionsCnt >= $maxSuggestions - 1)) {
                $ajaxUrl = toUrl(array($this, 'getOptions', 'hnd' => $handler, 'maxSugg' => $maxSuggestions, 'ajax_mode' => 1, 'matchOnlyStartsWith' => $matchOnlyStartsWith), 'absolute-force');
            }

            $allowClear = true;

            $minimumResultsForSearch = $this->params['minimumResultsForSearch'] ?? null;

            $placeholder = ($attr['placeholder'] ?? null) ?: ' ';
            select2_Adapter::appendAndRun($tpl, $attr['id'], $placeholder, $allowClear, null, $ajaxUrl, false, $this->params['forceOpen'] ?? null, $minimumResultsForSearch, $matchOnlyStartsWith);
        }

        return $tpl;
    }


    /**
     * Филтрира масив от suggestions по ids, search и limit — използва се когато
     * опциите са зададени директно чрез setSuggestions вместо чрез MVC/AJAX.
     */
    private function filterSuggestions($suggestions, $limit, $search, $ids)
    {
        // Разбираме $ids в асоциативен масив за бързо търсене
        $idsArr = null;
        if ($ids !== null) {
            if (is_array($ids)) {
                $idsArr = array_flip(array_map('strval', $ids));
            } elseif (preg_match("/^[0-9,]+$/", $ids)) {
                $idsArr = array_flip(explode(',', trim($ids, ',')));
            } elseif (ctype_digit("{$ids}")) {
                $idsArr = array("{$ids}" => true);
            }
        }

        $result = array();
        foreach ($suggestions as $key => $v) {
            if ($idsArr !== null && !isset($idsArr["{$key}"])) {
                continue;
            }
            if ($search) {
                $title = is_object($v) ? ($v->title ?? '') : (string) $v;
                if (mb_stripos($title, $search) === false) {
                    continue;
                }
            }
            $result[$key] = $v;
            if ($limit && count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }


    /**
     * Кодира параметрите на типа в хендлър за AJAX заявки
     */
    private function getHandler()
    {
        return core_Crypt::encodeVar($this->params, core_Crypt::EF_CRYPT_CODE . 'Keylist2');
    }


    /**
     * Връща списък с елементи при AJAX заявка от Select2
     */
    public function act_GetOptions()
    {
        $hnd = Request::get('hnd');
        $hnd = core_Crypt::decodeVar($hnd, core_Crypt::EF_CRYPT_CODE . 'Keylist2');

        if (!$hnd) {
            $res = array('error' => 'Липсва данни за елемента за избор');
        } else {
            $res = array();
            $this->params = $hnd;
            $q = trim(Request::get('q', 'varchar'));
            $options = $this->getOptions($this->params['maxSuggestions'], $q);

            if (is_array($options)) {
                foreach ($options as $key => $title) {
                    $isGroup = false;
                    $titleClass = null;

                    if (is_object($title)) {
                        $isGroup = !empty($title->group);
                        if (isset($title->attr['class'])) {
                            $titleClass = $title->attr['class'];
                        }
                        $title = $title->title;
                    }

                    $obj = (object) array('id' => $key, 'text' => $title);

                    if ($isGroup) {
                        $obj->group = true;
                        $obj->gElement = new stdClass();
                        $obj->gElement->className = 'group';
                        $obj->gElement->group = true;
                        $obj->id = null;
                    }

                    if (isset($titleClass)) {
                        if (!($obj->gElement ?? null)) {
                            $obj->gElement = new stdClass();
                            $obj->gElement->className = $titleClass;
                        } else {
                            $obj->gElement->className .= ' ' . $titleClass;
                        }
                    }

                    $res[] = $obj;
                }
            }
        }

        core_App::outputJson($res);
    }


    /**
     * Конвертира стойността от вербална (масив от Select2 multiple) към keylist стринг
     *
     * Обработва два формата:
     * - [0 => 'id1', 1 => 'id2']  - от <select multiple name="field[]">
     * - ['id1' => 'id1', ...]      - от чекбокс-стил (съвместимост с type_Keylist)
     */
    public function fromVerbal_($value)
    {
        if (!is_array($value)) {

            return;
        }

        $mapped = array();
        foreach ($value as $k => $v) {
            $id = is_numeric($k) ? $v : $k;
            if (is_numeric(trim($id ?? ''))) {
                $mapped[$id] = $id;
            }
        }

        try {
            $res = self::fromArray($mapped);
        } catch (core_exception_Expect $e) {
            $this->error = $e->getMessage();
            $res = false;
        }

        return $res;
    }
}

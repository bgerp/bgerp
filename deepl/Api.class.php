<?php


/**
 * Връзка с deepl
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
class deepl_Api
{
    /**
     * УРЛ на версиите
     */
    protected static $versionUrlArr = array('freev2' => 'https://api-free.deepl.com/v2/translate',
                                            'prov2' => 'https://api.deepl.com/v2/translate');


    /**
     *
     *
     * @param $text - стринг, който да се превеждат
     * @param string $targetLang - езика на който искаме да превеждаме. Ако не е подаден се използва DEEPL_LANG
     * @param null|string $sourceLang - езика на текста, от който се превежда. Ако не се подаде, си го определя само
     * @param boolean $cache - дали да се кешира и използва записа от кеша
     * @param array $pArr - други параметри https://www.deepl.com/docs-api/translate-text/translate-text/
     *
     * @trows deepl_Exception
     *
     * @return string
     */
    public static function translate($text, $targetLang = null, $sourceLang = null, $cache = true, $pArr = array())
    {
        // Езици, които използват кирилица
        $cyrillicLangArr = array('bg' => 'bg', 'ru' => 'ru', 'md' => 'md', 'sr' => 'sr');

        $text = trim($text);

        if (!isset($targetLang)) {
            $targetLang = deepl_Setup::get('LANG');
        }

        if (!isset($targetLang)) {
            $targetLang = core_Lg::getCurrentLanguage();
        }

        $isCyrillic = false;
        $targetLangLower = strtolower($targetLang);
        if (isset($cyrillicLangArr[$targetLangLower])) {
            $isCyrillic = true;
        }

        $pArr['target_lang'] = strtoupper($targetLang);
        if (isset($sourceLang)) {
            $pArr['source_lang'] = strtoupper($sourceLang);
        }

        deepl_Exception::expect($text, 'Не е подаден текст');
        deepl_Exception::expect(trim($pArr['target_lang']), 'Не е подаден език');

        $translateArr = $noTranslateArr = $translateMap = array();

        $stArr = preg_split('/\s*<br>\s*<br>\s*/ui', $text);

        foreach ($stArr as $k => $st) {
            $st = trim($st);
            $strip = strip_tags(html_entity_decode($st));
            $strip = preg_replace('#[\xC2\xA0]#', '', $strip);
            $strip = trim($strip);

            $hasWords = core_String::hasWords($st, $isCyrillic);
            if (!$hasWords) {
                $noTranslateArr[$k] = $st;

                continue;
            }

            $tText = '';
            if ($cache) {
                $tText = deepl_Cache::get(array('sourceText' => $st, 'sourceLg' => $sourceLang, 'translatedLg' => $targetLang));
            }

            if (strlen($tText)) {
                $noTranslateArr[$k] = $tText;

                continue;
            } else {
                if (!strlen($strip) || !strlen($st)) {
                    $noTranslateArr[$k] = $st;

                    continue;
                }
            }

            $translateArr[$k] = $st;
        }

        $pArr['text'] = array_values($translateArr);
        $translateMap = array_keys($translateArr);

        $resStrArr = $allResStrArr = array();

        if ($pArr['text']) {
            try {
                $fRes = self::execCurl($pArr);

                foreach ((array)$fRes as $k => $r) {
                    $resStrArr[$k] = $r->text;

                    if ($cache) {
                        deepl_Cache::set(array('sourceText' => $pArr['text'][$k], 'sourceLg' => $sourceLang, 'translatedText' => $r->text, 'translatedLg' => $targetLang));
                    }
                }
            } catch (deepl_Exception $e) {
                reportException($e);
                $msg = $e->getMessage();
                log_System::add(get_called_class(), "Грешка при превеждане: " . $msg, null, 'err');
                if (haveRole('debug')) {
                    status_Messages::newStatus($msg, 'warning');
                }
                $resStrArr = $pArr['text'];
            }
        }

        if (!empty($noTranslateArr)) {
            $mCnt = countR($noTranslateArr) + countR($translateArr);
            for ($i = 0; $i < $mCnt; $i++) {
                if (isset($translateArr[$i])) {
                    $k = array_search($i, $translateMap);
                    $allResStrArr[$i] = $resStrArr[$k];
                } else {
                    $allResStrArr[$i] = $noTranslateArr[$i];
                }
            }
        } else {
            $allResStrArr = $resStrArr;
        }

        return implode("<br><br>", $allResStrArr);
    }


    /**
     * Праща заявка и връща резултата
     *
     * @param string $prompt
     * @param array $pArr
     * @param boolean $useCache
     * @param integer $index
     *
     * @trows deepl_Exception
     *
     * @return string|false
     */
    public static function getRes($prompt, $pArr = array(), $useCache = true, $index = 0)
    {
        setIfNot($pArr['model'], 'text-davinci-003');
        setIfNot($pArr['temperature'], 0.7);
        setIfNot($pArr['max_tokens'], 256);
        setIfNot($pArr['top_p'], 1);
        setIfNot($pArr['frequency_penalty'], 0);
        setIfNot($pArr['presence_penalty'], 0);

        $pArr['prompt'] = $prompt;

        $resObj = self::execCurl($pArr, $useCache);

        return $resObj->choices[$index]->text ? $resObj->choices[$index]->text : false;
    }


    /**
     * Помощна функция за правена на GET заявки
     *
     * @param array $params
     * @param boolean $useCache
     *
     * @return mixed
     */
    protected static  function execCurl($params)
    {
        $version = deepl_Setup::get('VERSION');

        $url = self::$versionUrlArr[$version];

        deepl_Exception::expect($url, 'Не е настроен пакета');

        $curl = self::prepareCurl($url);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');

        curl_setopt($curl, CURLOPT_POSTFIELDS, @json_encode($params));

        core_Debug::startTimer('DEEPL_EXEC');
        $responseJson = @curl_exec($curl);
        core_Debug::stopTimer('DEEPL_EXEC');

        return self::prepareRes($responseJson);
    }


    /**
     * Помощна функция за подготвяне на curl ресурса от URL-то
     *
     * @param $url
     *
     * @return resource
     */
    protected static function prepareCurl($url)
    {
        $curl = curl_init($url);

        // Да не се проверява сертификата
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Хедъри
        $headersArr = array("Content-Type: application/json", "Accept: application/json");

        $token = deepl_Setup::get('TOKEN');

        deepl_Exception::expect($token, 'Не е зададен токън');

        $headersArr[] = "Authorization: DeepL-Auth-Key {$token}";

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headersArr);

        return $curl;
    }


    /**
     * Помощна фунмкция за подготвяне на JSON резултата
     *
     * @param string$json
     *
     * @return mixed
     */
    public static function prepareRes($json)
    {
        deepl_Exception::expect($json, 'Празен резултат');

        $response = @json_decode($json);

        deepl_Exception::expect($response, 'Грешен резултат: ' . core_Type::mixedToString($json), $json);
        deepl_Exception::expect($response->translations, 'Грешен резултат: ' . core_Type::mixedToString($json), $json);

        return $response->translations;
    }
}

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
     * @param $text - стринг или масив от стрингове, които да се превеждат
     * @param string|array $targetLang - езика на който искаме да превеждаме. Ако не е подаден се използва DEEPL_LANG
     * @param null|string $sourceLang - езика на текста, от който се превежда. Ако не се подаде, си го определя само
     * @param array $otherParams - други параметри https://www.deepl.com/docs-api/translate-text/translate-text/
     * @param array $fRes - пълният резултат, който се връща
     *
     * @trows deepl_Exception
     *
     * @return string
     */
    public static function translate($text, $targetLang = null, $sourceLang = null, $otherParams = array(), &$fRes = array())
    {
        $pArr = array();
        if (!is_array($text)) {
            $pArr['text'] = array(trim($text));
        } else {
            $pArr['text'] = $text;
        }

        if (!isset($targetLang)) {
            $targetLang = deepl_Setup::get('LANG');
        }

        if (!isset($targetLang)) {
            $targetLang = core_Lg::getCurrentLanguage();
        }

        $pArr['target_lang'] = strtoupper($targetLang);
        if (isset($sourceLang)) {
            $pArr['source_lang'] = strtoupper($sourceLang);
        }

        deepl_Exception::expect($pArr['text'], 'Не е подаден текст');
        deepl_Exception::expect(trim($pArr['target_lang']), 'Не е подаден език');

        $fRes = self::execCurl($pArr);

        $resStr = '';

        foreach ((array)$fRes as $r) {
            $resStr .= $r->text;
        }

        return $resStr;
    }


    /**
     * Праща заявка и връща резултата
     *
     * @param string $prompt
     * @param array $pArr
     * @param boolean $useCache
     * @param interger $index
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

<?php


/**
 * Връзка с openai
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
class openai_Api
{

    /**
     * Праща заявка и връща резултата
     *
     * @param string $prompt
     * @param array $pArr
     * @param boolean $useCache
     * @param interger $index
     *
     * @trows openai_Exception
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
    protected static  function execCurl($params, $useCache)
    {
        $responseJson = false;
        if ($useCache !== false) {
            $responseJson = openai_Cache::get($params);
        }

        if ($responseJson === false) {
            $url = openai_Setup::get('URL');

            openai_Exception::expect($url, 'Не е настроен пакета');

            $curl = self::prepareCurl($url);

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');

            curl_setopt($curl, CURLOPT_POSTFIELDS, @json_encode($params));

            core_Debug::startTimer('OPENAI_EXEC');
            $responseJson = @curl_exec($curl);
            core_Debug::stopTimer('OPENAI_EXEC');

            if ($useCache === true) {
                openai_Cache::set($params, $responseJson);
            }
        }

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

        $token = openai_Setup::get('TOKEN');

        openai_Exception::expect($token, 'Не е зададен токън');

        $headersArr[] = "Authorization: Bearer {$token}";

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
        openai_Exception::expect($json, 'Празен резултат');

        $response = @json_decode($json);

        openai_Exception::expect($response, 'Грешен резултат: ' . core_Type::mixedToString($json), $json);

        return $response;
    }
}

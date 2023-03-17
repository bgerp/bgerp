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
     * Праща заявка и връща резултата чрез text-davinci-003
     *
     * @param null|string $prompt - стойността, която се подава на `prompt`
     * @param array $pArr
     * ['prompt'] - въпорсът, който задаваме
     * @param boolean|string $useCache
     * @param interger $index
     * @param null|string $cKey
     *
     * @trows openai_Exception
     *
     * @return string|false
     */
    public static function getRes($prompt = null, $pArr = array(), $useCache = true, $index = 0, &$cKey = null)
    {
        self::setDefaultParams($pArr);
        setIfNot($pArr['__endpoint'], 'completions');
        setIfNot($pArr['model'], 'text-davinci-003');

        if (isset($prompt)) {
            $pArr['prompt'] = $prompt;
        }

        expect($pArr['prompt'], $pArr);

        $resObj = self::execCurl($pArr, $useCache, $cKey);

        return $resObj->choices[$index]->text ? $resObj->choices[$index]->text : false;
    }


    /**
     * Праща заявка и връща резултата чрез gpt-3.5-turbo
     *
     * @param null|string $prompt - стойността, която се подава на `messages` => `content` с `role` => `user`
     * @param array $pArr
     * ['messages'] - въпорсът, който задаваме
     * ['messages'][['role' => 'user', 'content' => 'Hello']]
     * ['messages'][['role' => 'system', 'content' => 'You are a helpful assistant.']]
     * ['messages'][['role' => 'assistant', 'content' => 'Prev answer']]
     * @param boolean|string $useCache
     * @param interger $index
     * @param null|string $cKey
     *
     * @trows openai_Exception
     *
     * @return string|false
     */
    public static function getChatRes($prompt = null, $pArr = array(), $useCache = true, $index = 0, &$cKey = null)
    {
        self::setDefaultParams($pArr);
        setIfNot($pArr['__endpoint'], 'chat/completions');
        setIfNot($pArr['model'], 'gpt-3.5-turbo');

        if (isset($prompt)) {
            $pArr['messages'] = array(array('role' => 'user', 'content' => $prompt));
        }

        expect($pArr['messages'], $pArr);

        $resObj = self::execCurl($pArr, $useCache, $cKey);

        return $resObj->choices[$index]->message ? $resObj->choices[$index]->message->content : false;
    }


    /**
     * Взема всички модели в API
     *
     * @param array $pArr
     * @param boolean|string $useCache
     *
     * @trows openai_Exception
     *
     * @return object
     */
    public static function listModels($useCache = false)
    {
        $models = self::execCurl(array('__method' => 'GET', '__endpoint' => 'models'), $useCache);

        return $models->data;
    }


    /**
     * Връща информация за модела
     *
     * @param array $pArr
     * @param boolean|string $useCache
     *
     * @trows openai_Exception
     *
     * @return object
     */
    public static function retrieveModel($model, $useCache = false)
    {
        $model = self::execCurl(array('__method' => 'GET', '__endpoint' => 'models/' . $model), $useCache);

        return $model;
    }


    /**
     * Задава дефолтни стойности на параметрите
     *
     * @param array $pArr
     */
    protected static function setDefaultParams(&$pArr)
    {
        setIfNot($pArr['temperature'], openai_Setup::get('API_TEMPERATURE'));
        setIfNot($pArr['max_tokens'], openai_Setup::get('API_MAX_TOKENS'));
        setIfNot($pArr['top_p'], openai_Setup::get('API_TOP_P'));
        setIfNot($pArr['frequency_penalty'], openai_Setup::get('API_FREQUENCY_PENALTY'));
        setIfNot($pArr['presence_penalty'], openai_Setup::get('API_PRESENCE_PENALTY'));
    }


    /**
     * Помощна функция за правена на GET заявки
     *
     * @param array $params
     * @param boolean|string $useCache
     * @param null|string $cKey
     *
     * @return mixed
     */
    protected static  function execCurl($params, $useCache, &$cKey = null)
    {
        setIfNot($params['__method'], 'POST');
        expect($params['__endpoint']);
        $cParams = $params;

        $responseJson = false;
        if ($useCache !== false) {
            $responseJson = openai_Cache::get($cParams, $cKey);
        }

        if ($useCache !== 'only') {
            if ($responseJson === false) {
                $url = rtrim(openai_Setup::get('URL'), '/') . '/' .  ltrim($params['__endpoint'], '/');

                unset($params['__endpoint']);
                openai_Exception::expect($url, 'Не е настроен пакета');

                $curl = self::prepareCurl($url);

                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $params['__method']);
                $method = $params['__method'];
                unset($params['__method']);
                if ($method != 'GET') {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, @json_encode($params));
                }

                curl_setopt($curl, CURLOPT_TIMEOUT_MS, 7000);

                core_Debug::startTimer('OPENAI_EXEC');

                $responseJson = @curl_exec($curl);

                core_Debug::stopTimer('OPENAI_EXEC');

                if ($useCache === true) {
                    if ($responseJson !== false) {
                        $cKey = openai_Cache::set($cParams, $responseJson);
                    }
                }
            }
        } else {
            if ($responseJson === false) {

                return false;
            }
        }

        return self::prepareRes($responseJson, $curl);
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
        curl_setopt($curl, CURLOPT_USERAGENT, 'openai-php/' . openai_Setup::get('VERSION'));
        curl_setopt($curl, CURLOPT_HEADER, false);

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
    public static function prepareRes($json, $curl = null)
    {
        if ($json === false) {
            self::logErr('Празен резултат от сървъра', $curl);

            openai_Exception::expect($json, 'Празен резултат');
        }

        $response = @json_decode($json);

        if ($response === false) {
            self::logErr('Грешен резултат: ' . core_Type::mixedToString($json), $curl);
        }
        openai_Exception::expect($response, 'Грешен резултат: ' . core_Type::mixedToString($json), $json);

        // Ако върне грешка
        if ($response->error) {

            self::logErr($response->error->type . ': ' .$response->error->message, $curl);

            openai_Exception::expect(false, $response->error->message, $json, $response->error);
        }

        return $response;
    }


    /**
     * Помощна функция за репортване на грешка
     *
     * @param string $msg
     */
    protected static function logErr($msg, $curl = null)
    {
        if ($curl) {
            $msg .= ' ' . curl_errno($curl) . ': ' . curl_error($curl);
        }

        if (haveRole('debug')) {
            log_System::add(get_called_class(), $msg, null, 'warning');
        }

        status_Messages::newStatus($msg, 'warning');
    }
}

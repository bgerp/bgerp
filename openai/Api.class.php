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
abstract class openai_Api
{



    /**
     * Инрерфейси
     */
    public $interfaces = 'openai_GPTIntf';


    /**
     * @param $prompt
     * @param $pArr
     * @param $useCache
     * @param $index
     * @param $cKey
     * @param $timeout
     * @return mixed
     */
    abstract public function getRes($prompt = null, $pArr = array(), $useCache = true, $index = 0, &$cKey = null, $timeout = 12);


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
    public static function setDefaultParams(&$pArr)
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
     * @param integer $timeout
     *
     * @return mixed
     */
    public static function execCurl($params, $useCache, &$cKey = null, $timeout = 12)
    {
        setIfNot($params['__method'], 'POST');
        expect($params['__endpoint']);
        $cParams = $params;

        $responseJson = false;
        if ($useCache !== false) {
            $responseJson = openai_Cache::get($cParams, $cKey);
        }

        if ($responseJson === false) {
            if (!core_Locks::get('openai_' . $cKey, 1000, 0, false)) {

                return false;
            }
        }

        if ($useCache !== 'only') {
            if ($responseJson === false) {
                $url = rtrim(openai_Setup::get('BASE_URL'), '/') . '/' .  ltrim($params['__endpoint'], '/');

                openai_Exception::expect($url, 'Не е настроен пакета');

                $curl = self::prepareCurl($url);

                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $params['__method']);
                $method = $params['__method'];

                foreach ($params as $pKey => $pVal) {
                    if (substr($pKey, 0, 2) == '__') {
                        unset($params[$pKey]);
                    }
                }

                if ($method != 'GET') {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, @json_encode($params));
                }

                curl_setopt($curl, CURLOPT_TIMEOUT_MS, $timeout * 1000);

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

        if ($responseJson !== false) {
            core_Locks::release('openai_' . $cKey);
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
            status_Messages::newStatus($msg, 'warning');
        }
        log_System::add(get_called_class(), $msg, null, 'warning');
    }
}

<?php


/**
 * Драйвер за работа с маршрути чрез OSRM API
 *
 * @category  bgerp
 * @package   osrm
 *
 * @author    David Dimitriev
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     OSRM маршрути
 */
class osrm_Api
{
    /**
     * Взима маршрут от OSRM
     *
     * @param string $startCoord
     * @param string $endCoord
     * @param array  $additionalCoord
     *
     * @return array
     */
    public static function getRoute($startCoord, $endCoord, $additionalCoord = array())
    {
        $coordinates = array();

        $coordinates[] = $startCoord;

        if (!empty($additionalCoord)) {
            $coordinates = array_merge($coordinates, $additionalCoord);
        }

        $coordinates[] = $endCoord;

        $coordinatesStr = implode(';', $coordinates);
        
        $url = "https://router.project-osrm.org/route/v1/driving/" .
            $coordinatesStr .
            "?overview=false";

        return self::execCurl($url);
    }


    /**
     * Изпълнява CURL заявка
     *
     * @param string $url
     *
     * @return array
     */
    protected static function execCurl($url)
    {
        $curl = self::prepareCurl($url);

        $responseJson = curl_exec($curl);

        if ($responseJson === false) {
            self::logErr('cURL error: ' . curl_error($curl));
        }

        $response = json_decode($responseJson, true);

        if ($response === null) {
            self::logErr('OSRM врати невалиден JSON: ' . $responseJson);
        }

        self::checkResponse($response);

        return $response;
    }


    /**
     * Подготвя CURL ресурса
     *
     * @param string $url
     *
     * @return resource
     */
    protected static function prepareCurl($url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        return $curl;
    }


    /**
     * Взима най-близките точки до координата
     *
     * @param string $coord
     * @param int    $number
     *
     * @return array
     */
    public static function getNearest($coord, $number = 1)
    {
        $url = 'https://router.project-osrm.org/nearest/v1/driving/' .
            $coord .
            '?number=' . $number;

        return self::execCurl($url);
        $res = osrm_Api::getNearest('23.3219,42.6977');
        bp($res);
    }


    /**
     * Взима матрица с разстояния и времена
     *
     * @param array $coordinates
     *
     * @return array
     */
    public static function getTable($coordinates)
    {
        $url = 'https://router.project-osrm.org/table/v1/driving/' .
            implode(';', $coordinates) .
            '?annotations=distance,duration';

        return self::execCurl($url);
    }

    
    /**
     * Съпоставя координати към маршрут
     *
     * @param array $coordinates
     *
     * @return array
     */
    public static function getMatch($coordinates)
    {
        $url = 'https://router.project-osrm.org/match/v1/driving/' .
            implode(';', $coordinates) .
            '?overview=false';
        return self::execCurl($url);
    }
    
    
    /**
     * Генерира оптимален маршрут между точки
     *
     * @param array $coordinates
     *
     * @return array
     */
    public static function getTrip($coordinates)
    {
        $url = 'https://router.project-osrm.org/trip/v1/driving/' .
            implode(';', $coordinates) .
            '?overview=false';

        return self::execCurl($url);
    }


    /**
     * Проверява отговора от OSRM
     *
     * @param array $response
     *
     * @return void
     */
    protected static function checkResponse($response)
    {
        if (!is_array($response)) {
            self::logErr('OSRM врати невалиден JSON');
        }

        if (!isset($response['code'])) {
            self::logErr('OSRM врати невалиден одговор');
        }

        if ($response['code'] != 'Ok') {
            self::logErr('OSRM грешка: ' . $response['code']);
        }
    }


    /**
     * Помощна функция за логиране на грешки
     *
     * @param string $msg
     *
     * @return void
     */
    protected static function logErr($msg)
    {
        $className = get_called_class();

        log_System::add($className, $msg, null, 'err', 7);

        if (haveRole('debug')) {
            status_Messages::newStatus($msg, 'error');
        }

        expect(false, $msg);
    }
}
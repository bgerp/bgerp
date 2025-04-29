<?php


/**
 * Прототип на драйвер за NVR устройство
 *
 *
 * @category  bgerp
 * @package   cams
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cams_driver_NVRDevice extends core_BaseClass
{


    /**
     * IP на устройството
     */
    public $ip;
    
    
    /**
     * id на устройството
     */
    public $id;
    
    
    /**
     * Потребителско име
     */
    public $user;
    
    
    /**
     * Парола за достъп
     */
    public $pass;
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'cams_DriverIntf';

    
    /**
     * Начално установяване на параметрите
     */
    public function init($params = array())
    {
        if (strpos($params, '}')) {
            $params = arr::make(json_decode($params));
        } else {
            $params = arr::make($params, true);
        }

        parent::init($params);
    }
    
    
    /**
     * Записва снимка от камерата в указания файл;
     */
    public function getPicture()
    {

        return @imagecreatefromstring(getFullPath('cams/img/novideo.jpg'));
    }
    
    
    /**
     * Записва видео в указания файл с продължителност $duration
     */
    public function captureVideo($savePath, $duration)
    {

    }
    
    
    /**
     * Взимаме настройките на камерата за резолюцията и скоростта на записа
     */
    public function getParamsFromCam($params)
    {

        return $params;
    }


    /**
     * Подготвя формата за настройките
     */
    public function prepareSettingsForm($form)
    {
    }

    
    /**
     * Проверява дали данните във формата са въведени правилно
     */
    public function validateSettingsForm($form)
    {
    }

    
    /**
     * Дали има отдалечено управление?
     */
    public function havePtzControl()
    {

        return false;
    }


    /**
     * Проверява дали състоянието е активно
     */
    public function isActive()
    {

        return true;
    }
}

<?php



/**
 * Интерфейс за драйвер на IP камера
 *
 * Медиен (картинка, видео) и PTZ (местене)
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Драйвер на IP камера
 */
class cams_DriverIntf
{
    
    /****************************************************************************************
     *                                                                                      *
     *    Видео                                                                             *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Записва видео в указания файл с продължителност $duration
     */
    function captureVideo($savePath, $duration)
    {
        return $this->class->captureVideo($savePath, $duration);
    }
    
    /****************************************************************************************
     *                                                                                      *
     *     Картинки                                                                         *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Записва снимка от камерата в указания файл;
     */
    function getPicture()
    {
        return $this->class->getPicture();
    }
    
    
    /**
     * Връща скоростта на стрийма
     */
    function getFPS()
    {
        return $this->class->FPS;
    }
    
    
    /**
     * Връща широчината на картинката
     */
    function getWidth()
    {
        return $this->class->width;
    }


    /**
     * Връща височината на картинката
     */
    function getHeight()
    {
        return $this->class->height;
    }

    /**
     * 
     * Връща резолюцията и скоростта на запис зададени в камерата
     */
	function getParamsFromCam($params)
	{
		return $this->class->getParamsFromCam($params);
	}
	
    /****************************************************************************************
     *                                                                                      *
     *  Pan, Tilt, Zoom контрол                                                             *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Дали камерата има управление PTZ
     */
    function havePtzControl()
    {
        return $this->class->havePtzControl();
    }
    
    
    /**
     * Връща формата за местене на камерата
     */
    function preparePtzForm($form)
    {
        return $this->class->preparePtzform($form);
    }
    
    
    /**
     * Изпълнява PTZ команда
     */
    function applyPtzCommands($cmdArr)
    {
        return $this->class->applyPtzCommands($cmdArr);
    }
    
    /****************************************************************************************
     *                                                                                      *
     *   Настройки и състояние                                                              *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Подготвя формата за настройките
     */
    function prepareSettingsForm($form)
    {
        return $this->class->prepareSettingsForm($form);
    }
    
    
    /**
     * Подготвя формата за настройките
     */
    function validateSettingsForm($form)
    {
        return $this->class->validateSettingsForm($form);
    }
    
    
    /**
     * Дали камерата е активна
     */
    function isActive()
    {
        return $this->class->isActive();
    }
}
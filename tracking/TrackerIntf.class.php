<?php

/**
 * Интерфейс за тракване на обекти
 *
 *
 * @category  bgerp
 * @package   tracking
 * @author    Dimitar Minekov <mitko@extperta.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title      Интерфейс на проследяващи устройства
 */
class tracking_TrackerIntf
{

    /**
     *  Информация за свободни тракери
     *
     * @return array Масив с ключове номерата на незаетите тракери
     *               или празен стринг, ако такива няма
     */
    public function getFreeTrackers()
    {
        return $this->class->getFreeTrackers();
    }

    
    /**
     * Освобождава тракер
     *
     * @param  string $trackerId системният номер на тракера
     * @return bool   TRUE - при успех FALSE при грешка
     *
     */
    public function releaseTracker($trackerId)
    {
        return $this->class->releaseTracker($trackerId);
    }


    /**
     * Заема тракер за обект за проследяване
     *
     * @param  string $trackerId системният номер на тракера
     * @return bool   TRUE - при успех FALSE при грешка
     *
     */
    public function occupyTracker($trackerId)
    {
        return $this->class->occupyTracker($inputs, $config, $persistentState);
    }
}

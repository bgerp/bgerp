<?php

/**
 * Интерфейс за създаване на отчети от различни източници в системата
 *
 *
 * @category  bgerp
 * @package   frame
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс на драйвер на I/O контролер
 */
class frame_ReportSourceIntf extends core_InnerObjectIntf
{
    
    
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Скрива полетата, които потребител с ниски права не може да вижда
     *
     * @param stdClass $data
     */
    public function hidePriceFields(&$data)
    {
        return $this->class->hidePriceFields($data);
    }
    
    
    /**
     * Връща дефолт заглавието на репорта
     */
    public function getReportTitle()
    {
        return $this->class->getReportTitle();
    }
    
    
    /**
     * Връща най-ранната дата, когато отчета може да се активира
     */
    public function getEarlyActivation()
    {
        return $this->class->getEarlyActivation();
    }
}

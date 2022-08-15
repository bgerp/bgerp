<?php

require_once 'ResultParcelInfo.class.php';

/**
 * Instances of this class are returned as a result of office working time information
 */
class ResultWorkingTime {

    /**
     * Date for which working time is valid
     * @var date
     */
    private $_date;
    
    /**
     * Flag whether office working time is overriden
     * @var boolean
     * @since 2.8.0
     */
    private $_workingTimeException;
    
    /**
     * Working time start (HHMM)
     * @var signed 16 bit integer
     */
    private $_workingTimeFrom;
    
    /**
     * Working time end (HHMM)
     * @var signed 16 bit integer
     */
    private $_workingTimeTo;

    /**
     * Constructs new instance of ResultWorkingTime from stdClass
     * @param stdClass $stdClassResultWorkingTime
     */
    function __construct($stdClassResultWorkingTime) {
        $this->_date                 = isset($stdClassResultWorkingTime->date)                 ? $stdClassResultWorkingTime->date                 : null;
        $this->_workingTimeException = isset($stdClassResultWorkingTime->workingTimeException) ? $stdClassResultWorkingTime->workingTimeException : null;
        $this->_workingTimeFrom      = isset($stdClassResultWorkingTime->workingTimeFrom)      ? $stdClassResultWorkingTime->workingTimeFrom      : null;
        $this->_workingTimeTo        = isset($stdClassResultWorkingTime->workingTimeTo)        ? $stdClassResultWorkingTime->workingTimeTo        : null;
     }

    /**
     * Gets the date this working time is valid
     * @return Working time date
     */
    public function getDate() {
        return $this->_date;
    }
    
     /**
     * Gets the flag whether office working time is overriden
     * @return Flag whether office working time is overriden
     * @since 2.8.0
     */
    public function isWorkingTimeException() {
        return $this->_workingTimeException;
    }

    /**
     * Gets the working time start time (HHMM)
     * @return Working time start time (HHMM)
     */
    public function getWorkingTimeFrom() {
        return $this->_workingTimeFrom;
    }

    /**
     * Gets the working time end time (HHMM)
     * @return Working time end time (HHMM)
     */
    public function getWorkingTimeTo() {
        return $this->_workingTimeTo;
    }
}
?>
<?php

require_once 'ResultSite.class.php';

/**
 * ResultSite instances are returned as a result of sites speedy web service requests
 */
class ResultSiteEx {

    /**
     * Site data
     * @access private
     * @var ResultSite
     */
    private $_site;

    /**
     * Specifies if there is an exact match
     * @access private
     * @var boolean
     */
    private $_exactMatch;

    /**
     * Constructs new instance of ResultSiteEx
     * @param stdClass $stdClassResultSiteEx
     */
    function __construct($stdClassResultSiteEx) {
        $this->_site       = new ResultSite($stdClassResultSiteEx->site);
        $this->_exactMatch = isset($stdClassResultSiteEx->exactMatch) ? $stdClassResultSiteEx->exactMatch : null;
    }

    /**
     * Get site data
     * @return ResultSite
     */
    public function getSite() {
        return $this->_site;
    }

    /**
     * Get flag if there is an exact match
     * @return boolean Site type
     */
    public function isExactMatch() {
        return $this->_exactMatch;
    }
}
?>
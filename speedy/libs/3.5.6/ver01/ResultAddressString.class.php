<?php

/**
 * Instances of this class are used as a result of make address methods
 * @since 2.3.0
 */
class ResultAddressString {
	
	/**
	 * Full address string
	 * @var string
	 */
	protected $_fullAddress;
	
	/**
	 * Local (street) address string. Address within site
	 * @var string
	 */
	protected $_localAddress;
	
	/**
	 * Site address string (address without street/block, streetNo, blockNo and etc. details)
	 * @var string
	 */
	protected $_siteAddress;
	
	/**
	 * Constructs new instance of this class
	 * @param unknown $stdClassResultAddressString
	 */
	function __construct($stdClassResultAddressString) {
		$this->_fullAddress  = isset($stdClassResultAddressString->fullAddress)  ? $stdClassResultAddressString->fullAddress  : null;
		$this->_localAddress = isset($stdClassResultAddressString->localAddress) ? $stdClassResultAddressString->localAddress : null;
		$this->_siteAddress  = isset($stdClassResultAddressString->siteAddress)  ? $stdClassResultAddressString->siteAddress  : null;
	}
	
	/**
	 * Gets the full address string
	 * @return string  Full address string
	 */
	public function getFullAddress() {
		return $this->_fullAddress;
	}
	
	
	/**
	 * Gets the local (street) address string. The address within site
	 * @return string Local address string
	 */
	public function getLocalAddress() {
		return $this->_localAddress;
	}
	
	/**
	 * Gets the site address string. The address without street information
	 * @return string Site address string
	 */
	public function getSiteAddress() {
		return $this->_siteAddress;
	}
}
?>
<?php

/**
 * This class is enumeration of ComplementaryServiceAllowance
 */
class ComplementaryServiceAllowance {

    /**
     * The complementary service is not allowed.
     * @var string
     */
    const COMPL_SERVICE_ALLOWANCE_BANNED = 'BANNED';

    /**
     * The complementary service is allowed (but not required).
     * @var string
     */
    const COMPL_SERVICE_ALLOWANCE_ALLOWED = 'ALLOWED';

    /**
     * The complementary service is required.
     * @var string
     */
    const COMPL_SERVICE_ALLOWANCE_REQUIRED = 'REQUIRED';

    /**
     * Value is one of the constants (BANNED, ALLOWED, REQUIRED)
     * @var string
     */
    private $_value;

    /**
     * Constructs new instance of Complementary service allownace from string
     * @param string $value
     */
    function __construct($value) {
        if ($value != self::COMPL_SERVICE_ALLOWANCE_REQUIRED && $value != self::COMPL_SERVICE_ALLOWANCE_ALLOWED ) {
            $this->_value = self::COMPL_SERVICE_ALLOWANCE_BANNED;
        } else {
            $this->_value = $value;
        }
    }

    /**
     * Get value - one of the constants (BANNED, ALLOWED, REQUIRED)
     * @return string
     */
    public function getValue() {
        return $this->_value;
    }
}
?>
<?php

/**
 * This class is enumeration of address nomenclature state
 */
class AddrNomen {

    /**
     * Speedy has no address nomenclature (streets, quarters etc.) for the site.
     * @var string
     */
    const ADDR_NOMEN_NO = 'NO';

    /**
     * Speedy has full address nomenclature (streets, quarters etc.) for the site.
     * @var string
     */
    const ADDR_NOMEN_FULL = 'FULL';

    /**
     * Speedy has partial address nomenclature (streets, quarters etc.) for this site.
     * @var string
     */
    const ADDR_NOMEN_PARTIAL = 'PARTIAL';

    /**
     * Value is one of the constants (NO, FULL, PARTIAL)
     * @var string
     */
    private $_value;

    /**
     * Constructs new instance of AddrNomen from string
     * @param string $value
     */
    function __construct($value) {
        if ($value != self::ADDR_NOMEN_FULL && $value != self::ADDR_NOMEN_PARTIAL ) {
            $this->_value = self::ADDR_NOMEN_NO;
        } else {
            $this->_value = $value;
        }
    }

    /**
     * Get value - one of the constants (NO, FULL, PARTIAL)
     * @return string
     */
    public function getValue() {
        return $this->_value;
    }
}
?>
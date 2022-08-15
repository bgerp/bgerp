<?php

/**
 * This class is enumeration of ParamLanguage
 */
class ParamLanguage {

    /**
     * Bulgarian language
     * @var string
     */
    const PARAM_LANGUAGE_BG = 'BG';

    /**
     * Engilish language
     * @var string
     */
    const PARAM_LANGUAGE_EN = 'EN';

    /**
     * Value is one of the constants (BG, EN)
     * @var string
     */
    private $_value;

    /**
     * Constructs new instance of ParamLangage from string.
     * Defaults to BG
     * @param string $value
     */
    function __construct($value) {
        if ($value != self::PARAM_LANGUAGE_BG && $value != self::PARAM_LANGUAGE_EN ) {
            $this->_value = self::PARAM_LANGUAGE_BG;
        } else {
            $this->_value = $value;
        }
    }

    /**
     * Get value - one of the constants (BG, EN)
     * @return string
     */
    public function getValue() {
        return $this->_value;
    }
}
?>
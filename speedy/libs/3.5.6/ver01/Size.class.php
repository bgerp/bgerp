<?php
/**
 * Instances of this class are used in parameter classes to specify picking size dimensions
 */
class Size {

    /**
     * Width (cm). Maximum value is 9999
     * MANDATORY: NO
     * @var integer Signed 32-bit
     */
    private $_width;

    /**
     * Height (cm). Maximum value is 9999
     * MANDATORY: NO
     * @var integer Signed 32-bit
     */
    private $_height;

    /**
     * Depth (cm). Maximum value is 9999
     * MANDATORY: NO
     * @var integer Signed 32-bit
     */
    private $_depth;

    /**
     * Constructs new instance of Size
     * @param stdClass $stdClassSize
     */
    function __construct($stdClasSize='') {
        $this->_width  = isset($stdClasSize->width)  ? $stdClasSize->width  : null;
        $this->_height = isset($stdClasSize->height) ? $stdClasSize->height : null;
        $this->_depth  = isset($stdClasSize->depth)  ? $stdClasSize->depth  : null;
    }
    

    /**
     * Get width
     * @return integer Signed 32-bit
     */
    public function getWidth() {
        return $this->_width;
    }
    
    /**
     * Set width
     * @param integer $width Signed 32-bit
     */
    public function setWidth($width) {
        $this->_width = $width;
    }

    /**
     * Get height
     * @return integer Signed 32-bit
     */
    public function getHeight() {
        return $this->_height;
    }
    
    /**
     * Set height
     * @param integer $height Signed 32-bit
     */
    public function setHeight($height) {
        $this->_height = $height;
    }

    /**
     * Get depth
     * @return integer Signed 32-bit
     */
    public function getDepth() {
        return $this->_depth;
    }
    
    /**
     * Set depth
     * @param integer $depth Signed 32-bit
     */
    public function setDepth($depth) {
        $this->_depth = $depth;
    }

    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->width  = $this->_width;
        $stdClass->height = $this->_height;
        $stdClass->depth  = $this->_depth;
        return $stdClass;
    }
}
?>
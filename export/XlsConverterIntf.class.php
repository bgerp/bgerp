<?php


/**
 * Интерфейс за конвертиране на подготвен CSV файл към XLS
 *
 * @category  bgerp
 * @package   export
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class export_XlsConverterIntf
{
    public $class;


    public function isAvailable()
    {
        return $this->class->isAvailable();
    }


    /**
     * @param string        $fileHnd - fileman манипулатор на CSV
     * @param stdClass|null $csvData - fieldSet, listFields и params на CSV експорта
     * @return string|null - fileman манипулатор на XLS
     */
    public function convertToXls($fileHnd, $csvData = null)
    {
        return $this->class->convertToXls($fileHnd, $csvData);
    }
}

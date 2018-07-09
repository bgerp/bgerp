<?php


/**
 * Интерфейс за добавяне на файлове от документи в диструбутив
 *
 * @category  bgerp
 * @package   distro
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class distro_AddFilesIntf
{
    /**
     * Функция, която връща масив с манипулаторите на всички файлове,
     * които ще се добавят в диструбутива
     *
     * @param int id - id на записа от модела
     *
     * @return array - Масив с ключ манипулатора на файла
     */
    public function getFilesArr($id)
    {
        return $this->class->getFilesArr($id);
    }
}

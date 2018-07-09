<?php


/**
 * Клас 'doc_plg_TransferOwnership'
 *
 * Плъгин за за прехвурляне на собственоста на кориците, на които е отговорник
 *
 * @system или @anonym на първия регистриран потребител
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class doc_plg_TransferOwnership extends core_Plugin
{
    /**
     * След създаването на първия потребител
     */
    public static function on_AfterCreateFirstUser($mvc, &$res)
    {
        // Намираме всички класове, които са корици на папки
        $options = core_Classes::getOptionsByInterface('doc_FolderIntf');
        if (count($options)) {
            foreach ($options as $name) {
                
                // За всяка корица прехвърляме празните отговорници на първия потребител
                $Class = cls::get($name);
                doc_FolderPlg::transferEmptyOwnership($Class, $res);
            }
        }
    }
}

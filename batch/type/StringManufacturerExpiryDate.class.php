<?php

/**
 * Клас  'batch_type_StringManufacturerExpiryDate' - Номер + Производител + Годен до
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 *
 * @license   GPL 3
 * @since     v 0.1
 *
 */
class batch_type_StringManufacturerExpiryDate extends batch_type_StringExpiryDate
{
    /**
     * Получава дата от трите входни стойности
     */
    public function fromVerbal($value)
    {
        if (empty($value)) return;

        $this->error = null;
        
        $delimiter = html_entity_decode($this->params['delimiter'], ENT_COMPAT, 'UTF-8');

        if (is_scalar($value)) {
            $value = str_replace($delimiter, '|', $value);
            $parts = explode('|', $value);
            $valueArr = array(
                's' => isset($parts[0]) ? trim($parts[0]) : '',
                'm' => isset($parts[1]) ? trim($parts[1]) : '',
                'd' => isset($parts[2]) ? trim($parts[2]) : '',
            );
        } else {
            $valueArr = $value;
        }

        $manufacturerErrors = array();
        if (empty($valueArr['m'])) {
            $manufacturerErrors[] = 'Липсва производител';
        } elseif (strpos($valueArr['m'], $delimiter) !== false) {
            $manufacturerErrors[] = "В производителя не трябва да се съдържа|* <b>{$delimiter}</b>";
        }

        $parentResult = parent::fromVerbal(array('s' => $valueArr['s'], 'd' => $valueArr['d']));

        if ($parentResult === false || $parentResult === null || countR($manufacturerErrors)) {
            $this->error = implode(', ', array_filter(array_merge($manufacturerErrors, array($this->error))));
            return false;
        }

        $parentParts = explode('|', $parentResult);

        return implode('|', array(
            $parentParts[0],
            trim($valueArr['m']),
            $parentParts[1] ?? '',
        ));
    }
}
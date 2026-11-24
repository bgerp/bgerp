<?php


/**
 * Portable JSON representation of the current drawing scripts.
 */
class draw_ScriptArchive
{
    const VERSION = 1;


    public static function export($mvc)
    {
        $scripts = array();
        $query = $mvc->getQuery();
        $query->show('name,script,order,state,exState');
        $query->orderBy('name');
        while ($rec = $query->fetch()) {
            $row = array(
                'name' => $rec->name,
                'script' => $rec->script ?? '',
                'order' => isset($rec->order) ? (int) $rec->order : null,
                'state' => $rec->state,
            );
            if ($rec->state === 'rejected') {
                $row['exState'] = !empty($rec->exState) ? $rec->exState : 'active';
            }
            $scripts[] = $row;
        }

        $archive = array('format' => 'draw_Designs', 'version' => self::VERSION, 'scripts' => $scripts);

        return json_encode($archive, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
    }


    /**
     * Validate the complete archive before writing any records.
     */
    public static function decode($json)
    {
        if (!is_string($json)) {
            throw new InvalidArgumentException('Файлът не може да се прочете');
        }
        if (strncmp($json, "\xEF\xBB\xBF", 3) === 0) {
            $json = substr($json, 3);
        }
        $archive = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Невалиден JSON файл');
        }
        if (($archive->format ?? null) !== 'draw_Designs' || ($archive->version ?? null) !== self::VERSION || !is_array($archive->scripts ?? null)) {
            throw new InvalidArgumentException('Очаква се JSON файл от експорт на скриптове за параметрични дизайни');
        }

        $names = array();
        foreach ($archive->scripts as $index => $row) {
            $position = $index + 1;
            if (!is_string($row->name ?? null) || trim($row->name) === '' || mb_strlen($row->name, 'UTF-8') > 255) {
                throw new InvalidArgumentException("Невалидно име на скрипт на позиция|* {$position}");
            }
            $key = rtrim($row->name, ' ');
            if (isset($names[$key])) {
                throw new InvalidArgumentException("Повторено име на скрипт на позиция|* {$position}");
            }
            $names[$key] = true;
            // draw_Designs.script maps to MEDIUMTEXT, whose limit is in bytes.
            if (!is_string($row->script ?? null) || strlen($row->script) > 16777215) {
                throw new InvalidArgumentException("Невалиден код на скрипта на позиция|* {$position}");
            }
            if (!property_exists($row, 'order') || ($row->order !== null && (!is_int($row->order) || $row->order < -2147483648 || $row->order > 2147483647))) {
                throw new InvalidArgumentException("Невалиден пореден номер на позиция|* {$position}");
            }
            if (!in_array($row->state ?? null, array('active', 'closed', 'rejected'), true)
                || ($row->state === 'rejected' && !in_array($row->exState ?? null, array('active', 'closed'), true))) {
                throw new InvalidArgumentException("Невалидно състояние на скрипта на позиция|* {$position}");
            }
        }

        return $archive->scripts;
    }


    public static function import($mvc, $rows, $updateExisting)
    {
        $counts = array('added' => 0, 'updated' => 0, 'skipped' => 0);
        $lock = 'draw_Designs_import';
        if (!core_Locks::obtain($lock, 120, 1)) {
            throw new InvalidArgumentException('В момента се зарежда друг файл със скриптове. Опитайте отново.');
        }
        $renewAt = time() + 60;
        try {
            foreach ($rows as $index => $row) {
                if (time() >= $renewAt) {
                    core_Locks::obtain($lock, 120, 1);
                    $renewAt = time() + 60;
                }
                $existing = $mvc->fetch(array("#name = '[#1#]'", $row->name), '*', false);
                if (!$existing) {
                    $rec = (object) array('name' => $row->name, 'script' => $row->script, 'order' => $row->order, 'state' => $row->state);
                    if ($row->state === 'rejected') {
                        $rec->exState = $row->exState;
                    }
                    $mvc->requireRightFor('add', $rec);
                    $mvc->save($rec);
                    $mvc->logWrite('Импорт на скрипт', $rec->id);
                    if ($row->state === 'rejected') {
                        plg_Rejected::invalidateBinCache($mvc);
                    }
                    $counts['added']++;
                    continue;
                }

                $order = isset($existing->order) ? (int) $existing->order : null;
                $scriptChanged = ($existing->script ?? '') !== $row->script;
                if (!$updateExisting || (!$scriptChanged && $order === $row->order) || !$mvc->haveRightFor('changerec', $existing)) {
                    $counts['skipped']++;
                    continue;
                }

                $rec = (object) array('id' => $existing->id, 'order' => $row->order);
                if ($scriptChanged) {
                    $rec->name = $existing->name;
                    $rec->script = $row->script;
                    $rec->version = $existing->version ?? null;
                    change_Plugin::saveChanges($mvc, $existing, $rec, $mvc->changableFields);
                } else {
                    $mvc->save($rec, 'order');
                }
                $mvc->logWrite('Обновяване от импорт', $rec->id);
                $counts['updated']++;
            }
        } catch (core_exception_Db $e) {
            reportException($e);
            $position = $index + 1;
            throw new InvalidArgumentException("Грешка при запис на позиция|* {$position}. |Добавени|*: {$counts['added']}, |обновени|*: {$counts['updated']}.");
        } finally {
            core_Locks::release($lock);
        }

        return $counts;
    }
}

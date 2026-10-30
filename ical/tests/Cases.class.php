<?php


/**
 * Повторяеми проверки на iCalendar адаптера без записи в база данни
 *
 * @category  tests
 * @package   ical
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class ical_tests_Cases
{
    /** Създава самостоятелен календар от синтетични събития. */
    public static function calendar($events, $header = '')
    {
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//bgERP//ICS test//BG\r\n" . $header . $events . "END:VCALENDAR\r\n";
    }


    /** Пример за реалния файлов преглед, без действителни участници. */
    public static function preview()
    {
        return self::calendar("BEGIN:VEVENT\r\nUID:meeting@example.invalid\r\nDTSTAMP:20261001T100000Z\r\nDTSTART;TZID=Europe/Sofia:20261029T143000\r\nDTEND;TZID=Europe/Sofia:20261029T153000\r\nSUMMARY:Тестова среща - кирилица\r\nLOCATION:София\\, зала 1\r\nDESCRIPTION:Първи ред\\nВтори ред https://example.com/\r\nORGANIZER;CN=Тест Организатор:mailto:organizer@example.com\r\nATTENDEE;CN=Тест Участник:mailto:attendee@example.com\r\nSTATUS:CONFIRMED\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nUID:duration@example.invalid\r\nDTSTART:20261030T100000Z\r\nDURATION:PT1H2M35S\r\nSUMMARY:Продължителност 1 час 2 минути 35 секунди\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nUID:allday@example.invalid\r\nDTSTART;VALUE=DATE:20261031\r\nDTEND;VALUE=DATE:20261101\r\nSUMMARY:Целодневно събитие\r\nEND:VEVENT\r\n");
    }


    /** Проверява адаптера; snapshot служи за сравнение с предишния код. */
    public static function run($parser = 'ical_Parser', $baseline = false)
    {
        $checks = 0;
        $assert = function ($condition, $message) use (&$checks) {
            expect($condition, $message);
            $checks++;
        };
        $modern = ical_Setup::get('VERSION') === '3.6.0';
        $parse = function ($body, $header = '') use ($parser) {
            return $parser::getEvents(self::calendar($body, $header), 'Y-m-d H:i:s');
        };
        $previousZone = date_default_timezone_get();
        date_default_timezone_set('Europe/Sofia');
        try {
            $assert($parser::getEvents('') === array(), 'Empty input');
            if (!$baseline) {
                $assert($parser::getEvents(null) === array(), 'Null input');
            }
            $assert($parser::getEvents(self::calendar('')) === array(), 'Empty calendar');
            $events = $parser::getEvents(self::preview(), 'Y-m-d H:i:s');
            $assert(count($events) === 3, 'Preview event count');
            $assert($events[0]->summary === 'Тестова среща - кирилица', 'Cyrillic');
            $assert($events[0]->dtstart === '2026-10-29 14:30:00', 'Sofia start');
            $assert($events[0]->dtend === '2026-10-29 15:30:00', 'Sofia end');
            $assert(strpos((string) $events[0]->organizerVrb, 'Тест Организатор') !== false, 'Organizer');
            $assert(strpos((string) $events[0]->attendeeVrb, 'Тест Участник') !== false, 'Attendee');
            $assert(strpos((string) $events[0]->descriptionVrb, 'https://example.com/') !== false, 'Description URL');
            $assert($events[2]->dtstart === '2026-10-31 00:00:00', 'All-day start');
            $assert($events[2]->dtend === '2026-11-01 00:00:00', 'Exclusive all-day end');
            if (!$baseline) {
                $assert($events[1]->duration == 3755, 'Duration seconds');
                $assert(strpos($events[1]->statusVrb, 'Не ангажира време') !== false, 'Transparent event');
            }
            // Продължителността се проверява отделно, защото старият адаптер бърка секундите.
            $snapshot = array_map('get_object_vars', array($events[0], $events[2]));
            $minimal = $parse("BEGIN:VEVENT\r\nDTSTART:20261029T100000Z\r\nEND:VEVENT\r\n");
            $assert($minimal[0]->summary === tr('Събитие') . ' 1', 'Missing optional properties');
            $assert($minimal[0]->dtstart === '2026-10-29 12:00:00', 'UTC to local');
            $recurring = $parse("BEGIN:VEVENT\r\nUID:daily@example.invalid\r\nDTSTART:20261001T100000Z\r\nDTEND:20261001T110000Z\r\nRRULE:FREQ=DAILY;COUNT=3\r\nEXDATE:20261002T100000Z\r\nSUMMARY:Повтарящо се събитие\r\nEND:VEVENT\r\n");
            $assert(count($recurring) === 2, 'Recurrence with EXDATE');
            $dates = array_map(function ($event) { return $event->dtstart; }, $recurring);
            sort($dates);
            $assert($dates === array('2026-10-01 13:00:00', '2026-10-03 13:00:00'), 'Recurrence dates');
            $snapshot[] = $dates;
            $folded = $parse("BEGIN:VEVENT\r\nDTSTART:20261029T100000Z\r\nSUMMARY:Събитие с дълъг\r\n  текст\r\nEND:VEVENT\r\n");
            $assert(strpos($folded[0]->summary, 'текст') !== false, 'Folded Cyrillic');
            if (!$baseline) {
                $escaped = $parse("BEGIN:VEVENT\r\nDTSTART:20261029T100000Z\r\nSUMMARY:<b>Текст</b>\r\nORGANIZER;CN=<b>Име</b>:mailto:test@example.invalid\r\nEND:VEVENT\r\n");
                $assert(strpos($escaped[0]->summaryVrb, '<b>') === false, 'Summary escaping');
                $assert(strpos($escaped[0]->organizerVrb, '<b>') === false, 'Person escaping');
            }
            if ($modern) {
                $monthly = $parse("BEGIN:VEVENT\r\nUID:monthly@example.invalid\r\nDTSTART:20260330T100000Z\r\nDTEND:20260330T110000Z\r\nRRULE:FREQ=MONTHLY;BYDAY=5MO;COUNT=2\r\nEND:VEVENT\r\n");
                $assert(count($monthly) === 2, 'Fifth Monday count');
                $monthlyDates = array_map(function ($event) { return substr($event->dtstart, 0, 10); }, $monthly);
                sort($monthlyDates);
                $assert($monthlyDates === array('2026-03-30', '2026-06-29'), 'Fifth Monday skips short months');
                foreach (array('Europe/Berlin', 'W. Europe Standard Time') as $zone) {
                    $zoned = $parse("BEGIN:VEVENT\r\nDTSTART;TZID=$zone:20261029T100000\r\nDTEND;TZID=$zone:20261029T110000\r\nLAST-MODIFIED:20261001T120000Z\r\nEND:VEVENT\r\n");
                    $assert($zoned[0]->dtstart === '2026-10-29 11:00:00', 'Zone ' . $zone);
                    $assert($zoned[0]->dtend === '2026-10-29 12:00:00', 'Zone end ' . $zone);
                    $assert($zoned[0]->lastmodified === '2026-10-01 15:00:00', 'LAST-MODIFIED compatibility');
                }
                $outlook = $parse("BEGIN:VEVENT\r\nDTSTART;TZID=FLE Standard Time:20261029T100000\r\nDTEND;TZID=FLE Standard Time:20261029T110000\r\nEND:VEVENT\r\n", "BEGIN:VTIMEZONE\r\nTZID:FLE Standard Time\r\nBEGIN:STANDARD\r\nDTSTART:19701025T040000\r\nRRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10\r\nTZOFFSETFROM:+0300\r\nTZOFFSETTO:+0200\r\nEND:STANDARD\r\nBEGIN:DAYLIGHT\r\nDTSTART:19700329T030000\r\nRRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3\r\nTZOFFSETFROM:+0200\r\nTZOFFSETTO:+0300\r\nEND:DAYLIGHT\r\nEND:VTIMEZONE\r\n");
                $assert($outlook[0]->dtstart === '2026-10-29 10:00:00', 'Outlook VTIMEZONE');
                $dst = $parse("BEGIN:VEVENT\r\nUID:dst@example.invalid\r\nDTSTART;TZID=Europe/Sofia:20261024T100000\r\nDTEND;TZID=Europe/Sofia:20261024T110000\r\nRRULE:FREQ=DAILY;COUNT=3\r\nEND:VEVENT\r\n");
                $assert(count($dst) === 3, 'DST count');
                foreach ($dst as $e) {
                    $assert(substr($e->dtstart, 11) === '10:00:00', 'DST preserves local time');
                }
                $invalid = $parse("BEGIN:VEVENT\r\nSUMMARY:Без начало\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nDTSTART:20261029T100000Z\r\nDTEND:None\r\nSUMMARY:Буквални \"кавички\" 😀\r\nEND:VEVENT\r\n");
                $assert(count($invalid) === 1, 'Ignore missing DTSTART and DTEND None');
                $assert($invalid[0]->summary === 'Буквални "кавички" 😀', 'Quotes and emoji');
                $moved = $parse("BEGIN:VEVENT\r\nUID:moved@example.invalid\r\nDTSTART:20261001T100000Z\r\nDTEND:20261001T110000Z\r\nRRULE:FREQ=DAILY;COUNT=3\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nUID:moved@example.invalid\r\nRECURRENCE-ID:20261002T100000Z\r\nDTSTART:20261002T140000Z\r\nDTEND:20261002T150000Z\r\nEND:VEVENT\r\n");
                $assert(count($moved) === 3, 'Moved recurrence count');
                $assert(count(array_filter($moved, function ($e) { return $e->dtstart === '2026-10-02 17:00:00'; })) === 1, 'Moved recurrence time');
            }
            $body = '';
            for ($i = 0; $i < 3000; $i++) {
                $body .= "BEGIN:VEVENT\r\nUID:large-$i@example.invalid\r\nDTSTART:20261029T100000Z\r\nDTEND:20261029T110000Z\r\nSUMMARY:Събитие $i\r\nEND:VEVENT\r\n";
            }
            $started = microtime(true);
            $large = $parse($body);
            $seconds = round(microtime(true) - $started, 3);
            $assert(count($large) === 3000, 'Large calendar count');
            $assert($large[2999]->summary === 'Събитие 2999', 'Large calendar complete');
        } finally {
            date_default_timezone_set($previousZone);
        }

        return array('checks' => $checks, 'largeSeconds' => $seconds, 'snapshot' => $snapshot);
    }
}

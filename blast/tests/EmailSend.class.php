<?php


/**
 * Тестове за опашката на циркулярните имейли
 *
 * @category bgerp
 * @package  blast
 */
class blast_tests_EmailSend extends unit_Class
{
    /**
     * Проверява оптималното разреждане и запазването на реда вътре в домейна
     */
    public function test_SpreadMaximizesMinimumDomainDistance()
    {
        $records = $this->makeRecords(array(
            'a1@Example.COM',
            'a2@example.com',
            'a3@example.com',
            'b1@test.bg',
            'b2@test.bg',
            'b3@test.bg',
            'c1@company.eu',
            'c2@company.eu',
        ));

        $ordered = blast_EmailSend::spreadByDomain($records, $distance);

        UT::expectEqual($distance, 3);
        UT::expectEqual($this->getMinimumDistance($ordered), 3);
        UT::expectEqual(count($ordered), count($records));

        $inputEmails = $this->getEmails($records);
        $orderedEmails = $this->getEmails($ordered);
        sort($inputEmails);
        sort($orderedEmails);
        UT::expectEqual(implode(',', $orderedEmails), implode(',', $inputEmails));

        $inputByDomain = $this->getEmailsByDomain($records);
        $orderedByDomain = $this->getEmailsByDomain($ordered);

        foreach ($inputByDomain as $domain => $emails) {
            UT::expectEqual(implode(',', $orderedByDomain[$domain]), implode(',', $emails));
        }
    }


    /**
     * Проверява граничните случаи
     */
    public function test_SpreadHandlesSingleAndUniqueDomains()
    {
        $ordered = blast_EmailSend::spreadByDomain(array(), $distance);
        UT::expectEqual(count($ordered), 0);
        UT::expectEqual($distance, 0);

        $singleDomain = $this->makeRecords(array(
            'a1@example.com',
            'a2@example.com',
            'a3@example.com',
        ));
        $ordered = blast_EmailSend::spreadByDomain($singleDomain, $distance);
        UT::expectEqual($distance, 1);
        UT::expectEqual($this->getMinimumDistance($ordered), 1);

        $uniqueDomains = $this->makeRecords(array(
            'a@example.com',
            'b@test.bg',
            'c@company.eu',
        ));
        blast_EmailSend::spreadByDomain($uniqueDomains, $distance);
        UT::expectEqual($distance, 3);

        $tiedMaxima = $this->makeRecords(array(
            'a1@a.test',
            'a2@a.test',
            'a3@a.test',
            'a4@a.test',
            'b1@b.test',
            'b2@b.test',
            'b3@b.test',
            'b4@b.test',
            'c1@c.test',
        ));
        $ordered = blast_EmailSend::spreadByDomain($tiedMaxima, $distance);
        UT::expectEqual($distance, 2);
        UT::expectEqual($this->getMinimumDistance($ordered), 2);
    }


    /**
     * Проверява отстоянието спрямо края на вече изпратената поредица
     */
    public function test_SpreadRespectsPreviouslySentDomain()
    {
        $previous = $this->makeRecords(array(
            'sent@a.test',
        ));
        $pending = $this->makeRecords(array(
            'next@a.test',
            'b@b.test',
            'c@c.test',
        ));

        $ordered = blast_EmailSend::spreadByDomain($pending, $distance, $previous);

        UT::expectEqual($distance, 3);
        UT::expectEqual($this->getMinimumDistance(array_merge($previous, $ordered)), 3);

        $pending = $this->makeRecords(array(
            'next1@a.test',
            'next2@a.test',
            'b@b.test',
            'c@c.test',
        ));
        $ordered = blast_EmailSend::spreadByDomain($pending, $distance, $previous);

        UT::expectEqual($distance, 2);
        UT::expectEqual($this->getMinimumDistance(array_merge($previous, $ordered)), 2);
    }


    /**
     * Създава тестови записи
     */
    private function makeRecords($emails)
    {
        $records = array();

        foreach ($emails as $email) {
            $rec = new stdClass();
            $rec->email = $email;
            $records[] = $rec;
        }

        return $records;
    }


    /**
     * Връща имейлите от записите
     */
    private function getEmails($records)
    {
        $emails = array();

        foreach ($records as $rec) {
            $emails[] = $rec->email;
        }

        return $emails;
    }


    /**
     * Групира имейлите по домейн
     */
    private function getEmailsByDomain($records)
    {
        $result = array();

        foreach ($records as $rec) {
            $domain = mb_strtolower(type_Email::domain($rec->email));
            $result[$domain][] = $rec->email;
        }

        return $result;
    }


    /**
     * Изчислява минималното отстояние между еднакви домейни
     */
    private function getMinimumDistance($records)
    {
        $lastPosition = array();
        $minDistance = count($records);

        foreach ($records as $position => $rec) {
            $domain = mb_strtolower(type_Email::domain($rec->email));

            if (isset($lastPosition[$domain])) {
                $minDistance = min($minDistance, $position - $lastPosition[$domain]);
            }

            $lastPosition[$domain] = $position;
        }

        return $minDistance;
    }
}

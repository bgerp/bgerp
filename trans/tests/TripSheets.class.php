<?php


/**
 * Local integration checks. Run via the normal application index/bootstrap.
 * Requires TRANS_TRIP_TESTS=1 and a test user nickname as the fourth argument.
 */
class trans_tests_TripSheets extends core_BaseClass
{
    private $checks = 0;


    private function check($condition, $message)
    {
        if (!$condition) throw new RuntimeException($message);
        $this->checks++;
    }


    public function cli_Run()
    {
        global $argv;
        expect(getenv('TRANS_TRIP_TESTS') === '1', 'Run only against an explicitly selected local test database');
        $user = core_Users::fetch(array("#nick = '[#1#]'", $argv[4] ?? ''));
        expect($user && core_Users::haveRole('ceo', $user->id ?? null), 'A local test user with the ceo role is required');
        core_Users::sudo($user->id ?? null);
        $mvc = cls::get('trans_TripSheets');
        $mvc->setupMVC();
        $sheets = array();
        $line = null;
        $vehicle = null;

        try {
            $valid = (object) array('odometerStart' => 0, 'odometerEnd' => 0,
                'departedOn' => '2026-01-01 08:00:00', 'returnedOn' => '2026-01-01 09:00:00');
            $this->check(!trans_TripSheets::getReportErrors($valid), 'Zero odometer and zero distance are valid');
            $this->check(count(trans_TripSheets::getReportErrors(new stdClass())) == 4, 'Missing report values must not become zero');
            foreach (array(array('odometerStart', -1), array('odometerEnd', -1), array('returnedOn', '2026-01-01 07:59:00')) as $case) {
                $invalid = clone $valid;
                $invalid->{$case[0]} = $case[1];
                $this->check((bool) trans_TripSheets::getReportErrors($invalid), 'Reject invalid ' . $case[0]);
            }

            $existingLine = trans_Lines::getQuery()->fetch();
            expect(!empty($existingLine->folderId), 'Create a local transport folder/line before running integration checks');
            $drivers = trans_Vehicles::getDriverOptions();
            expect(count($drivers), 'A local driver is required');
            $vehicle = (object) array('name' => 'Trip sheet test ' . uniqid(), 'number' => 'TEST1234', 'type' => 'truck', 'state' => 'active');
            trans_Vehicles::save($vehicle);
            $line = (object) array('title' => 'TEST - Пътни листи ' . date('Y-m-d H:i:s'), 'start' => '2026-01-01 08:00:00',
                'vehicle' => $vehicle->name ?? '', 'folderId' => $existingLine->folderId ?? null, 'state' => 'draft');
            trans_Lines::save($line);
            $line = trans_Lines::fetch($line->id ?? null, '*', false);
            $base = (object) array('lineId' => $line->id ?? null, 'originId' => $line->containerId ?? null,
                'threadId' => $line->threadId ?? null, 'folderId' => $line->folderId ?? null, 'series' => 'TEST',
                'companyName' => 'ТЕСТОВО ПРЕДПРИЯТИЕ', 'companyNumber' => '000000000', 'companyAddress' => 'Тестов адрес',
                'vehicleId' => $vehicle->id ?? null, 'vehicleModel' => 'Тестов автомобил', 'driverId' => key($drivers),
                'departure' => '2026-01-01 08:00:00', 'route' => 'Склад - клиент - склад', 'cargo' => 'Тестови стоки',
                'transportDocuments' => 'Тестов документ', 'authorizedBy' => 'Тестов ръководител', 'transportType' => 'own', 'state' => 'draft');
            $sheet = clone $base;
            $mvc->save($sheet);
            $sheets[] = $sheet->id ?? null;
            $this->check((trans_TripSheets::getLineSummary($line->id ?? null)->distance ?? null) === null, 'Draft has no reported distance');
            $this->check(!$mvc->haveRightFor('report', $sheet), 'Draft cannot be reported');
            $sheet->state = 'pending';
            $sheet->route = '';
            $this->check(!$mvc->save($sheet, 'state'), 'An incomplete header cannot be issued');
            $this->check($mvc->fetchField($sheet->id ?? null, 'state') === 'draft', 'Failed issue leaves the draft intact');
            $sheet->route = $base->route ?? '';
            $this->check((bool) $mvc->save($sheet, 'state'), 'Issue through a partial state save');
            $sheet = $mvc->fetch($sheet->id ?? null, '*', false);
            $this->check(!empty($sheet->issuedOn) && ($sheet->vehicleNumber ?? '') === 'TEST1234', 'Issue snapshot is persisted');
            $this->check(!$mvc->haveRightFor('edit', $sheet) && !$mvc->haveRightFor('pending', $sheet), 'Issued header cannot be edited or reissued');
            core_Users::sudo(core_Users::ANONYMOUS_USER);
            try {
                $this->check(!$mvc->haveRightFor('report', $sheet), 'Anonymous user cannot report');
            } finally {
                core_Users::exitSudo();
            }
            $this->check($mvc->haveRightFor('order', $sheet), 'Own-account order is available');
            $public = clone $sheet;
            $public->transportType = 'public';
            $this->check(!$mvc->haveRightFor('order', $public), 'Public transport has no own-account order');
            Request::push(array('id' => $sheet->id ?? null), 'tripReportTest');
            try {
                $html = $mvc->act_Report()->getContent();
                $this->check(strpos($html, 'odometerStart') !== false && strpos($html, 'odometerEnd') !== false, 'Report form renders with its native action');
            } finally {
                Request::pop('tripReportTest');
            }
            $sheet->route = 'tampered';
            $sheet->vehicleNumber = 'tampered';
            $mvc->save($sheet, 'route,vehicleNumber');
            $sheet = $mvc->fetch($sheet->id ?? null, '*', false);
            $this->check(($sheet->route ?? '') === ($base->route ?? '') && ($sheet->vehicleNumber ?? '') === 'TEST1234', 'Issued snapshots survive attempted changes');

            $line->state = 'closed';
            trans_Lines::save($line, 'state');
            $this->check($mvc->haveRightFor('report', $sheet), 'Closed line still accepts a report');
            foreach ((array) $valid as $field => $value) $sheet->{$field} = $value;
            $sheet->odometerEnd = 125;
            $sheet->state = 'active';
            $sheet->_tripReport = true;
            $this->check((bool) $mvc->save($sheet), 'Report is saved');
            $this->check((trans_TripSheets::getLineSummary($line->id ?? null)->distance ?? null) === 125, 'Reported distance is summed');
            $mvc->reject($sheet->id ?? null);
            $this->check((trans_TripSheets::getLineSummary($line->id ?? null)->distance ?? null) === null, 'Rejection removes the distance');

            $replacement = clone $base;
            $replacement->state = 'pending';
            $mvc->save($replacement);
            $sheets[] = $replacement->id ?? null;
            foreach ((array) $valid as $field => $value) $replacement->{$field} = $value;
            $replacement->odometerEnd = 125;
            $replacement->state = 'active';
            $replacement->_tripReport = true;
            $this->check((bool) $mvc->save($replacement), 'Replacement may reuse rejected mileage');
            $rejected = $mvc->fetch($sheet->id ?? null, '*', false);
            $this->check(!$mvc->haveRightFor('restore', $rejected), 'Overlapping restoration is denied');
            $this->check(!$mvc->restore($sheet->id ?? null), 'Save path also prevents overlapping restoration');

            $next = clone $base;
            $next->state = 'pending';
            $mvc->save($next);
            $sheets[] = $next->id ?? null;
            $next->state = 'active';
            $next->_tripReport = true;
            $next->odometerStart = 100;
            $next->odometerEnd = 150;
            $next->departedOn = '2026-01-01 10:00:00';
            $next->returnedOn = '2026-01-01 11:00:00';
            $this->check(!$mvc->save($next), 'Overlapping mileage is refused');
            $next->odometerStart = 125;
            $next->departedOn = '2026-01-01 08:30:00';
            $this->check(!$mvc->save($next), 'Overlapping time is refused');
            $next->departedOn = '2026-01-01 09:00:00';
            $this->check((bool) $mvc->save($next), 'Adjacent time and mileage are allowed');
            $this->check((trans_TripSheets::getLineSummary($line->id ?? null)->distance ?? null) === 150, 'Multiple reports add exactly once');
            $mvc->reject($replacement->id ?? null);
            $this->check((bool) $mvc->restore($sheet->id ?? null), 'Non-overlapping report can be restored');
            $this->check((trans_TripSheets::getLineSummary($line->id ?? null)->distance ?? null) === 150, 'Restore includes the distance again');
            echo 'PASS ' . $this->checks . " checks\n";
        } catch (Throwable $error) {
            echo 'FAIL after ' . $this->checks . ' checks: ' . $error->getMessage() . "\n";
            return 1;
        } finally {
            foreach ($sheets as $id) {
                $record = $mvc->fetch($id, '*', false);
                if (($record->state ?? null) != 'rejected') $mvc->reject($id);
            }
            if (!empty($line->id)) trans_Lines::reject($line->id ?? null);
            if (!empty($vehicle->id)) trans_Vehicles::reject($vehicle->id ?? null);
            core_Cls::shutdown();
            core_Users::exitSudo();
        }

        return 0;
    }
}

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


    /**
     * Exercise native role expansion without changing any persisted account.
     * Negative, non-system identities use currentUserRec in core_Users::getRoles().
     */
    private function checkReportRolePredicates($mvc)
    {
        $cases = array(
            array('executive,acc', true, true),
            array('executive,trans', false, false),
            array('executive,trans,seePrice', true, false),
            array('executive,acc,noPrice', false, true),
            array('ceo,noPrice', false, true),
        );
        foreach ($cases as $index => $case) {
            $roles = core_Roles::expand(keylist::toArray(core_Roles::getRolesAsKeylist($case[0])));
            $identity = (object) array('id' => -900 - $index, 'roles' => keylist::fromArray($roles), 'state' => 'active');
            Mode::push('currentUserRec', $identity);
            try {
                $this->check(trans_TripSheets::canViewReport() === $case[1], 'Current-user report visibility for ' . $case[0]);
                $this->check(trans_TripSheets::canViewReport($identity->id) === $case[1], 'Explicit-user report visibility for ' . $case[0]);
                $this->check(core_Users::haveRole($mvc->canReport, $identity->id) === $case[2], 'Native accounting action role for ' . $case[0]);
            } finally {
                Mode::pop('currentUserRec');
            }
        }
    }


    private function getExpenses()
    {
        return trans_TripExpenses::normalize(array(
            'date' => array('01.01.2026', '01.01.2026'),
            'category' => array('fuel', 'electricity'), 'quantity' => array('12,5', '20'),
            'unit' => array('l', 'kWh'), 'amount' => array('37,50', '0'),
            'document' => array('TEST-EXPENSE-PRIVATE', 'TEST-CHARGING-PRIVATE'),
            'note' => array('TEST-EXPENSE-NOTE-PRIVATE', ''),
        ));
    }


    private function renderSheet($mvc, $rec)
    {
        $data = $mvc->prepareSingle((object) array('rec' => $rec));

        return $mvc->renderSingle($data)->getContent();
    }


    private function checkNoExpenseBlock($html, $message)
    {
        $this->check(strpos($html, 'trip-expenses') === false
            && strpos($html, tr('Зареждания и други разходи за автомобила')) === false, $message);
    }


    private function getReportForm($mvc, $id)
    {
        Request::push(array('id' => $id, 'Cmd' => null), 'tripReportForm');
        try {
            return $mvc->act_Report()->getContent();
        } finally {
            Request::pop('tripReportForm');
        }
    }


    private function getInputValue($html, $name)
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//input[@name="' . $name . '"]');

        return $nodes->length ? $nodes->item(0)->getAttribute('value') : null;
    }


    private function submitReport($mvc, $rec, $hash, $end)
    {
        $request = array('id' => $rec->id, 'Cmd' => 'save', 'reportHash' => $hash,
            'odometerStart' => $rec->odometerStart, 'odometerEnd' => $end,
            'departedOn' => $rec->departedOn, 'returnedOn' => $rec->returnedOn,
            'expenses' => json_decode($rec->expenses, true), 'expenseCurrencyId' => $rec->expenseCurrencyId,
            'reportNotes' => $rec->reportNotes);
        Request::push($request, 'tripReportSubmit');
        $previousMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        try {
            return $mvc->act_Report();
        } finally {
            if ($previousMethod === null) unset($_SERVER['REQUEST_METHOD']);
            else $_SERVER['REQUEST_METHOD'] = $previousMethod;
            Request::pop('tripReportSubmit');
        }
    }


    private function checkReportPrivacy($mvc, $sheet, $line)
    {
        $privateMarkers = array('TEST-EXPENSE-PRIVATE', 'TEST-CHARGING-PRIVATE', 'TEST-EXPENSE-NOTE-PRIVATE', 'TEST-REPORT-NOTE-PRIVATE');
        $this->check(trans_TripSheets::canViewReport(), 'The CEO can read the report');
        $allowedHtml = $this->renderSheet($mvc, $sheet);
        $this->check(strpos($allowedHtml, $privateMarkers[0]) !== false && strpos($allowedHtml, $privateMarkers[3]) !== false, 'Authorized single view renders itemized expenses and report notes');
        $this->check(strpos($allowedHtml, 'trip-expenses') !== false
            && strpos($allowedHtml, tr('Зареждания и други разходи за автомобила')) !== false, 'A populated expense report includes its heading and table');
        $issuedHtml = '';
        Request::push(array('tripView' => 'issued'), 'tripIssuedPrivacy');
        Mode::push('printing', true);
        try {
            $issuedHtml = $this->renderSheet($mvc, $sheet);
        } finally {
            Mode::pop('printing');
            Request::pop('tripIssuedPrivacy');
        }
        foreach ($privateMarkers as $marker) $this->check(strpos($issuedHtml, $marker) === false, 'Issued-sheet reprint omits saved report value ' . $marker);
        $this->checkNoExpenseBlock($issuedHtml, 'Issued-sheet reprint omits the expense heading and table');

        $reader = core_Users::fetch(array("LOWER(#nick) = '[#1#]'", 'ai5'));
        expect($reader && !trans_TripSheets::canViewReport($reader->id), 'Existing Ai5 must remain a local test account without price visibility');
        core_Users::sudo($reader->id);
        try {
            $this->check(!$mvc->haveRightFor('report', $sheet), 'The real no-price account cannot report');
            foreach (arr::make(trans_TripSheets::PRIVATE_REPORT_FIELDS) as $field) {
                $verbal = (string) $mvc->getVerbal($sheet, $field);
                $this->check($verbal === '' || strpos($verbal, 'confidential-field') !== false || strpos($verbal, tr('заличено')) !== false,
                    'Direct verbalization hides private field ' . $field);
            }
            $fields = $mvc->selectFields();
            $fields['-list'] = true;
            $row = $mvc->recToVerbal($sheet, $fields);
            $listHtml = '';
            foreach (arr::make(trans_TripSheets::PRIVATE_REPORT_FIELDS) as $field) $listHtml .= ' ' . (string) ($row->{$field} ?? '');
            $singleHtml = $this->renderSheet($mvc, $sheet);
            Mode::push('printing', true);
            try {
                $printedHtml = $this->renderSheet($mvc, $sheet);
            } finally {
                Mode::pop('printing');
            }
            $this->checkNoExpenseBlock($singleHtml, 'The no-price single view omits the expense heading and table');
            $this->checkNoExpenseBlock($printedHtml, 'The no-price print omits the expense heading and table');
            $txt = $mvc->getTxtContent($sheet->id);
            $llm = $mvc->getLlmContent($sheet->id, array(), true);
            foreach (array('list row' => $listHtml, 'single' => $singleHtml, 'print' => $printedHtml, 'TXT' => $txt, 'LLM' => $llm) as $surface => $content) {
                foreach ($privateMarkers as $marker) {
                    $this->check(strpos($content, $marker) === false, 'No private report value in ' . $surface . ': ' . $marker);
                }
            }
            $summary = trans_TripSheets::getLineSummary($line->id);
            $this->check($summary->distance === null, 'Line summary hides report mileage without price rights');
            foreach ($summary->sheets as $redacted) {
                foreach (arr::make(trans_TripSheets::PRIVATE_REPORT_FIELDS) as $field) {
                    $this->check(!isset($redacted->{$field}), 'Line-summary records redact ' . $field);
                }
            }
            $lineRow = trans_Lines::recToVerbal($line, array('-list' => true, 'reportedDistance' => 'Пробег'));
            $this->check(strpos((string) $lineRow->reportedDistance, 'confidential-field') !== false, 'Transport-line list buries reported mileage');
            $attempt = clone $sheet;
            $attempt->_tripReport = true;
            $attempt->odometerEnd = 999;
            $this->check(!$mvc->save($attempt), 'A forged report flag cannot bypass the report action rights');
        } finally {
            core_Users::exitSudo();
        }
        $stored = $mvc->fetch($sheet->id, '*', false);
        $this->check($stored->odometerEnd === $sheet->odometerEnd && $stored->expenses === $sheet->expenses, 'Privacy checks and refused saves preserve stored report values');
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
            $this->checkReportRolePredicates($mvc);
            $valid = (object) array('odometerStart' => 0, 'odometerEnd' => 0,
                'departedOn' => '2026-01-01 08:00:00', 'returnedOn' => '2026-01-01 09:00:00');
            $this->check(!trans_TripSheets::getReportErrors($valid), 'Zero odometer and zero distance are valid');
            $this->check(count(trans_TripSheets::getReportErrors(new stdClass())) == 4, 'Missing report values must not become zero');
            foreach (array(array('odometerStart', -1), array('odometerEnd', -1), array('returnedOn', '2026-01-01 07:59:00')) as $case) {
                $invalid = clone $valid;
                $invalid->{$case[0]} = $case[1];
                $this->check((bool) trans_TripSheets::getReportErrors($invalid), 'Reject invalid ' . $case[0]);
            }
            $expenseReport = clone $valid;
            $expenseReport->expenses = $this->getExpenses();
            $this->check(isset(trans_TripSheets::getReportErrors($expenseReport)['expenseCurrencyId']), 'Expenses require a report currency');
            $expenseReport->expenseCurrencyId = currency_Currencies::getIdByCode(acc_Periods::getBaseCurrencyCode('2026-01-01'));
            $this->check(!trans_TripSheets::getReportErrors($expenseReport), 'Normalized itemized expenses and zero-cost charging are valid');
            $invalidExpenseReport = clone $expenseReport;
            $badExpenses = json_decode($invalidExpenseReport->expenses, true);
            $badExpenses['amount'][0] = '-1';
            $invalidExpenseReport->expenses = json_encode($badExpenses);
            $this->check(isset(trans_TripSheets::getReportErrors($invalidExpenseReport)['expenses']), 'Negative expenses cannot be reported');
            $defaultOnlyReport = clone $valid;
            $defaultOnlyReport->expenses = json_encode(array('date' => array('01.01.2026'), 'unit' => array('l')));
            $this->check(!trans_TripSheets::getReportErrors($defaultOnlyReport)
                && !type_Table::toArray(trans_TripExpenses::normalize($defaultOnlyReport->expenses)),
                'A row containing only automatic date and unit needs no expense or currency');

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
            doc_Threads::doUpdateThread($line->threadId);
            $base = (object) array('lineId' => $line->id ?? null, 'originId' => $line->containerId ?? null,
                'threadId' => $line->threadId ?? null, 'folderId' => $line->folderId ?? null, 'series' => 'TEST',
                'companyName' => 'ТЕСТОВО ПРЕДПРИЯТИЕ', 'companyNumber' => '000000000', 'companyAddress' => 'Тестов адрес',
                'vehicleId' => $vehicle->id ?? null, 'vehicleModel' => 'Тестов автомобил', 'driverId' => key($drivers),
                'departure' => '2026-01-01 08:00:00', 'expectedReturn' => '2026-01-01 09:30:00',
                'route' => 'Склад - клиент - склад', 'cargo' => 'Тестови стоки',
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
                $this->check(strpos($html, 'expenseCurrencyId') !== false && strpos($html, 'expenses') !== false, 'Native report form includes itemized expenses and currency');
                $this->check(strpos($html, 'name="signedSheet"') === false, 'Reporting does not offer a new shared-file upload');
                $this->check($this->getInputValue($html, 'departedOn[d]') === '01.01.2026'
                    && $this->getInputValue($html, 'departedOn[t]') === '08:00', 'A new report defaults departure from the issued plan');
                $this->check($this->getInputValue($html, 'returnedOn[d]') === '01.01.2026'
                    && $this->getInputValue($html, 'returnedOn[t]') === '09:30', 'A new report defaults return from the issued plan');
                $this->check($this->getInputValue($html, 'odometerStart') === ''
                    && $this->getInputValue($html, 'odometerEnd') === '', 'A first report does not invent odometer readings');
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
            $sheet->expenses = $expenseReport->expenses;
            $sheet->expenseCurrencyId = $expenseReport->expenseCurrencyId;
            $sheet->reportNotes = 'TEST-REPORT-NOTE-PRIVATE';
            $sheet->odometerEnd = 125;
            $sheet->state = 'active';
            $sheet->_tripReport = true;
            $this->check((bool) $mvc->save($sheet), 'Report is saved');
            $sheet = $mvc->fetch($sheet->id, '*', false);
            $this->check($sheet->expenses === $expenseReport->expenses && $sheet->expenseCurrencyId == $expenseReport->expenseCurrencyId, 'Itemized expenses persist with their report currency');
            $this->check(!cls::haveInterface('acc_TransactionSourceIntf', $mvc)
                && !acc_Journal::fetch(array('#docType = [#1#] AND #docId = [#2#]', $mvc->getClassId(), $sheet->id)),
                'Expense reporting creates no accounting transaction');
            $this->check((trans_TripSheets::getLineSummary($line->id ?? null)->distance ?? null) === 125, 'Reported distance is summed');
            $this->check($mvc->haveRightFor('report', $sheet), 'An authorized accountant action permits report corrections');
            $reportHtml = $this->getReportForm($mvc, $sheet->id);
            $this->check($this->getInputValue($reportHtml, 'odometerStart') === '0', 'Correction form preserves a zero initial odometer');
            $reportHash = $this->getInputValue($reportHtml, 'reportHash');
            $this->check(strlen((string) $reportHash) > 0, 'The native report form carries its optimistic-lock snapshot');
            $correctionResult = $this->submitReport($mvc, $sheet, $reportHash, 120);
            $sheet = $mvc->fetch($sheet->id, '*', false);
            $correctionError = '';
            if ($correctionResult instanceof core_ET && !($correctionResult instanceof core_Redirect)
                && preg_match('~<div class="formError">(.*?)</div>~s', $correctionResult->getContent(), $errorMatch)) {
                $correctionError = ' ' . trans_LineDocuments::plain($errorMatch[1]);
            }
            $this->check((int) $sheet->odometerEnd === 120
                && (trans_TripSheets::getLineSummary($line->id)->distance ?? null) === 120,
                'Native correction replaces mileage without double counting.' . $correctionError);
            $this->submitReport($mvc, $sheet, $reportHash, 115);
            $this->check((int) $mvc->fetchField($sheet->id, 'odometerEnd') === 120, 'A stale report form cannot overwrite the accepted correction');
            $reportHash = $this->getInputValue($this->getReportForm($mvc, $sheet->id), 'reportHash');
            $this->submitReport($mvc, $sheet, $reportHash, 125);
            $sheet = $mvc->fetch($sheet->id, '*', false);
            $this->check((int) $sheet->odometerEnd === 125 && (int) $sheet->odometerStart === 0, 'A fresh correction preserves zero and saves the revised mileage');
            $withoutExpenses = clone $sheet;
            $withoutExpenses->expenses = json_encode(array_fill_keys(array('date', 'category', 'quantity', 'unit', 'amount', 'document', 'note'), array('')));
            $reportHash = $this->getInputValue($this->getReportForm($mvc, $sheet->id), 'reportHash');
            $this->submitReport($mvc, $withoutExpenses, $reportHash, 124);
            $sheet = $mvc->fetch($sheet->id, '*', false);
            $this->check((int) $sheet->odometerEnd === 124 && !type_Table::toArray($sheet->expenses),
                'Clearing every expense-table cell saves a mileage-only correction');
            $this->checkNoExpenseBlock($this->renderSheet($mvc, $sheet), 'A mileage-only single view omits the expense heading and table');
            Mode::push('printing', true);
            try {
                $this->checkNoExpenseBlock($this->renderSheet($mvc, $sheet), 'A mileage-only print omits the expense heading and table');
            } finally {
                Mode::pop('printing');
            }
            $sheet->expenses = $defaultOnlyReport->expenses;
            $sheet->expenseCurrencyId = '';
            $reportHash = $this->getInputValue($this->getReportForm($mvc, $sheet->id), 'reportHash');
            $this->submitReport($mvc, $sheet, $reportHash, 123);
            $sheet = $mvc->fetch($sheet->id, '*', false);
            $this->check((int) $sheet->odometerEnd === 123 && !type_Table::toArray($sheet->expenses)
                && empty($sheet->expenseCurrencyId), 'A native correction ignores a default-only row and accepts an empty expense currency');
            $sheet->expenses = json_encode(array(
                'date' => array('', ''), 'category' => array('fuel', 'fuel'),
                'quantity' => array('', '1'), 'unit' => array('kWh', 'pcs'),
                'amount' => array('10', '20'), 'document' => array('TEST-FUEL-ONE', 'TEST-FUEL-TWO'),
                'note' => array('', ''),
            ));
            $sheet->expenseCurrencyId = $expenseReport->expenseCurrencyId;
            $reportHash = $this->getInputValue($this->getReportForm($mvc, $sheet->id), 'reportHash');
            $this->submitReport($mvc, $sheet, $reportHash, 125);
            $sheet = $mvc->fetch($sheet->id, '*', false);
            $expenseRows = array_values(type_Table::toArray($sheet->expenses));
            $this->check(count($expenseRows) === 2 && $expenseRows[0]->document === 'TEST-FUEL-ONE'
                && $expenseRows[1]->document === 'TEST-FUEL-TWO', 'Repeated expense categories remain distinct saved rows');
            $this->check($expenseRows[0]->quantity === '' && $expenseRows[0]->unit === 'l'
                && $expenseRows[1]->unit === 'l', 'The native report permits an omitted quantity and normalizes submitted units server-side');
            $this->check($expenseRows[0]->date === '2026-01-01' && $expenseRows[1]->date === '2026-01-01',
                'The native report fills missing expense dates from actual departure');
            $sheet->expenses = $expenseReport->expenses;
            $reportHash = $this->getInputValue($this->getReportForm($mvc, $sheet->id), 'reportHash');
            $this->submitReport($mvc, $sheet, $reportHash, 125);
            $sheet = $mvc->fetch($sheet->id, '*', false);
            $this->check((int) $sheet->odometerEnd === 125 && $sheet->expenses === $expenseReport->expenses,
                'A fresh native correction can restore itemized expenses after clearing them');
            $tampered = clone $sheet;
            $tampered->expenses = '';
            $tampered->reportNotes = 'changed outside report';
            $mvc->save($tampered, 'expenses,reportNotes');
            $storedReport = $mvc->fetch($sheet->id, '*', false);
            $this->check($storedReport->expenses === $sheet->expenses && $storedReport->reportNotes === $sheet->reportNotes,
                'The dedicated report path owns expense and report-note changes');
            $this->checkReportPrivacy($mvc, $sheet, $line);
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
            $next->departure = '2026-01-01 09:00:00';
            $next->expectedReturn = '2026-01-01 11:00:00';
            $next->state = 'pending';
            $mvc->save($next);
            $sheets[] = $next->id ?? null;
            $nextHtml = $this->getReportForm($mvc, $next->id);
            $this->check($this->getInputValue($nextHtml, 'odometerStart') === '125'
                && $this->getInputValue($nextHtml, 'odometerEnd') === '', 'A subsequent report defaults the previous completed odometer and leaves the end unknown');
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

<?php


/**
 * Native-bootstrap expense checks without creating business records.
 */
class trans_tests_TripExpenses extends core_BaseClass
{
    private $checks = 0;


    private function check($condition, $message)
    {
        if (!$condition) throw new RuntimeException($message);
        $this->checks++;
    }


    public function cli_Run()
    {
        expect(getenv('TRANS_TRIP_TESTS') === '1', 'Local test opt-in required');
        core_Lg::push('bg');
        try {
            $type = trans_TripExpenses::getType();
            $value = array('date' => array('31.10.2026', '', '2026-11-01'),
                'category' => array('electricity', 'fuel', 'other'), 'quantity' => array('20,5', '10', ''),
                'unit' => array('kWh', 'l', ''), 'amount' => array('12,30', '20.5', '0'),
                'document' => array('Фактура <123>', 'Бон №456', ''), 'note' => array('', '', 'Безплатна помощ'));
            $normalized = trans_TripExpenses::normalize($value);
            $rows = type_Table::toArray($normalized);
            $this->check(count($rows) === 3 && !trans_TripExpenses::getErrors($normalized), 'Valid expenses retain all rows');
            $this->check(($rows[0]->date ?? null) === '2026-10-31' && ($rows[0]->quantity ?? null) == 20.5
                && ($rows[0]->amount ?? null) == 12.3 && ($rows[2]->amount ?? null) === 0,
                'Dates and decimal commas normalize while explicit zero remains present');
            $this->check(trans_TripExpenses::getCategoryUnits() === array('electricity' => 'kWh', 'fuel' => 'l', 'adblue' => 'l',
                'toll' => 'pcs', 'parking' => 'pcs', 'repair' => 'pcs', 'consumables' => 'pcs', 'other' => 'pcs'),
                'Every expense category has one canonical unit');
            $partial = $type->fromVerbal(array('note' => array('Само бележка')));
            $this->check(count(type_Table::toArray($partial)) === 1 && isset(trans_TripExpenses::getErrors($partial)['expenses']),
                'Incomplete native-table rows remain available for validation');
            $empty = $type->fromVerbal(array('date' => array(''), 'amount' => array('')));
            $this->check(!trans_TripExpenses::getErrors($empty) && trans_TripExpenses::render($empty) === '', 'Empty expenses are allowed');
            $untouched = $type->fromVerbal(array('date' => array(''), 'category' => array(''), 'quantity' => array(''),
                'unit' => array(''), 'amount' => array(''), 'document' => array(''), 'note' => array('')));
            $this->check(key($type->params['category_opt']) === '' && key($type->params['unit_opt']) === ''
                && !type_Table::toArray($untouched) && !trans_TripExpenses::getErrors($untouched),
                'An untouched report row stays blank and does not require an expense');
            $defaultsOnly = array('date' => array('31.10.2026'), 'unit' => array('pcs'));
            $this->check(!trans_TripExpenses::getErrors($defaultsOnly)
                && !type_Table::toArray(trans_TripExpenses::normalize($defaultsOnly, '2026-11-01'))
                && trans_TripExpenses::render($defaultsOnly) === '', 'Date and unit defaults alone do not create an expense');
            foreach (array('quantity', 'amount', 'category', 'document', 'note') as $field) {
                $incomplete = $defaultsOnly + array($field => array('0'));
                $this->check(count(type_Table::toArray(trans_TripExpenses::normalize($incomplete))) === 1
                    && isset(trans_TripExpenses::getErrors($incomplete)['expenses']), 'A real entered value retains the row for validation: ' . $field);
            }
            $dated = array('date' => array('2026-10-31', '', '2026-10-30'), 'category' => array('', 'fuel', 'fuel'),
                'amount' => array('', '0', '2'), 'unit' => array('pcs', '', 'kWh'));
            $datedRows = type_Table::toArray(trans_TripExpenses::normalize($dated, '2026-11-01'));
            $this->check(count($datedRows) === 2 && ($datedRows[0]->date ?? null) === '2026-11-01'
                && ($datedRows[1]->date ?? null) === '2026-10-30' && ($datedRows[0]->amount ?? null) === 0
                && ($datedRows[0]->unit ?? null) === 'l' && ($datedRows[1]->unit ?? null) === 'l',
                'Default date fills only populated rows and preserves entered dates, zero amounts and repeated categories');
            foreach (array('-1', '1e309', 'not a number', '<script>alert(1)</script>', '', '1.005', '0.001', '0.00000000000001', '10/3') as $amount) {
                $bad = array('category' => array('parking'), 'amount' => array($amount));
                $this->check(isset(trans_TripExpenses::getErrors(trans_TripExpenses::normalize($bad))['expenses']),
                    'Invalid or missing amount is rejected: ' . $amount);
            }
            foreach (array('31.02.2026', '2026-02-29', '2026-13-01', 'tomorrow') as $date) {
                $bad = array('category' => array('parking'), 'amount' => array('1'), 'date' => array($date));
                $this->check(isset(trans_TripExpenses::getErrors($bad)['expenses']), 'Invalid receipt date is rejected: ' . $date);
            }
            $this->check(!trans_TripExpenses::getErrors(array('category' => array('parking'), 'amount' => array('1'), 'date' => array('29.02.2028'))),
                'A valid leap-day receipt is accepted');
            foreach (array('0', '-1', 'not a number', '1e309') as $quantity) {
                $bad = array('category' => array('fuel'), 'amount' => array('1'), 'quantity' => array($quantity), 'unit' => array('l'));
                $this->check(isset(trans_TripExpenses::getErrors($bad)['expenses']), 'An entered quantity must be finite and positive');
            }
            foreach (trans_TripExpenses::getCategoryUnits() as $category => $unit) {
                $automatic = array('category' => array($category), 'amount' => array('1'), 'quantity' => array(''),
                    'unit' => array('<script>wrong unit</script>'), 'note' => array('Expense note'));
                $canonical = trans_TripExpenses::normalize($automatic);
                $this->check(!trans_TripExpenses::getErrors($automatic) && !trans_TripExpenses::getErrors($canonical)
                    && (type_Table::toArray($canonical)[0]->unit ?? null) === $unit,
                    'Quantity is optional and the category determines the unit: ' . $category);
            }
            $legacy = array('category' => array('electricity', 'fuel', 'parking'), 'amount' => array('1', '2', '3'),
                'unit' => array('l', '<script>wrong unit</script>', 'kWh'));
            $legacyHtml = trans_TripExpenses::render($legacy, 'EUR');
            $this->check(strpos($legacyHtml, '<td>kWh</td>') !== false && strpos($legacyHtml, '<td>l</td>') !== false
                && strpos($legacyHtml, '<td>бр.</td>') !== false && strpos($legacyHtml, 'wrong unit') === false,
                'Legacy saved rows render their canonical category unit');
            $this->check(isset(trans_TripExpenses::getErrors(array('category' => array('other'), 'amount' => array('1')))['expenses']),
                'Other expenses require an explanation');
            $this->check(isset(trans_TripExpenses::getErrors(array('category' => array('<img src=x>'), 'amount' => array('1')))['expenses']),
                'Unrecognized category options are rejected');
            foreach (array('{invalid', array('amount' => array(array('nested'))), array('amount' => array(2 => '1'))) as $bad) {
                $this->check(isset(trans_TripExpenses::getErrors($bad)['expenses']), 'Malformed expense table is rejected');
            }
            $safe = array('category' => array('other'), 'amount' => array('1'), 'document' => array('<img src=x onerror=alert(1)>'),
                'note' => array('<script>alert(1)</script>'));
            $html = trans_TripExpenses::render(trans_TripExpenses::normalize($safe), '<EUR>');
            $this->check(strpos($html, '<img') === false && strpos($html, '<script') === false && strpos($html, '<EUR>') === false
                && strpos($html, '&lt;img') !== false && strpos($html, '&lt;script') !== false && strpos($html, '&lt;EUR&gt;') !== false,
                'Saved notes, document references and currency are escaped');
            $sum = array('category' => array('fuel', 'fuel', 'parking'), 'amount' => array('10.25', '20.50', '0'));
            $html = trans_TripExpenses::render(trans_TripExpenses::normalize($sum), 'EUR');
            $money = core_Type::getByName('double(smartRound,minDecimals=2)')->toVerbal(30.75);
            $this->check(strpos($html, 'Общо — Гориво') !== false && strpos($html, 'Общо — Паркинг') !== false
                && strpos($html, 'Общо разходи (EUR)') !== false && substr_count($html, $money) === 2,
                'Repeated categories aggregate once and grand total uses the report currency');
            preg_match('~<tbody>(.*?)</tbody>~s', $html, $expenseBody);
            $this->check(count(type_Table::toArray(trans_TripExpenses::normalize($sum))) === 3
                && substr_count($expenseBody[1] ?? '', '<tr') === 3 && substr_count($expenseBody[1] ?? '', '<td>Гориво</td>') === 2,
                'Repeated categories remain independent expense rows in storage and print');
            $tenths = trans_TripExpenses::normalize(array('category' => array('fuel', 'fuel'), 'amount' => array('0.1', '0.2')));
            $tenthsHtml = trans_TripExpenses::render($tenths, 'EUR');
            $thirtyCents = core_Type::getByName('double(smartRound,minDecimals=2)')->toVerbal(0.3);
            $this->check(!trans_TripExpenses::getErrors($tenths) && substr_count($tenthsHtml, $thirtyCents) === 2,
                'Integer-cent category and grand totals display 0.1 plus 0.2 as exactly 0.30');
            $this->check(strpos($type->toVerbal($normalized), 'Електроенергия') !== false, 'Native type_Table render callback accepts its type argument');
            echo 'PASS ' . $this->checks . " expense checks\n";
        } catch (Throwable $error) {
            echo 'FAIL after ' . $this->checks . ' checks: ' . $error->getMessage() . "\n";
            return 1;
        } finally {
            core_Lg::pop();
        }

        return 0;
    }
}

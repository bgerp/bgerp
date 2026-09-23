<?php


/**
 * Regression tests for heuristic SQL and XSS detection.
 */
class core_tests_HackDetector extends unit_Class
{
    public static function test_PlainText(core_HackDetector $detector)
    {
        $description = "Universal bgERP task worker for a bounded part of Aida's work: research and compare years, document types or counterparties; inspect documents; prepare authorized drafts or execute a well-defined workflow phase. Has the chat agent's business tools and requesting user's rights. Returns a report with results, document handles, problems and unfinished work. Cannot send email, rename/replace the parent conversation, schedule work or delegate by default. Prefer FileExtractionAgent for extraction from one file and WebResearchAgent for public web research.";
        $samples = array(
            $description,
            str_repeat("Customer's order; compare red/green and blue/white; do not rename or replace. ", 8),
            "O'Reilly's products; # reference\n" . str_repeat('Research and compare documents or counterparties; prepare drafts. ', 6),
            '',
        );

        foreach ($samples as $sample) {
            ut::expectEqual($detector->sqlInjectionScore($sample), 0);
            ut::expectEqual($detector->xssLikelihoodScore($sample) <= 2, true);
        }
    }


    public static function test_SqlScoresPreserved(core_HackDetector $detector)
    {
        // Preserve existing scores, including probes below the default blocking threshold.
        $samples = array(
            "' OR 1=1 -- " => 3,
            "' UNION SELECT username, password FROM users -- " => 2,
            '1 AND SLEEP(5)-- ' => 3,
            "'; DROP TABLE users; -- " => 2,
        );

        foreach ($samples as $sample => $expected) {
            ut::expectEqual($detector->sqlInjectionScore($sample), $expected);
        }
        ut::expectEqual($detector->sqlInjectionScore('%27%20OR%201%3D1%20--%20'), 3);
    }


    public static function test_XssScoresPreserved(core_HackDetector $detector)
    {
        $samples = array(
            '<script>alert(1)</script>',
            '<img src=x onerror="alert(1)">',
            '<svg onload="alert(1)">',
            '&lt;script&gt;alert(1)&lt;/script&gt;',
        );

        foreach ($samples as $sample) {
            ut::expectEqual($detector->xssLikelihoodScore($sample), 4);
        }
    }
}

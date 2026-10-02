<?php

/**
 * Static catch-order/redirect check, without loading the application or calling its code.
 * From any repo: git diff --cached --name-only -z -- '*.php' | php /path/to/core/tests/checkRedirectCatches.php --stdin0
 * Or pass explicit PHP files. Tests and bundled dependencies are excluded.
 * Terminal handlers may use a first comment "redirect-catch: terminal" with an explanation.
 * This checks local control flow conventions, not reachability of redirect() through callees.
 *
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 */
if (PHP_SAPI !== 'cli') exit("CLI only.\n");

/**
 * Tokenizes PHP while retaining source line numbers and discarding whitespace.
 *
 * @param string $source
 * @return array
 */
function redirectCatchTokens($source)
{
    $tokens = array();
    $line = 1;
    foreach (token_get_all($source) as $token) {
        $id = is_array($token) ? $token[0] : null;
        $text = is_array($token) ? $token[1] : $token;
        if ($id !== T_WHITESPACE) $tokens[] = array($id, $text, $line);
        $line += substr_count($text, "\n");
    }
    return $tokens;
}

/**
 * Finds shadowed catches and broad handlers without redirect propagation.
 *
 * @param string $source
 * @return array Pairs of source line and diagnostic message
 */
function redirectCatchIssues($source)
{
    $raw = redirectCatchTokens($source);
    $tokens = array();
    $terminal = array();
    foreach ($raw as $token) {
        if (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT), true)) {
            if (strpos($token[1], 'redirect-catch: terminal') !== false) $terminal[count($tokens)] = true;
        } else {
            $tokens[] = $token;
        }
    }
    $pairs = $stack = array();
    foreach ($tokens as $i => $token) {
        if (($token[0] === null && in_array($token[1], array('(', '{', '['), true))
            || in_array($token[0], array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES), true)) $stack[] = $i;
        elseif ($token[0] === null && in_array($token[1], array(')', '}', ']'), true)) {
            $start = array_pop($stack);
            if ($start !== null) $pairs[$start] = $i;
        }
    }
    $parents = array('throwable' => array(), 'core_exception_redirect' => array('exception'), 'core_exception_expect' => array('errorexception'));
    foreach (get_declared_classes() as $class) {
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInternal()) continue;
        $parent = $reflection->getParentClass();
        $parents[strtolower($class)] = array_map('strtolower', array_merge($reflection->getInterfaceNames(), $parent ? array($parent->getName()) : array()));
    }
    foreach ($tokens as $i => $token) {
        if ($token[0] === T_CLASS && ($tokens[$i + 1][0] ?? null) === T_STRING
            && ($tokens[$i + 2][0] ?? null) === T_EXTENDS) {
            $parents[strtolower($tokens[$i + 1][1])][] = strtolower(ltrim($tokens[$i + 3][1], '\\'));
        }
    }
    $isA = function ($type, $base, $seen = array()) use (&$isA, $parents) {
        if ($type === $base) return true;
        if (isset($seen[$type])) return false;
        $seen[$type] = true;
        foreach ($parents[$type] ?? array() as $parent) if ($isA($parent, $base, $seen)) return true;
        return false;
    };
    $issues = array();
    foreach ($tokens as $i => $token) {
        if ($token[0] !== T_TRY || !isset($pairs[$i + 1])) continue;
        $j = $pairs[$i + 1] + 1;
        $earlier = array();
        $redirectHandled = false;
        while (($tokens[$j][0] ?? null) === T_CATCH && isset($pairs[$j + 1])) {
            $close = $pairs[$j + 1];
            $types = $variable = '';
            for ($k = $j + 2; $k < $close; $k++) {
                if ($tokens[$k][0] === T_VARIABLE) $variable = $tokens[$k][1];
                else $types .= $tokens[$k][1];
            }
            $types = array_map(function ($type) { return strtolower(ltrim($type, '\\')); }, explode('|', $types));
            $bodyStart = $close + 1;
            $bodyEnd = $pairs[$bodyStart] ?? null;
            if ($bodyEnd === null) break;
            $bodyTokens = array_slice($tokens, $bodyStart + 1, $bodyEnd - $bodyStart - 1);
            $body = implode('', array_column($bodyTokens, 1));
            foreach ($types as $type) {
                foreach ($earlier as $previous) if ($isA($type, $previous)) {
                    $issues[] = array($tokens[$j][2], "{$type} is shadowed by earlier {$previous}");
                    break;
                }
            }
            $broad = array_intersect($types, array('exception', 'throwable'));
            $passThrough = $variable !== '' && substr($body, -strlen('throw' . $variable . ';')) === 'throw' . $variable . ';'
                && ($bodyEnd - 3 === $bodyStart + 1 || in_array($tokens[$bodyEnd - 4][1], array(';', '}'), true))
                && !array_intersect(array_column($bodyTokens, 0), array(T_RETURN, T_EXIT));
            $redirectGuard = $variable !== '' && preg_match('/if\\(' . preg_quote($variable, '/') . 'instanceof\\\\?core_exception_Redirect\\)\\{?throw' . preg_quote($variable, '/') . ';/', $body);
            if ($broad && !$redirectHandled && !$passThrough && !$redirectGuard && empty($terminal[$bodyStart + 1])) {
                $issues[] = array($tokens[$j][2], 'Handle/rethrow core_exception_Redirect before this catch, or document the terminal boundary');
            }
            if (in_array('core_exception_redirect', $types, true)) $redirectHandled = true;
            $earlier = array_merge($earlier, $types);
            $j = $bodyEnd + 1;
        }
    }
    return $issues;
}

/**
 * Resolves exclusions relative to the checkout, ignoring ancestors such as /tmp.
 * Explicit standalone files outside a checkout and cwd are checked by basename.
 *
 * @param string $file
 * @return string
 */
function redirectCatchRelativePath($file)
{
    $path = realpath($file);
    if ($path === false) return $file;
    $directory = dirname($path);
    while (true) {
        if (file_exists($directory . '/.git')) return substr($path, strlen($directory) + 1);
        $parent = dirname($directory);
        if ($parent === $directory) break;
        $directory = $parent;
    }
    $cwd = rtrim(getcwd(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    return strpos($path, $cwd) === 0 ? substr($path, strlen($cwd)) : basename($path);
}

$args = array_slice($argv, 1);
if ($args === array('--self-test')) {
    $examples = array(
        array('try { f(); } catch (Throwable $e) {}', 1),
        array('try { f(); } catch (core_exception_Redirect $e) { throw $e; } catch (Throwable $e) {}', 0),
        array('try { f(); } catch (Throwable $e) { throw $e; } catch (Error $e) {}', 1),
        array('try { f(); } catch (Exception $e) {} catch (core_exception_Redirect $e) { throw $e; }', 2),
        array('try { f(); } catch (Error $e) {} catch (Exception $e) { throw $e; }', 0),
        array('try { f(); } catch (\\Exception | \\Error $e) { throw $e; }', 0),
        array('try { f(); } catch (Throwable $e) { if ($e instanceof core_exception_Redirect) throw $e; logError($e); }', 0),
        array('try { f(); } catch (Throwable $e) { /* redirect-catch: terminal - process ends here */ logError($e); }', 0),
        array('class ParentError extends Exception {} class ChildError extends ParentError {} try { f(); } catch (ParentError $e) {} catch (ChildError $e) {}', 1),
        array('/* catch (Throwable $e) {} */ $s = "catch (Exception) {}";', 0),
        array('try { f(); } catch (Throwable $e) { if (test()) return null; throw $e; }', 1),
        array('try { f(); } catch (Throwable $e) { if (test()) throw $e; }', 1),
        array('try { f(); } catch (Error $e) {} catch (TypeError $e) {}', 1),
    );
    foreach ($examples as $example) if (count(redirectCatchIssues('<?php ' . $example[0])) !== $example[1]) {
        fwrite(STDERR, 'FAIL: ' . $example[0] . PHP_EOL); exit(1);
    }
    echo 'OK: ' . count($examples) . " catch-checker cases.\n";
    exit(0);
}
if (!$args) { fwrite(STDERR, "Pass PHP files or --stdin0 with a NUL-separated file list.\n"); exit(2); }
if ($args === array('--stdin0')) $args = array_filter(explode("\0", stream_get_contents(STDIN)), 'strlen');
$count = $failed = $skipped = 0;
foreach (array_unique($args) as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "Missing file: {$file}\n");
        $failed++;
        continue;
    }
    if (substr($file, -4) !== '.php'
        || preg_match('~(^|/)(tests?|vendor|node_modules|cache|tmp)/~', redirectCatchRelativePath($file))) {
        $skipped++;
        continue;
    }
    $count++;
    foreach (redirectCatchIssues(file_get_contents($file)) as $issue) {
        fwrite(STDERR, $file . ':' . $issue[0] . ': ' . $issue[1] . PHP_EOL);
        $failed++;
    }
}
echo "Checked {$count} PHP files; {$failed} catch issues; {$skipped} skipped files.\n";
if (!$count && !$failed) { fwrite(STDERR, "No PHP files were checked.\n"); exit(2); }
exit($failed ? 1 : 0);

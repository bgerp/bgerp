<?php

class fileman_HexRenderer
{
    /**
     * Основен статичен метод за генериране на Hex изглед.
     * Връща чист HTML стринг.
     */
    public static function render(string $blob, int $offset = 0, bool $isDark = true): string
    {
        $bytes = str_split($blob);
        $totalLen = count($bytes);
        
        // 1. Предварителен анализ на всички символи (UTF-8 карта)
        $charMap = self::analyzeBytes($bytes);
        
        $themeClass = $isDark ? 'hex-dark' : 'hex-light';
        $html = self::getStyles();
        $html .= '<div class="hex-container ' . $themeClass . '" id="hex-viewer">';
        $html .= self::renderHeader();

        // 2. Рендиране на редовете (по 16 байта)
        for ($rowStart = 0; $rowStart < $totalLen; $rowStart += 16) {
            $rowAddr = $offset + $rowStart;
            $html .= '<div class="hex-row">';
            
            // Адресна колона
            $html .= '<span class="hex-addr">' . sprintf('%04X', $rowAddr & 0xFFFF) . '</span>';
            
            // Hex колона
            $html .= '<span class="hex-bytes">';
            for ($i = 0; $i < 16; $i++) {
                $idx = $rowStart + $i;
                if ($idx < $totalLen) {
                    if($bytes[$idx] === "\x00") {
                        $classZero = ' zero';
                    } else {
                        $classZero = '';
                    }
                    $byteVal = strtoupper(str_pad(dechex(ord($bytes[$idx])), 2, '0', STR_PAD_LEFT));
                    $html .= '<span class="b' . $classZero . '" data-idx="' . $idx . '">' . $byteVal . '</span>';
                } else {
                    $html .= '<span class="b empty">  </span>';
                }
                $html .= ($i % 4 == 3 && $i < 15) ? '  ' : ' ';
            }
            $html .= '</span>';

            // Символна колона (използваме картата)
            $html .= '<span class="hex-chars">';
            for ($i = 0; $i < 16; $i++) {
                $idx = $rowStart + $i;
                if ($idx < $totalLen) {
                    $mapping = $charMap[$idx];
                    $displayChar = $mapping['char'];
                    // Ако е водещ байт на UTF-8 последователност (не последен), показваме интервал
                    if ($mapping['type'] === 'utf8_lead') {
                        $displayChar = ' ';
                    }
                    if($bytes[$idx] === "\x00") {
                        $classZero = ' zero';
                    } else {
                        $classZero = '';
                    }
                    $html .= '<span class="c' . $classZero . '" data-idx="' . $idx . '">' . htmlspecialchars($displayChar, ENT_QUOTES, 'UTF-8') . '</span>';
                } else {
                    $html .= '<span class="c empty"> </span>';
                }
            }
            $html .= '</span>';
            $html .= '</div>';
        }

        $html .= '</div>' . self::getScripts();
        return $html;
    }

    /**
     * Анализира целия масив от байтове и определя кой байт какво трябва да визуализира.
     * Справя се с UTF-8 символи, които са разделени между два реда.
     */
    private static function analyzeBytes(array $bytes): array
    {
        $total = count($bytes);
        $map = array_fill(0, $total, ['type' => 'single', 'char' => '.']);
        $i = 0;

        while ($i < $total) {
            $byte = ord($bytes[$i]);
            $seqLen = 1;

            if ($byte >= 0xC0 && $byte <= 0xDF) $seqLen = 2;
            elseif ($byte >= 0xE0 && $byte <= 0xEF) $seqLen = 3;
            elseif ($byte >= 0xF0 && $byte <= 0xF7) $seqLen = 4;

            $isValidUtf8 = false;
            if ($seqLen > 1 && ($i + $seqLen) <= $total) {
                $substr = '';
                for ($j = 0; $j < $seqLen; $j++) $substr .= $bytes[$i + $j];
                
                if (mb_check_encoding($substr, 'UTF-8')) {
                    // Маркираме всички байтове освен последния като "водещи"
                    for ($j = 0; $j < $seqLen - 1; $j++) {
                        $map[$i + $j] = ['type' => 'utf8_lead', 'char' => ' '];
                    }
                    // Последният байт държи целия символ за визуализация
                    $map[$i + $seqLen - 1] = ['type' => 'utf8_display', 'char' => $substr];
                    $isValidUtf8 = true;
                    $i += $seqLen;
                }
            }

            if (!$isValidUtf8) {
                $char = '.';
                if ($byte === 0x09) $char = '⇥';
                elseif ($byte === 0x0A) $char = '↵';
                elseif ($byte === 0x0D) $char = '⇠';
                elseif ($byte >= 32 && $byte <= 126) $char = chr($byte);
                
                $map[$i] = ['type' => 'single', 'char' => $char];
                $i++;
            }
        }
        return $map;
    }

    private static function renderHeader(): string
    {
        $h = '<div class="hex-row hex-header"><span class="hex-addr">ADDR</span>';
        $h .= '<span class="hex-bytes">';
        for ($i = 0; $i < 16; $i++) {
            $h .= '<span class="b">' . strtoupper(dechex($i)) . '</span>' . (($i % 4 == 3 && $i < 15) ? '  ' : ' ');
        }
        $h .= '</span><span class="hex-chars">';
        for ($i = 0; $i < 16; $i++) $h .= '<span class="c">' . strtoupper(dechex($i)) . '</span>';
        $h .= '</span></div>';
        return $h;
    }

    private static function getStyles(): string
    {
        return '<style>
            .hex-container { font-family: "Courier New", monospace; font-size: 14px; padding: 20px; display: inline-block; border-radius: 6px; line-height: 1.2; }
            .hex-dark { background: #1e1e1e; color: #9cdcfe; }
            .hex-light { background: #f5f5f5; color: #111; border: 1px solid #ccc; }
            .hex-row { display: flex; white-space: pre; margin-bottom: 1px; }
            .hex-header { color: #569cd6; border-bottom: 1px solid #444; margin-bottom: 10px; padding-bottom: 5px; }
            .hex-addr { color: #85c46c; margin-right: 15px; user-select: none; }
            .hex-bytes { margin-right: 20px; color: #d4d4d4; }
            .hex-light .hex-bytes { color: #333; }
            .hex-chars { color: #ce9178; border-left: 1px solid #444; padding-left: 10px; }
            .hex-light .hex-chars { color: #9B4E30;}
            .b, .c { display: inline-block; }
            .b { width: 2ch; text-align: right; }
            .c { width: 1.2ch; text-align: center; }
            .zero { color:#3399ff }
            .b.empty, .c.empty { visibility: hidden; }
            .hover { background: #3e3e3e !important; color: #fff !important; }
            .hex-light .hover { background: #d0d0d0 !important; }
            .active { background: #264f78 !important; color: #fff !important; }
        </style>';
    }

    private static function getScripts(): string
    {
        return '<script>
        (function() {
            const container = document.getElementById("hex-viewer");
            const highlight = (idx, add) => {
                if (idx === undefined || idx === null) return;
                container.querySelectorAll(`[data-idx="${idx}"]`).forEach(el => {
                    el.classList[add ? "add" : "remove"]("hover");
                });
            };
            container.addEventListener("mouseover", e => highlight(e.target.dataset.idx, true));
            container.addEventListener("mouseout", e => highlight(e.target.dataset.idx, false));
            container.addEventListener("click", e => {
                const idx = e.target.dataset.idx;
                if (!idx) return;
                const wasActive = e.target.classList.contains("active");
                container.querySelectorAll(".active").forEach(el => el.classList.remove("active"));
                if (!wasActive) container.querySelectorAll(`[data-idx="${idx}"]`).forEach(el => el.classList.add("active"));
            });
        })();
        </script>';
    }
}


 
<?php

declare(strict_types=1);

namespace MyAds\Plugins\SmartSocialCards\Support;

/**
 * Lightweight native Arabic text shaper and bidirectional helper for PHP GD / FreeType.
 * Connects cursive Arabic glyphs and reorders text for right-to-left layout.
 */
class ArabicGlyphShaper
{
    /**
     * Arabic letters map: [isolated, final, initial, medial]
     */
    private const GLYPHS = [
        // Hamza
        0x0621 => [0xFE80, 0xFE80, 0xFE80, 0xFE80],
        // Alef with madda
        0x0622 => [0xFE81, 0xFE82, 0xFE81, 0xFE82],
        // Alef with hamza above
        0x0623 => [0xFE83, 0xFE84, 0xFE83, 0xFE84],
        // Waw with hamza
        0x0624 => [0xFE85, 0xFE86, 0xFE85, 0xFE86],
        // Alef with hamza below
        0x0625 => [0xFE87, 0xFE88, 0xFE87, 0xFE88],
        // Yeh with hamza
        0x0626 => [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C],
        // Alef
        0x0627 => [0xFE8D, 0xFE8E, 0xFE8D, 0xFE8E],
        // Beh
        0x0628 => [0xFE8F, 0xFE90, 0xFE91, 0xFE92],
        // Teh Marbuta
        0x0629 => [0xFE93, 0xFE94, 0xFE93, 0xFE94],
        // Teh
        0x062A => [0xFE95, 0xFE96, 0xFE97, 0xFE98],
        // Theh
        0x062B => [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C],
        // Jeem
        0x062C => [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0],
        // Hah
        0x062D => [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4],
        // Khah
        0x062E => [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8],
        // Dal
        0x062F => [0xFEA9, 0xFEAA, 0xFEA9, 0xFEAA],
        // Thal
        0x0630 => [0xFEAB, 0xFEAC, 0xFEAB, 0xFEAC],
        // Reh
        0x0631 => [0xFEAD, 0xFEAE, 0xFEAD, 0xFEAE],
        // Zain
        0x0632 => [0xFEAF, 0xFEB0, 0xFEAF, 0xFEB0],
        // Seen
        0x0633 => [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4],
        // Sheen
        0x0634 => [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8],
        // Sad
        0x0635 => [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC],
        // Dad
        0x0636 => [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0],
        // Tah
        0x0637 => [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4],
        // Zah
        0x0638 => [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8],
        // Ain
        0x0639 => [0xFEC9, 0xFECA, 0xFECB, 0xFECC],
        // Ghain
        0x063A => [0xFECD, 0xFECE, 0xFECF, 0xFED0],
        // Feh
        0x0641 => [0xFED1, 0xFED2, 0xFED3, 0xFED4],
        // Qaf
        0x0642 => [0xFED5, 0xFED6, 0xFED7, 0xFED8],
        // Kaf
        0x0643 => [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC],
        // Lam
        0x0644 => [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0],
        // Meem
        0x0645 => [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4],
        // Noon
        0x0646 => [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8],
        // Heh
        0x0647 => [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC],
        // Waw
        0x0648 => [0xFEED, 0xFEEE, 0xFEED, 0xFEEE],
        // Alef Maksura
        0x0649 => [0xFEEF, 0xFEF0, 0xFBE8, 0xFBE9],
        // Yeh
        0x064A => [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4],
    ];

    /**
     * Non-connecting characters (do not connect to following characters).
     */
    private const NON_CONNECTING = [
        0x0621, 0x0622, 0x0623, 0x0624, 0x0625, 0x0627, 0x0629, 0x062F, 0x0630, 0x0631, 0x0632, 0x0648, 0x0649,
    ];

    /**
     * Detect if text contains Arabic characters.
     */
    public static function isArabic(string $text): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text);
    }

    /**
     * Shape text: join letters and apply BiDi visual order.
     */
    public static function shape(string $text): string
    {
        if (!self::isArabic($text)) {
            return $text;
        }

        // Split into lines if any
        $lines = explode("\n", $text);
        $shapedLines = [];

        foreach ($lines as $line) {
            $shapedLines[] = self::shapeLine($line);
        }

        return implode("\n", $shapedLines);
    }

    private static function shapeLine(string $line): string
    {
        // 1. Process Lam-Alef ligatures
        $line = self::processLamAlef($line);

        // 2. Decode UTF-8 string to unicode codepoint array
        $chars = mb_str_split($line);
        $codes = array_map(static fn (string $c): int => mb_ord($c, 'UTF-8'), $chars);
        $len = count($codes);
        $shapedCodes = [];

        for ($i = 0; $i < $len; $i++) {
            $current = $codes[$i];

            if (!isset(self::GLYPHS[$current])) {
                $shapedCodes[] = $current;
                continue;
            }

            $prevConnects = false;
            if ($i > 0 && isset(self::GLYPHS[$codes[$i - 1]])) {
                $prevCode = $codes[$i - 1];
                if (!in_array($prevCode, self::NON_CONNECTING, true)) {
                    $prevConnects = true;
                }
            }

            $nextConnects = false;
            if ($i < $len - 1 && isset(self::GLYPHS[$codes[$i + 1]])) {
                $nextConnects = true;
            }

            // Determine position: 0: isolated, 1: final, 2: initial, 3: medial
            if ($prevConnects && $nextConnects && !in_array($current, self::NON_CONNECTING, true)) {
                $pos = 3; // medial
            } elseif ($prevConnects) {
                $pos = 1; // final
            } elseif ($nextConnects && !in_array($current, self::NON_CONNECTING, true)) {
                $pos = 2; // initial
            } else {
                $pos = 0; // isolated
            }

            $shapedCodes[] = self::GLYPHS[$current][$pos];
        }

        // 3. Convert codepoints back to UTF-8 characters
        $shapedChars = array_map(static fn (int $code): string => mb_chr($code, 'UTF-8'), $shapedCodes);

        // 4. BiDi reordering for GD
        return self::bidiReorder($shapedChars);
    }

    private static function processLamAlef(string $text): string
    {
        $map = [
            'لآ' => 'ﻵ',
            'لأ' => 'ﻷ',
            'لإ' => 'ﻹ',
            'لا' => 'ﻻ',
        ];
        return str_replace(array_keys($map), array_values($map), $text);
    }

    private static function bidiReorder(array $chars): string
    {
        // Simple and robust BiDi tokenization:
        // Group consecutive Arabic characters and reverse them, but preserve numbers (0-9) and Latin words in original order.
        $text = implode('', $chars);
        
        // Match tokens: Arabic sequences, Latin words, Numbers, or Punctuations
        preg_match_all('/[\p{Arabic}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]+|[0-9]+|[a-zA-Z0-9_\-]+|[^\s\p{L}\p{N}]+|\s+/u', $text, $matches);
        $tokens = $matches[0] ?? [];

        $reversedTokens = [];
        foreach ($tokens as $token) {
            if (self::isArabic($token)) {
                // Reverse characters within the Arabic word for RTL display in GD
                $arabicChars = mb_str_split($token);
                $reversedTokens[] = implode('', array_reverse($arabicChars));
            } else {
                // Keep numbers, latin words, and symbols in their natural order
                $reversedTokens[] = $token;
            }
        }

        // Reverse the order of tokens in the line for RTL sentence flow
        return implode('', array_reverse($reversedTokens));
    }
}

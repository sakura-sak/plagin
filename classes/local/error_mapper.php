<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Maps transport and Guard failures to the short categories from the requirements.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat\local;

defined('MOODLE_INTERNAL') || die();

final class error_mapper {
    /** @return string[] */
    public static function keys(): array {
        return [
            'unavailable',
            'timeout',
            'overloaded',
            'unsupported_format',
            'corrupted_file',
            'no_text',
            'extraction_failed',
            'internal',
            'result_transfer',
            'auth',
        ];
    }

    public static function is_key(string $value): bool {
        return in_array($value, self::keys(), true);
    }

    /** User-facing sentence. Technical detail stays in the Moodle log. */
    public static function display(string $stored): string {
        $key = self::is_key($stored) ? $stored : self::from_text($stored);
        $stringkey = 'err_' . $key;
        if (!get_string_manager()->string_exists($stringkey, 'plagiarism_antiplagiat')) {
            $stringkey = 'err_internal';
        }
        return get_string($stringkey, 'plagiarism_antiplagiat');
    }

    public static function from_text(string $detail): string {
        $text = \core_text::strtolower(trim($detail));
        if ($text === '') {
            return 'internal';
        }
        foreach (self::keys() as $key) {
            if ($text === $key || strpos($text, $key) !== false) {
                return $key;
            }
        }
        if (strpos($text, 'timed out') !== false || strpos($text, 'timeout') !== false
                || strpos($text, 'curl error 28') !== false || preg_match('/\b(408|504)\b/', $text)) {
            return 'timeout';
        }
        if (preg_match('/\b(429|409)\b/', $text) || strpos($text, 'перегруж') !== false) {
            return 'overloaded';
        }
        if (preg_match('/\b(401|403)\b/', $text) || strpos($text, 'api key') !== false
                || strpos($text, 'unauthorized') !== false || strpos($text, 'авториз') !== false) {
            return 'auth';
        }
        if (preg_match('/\b(502|503)\b/', $text) || strpos($text, 'connection') !== false
                || strpos($text, 'could not resolve') !== false || strpos($text, 'соединен') !== false) {
            return 'unavailable';
        }
        if (strpos($text, 'docx') !== false || strpos($text, 'формат') !== false) {
            return 'unsupported_format';
        }
        return 'internal';
    }

    public static function from_throwable(\Throwable $e): string {
        if ($e instanceof api_exception) {
            $mapped = self::from_text((string)$e->a);
            if ($e->is_retryable() && $mapped === 'internal') {
                return 'unavailable';
            }
            return $mapped;
        }
        return self::from_text($e->getMessage());
    }
}

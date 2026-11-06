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
 * Effective plugin settings: config-guard.php then Moodle form values.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat\local;

defined('MOODLE_INTERNAL') || die();

final class plugin_config {
    /**
     * @return mixed
     */
    public static function get(string $name, $default = null) {
        $file = self::from_file();
        if (array_key_exists($name, $file) && self::has_value($file[$name])) {
            return $file[$name];
        }
        $moodle = get_config('plagiarism_antiplagiat', $name);
        if (self::has_value($moodle)) {
            return $moodle;
        }
        if (array_key_exists($name, $file)) {
            return $file[$name];
        }
        return $default;
    }

    public static function from_file(): array {
        $path = dirname(__DIR__, 2) . '/config-guard.php';
        if (!is_readable($path)) {
            return [];
        }
        $data = include $path;
        return is_array($data) ? $data : [];
    }

    public static function defaults_for_form(): \stdClass {
        return (object)[
            'enabled' => (int)self::get('enabled', 0),
            'apiurl' => rtrim((string)self::get('apiurl', ''), '/'),
            'apikey' => (string)self::get('apikey', ''),
            'university' => (string)self::get('university', 'БГУИР'),
            'maxattempts' => max(1, (int)self::get('maxattempts', 6)),
            'limitations' => (string)self::get('limitations', ''),
        ];
    }

    public static function save(\stdClass $data): void {
        set_config('enabled', empty($data->enabled) ? 0 : 1, 'plagiarism_antiplagiat');
        set_config('apiurl', rtrim(trim((string)($data->apiurl ?? '')), '/'), 'plagiarism_antiplagiat');
        set_config('apikey', trim((string)($data->apikey ?? '')), 'plagiarism_antiplagiat');
        set_config('university', trim((string)($data->university ?? 'БГУИР')), 'plagiarism_antiplagiat');
        set_config('maxattempts', max(1, (int)($data->maxattempts ?? 6)), 'plagiarism_antiplagiat');
        set_config('limitations', trim((string)($data->limitations ?? '')), 'plagiarism_antiplagiat');
    }

    /**
     * @param mixed $value
     */
    private static function has_value($value): bool {
        if ($value === false || $value === null) {
            return false;
        }
        if (is_string($value)) {
            return trim($value) !== '';
        }
        return true;
    }
}

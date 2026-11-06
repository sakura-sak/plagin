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
 * Moodle log event: a check finished with an error.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Событие журнала Moodle: проверка завершилась ошибкой.
 */
class check_failed extends \core\event\base {
    /** Тип записи: обновление проверки, без учебного уровня. */
    protected function init(): void {
        $this->data['objecttable'] = 'plagiarism_antiplagiat';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /** Название события в отчёте «Журналы». */
    public static function get_name(): string {
        return get_string('eventcheckfailed', 'plagiarism_antiplagiat');
    }

    /** Текст строки журнала: какая проверка и почему упала. */
    public function get_description(): string {
        $error = $this->other['error'] ?? '';
        $filename = $this->other['filename'] ?? '';
        return "Antiplagiat BGUIR check {$this->objectid} for user {$this->userid}" .
            ($filename !== '' ? " (file '{$filename}')" : '') . " failed: {$error}";
    }

    /** Ссылка из журнала на отчёт курса. */
    public function get_url(): \moodle_url {
        return new \moodle_url('/plagiarism/antiplagiat/report.php', [
            'courseid' => $this->courseid,
        ]);
    }
}

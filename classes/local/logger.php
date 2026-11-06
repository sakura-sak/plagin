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
 * Check failure logging.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Запись сбоя проверки в журнал Moodle и в вывод cron.
 */
final class logger {
    /** Пишет причину ошибки в cron, отладку и событие журнала сайта. */
    public static function failure(\stdClass $check, string $message): void {
        $line = 'Антиплагиат БГУИР #' . $check->id . ': ' . $message;
        mtrace($line);
        debugging($line, DEBUG_NORMAL);
        try {
            $context = \context_module::instance($check->cmid, IGNORE_MISSING);
            if (!$context) {
                $context = \context_system::instance();
            }
            $event = \plagiarism_antiplagiat\event\check_failed::create([
                'context' => $context,
                'objectid' => (int)$check->id,
                'userid' => (int)$check->userid,
                'courseid' => (int)$check->courseid,
                'other' => [
                    'error' => \core_text::substr($message, 0, 1000),
                    'status' => (string)$check->status,
                    'filename' => (string)($check->filename ?? ''),
                ],
            ]);
            $event->trigger();
        } catch (\Throwable $e) {
            mtrace('Антиплагиат БГУИР: не удалось записать событие журнала: ' . $e->getMessage());
        }
    }
}

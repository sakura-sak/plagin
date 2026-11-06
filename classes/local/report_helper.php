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
 * Check results in assignment, gradebook and course report.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Показ результатов проверки в задании, журнале оценок и отчёте курса.
 */
final class report_helper {
    /** Может ли пользователь видеть проверки всех студентов курса (преподаватель). */
    public static function can_view_all(\context $context): bool {
        return has_capability('plagiarism/antiplagiat:viewallreports', $context)
            || has_capability('mod/assign:grade', $context);
    }

    /**
     * Список проверок курса. Если передан userid — только работы этого студента.
     */
    public static function get_course_checks(int $courseid, ?int $userid = null, int $limit = 0): array {
        global $DB;
        $params = ['courseid' => $courseid];
        $where = 'pa.courseid = :courseid';
        if ($userid) {
            $where .= ' AND pa.userid = :userid';
            $params['userid'] = $userid;
        }
        $sql = "SELECT pa.*, a.name AS assignmentname,
                       u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                       u.middlename, u.alternatename
                  FROM {plagiarism_antiplagiat} pa
                  JOIN {user} u ON u.id = pa.userid
             LEFT JOIN {assign} a ON a.id = pa.assignmentid
                 WHERE {$where}
              ORDER BY pa.timecreated DESC";
        return $DB->get_records_sql($sql, $params, 0, $limit);
    }

    /** Короткая строка рядом с файлом: статус или проценты со ссылкой на отчёт. */
    public static function summary_html(\stdClass $check, bool $fullreport = false): string {
        $statuskey = self::status_key($check->status ?? '');
        if ($statuskey !== 'completed') {
            $html = \html_writer::span(
                get_string('checkstatus', 'plagiarism_antiplagiat',
                    get_string('status_' . $statuskey, 'plagiarism_antiplagiat')),
                'plagiarism-antiplagiat status-' . $statuskey);
            if (!empty($check->lasterror)) {
                $html .= ' ' . \html_writer::span(
                    s(error_mapper::display((string)$check->lasterror)),
                    'text-muted');
            }
            return $html;
        }
        $text = get_string('resultsummary', 'plagiarism_antiplagiat', (object)[
            'originality' => format_float($check->originality, 2),
            'borrowing' => format_float($check->borrowing, 2),
            'ai' => format_float($check->aipercent, 2),
        ]);
        $url = self::report_url($check, $fullreport);
        if ($url !== '') {
            return \html_writer::link(new \moodle_url($url), $text,
                ['target' => '_blank', 'rel' => 'noopener']);
        }
        return \html_writer::span($text, 'plagiarism-antiplagiat');
    }

    /** Teacher gets the full report. Student never receives that URL. */
    public static function report_url(\stdClass $check, bool $fullreport): string {
        if ($fullreport && !empty($check->reporturlfull)) {
            return (string)$check->reporturlfull;
        }
        return (string)($check->reporturl ?? '');
    }

    /** Таблица результатов для журнала оценок и страницы отчёта курса. */
    public static function render_table(array $checks, bool $showstudent): \html_table {
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable plagiarism-antiplagiat-table';
        $table->head = [];
        if ($showstudent) {
            $table->head[] = get_string('student', 'plagiarism_antiplagiat');
        }
        $table->head[] = get_string('assignmentname', 'plagiarism_antiplagiat');
        $table->head[] = self::heading('reportcolfile', 'file');
        $table->head[] = self::heading('status', 'status');
        $table->head[] = get_string('originality', 'plagiarism_antiplagiat');
        $table->head[] = get_string('borrowing', 'plagiarism_antiplagiat');
        $table->head[] = get_string('aipercent', 'plagiarism_antiplagiat');
        $table->head[] = get_string('timecompleted', 'plagiarism_antiplagiat');
        $table->head[] = get_string('fullreport', 'plagiarism_antiplagiat');
        foreach ($checks as $check) {
            $statuskey = self::status_key($check->status ?? '');
            $row = [];
            if ($showstudent) {
                $row[] = fullname($check);
            }
            $row[] = s($check->assignmentname ?? '—');
            $row[] = s($check->filename);
            $row[] = get_string('status_' . $statuskey, 'plagiarism_antiplagiat');
            $row[] = $check->originality !== null ? format_float($check->originality, 2) : '—';
            $row[] = $check->borrowing !== null ? format_float($check->borrowing, 2) : '—';
            $row[] = $check->aipercent !== null ? format_float($check->aipercent, 2) : '—';
            $row[] = !empty($check->timecompleted) ? userdate($check->timecompleted) : '—';
            $url = self::report_url($check, $showstudent);
            $row[] = $url !== ''
                ? \html_writer::link(new \moodle_url($url),
                    get_string($showstudent ? 'openfullreport' : 'openbriefreport', 'plagiarism_antiplagiat'),
                    ['target' => '_blank', 'rel' => 'noopener'])
                : '—';
            $table->data[] = $row;
        }
        return $table;
    }

    /**
     * Заголовок колонки: строка плагина, иначе стандартная строка Moodle (file → «Файл»).
     * Старый get_string('filename') без компонента давал [[filename]].
     */
    private static function heading(string $pluginkey, string $corekey): string {
        if (get_string_manager()->string_exists($pluginkey, 'plagiarism_antiplagiat')) {
            $value = get_string($pluginkey, 'plagiarism_antiplagiat');
            if ($value !== '' && $value !== '[[' . $pluginkey . ']]' && strcasecmp($value, $pluginkey) !== 0) {
                return $value;
            }
        }
        return get_string($corekey);
    }

    /** Оставляет только допустимый статус: queued, processing, completed, error. */
    public static function status_key(string $status): string {
        $allowed = ['queued', 'processing', 'completed', 'error'];
        return in_array($status, $allowed, true) ? $status : 'error';
    }

    /** Moodle assignment work type → Guard category slug. */
    public static function category_from_worktype(string $worktype): string {
        return work_types::guard_category($worktype);
    }

    /** Map Guard analysis JSON to Moodle check status. */
    public static function status_from_guard(array $result): string {
        $doc = (string)($result['documentStatus'] ?? $result['status'] ?? '');
        $job = (string)($result['jobStatus'] ?? '');
        if (in_array($doc, ['draft', 'final'], true)) {
            return 'completed';
        }
        if ($doc === 'failed' || $job === 'failed') {
            return 'error';
        }
        if (in_array($doc, ['processing', 'queued'], true) || in_array($job, ['queued', 'processing', 'running'], true)) {
            return 'processing';
        }
        return self::status_key($doc);
    }

    /** @return array{originality: ?float, borrowing: ?float, aipercent: ?float} */
    public static function percents_from_guard(array $result): array {
        $originality = $result['originalityPercent'] ?? $result['originality_percent'] ?? null;
        $borrowing = $result['plagiarismPercent'] ?? $result['plagiarismPercentMl'] ?? $result['borrowing_percent'] ?? null;
        $ai = $result['aiPercentMl'] ?? $result['ai_percent'] ?? null;
        return [
            'originality' => is_numeric($originality) ? (float)$originality : null,
            'borrowing' => is_numeric($borrowing) ? (float)$borrowing : null,
            'aipercent' => is_numeric($ai) ? (float)$ai : null,
        ];
    }
}

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
 * Observer for assignment file uploads.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat;

defined('MOODLE_INTERNAL') || die();

/**
 * Следит за сохранением файлов в задании Moodle.
 */
final class observer {
    /**
     * Срабатывает, когда студент нажал «Сохранить» и прикрепил файл.
     * Считает отпечаток содержимого (SHA-256). Если такой файл уже проверяли —
     * повторно не отправляет. Иначе создаёт запись «в очереди» и ставит фоновую отправку.
     */
    public static function assessable_uploaded(\assignsubmission_file\event\assessable_uploaded $event): void {
        global $DB;
        if (!(int)\plagiarism_antiplagiat\local\plugin_config::get('enabled', 0)) {
            return;
        }
        $data = $event->get_data();
        $hashes = $data['other']['pathnamehashes'] ?? [];
        if (!is_array($hashes)) {
            return;
        }
        $fs = get_file_storage();
        $cm = get_coursemodule_from_id('assign', $event->contextinstanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $modulecfg = $DB->get_record('plagiarism_antiplag_cfg', ['cmid' => $cm->id]);
        if ($modulecfg && !$modulecfg->enabled) {
            return;
        }
        $queued = 0;
        foreach ($hashes as $pathnamehash) {
            $file = $fs->get_file_by_hash($pathnamehash);
            if (!$file || $file->is_directory()) {
                continue;
            }
            $tmp = make_request_directory() . '/antiplagiat-hash';
            $file->copy_content_to($tmp);
            $contenthash = hash_file('sha256', $tmp);
            $params = ['contenthash' => $contenthash, 'cmid' => $cm->id, 'userid' => $event->userid];
            $existingrecords = $DB->get_records_select('plagiarism_antiplagiat',
                'contenthash = :contenthash AND cmid = :cmid AND userid = :userid ' .
                "AND status IN ('queued','processing','completed')",
                $params, "CASE status WHEN 'completed' THEN 0 WHEN 'processing' THEN 1 ELSE 2 END, timemodified DESC",
                '*', 0, 1);
            $existing = $existingrecords ? reset($existingrecords) : false;
            if ($existing) {
                $existing->fileid = $file->get_id();
                $existing->submissionid = $event->objectid;
                $existing->filename = $file->get_filename();
                $existing->timemodified = time();
                $DB->update_record('plagiarism_antiplagiat', $existing);
                continue;
            }
            $now = time();
            $record = (object)[
                'cmid' => $cm->id, 'courseid' => $event->courseid, 'assignmentid' => $cm->instance,
                'submissionid' => $event->objectid ?: ($data['other']['itemid'] ?? 0),
                'userid' => $event->userid, 'fileid' => $file->get_id(),
                'filename' => $file->get_filename(), 'contenthash' => $contenthash,
                'status' => 'queued', 'attempts' => 0, 'timecreated' => $now, 'timemodified' => $now,
            ];
            $record->id = $DB->insert_record('plagiarism_antiplagiat', $record);
            $task = new \plagiarism_antiplagiat\task\submit_check();
            $task->set_custom_data(['checkid' => $record->id]);
            \core\task\manager::queue_adhoc_task($task);
            $queued++;
        }
        if ($queued) {
            \core\notification::info(get_string('submittednotice', 'plagiarism_antiplagiat'));
        } else {
            \core\notification::info(get_string('reusednotice', 'plagiarism_antiplagiat'));
        }
    }
}

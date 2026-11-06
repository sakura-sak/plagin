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
 * Background submit of one assignment file to Guard POST /api/upload.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat\task;

defined('MOODLE_INTERNAL') || die();

final class submit_check extends \core\task\adhoc_task {
    public function execute(): void {
        global $DB;
        $check = $DB->get_record('plagiarism_antiplagiat', ['id' => $this->get_custom_data()->checkid], '*', MUST_EXIST);
        $file = get_file_storage()->get_file_by_id($check->fileid);
            if (!$file) {
            $this->fail($check, 'corrupted_file', true);
            return;
        }
        try {
            $course = $DB->get_record('course', ['id' => $check->courseid], '*', MUST_EXIST);
            $assign = $DB->get_record('assign', ['id' => $check->assignmentid], '*', MUST_EXIST);
            $user = $DB->get_record('user', ['id' => $check->userid], '*', MUST_EXIST);
        } catch (\dml_missing_record_exception $e) {
            $this->fail($check, 'internal', true);
            return;
        }

        $extracted = \plagiarism_antiplagiat\local\text_extractor::from_stored_file($file);
        if (!empty($extracted['error'])) {
            $this->fail($check, (string)$extracted['error'], true);
            return;
        }
        if (\core_text::strlen($extracted['text']) < 50) {
            $this->fail($check, 'no_text', true);
            return;
        }

        $modulecfg = $DB->get_record('plagiarism_antiplag_cfg', ['cmid' => $check->cmid]);
        $lesson = \plagiarism_antiplagiat\local\work_types::normalize($modulecfg->worktype ?? 'ЛР');
        $fields = [
            'title' => $assign->name ?: $file->get_filename(),
            'content' => $extracted['text'],
            'category' => \plagiarism_antiplagiat\local\work_types::guard_category($lesson),
            'lessonType' => $lesson,
            'username' => $user->username,
            'fullName' => fullname($user),
            'institution' => (string)\plagiarism_antiplagiat\local\plugin_config::get('university', 'БГУИР') ?: 'БГУИР',
            'document_type' => $extracted['document_type'] === 'pdf' ? 'pdf' : 'word',
        ];

        try {
            $response = (new \plagiarism_antiplagiat\local\api_client())->submit($file, $fields);
            $documentid = $response['document']['id'] ?? $response['documentId'] ?? null;
            if ($documentid === null || $documentid === '') {
                throw new \plagiarism_antiplagiat\local\api_exception('invalidapiresponse', null, true);
            }
            $check->remotetaskid = (string)$documentid;
            $check->status = 'queued';
            $check->attempts = 0;
            $check->lasterror = null;
            $check->timemodified = time();
            $DB->update_record('plagiarism_antiplagiat', $check);
        } catch (\plagiarism_antiplagiat\local\api_exception $e) {
            $category = \plagiarism_antiplagiat\local\error_mapper::from_throwable($e);
            if ($category === 'overloaded') {
                $this->leave_waiting($check, $category);
                return;
            }
            if (!$this->fail($check, $category, !$e->is_retryable()) && $e->is_retryable()) {
                throw $e;
            }
        } catch (\Throwable $e) {
            $category = \plagiarism_antiplagiat\local\error_mapper::from_throwable($e);
            if (!$this->fail($check, $category)) {
                throw $e;
            }
        }
    }

    /** High load is not a failed check: the work stays in the queue. */
    private function leave_waiting(\stdClass $check, string $category): void {
        global $DB;
        $check->status = 'queued';
        $check->lasterror = $category;
        $check->timemodified = time();
        $DB->update_record('plagiarism_antiplagiat', $check);
        mtrace('Антиплагиат БГУИР #' . $check->id . ': ' . $category);
    }

    private function fail(\stdClass $check, string $message, bool $permanent = false): bool {
        global $DB;
        $check->attempts++;
        $category = \plagiarism_antiplagiat\local\error_mapper::is_key($message)
            ? $message
            : \plagiarism_antiplagiat\local\error_mapper::from_text($message);
        $check->lasterror = $category;
        $check->timemodified = time();
        $maxattempts = max(1, (int)\plagiarism_antiplagiat\local\plugin_config::get('maxattempts', 6));
        if ($permanent || $check->attempts >= $maxattempts) {
            if (!$permanent && ($category === 'unavailable' || $category === 'timeout')) {
                $category = 'timeout';
                $check->lasterror = $category;
            }
            $check->status = 'error';
            $DB->update_record('plagiarism_antiplagiat', $check);
            \plagiarism_antiplagiat\local\logger::failure($check, $category . ' ' . $message);
            return true;
        }
        $DB->update_record('plagiarism_antiplagiat', $check);
        mtrace('Антиплагиат БГУИР #' . $check->id . ' (попытка ' . $check->attempts . '/' . $maxattempts . '): ' . $message);
        return false;
    }
}

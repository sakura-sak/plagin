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
 * Poll Guard analysis status every 10 minutes.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat\task;

defined('MOODLE_INTERNAL') || die();

final class poll_checks extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('polltask', 'plagiarism_antiplagiat');
    }

    public function execute(): void {
        global $DB;
        $this->recover_unsent();
        $client = new \plagiarism_antiplagiat\local\api_client();
        $maxattempts = max(1, (int)\plagiarism_antiplagiat\local\plugin_config::get('maxattempts', 6));
        $checks = $DB->get_records_select('plagiarism_antiplagiat',
            "status IN ('queued','processing') AND remotetaskid IS NOT NULL");
        foreach ($checks as $check) {
            $user = $DB->get_record('user', ['id' => $check->userid]);
            $username = $user->username ?? '';
            try {
                $result = $client->status($check->remotetaskid, $username);
                $status = \plagiarism_antiplagiat\local\report_helper::status_from_guard($result);
                $check->status = $status;
                $check->attempts = 0;
                $check->lasterror = null;
                if ($status === 'completed') {
                    $percents = \plagiarism_antiplagiat\local\report_helper::percents_from_guard($result);
                    $check->originality = $percents['originality'];
                    $check->borrowing = $percents['borrowing'];
                    $check->aipercent = $percents['aipercent'];
                    $check->timecompleted = !empty($result['analysisCompletedAt'])
                        ? (strtotime((string)$result['analysisCompletedAt']) ?: time()) : time();
                    $this->store_report_links($client, $check, $username);
                } else if ($status === 'error') {
                    $raw = (string)($result['errorCode'] ?? $result['lastError'] ?? $result['error'] ?? 'internal');
                    $check->lasterror = \plagiarism_antiplagiat\local\error_mapper::from_text($raw);
                    \plagiarism_antiplagiat\local\logger::failure($check, $raw);
                } else if ($status === 'processing') {
                    $check->status = 'processing';
                }
            } catch (\plagiarism_antiplagiat\local\api_exception $e) {
                $this->handle_poll_failure($check, $e->getMessage(), $e->is_retryable(), $maxattempts);
            } catch (\Throwable $e) {
                $this->handle_poll_failure($check, $e->getMessage(), true, $maxattempts);
            }
            $check->timemodified = time();
            $DB->update_record('plagiarism_antiplagiat', $check);
        }
        $this->backfill_full_links($client);
    }

    /** Brief link stays in reporturl. Full link is reporturlfull (teacher only). */
    private function store_report_links(\plagiarism_antiplagiat\local\api_client $client, \stdClass $check,
            string $username): void {
        try {
            $links = $client->report_links($check->remotetaskid, $username);
            $check->reporturl = $links['verifyUrl'] ?? $links['reportPdfUrl'] ?? $check->reporturl;
            if (!empty($links['fullUrl'])) {
                $check->reporturlfull = $links['fullUrl'];
                if ($check->lasterror === 'result_transfer') {
                    $check->lasterror = null;
                }
            } else if (empty($check->reporturlfull)) {
                $check->lasterror = 'result_transfer';
            }
        } catch (\Throwable $e) {
            if (empty($check->reporturl) && empty($check->reporturlfull)) {
                $check->lasterror = 'result_transfer';
            }
            \plagiarism_antiplagiat\local\logger::failure($check, $e->getMessage());
        }
    }

    /** Completed checks from older plugin versions have only the brief link. */
    private function backfill_full_links(\plagiarism_antiplagiat\local\api_client $client): void {
        global $DB;
        $rows = $DB->get_records_select('plagiarism_antiplagiat',
            "status = 'completed' AND remotetaskid IS NOT NULL AND (reporturlfull IS NULL OR reporturlfull = '')",
            null, 'timemodified ASC', '*', 0, 30);
        foreach ($rows as $check) {
            $user = $DB->get_record('user', ['id' => $check->userid]);
            $this->store_report_links($client, $check, $user->username ?? '');
            $check->timemodified = time();
            $DB->update_record('plagiarism_antiplagiat', $check);
        }
    }

    private function recover_unsent(): void {
        global $DB;
        $maxattempts = max(1, (int)\plagiarism_antiplagiat\local\plugin_config::get('maxattempts', 6));
        $stuck = $DB->get_records_select('plagiarism_antiplagiat',
            "status = 'queued' AND remotetaskid IS NULL");
        foreach ($stuck as $check) {
            if ((int)$check->attempts >= $maxattempts) {
                $check->status = 'error';
                $check->lasterror = $check->lasterror ?: get_string('submitfailed', 'plagiarism_antiplagiat');
                $check->timemodified = time();
                $DB->update_record('plagiarism_antiplagiat', $check);
                \plagiarism_antiplagiat\local\logger::failure($check, $check->lasterror);
                continue;
            }
            $task = new submit_check();
            $task->set_custom_data(['checkid' => $check->id]);
            if (\core\task\manager::queue_adhoc_task($task, true)) {
                mtrace('Антиплагиат БГУИР #' . $check->id . ': повторная постановка в очередь отправки');
            }
        }
    }

    private function handle_poll_failure(\stdClass $check, string $message, bool $retryable, int $maxattempts): void {
        $category = \plagiarism_antiplagiat\local\error_mapper::from_text($message);
        if ($category === 'overloaded') {
            $check->lasterror = 'overloaded';
            mtrace('Антиплагиат БГУИР #' . $check->id . ': overloaded');
            return;
        }
        $check->lasterror = $category;
        if (!$retryable) {
            $check->status = 'error';
            \plagiarism_antiplagiat\local\logger::failure($check, $message);
            return;
        }
        $check->attempts++;
        if ($check->attempts >= $maxattempts) {
            $check->status = 'error';
            $check->lasterror = ($category === 'unavailable' || $category === 'timeout') ? 'timeout' : $category;
            \plagiarism_antiplagiat\local\logger::failure($check, $message);
            return;
        }
        mtrace('Антиплагиат БГУИР #' . $check->id . ' (попытка ' . $check->attempts . '/' . $maxattempts . '): ' . $message);
    }
}

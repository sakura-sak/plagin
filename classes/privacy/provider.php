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
 * Privacy API provider for plagiarism_antiplagiat.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Политика конфиденциальности Moodle: какие данные хранит плагин и что уходит на сайт антиплагиата.
 */
final class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /** Описывает поля БД и внешнюю передачу файла / ФИО / дисциплины. */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('plagiarism_antiplagiat', [
            'userid' => 'privacy:metadata:userid', 'filename' => 'privacy:metadata:filename',
            'contenthash' => 'privacy:metadata:contenthash', 'status' => 'privacy:metadata:status',
            'originality' => 'privacy:metadata:originality', 'borrowing' => 'privacy:metadata:borrowing',
            'aipercent' => 'privacy:metadata:aipercent', 'reporturl' => 'privacy:metadata:reporturl',
        ], 'privacy:metadata:checks');
        $collection->add_external_location_link('antiplagiat_bguir', [
            'file' => 'privacy:metadata:file',
            'username' => 'privacy:metadata:username',
            'student_name' => 'privacy:metadata:studentname',
            'discipline' => 'privacy:metadata:discipline',
            'university' => 'privacy:metadata:university',
        ], 'privacy:metadata:external');
        return $collection;
    }

    /** Находит задания, где у пользователя есть проверки. */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id FROM {context} ctx
                  JOIN {plagiarism_antiplagiat} pa ON pa.cmid = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel AND pa.userid = :userid";
        $list = new contextlist();
        $list->add_from_sql($sql, ['contextlevel' => CONTEXT_MODULE, 'userid' => $userid]);
        return $list;
    }

    /** Выгружает результаты проверок пользователя по запросу о персональных данных. */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $records = $DB->get_records('plagiarism_antiplagiat',
                ['cmid' => $context->instanceid, 'userid' => $contextlist->get_user()->id]);
            $data = [];
            foreach ($records as $record) {
                $data[] = (object)['filename' => $record->filename, 'status' => $record->status,
                    'originality' => $record->originality, 'borrowing' => $record->borrowing,
                    'aipercent' => $record->aipercent,
                    'timecreated' => transform::datetime($record->timecreated),
                    'timecompleted' => $record->timecompleted ? transform::datetime($record->timecompleted) : null];
            }
            writer::with_context($context)->export_data([get_string('pluginname', 'plagiarism_antiplagiat')],
                (object)['checks' => $data]);
        }
    }

    /** Удаляет все проверки и настройки антиплагиата в задании. */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel === CONTEXT_MODULE) {
            $DB->delete_records('plagiarism_antiplagiat', ['cmid' => $context->instanceid]);
            $DB->delete_records('plagiarism_antiplag_cfg', ['cmid' => $context->instanceid]);
        }
    }

    /** Удаляет проверки одного пользователя в выбранных заданиях. */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_MODULE) {
                $DB->delete_records('plagiarism_antiplagiat',
                    ['cmid' => $context->instanceid, 'userid' => $contextlist->get_user()->id]);
            }
        }
    }

    /** Список пользователей, у которых в этом задании есть проверки. */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $userlist->add_from_sql('userid',
            'SELECT userid FROM {plagiarism_antiplagiat} WHERE cmid = :cmid',
            ['cmid' => $context->instanceid]);
    }

    /** Удаляет проверки указанных пользователей в задании. */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        $userids = $userlist->get_userids();
        if ($context->contextlevel !== CONTEXT_MODULE || !$userids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['cmid'] = $context->instanceid;
        $DB->delete_records_select('plagiarism_antiplagiat', "cmid = :cmid AND userid $insql", $params);
    }
}

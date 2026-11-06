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
 * Страница отчёта курса: таблица проверок.
 * Преподаватель видит всех студентов, студент — только свои работы.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($courseid);
require_capability('moodle/course:view', $context);

$PAGE->set_url('/plagiarism/antiplagiat/report.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('report', 'plagiarism_antiplagiat'));
$PAGE->set_heading(format_string($course->fullname));

$showstudent = \plagiarism_antiplagiat\local\report_helper::can_view_all($context);
$userid = $showstudent ? null : $USER->id;
$checks = \plagiarism_antiplagiat\local\report_helper::get_course_checks($courseid, $userid);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'plagiarism_antiplagiat'));
if ($showstudent && plagiarism_plugin_antiplagiat::limitations_enabled(0)) {
    echo plagiarism_plugin_antiplagiat::limitations_box();
}
if (!$checks) {
    echo $OUTPUT->notification(get_string('nochecks', 'plagiarism_antiplagiat'), 'info');
} else {
    echo html_writer::table(\plagiarism_antiplagiat\local\report_helper::render_table($checks, $showstudent));
}
echo $OUTPUT->footer();

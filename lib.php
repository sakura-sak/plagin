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
 * Точка входа плагина в интерфейс Moodle: ссылка у файла и предупреждение студенту.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
// setup.php includes this file via get_plugins_with_function() before the
// plagiarism subsystem is loaded, so the parent class is not autoloaded.
if (!class_exists('plagiarism_plugin')) {
    if (!empty($CFG->libdir) && is_readable($CFG->libdir . '/plagiarismlib.php')) {
        require_once($CFG->libdir . '/plagiarismlib.php');
    }
    if (!class_exists('plagiarism_plugin') && !empty($CFG->dirroot)
            && is_readable($CFG->dirroot . '/plagiarism/lib.php')) {
        require_once($CFG->dirroot . '/plagiarism/lib.php');
    }
}

/**
 * Точка входа плагина в интерфейс Moodle: ссылка у файла и предупреждение студенту.
 */
class plagiarism_plugin_antiplagiat extends plagiarism_plugin {
    /** One limitations box per page: under the file, or in the page notice. */
    private static $limitationsprinted = false;

    /**
     * Рисует статус или проценты рядом с сданным файлом на странице задания.
     */
    public function get_links($linkarray) {
        global $DB;
        $file = $linkarray['file'] ?? null;
        if (!$file instanceof stored_file) {
            return '';
        }
        $checks = $DB->get_records('plagiarism_antiplagiat', ['fileid' => $file->get_id()],
            'timemodified DESC', '*', 0, 1);
        $check = $checks ? reset($checks) : false;
        if (!$check) {
            return '';
        }
        $cmid = (int)($linkarray['cmid'] ?? 0);
        $context = $cmid ? \context_module::instance($cmid, IGNORE_MISSING) : null;
        $full = $context ? \plagiarism_antiplagiat\local\report_helper::can_view_all($context) : false;
        return \plagiarism_antiplagiat\local\report_helper::summary_html($check, $full);
    }

    /**
     * Короткая пометка на вкладке «Задание». Текст об ограничениях ставится отдельно.
     */
    public function print_disclosure($cmid) {
        if (!self::limitations_enabled($cmid)) {
            return '';
        }
        return html_writer::div(get_string('disclosure', 'plagiarism_antiplagiat'), 'alert alert-info');
    }

    /** Plugin and this assignment both have the check turned on. */
    public static function limitations_enabled(int $cmid): bool {
        global $DB;
        if (!(int)\plagiarism_antiplagiat\local\plugin_config::get('enabled', 0)) {
            return false;
        }
        if ($cmid) {
            $cfg = $DB->get_record('plagiarism_antiplag_cfg', ['cmid' => $cmid]);
            if ($cfg && !$cfg->enabled) {
                return false;
            }
        }
        return true;
    }

    /** Warning text from the plugin setting, or the default list of limits. */
    public static function limitations_text(): string {
        $custom = trim((string)\plagiarism_antiplagiat\local\plugin_config::get('limitations', ''));
        if ($custom !== '') {
            return format_text($custom, FORMAT_PLAIN);
        }
        return get_string('limitations_default', 'plagiarism_antiplagiat');
    }

    /** Blue notice. Empty if this request already printed it. */
    public static function limitations_box(): string {
        if (self::$limitationsprinted) {
            return '';
        }
        self::$limitationsprinted = true;
        return html_writer::tag('div',
            html_writer::tag('strong', get_string('limitations_heading', 'plagiarism_antiplagiat'))
                . ' '
                . self::limitations_text(),
            ['id' => 'plagiarism-antiplagiat-limitations', 'class' => 'alert alert-info mt-3 mb-3']
        );
    }

    public static function on_assign_page(): bool {
        global $PAGE;
        return strpos((string)($PAGE->pagetype ?? ''), 'mod-assign') === 0;
    }

    /**
     * Страница ответа, не вкладка «Задание».
     * Студент: action=editsubmission. Преподаватель: action=grading («Ответы на задание»).
     */
    public static function is_answer_page(): bool {
        if (!self::on_assign_page()) {
            return false;
        }
        global $PAGE;
        $action = optional_param('action', '', PARAM_ALPHA);
        if ($action === 'grading' || $action === 'grader' || $action === 'editsubmission') {
            return true;
        }
        return (string)($PAGE->pagetype ?? '') === 'mod-assign-grading';
    }
}

/**
 * Пункт «Отчёт Антиплагиат БГУИР» в меню курса.
 */
function plagiarism_antiplagiat_extend_navigation_course(navigation_node $navigation, stdClass $course,
        context_course $context): void {
    if (!has_capability('moodle/course:view', $context)) {
        return;
    }
    $navigation->add(get_string('report', 'plagiarism_antiplagiat'),
        new moodle_url('/plagiarism/antiplagiat/report.php', ['courseid' => $course->id]),
        navigation_node::TYPE_SETTING, null, 'plagiarism_antiplagiat_report');
}

/**
 * Поля в форме задания: проверять ли файлы, тип работы и дисциплина.
 */
function plagiarism_antiplagiat_coursemodule_standard_elements($formwrapper, $mform): void {
    global $DB;
    $current = $formwrapper->get_current();
    if (($current->modulename ?? '') !== 'assign') {
        return;
    }
    $cm = $formwrapper->get_coursemodule();
    $cfg = $cm ? $DB->get_record('plagiarism_antiplag_cfg', ['cmid' => $cm->id]) : false;
    $mform->addElement('header', 'antiplagiatsettings', get_string('assignmentsettings', 'plagiarism_antiplagiat'));
    $mform->addElement('advcheckbox', 'antiplagiat_enabled', get_string('checkassignment', 'plagiarism_antiplagiat'));
    $mform->setDefault('antiplagiat_enabled', $cfg ? $cfg->enabled : 1);
    $options = [];
    foreach (\plagiarism_antiplagiat\local\work_types::options() as $code => $stringkey) {
        $options[$code] = get_string($stringkey, 'plagiarism_antiplagiat');
    }
    $mform->addElement('select', 'antiplagiat_worktype', get_string('worktype', 'plagiarism_antiplagiat'), $options);
    $mform->addHelpButton('antiplagiat_worktype', 'worktype', 'plagiarism_antiplagiat');
    $stored = \plagiarism_antiplagiat\local\work_types::normalize($cfg->worktype ?? 'ЛР');
    $mform->setDefault('antiplagiat_worktype', $stored);
    $mform->addElement('text', 'antiplagiat_discipline', get_string('discipline', 'plagiarism_antiplagiat'), ['size' => 60]);
    $mform->setType('antiplagiat_discipline', PARAM_TEXT);
    $mform->setDefault('antiplagiat_discipline', $cfg->discipline ?? $formwrapper->get_course()->fullname);
}

/**
 * Сохраняет настройки антиплагиата после создания или правки задания.
 */
function plagiarism_antiplagiat_coursemodule_edit_post_actions($moduleinfo, $course) {
    global $DB;
    if (($moduleinfo->modulename ?? '') !== 'assign' || empty($moduleinfo->coursemodule)) {
        return $moduleinfo;
    }
    $existing = $DB->get_record('plagiarism_antiplag_cfg', ['cmid' => $moduleinfo->coursemodule]);
    $record = (object)[
        'cmid' => $moduleinfo->coursemodule,
        'enabled' => empty($moduleinfo->antiplagiat_enabled) ? 0 : 1,
        'worktype' => \plagiarism_antiplagiat\local\work_types::normalize(
            $moduleinfo->antiplagiat_worktype ?? ($existing->worktype ?? 'ЛР')),
        'discipline' => clean_param($moduleinfo->antiplagiat_discipline ?? $course->fullname, PARAM_TEXT),
        'timemodified' => time(),
    ];
    if ($existing) {
        $record->id = $existing->id;
        $DB->update_record('plagiarism_antiplag_cfg', $record);
    } else {
        $DB->insert_record('plagiarism_antiplag_cfg', $record);
    }
    return $moduleinfo;
}

/**
 * Таблица последних проверок внизу страницы журнала оценок.
 */
function plagiarism_antiplagiat_before_footer(): void {
    global $PAGE, $COURSE, $USER;
    plagiarism_antiplagiat_place_limitations();
    if (strpos($PAGE->pagetype, 'grade-report-') !== 0 || empty($COURSE->id)) {
        return;
    }
    $context = context_course::instance($COURSE->id);
    $showstudent = \plagiarism_antiplagiat\local\report_helper::can_view_all($context);
    $userid = $showstudent ? null : $USER->id;
    $checks = \plagiarism_antiplagiat\local\report_helper::get_course_checks($COURSE->id, $userid, 100);
    if (!$checks) {
        return;
    }
    echo html_writer::start_tag('section', ['class' => 'mt-4 plagiarism-antiplagiat-gradebook']);
    echo html_writer::tag('h3', get_string('gradebookheading', 'plagiarism_antiplagiat'));
    echo html_writer::table(\plagiarism_antiplagiat\local\report_helper::render_table($checks, $showstudent));
    echo html_writer::div(html_writer::link(
        new moodle_url('/plagiarism/antiplagiat/report.php', ['courseid' => $COURSE->id]),
        get_string('openreport', 'plagiarism_antiplagiat')));
    echo html_writer::end_tag('section');
}

/**
 * Ставит предупреждение на вкладке «Задание»: после кнопки ответа и до «Состояние ответа».
 * На вкладке «Ответы на задание» не показывается. Страница отчёта выводит его сама.
 */
function plagiarism_antiplagiat_place_limitations(): void {
    global $PAGE;
    if (!plagiarism_plugin_antiplagiat::on_assign_page()) {
        return;
    }
    $action = optional_param('action', '', PARAM_ALPHA);
    $pagetype = (string)($PAGE->pagetype ?? '');
    if ($action === 'grading' || $pagetype === 'mod-assign-grading') {
        return;
    }
    if ($action !== '' && $action !== 'view') {
        return;
    }
    $cmid = !empty($PAGE->cm->id) ? (int)$PAGE->cm->id : optional_param('id', 0, PARAM_INT);
    if (!$cmid || !plagiarism_plugin_antiplagiat::limitations_enabled($cmid)) {
        return;
    }
    $box = plagiarism_plugin_antiplagiat::limitations_box();
    if ($box === '') {
        return;
    }
    echo $box;
    echo html_writer::script(
        "(function() {\n"
        . "function place() {\n"
        . "  var box = document.getElementById('plagiarism-antiplagiat-limitations');\n"
        . "  if (!box || box.getAttribute('data-placed') === '1') { return; }\n"
        . "  var anchor = document.querySelector('.submissionstatustable');\n"
        . "  if (!anchor || !anchor.parentNode) { return; }\n"
        . "  anchor.parentNode.insertBefore(box, anchor);\n"
        . "  box.setAttribute('data-placed', '1');\n"
        . "}\n"
        . "if (document.readyState === 'loading') {\n"
        . "  document.addEventListener('DOMContentLoaded', place);\n"
        . "} else {\n"
        . "  place();\n"
        . "}\n"
        . "})();"
    );
}

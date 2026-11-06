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
 * Standalone admin page: /plagiarism/antiplagiat/settings.php
 *
 * Moodle registers plagiarism settings as admin_externalpage, so this file
 * must render a full page (not only admin_setting objects).
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

try {
    admin_externalpage_setup('plagiarismantiplagiat');
} catch (moodle_exception $e) {
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/plagiarism/antiplagiat/settings.php'));
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title(get_string('pluginname', 'plagiarism_antiplagiat'));
    $PAGE->set_heading(get_string('pluginname', 'plagiarism_antiplagiat'));
}

$mform = new \plagiarism_antiplagiat\form\settings_form();
$mform->set_data(\plagiarism_antiplagiat\local\plugin_config::defaults_for_form());

if ($data = $mform->get_data()) {
    \plagiarism_antiplagiat\local\plugin_config::save($data);
    $istest = !empty($data->testconnection) || (bool)optional_param('testconnection', 0, PARAM_RAW);
    if ($istest) {
        $ping = \plagiarism_antiplagiat\local\api_client::ping((string)($data->apiurl ?? ''));
        if (!$ping['ok']) {
            $msg = get_string('testconnection_fail', 'plagiarism_antiplagiat', $ping['error']);
            redirect(new moodle_url('/plagiarism/antiplagiat/settings.php'), $msg, null, \core\output\notification::NOTIFY_ERROR);
        }
        $keycheck = \plagiarism_antiplagiat\local\api_client::ping_key(
            (string)($data->apiurl ?? ''),
            (string)($data->apikey ?? '')
        );
        if (!$keycheck['ok']) {
            $msg = get_string('testconnection_keyfail', 'plagiarism_antiplagiat', $keycheck['error']);
            redirect(new moodle_url('/plagiarism/antiplagiat/settings.php'), $msg, null, \core\output\notification::NOTIFY_ERROR);
        }
        $msg = get_string('testconnection_ok', 'plagiarism_antiplagiat', $ping['code']);
        redirect(new moodle_url('/plagiarism/antiplagiat/settings.php'), $msg, null, \core\output\notification::NOTIFY_SUCCESS);
    }
    redirect(new moodle_url('/plagiarism/antiplagiat/settings.php'),
        get_string('settingssaved', 'plagiarism_antiplagiat'),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'plagiarism_antiplagiat'));
if (empty($CFG->enableplagiarism)) {
    echo $OUTPUT->notification(get_string('enableplagiarismfirst', 'plagiarism_antiplagiat'), 'notifywarning');
}
echo $OUTPUT->box(get_string('configfilehint', 'plagiarism_antiplagiat',
    $CFG->dirroot . '/plagiarism/antiplagiat/config-guard.php'), 'generalbox');
$mform->display();
echo $OUTPUT->footer();

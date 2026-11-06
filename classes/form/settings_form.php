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
 * Admin form for Guard URL, API key and university.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class settings_form extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('advcheckbox', 'enabled',
            get_string('enabled', 'plagiarism_antiplagiat'),
            get_string('enabled_desc', 'plagiarism_antiplagiat'));
        $mform->addElement('text', 'apiurl', get_string('apiurl', 'plagiarism_antiplagiat'), ['size' => 60]);
        $mform->setType('apiurl', PARAM_URL);
        $mform->addElement('static', 'apiurl_help', '', get_string('apiurl_desc', 'plagiarism_antiplagiat'));
        $mform->addElement('passwordunmask', 'apikey', get_string('apikey', 'plagiarism_antiplagiat'));
        $mform->setType('apikey', PARAM_RAW);
        $mform->addElement('static', 'apikey_help', '', get_string('apikey_desc', 'plagiarism_antiplagiat'));
        $mform->addElement('text', 'university', get_string('university', 'plagiarism_antiplagiat'), ['size' => 40]);
        $mform->setType('university', PARAM_TEXT);
        $mform->addElement('text', 'maxattempts', get_string('maxattempts', 'plagiarism_antiplagiat'), ['size' => 6]);
        $mform->setType('maxattempts', PARAM_INT);
        $mform->addElement('textarea', 'limitations', get_string('limitations', 'plagiarism_antiplagiat'),
            ['rows' => 6, 'cols' => 60]);
        $mform->setType('limitations', PARAM_TEXT);
        $mform->addElement('static', 'limitations_help', '', get_string('limitations_desc', 'plagiarism_antiplagiat'));

        $buttons = [];
        $buttons[] = $mform->createElement('submit', 'save', get_string('savechanges'));
        $buttons[] = $mform->createElement('submit', 'testconnection',
            get_string('testconnection', 'plagiarism_antiplagiat'));
        $mform->addGroup($buttons, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
    }
}

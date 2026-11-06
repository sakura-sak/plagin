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
 * Plugin upgrade steps.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Обновление таблиц плагина при смене version.php.
 */
function xmldb_plagiarism_antiplagiat_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026081501) {
        $table = new xmldb_table('plagiarism_antiplag_cfg');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('worktype', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'assignment');
        $table->add_field('discipline', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('cmid', XMLDB_INDEX_UNIQUE, ['cmid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026081501, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026090900) {
        upgrade_plugin_savepoint(true, 2026090900, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026090901) {
        upgrade_plugin_savepoint(true, 2026090901, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026091300) {
        upgrade_plugin_savepoint(true, 2026091300, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026091301) {
        upgrade_plugin_savepoint(true, 2026091301, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026091302) {
        upgrade_plugin_savepoint(true, 2026091302, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026091303) {
        upgrade_plugin_savepoint(true, 2026091303, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026102500) {
        $table = new xmldb_table('plagiarism_antiplagiat');
        $field = new xmldb_field('reporturlfull', XMLDB_TYPE_TEXT, null, null, null, null, null, 'reporturl');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $DB->execute("UPDATE {plagiarism_antiplag_cfg}
                         SET worktype = 'ЛР'
                       WHERE worktype IN ('assignment', 'laboratory', 'lab', '')");
        $DB->execute("UPDATE {plagiarism_antiplag_cfg} SET worktype = 'ПЗ' WHERE worktype IN ('essay', 'report', 'practice')");
        $DB->execute("UPDATE {plagiarism_antiplag_cfg} SET worktype = 'КуР' WHERE worktype = 'coursework'");
        $DB->execute("UPDATE {plagiarism_antiplag_cfg} SET worktype = 'ДиР' WHERE worktype IN ('thesis', 'diploma')");
        upgrade_plugin_savepoint(true, 2026102500, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026102501) {
        upgrade_plugin_savepoint(true, 2026102501, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026102502) {
        upgrade_plugin_savepoint(true, 2026102502, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026102503) {
        upgrade_plugin_savepoint(true, 2026102503, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026102504) {
        upgrade_plugin_savepoint(true, 2026102504, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026102505) {
        upgrade_plugin_savepoint(true, 2026102505, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026102506) {
        upgrade_plugin_savepoint(true, 2026102506, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026102507) {
        upgrade_plugin_savepoint(true, 2026102507, 'plagiarism', 'antiplagiat');
    }

    if ($oldversion < 2026102508) {
        upgrade_plugin_savepoint(true, 2026102508, 'plagiarism', 'antiplagiat');
    }
    return true;
}

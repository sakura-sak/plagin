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
 * English language strings for plagiarism_antiplagiat.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['pluginname'] = 'Antiplagiat BGUIR';
$string['enabled'] = 'Enable integration';
$string['enabled_desc'] = 'Automatically submit assignment files for checking.';
$string['apiurl'] = 'Antiplagiat BGUIR URL';
$string['apiurl_desc'] = 'Base HTTPS REST API URL without a trailing slash.';
$string['apikey'] = 'REST API key';
$string['apikey_desc'] = 'Secret used to authenticate Moodle. Must match MOODLE_API_KEY on the Antiplagiat server.';
$string['university'] = 'University name';
$string['university_desc'] = 'Sent with each check and used to select the comparison corpus.';
$string['maxattempts'] = 'Maximum failed requests';
$string['maxattempts_desc'] = 'After this many failed polls or submits the check is marked as error and written to the Moodle log. Default 6 (about one hour at a 10-minute poll interval).';
$string['polltask'] = 'Poll Antiplagiat BGUIR results';
$string['status_queued'] = 'queued';
$string['status_processing'] = 'processing';
$string['status_completed'] = 'completed';
$string['status_error'] = 'error';
$string['checkstatus'] = 'Antiplagiat BGUIR: {$a}';
$string['resultsummary'] = 'Originality: {$a->originality}%; borrowing: {$a->borrowing}%; AI: {$a->ai}%';
$string['disclosure'] = 'After saving, this work will be sent to Antiplagiat BGUIR for borrowing analysis.';
$string['apierror'] = 'The plagiarism service returned HTTP {$a}.';
$string['connectionerror'] = 'Cannot reach the plagiarism service: {$a}';
$string['invalidapiresponse'] = 'The plagiarism service returned an invalid response.';
$string['unknownerror'] = 'Unknown check error.';
$string['filenotfound'] = 'The Moodle file was not found.';
$string['submitfailed'] = 'The document could not be submitted to Antiplagiat.';
$string['eventcheckfailed'] = 'Antiplagiat BGUIR check failed';
$string['report'] = 'Antiplagiat BGUIR report';
$string['student'] = 'Student';
$string['assignmentname'] = 'Assignment';
$string['reportcolfile'] = 'File';
$string['filename'] = 'File';
$string['status'] = 'Status';
$string['originality'] = 'Originality, %';
$string['borrowing'] = 'Borrowing, %';
$string['aipercent'] = 'AI, %';
$string['timecompleted'] = 'Completed';
$string['fullreport'] = 'Full report';
$string['openfullreport'] = 'Open report';
$string['nochecks'] = 'There are no checks in this course yet.';
$string['antiplagiat:viewallreports'] = 'View all plagiarism reports';
$string['submittednotice'] = 'The work has been submitted to Antiplagiat BGUIR for borrowing analysis.';
$string['recentchecks'] = 'Recent Antiplagiat BGUIR checks';
$string['gradebookheading'] = 'Antiplagiat BGUIR — gradebook results';
$string['openreport'] = 'Open the full course report';
$string['reusednotice'] = 'The work is unchanged; the previous check is reused.';
$string['assignmentsettings'] = 'Antiplagiat BGUIR';
$string['checkassignment'] = 'Check files submitted to this assignment';
$string['worktype'] = 'Work type';
$string['discipline'] = 'Discipline';
$string['type_assignment'] = 'Assignment';
$string['type_laboratory'] = 'Laboratory work';
$string['type_coursework'] = 'Coursework';
$string['type_thesis'] = 'Thesis';
$string['type_essay'] = 'Essay';
$string['type_report'] = 'Report';
$string['privacy:metadata:checks'] = 'User document check records.';
$string['privacy:metadata:userid'] = 'Moodle user identifier.';
$string['privacy:metadata:filename'] = 'Submitted filename.';
$string['privacy:metadata:contenthash'] = 'SHA-256 file content hash.';
$string['privacy:metadata:status'] = 'Current check status.';
$string['privacy:metadata:originality'] = 'Originality percentage.';
$string['privacy:metadata:borrowing'] = 'Borrowing percentage.';
$string['privacy:metadata:aipercent'] = 'AI-generated content percentage.';
$string['privacy:metadata:reporturl'] = 'Full report URL.';
$string['privacy:metadata:external'] = 'The document and metadata are sent to Antiplagiat BGUIR.';
$string['privacy:metadata:file'] = 'Submitted file content.';
$string['privacy:metadata:studentname'] = 'Student full name.';
$string['privacy:metadata:discipline'] = 'Discipline name.';
$string['settingssaved'] = 'Settings saved.';
$string['testconnection'] = 'Test connection to Guard';
$string['testconnection_ok'] = 'Guard is reachable and the API key was accepted (HTTP {$a}).';
$string['testconnection_fail'] = 'Moodle cannot reach Guard: {$a}';
$string['testconnection_keyfail'] = 'Guard opened, but the API key was rejected: {$a}';
$string['testconnection_needsenv'] = 'Guard has no MOODLE_API_KEY or the app container was not restarted after editing .env. Set the same key (≥16 characters) and restart app.';
$string['missingapikey'] = 'Set the Guard URL and API key (same as MOODLE_API_KEY) in settings or config-guard.php.';
$string['testconnection_nourl'] = 'Set the Guard URL first (config-guard.php or the field below).';
$string['enableplagiarismfirst'] = 'Enable the plagiarism subsystem (Site administration → Plugins → Plagiarism plugins) so assignment checks run.';
$string['configfilehint'] = 'Put the real Guard URL and MOODLE_API_KEY in one file on the Moodle server: {$a}. Non-empty values there override this form. URL must be reachable from the Moodle server, not localhost if Guard is on another machine.';
$string['emptytext'] = 'Could not extract enough text from the file (need at least 50 characters). Scanned PDFs are not supported.';
$string['privacy:metadata:username'] = 'Moodle / LDAP username of the student.';
$string['privacy:metadata:university'] = 'University name sent with the check.';
$string['openbriefreport'] = 'Open report';
$string['openfullreport'] = 'Open full report';
$string['type_lr'] = 'Laboratory work';
$string['type_pz'] = 'Practical work';
$string['type_ipr'] = 'Individual practical work';
$string['type_course'] = 'Course work/project';
$string['type_diploma'] = 'Diploma work/project';
$string['worktype_help'] = 'The type decides which works this submission is compared with. A laboratory is compared with laboratories, a practical with practicals, an individual practical with the same type. Course and diploma works share one pool.';
$string['limitations'] = 'Limitations notice';
$string['limitations_desc'] = 'Shown on the assignment tab before the submission status and on the report page before the table. Leave empty to use the default text.';
$string['limitations_heading'] = 'Limitations of the Antiplagiat BSUIR system.';
$string['limitations_default'] = 'The system does not compare images and formulas. Reports are available only from the university local network.';
$string['err_unavailable'] = 'The check service is temporarily unavailable. Try again later.';
$string['err_timeout'] = 'The check service did not respond in time.';
$string['err_overloaded'] = 'The system is busy. The work is waiting to be checked.';
$string['err_unsupported_format'] = 'This file format is not supported. Upload a PDF or DOCX.';
$string['err_corrupted_file'] = 'The file could not be processed. Check its contents.';
$string['err_no_text'] = 'No text to check was found in the document.';
$string['err_extraction_failed'] = 'Text could not be extracted from the document.';
$string['err_internal'] = 'An error occurred while processing the check.';
$string['err_result_transfer'] = 'The check result could not be sent back to the LMS.';
$string['err_auth'] = 'Could not connect to the check service.';

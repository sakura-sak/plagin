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
 * Assignment type → IIS lessonType and Guard category.
 *
 * KuP/KuR are one list item (coursework). DiP/DiR are one list item (diploma).
 * Stored value is the IIS lessonType code.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat\local;

defined('MOODLE_INTERNAL') || die();

final class work_types {
    /** @return array<string, string> lessonType => lang string key */
    public static function options(): array {
        return [
            'ЛР' => 'type_lr',
            'ПЗ' => 'type_pz',
            'ИПР' => 'type_ipr',
            'КуР' => 'type_course',
            'ДиР' => 'type_diploma',
        ];
    }

    /** Guard comparison category for a stored lesson type. */
    public static function guard_category(string $lessonType): string {
        $code = self::normalize($lessonType);
        if ($code === 'ПЗ') {
            return 'practice';
        }
        if ($code === 'ИПР') {
            return 'ipr';
        }
        if ($code === 'КуР') {
            return 'coursework';
        }
        if ($code === 'ДиР') {
            return 'diploma';
        }
        return 'lab';
    }

    /** Canonical IIS code stored in plagiarism_antiplag_cfg.worktype. */
    public static function normalize(string $lessonType): string {
        $raw = trim($lessonType);
        $map = [
            'lr' => 'ЛР',
            'лр' => 'ЛР',
            'laboratory' => 'ЛР',
            'lab' => 'ЛР',
            'assignment' => 'ЛР',
            'pz' => 'ПЗ',
            'пз' => 'ПЗ',
            'practice' => 'ПЗ',
            'essay' => 'ПЗ',
            'report' => 'ПЗ',
            'ipr' => 'ИПР',
            'ипр' => 'ИПР',
            'kur' => 'КуР',
            'kup' => 'КуР',
            'кур' => 'КуР',
            'куп' => 'КуР',
            'coursework' => 'КуР',
            'dir' => 'ДиР',
            'dip' => 'ДиР',
            'дир' => 'ДиР',
            'дип' => 'ДиР',
            'thesis' => 'ДиР',
            'diploma' => 'ДиР',
        ];
        $key = \core_text::strtolower($raw);
        if (isset($map[$key])) {
            return $map[$key];
        }
        if (isset(self::options()[$raw])) {
            return $raw;
        }
        return 'ЛР';
    }
}

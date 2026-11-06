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
 * Retryable vs permanent Guard API errors.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Ошибка обращения к API. Флаг retryable: можно ли повторить запрос позже.
 */
final class api_exception extends \moodle_exception {
    private bool $retryable;

    /**
     * @param string $errorcode код строки языка Moodle
     * @param mixed $a подстановка в сообщение (номер HTTP, текст сбоя)
     * @param bool $retryable true — обрыв связи / 5xx; false — ошибка 4xx
     */
    public function __construct(string $errorcode, $a, bool $retryable) {
        parent::__construct($errorcode, 'plagiarism_antiplagiat', '', $a);
        $this->retryable = $retryable;
    }

    /** Можно ли повторить запрос через интервал. */
    public function is_retryable(): bool {
        return $this->retryable;
    }
}

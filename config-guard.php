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
 * Единственный файл подключения к Guard (сайт «Антиплагиат БГУИР»).
 *
 * Положите его в <moodle>/plagiarism/antiplagiat/config-guard.php
 * и пропишите URL, который открывается С СЕРВЕРА Moodle (не localhost,
 * если СЭО и Guard на разных машинах). Ключ = MOODLE_API_KEY в .env Guard.
 *
 * Непустые apiurl и apikey отсюда имеют приоритет над формой в settings.php.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

return [
    // 1 = отправлять сдачи в Guard, 0 = только хранить в Moodle.
    'enabled' => 1,

    // Базовый URL Guard без слэша в конце. Пример: https://antiplagiat.bsuir.by
    // или http://172.16.x.x:3000 — то, что пингуется с сервера СЭО.
    'apiurl' => '',

    // Тот же секрет, что MOODLE_API_KEY на сервере Guard (≥16 символов).
    'apikey' => '',

    'university' => 'БГУИР',
    'maxattempts' => 6,
];

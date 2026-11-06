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
 * Russian language strings for plagiarism_antiplagiat.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['pluginname'] = 'Антиплагиат БГУИР';
$string['enabled'] = 'Включить интеграцию';
$string['enabled_desc'] = 'Автоматически отправлять файлы заданий на проверку.';
$string['apiurl'] = 'URL системы «Антиплагиат БГУИР»';
$string['apiurl_desc'] = 'Базовый HTTPS-адрес REST API без завершающего слеша.';
$string['apikey'] = 'Ключ REST API';
$string['apikey_desc'] = 'Секретный ключ для аутентификации Moodle. Должен совпадать с MOODLE_API_KEY на сервере антиплагиата.';
$string['university'] = 'Название университета';
$string['university_desc'] = 'Передаётся в метаданных проверки и используется при выборе корпуса.';
$string['maxattempts'] = 'Максимальное число неудачных запросов';
$string['maxattempts_desc'] = 'После превышения лимита проверка получает статус «ошибка», запись появляется в журнале Moodle. Рекомендуется 6 (около часа при опросе раз в 10 минут).';
$string['polltask'] = 'Опрос результатов «Антиплагиат БГУИР»';
$string['status_queued'] = 'в очереди';
$string['status_processing'] = 'обрабатывается';
$string['status_completed'] = 'завершена';
$string['status_error'] = 'ошибка';
$string['checkstatus'] = 'Антиплагиат БГУИР: {$a}';
$string['resultsummary'] = 'Оригинальность: {$a->originality}%; заимствования: {$a->borrowing}%; ИИ: {$a->ai}%';
$string['disclosure'] = 'После сохранения работа будет передана в систему «Антиплагиат БГУИР» для проверки на наличие заимствований.';
$string['apierror'] = 'Сервис антиплагиата вернул HTTP {$a}.';
$string['connectionerror'] = 'Нет соединения с сервисом антиплагиата: {$a}';
$string['invalidapiresponse'] = 'Сервис антиплагиата вернул некорректный ответ.';
$string['unknownerror'] = 'Неизвестная ошибка проверки.';
$string['filenotfound'] = 'Файл Moodle не найден.';
$string['submitfailed'] = 'Не удалось отправить документ в систему антиплагиата.';
$string['eventcheckfailed'] = 'Ошибка проверки «Антиплагиат БГУИР»';
$string['report'] = 'Отчёт «Антиплагиат БГУИР»';
$string['student'] = 'Студент';
$string['assignmentname'] = 'Задание';
$string['reportcolfile'] = 'Файл';
$string['filename'] = 'Файл';
$string['status'] = 'Статус';
$string['originality'] = 'Оригинальность, %';
$string['borrowing'] = 'Заимствования, %';
$string['aipercent'] = 'ИИ, %';
$string['timecompleted'] = 'Завершено';
$string['fullreport'] = 'Полный отчёт';
$string['openfullreport'] = 'Открыть отчёт';
$string['nochecks'] = 'Проверок в этом курсе пока нет.';
$string['antiplagiat:viewallreports'] = 'Просматривать все отчёты антиплагиата';
$string['submittednotice'] = 'Работа передана в систему «Антиплагиат БГУИР» для проверки на наличие заимствований.';
$string['recentchecks'] = 'Последние проверки «Антиплагиат БГУИР»';
$string['gradebookheading'] = 'Антиплагиат БГУИР — результаты в журнале оценок';
$string['openreport'] = 'Открыть полный отчёт по курсу';
$string['reusednotice'] = 'Содержимое работы не изменилось — используется ранее созданная проверка.';
$string['assignmentsettings'] = 'Антиплагиат БГУИР';
$string['checkassignment'] = 'Проверять файлы этого задания';
$string['worktype'] = 'Тип работы';
$string['discipline'] = 'Дисциплина';
$string['type_assignment'] = 'Обычное задание';
$string['type_laboratory'] = 'Лабораторная работа';
$string['type_coursework'] = 'Курсовая работа';
$string['type_thesis'] = 'ВКР / дипломная работа';
$string['type_essay'] = 'Реферат / эссе';
$string['type_report'] = 'Отчёт';
$string['privacy:metadata:checks'] = 'Сведения о проверках работ пользователя.';
$string['privacy:metadata:userid'] = 'Идентификатор пользователя Moodle.';
$string['privacy:metadata:filename'] = 'Имя отправленного файла.';
$string['privacy:metadata:contenthash'] = 'SHA-256 содержимого файла.';
$string['privacy:metadata:status'] = 'Текущий статус проверки.';
$string['privacy:metadata:originality'] = 'Процент оригинальности.';
$string['privacy:metadata:borrowing'] = 'Процент заимствований.';
$string['privacy:metadata:aipercent'] = 'Процент признаков генерации ИИ.';
$string['privacy:metadata:reporturl'] = 'Ссылка на полный отчёт.';
$string['privacy:metadata:external'] = 'Документ и метаданные передаются в систему «Антиплагиат БГУИР».';
$string['privacy:metadata:file'] = 'Содержимое проверяемого файла.';
$string['privacy:metadata:studentname'] = 'ФИО студента.';
$string['privacy:metadata:discipline'] = 'Название дисциплины.';
$string['settingssaved'] = 'Настройки сохранены.';
$string['testconnection'] = 'Проверить связь с Guard';
$string['testconnection_ok'] = 'Guard доступен, ключ API принят (HTTP {$a}).';
$string['testconnection_fail'] = 'С сервера Moodle Guard не открывается: {$a}';
$string['testconnection_keyfail'] = 'Сайт Guard открылся, но ключ API отклонён: {$a}';
$string['testconnection_needsenv'] = 'на сервере Guard не задан MOODLE_API_KEY или контейнер app не перезапущен после правки .env. Пропишите тот же ключ (≥16 символов), что в этом поле, и перезапустите app.';
$string['missingapikey'] = 'Укажите URL Guard и ключ API (тот же, что MOODLE_API_KEY) в настройках или в config-guard.php.';
$string['testconnection_nourl'] = 'Сначала укажите URL Guard (config-guard.php или поле ниже).';
$string['enableplagiarismfirst'] = 'Включите подсистему заимствований (Администрирование → Плагины → Плагины обнаружения заимствований), иначе проверки в заданиях не пойдут.';
$string['configfilehint'] = 'Реальный URL Guard и ключ MOODLE_API_KEY задайте в одном файле на сервере Moodle: {$a}. Непустые значения из файла важнее этой формы. URL должен открываться с сервера Moodle, не localhost, если Guard на другой машине.';
$string['emptytext'] = 'Не удалось извлечь достаточно текста из файла (нужно не менее 50 символов). Сканы PDF не поддерживаются.';
$string['privacy:metadata:username'] = 'Логин студента в Moodle / LDAP.';
$string['privacy:metadata:university'] = 'Название вуза, передаваемое вместе с проверкой.';
$string['openbriefreport'] = 'Открыть отчёт';
$string['openfullreport'] = 'Открыть полный отчёт';
$string['type_lr'] = 'Лабораторная работа';
$string['type_pz'] = 'Практическая работа';
$string['type_ipr'] = 'Индивидуальная практическая работа';
$string['type_course'] = 'Курсовая работа/проект';
$string['type_diploma'] = 'Дипломная работа/проект';
$string['worktype_help'] = 'От типа зависит, с какими работами сравнивается ответ. Лабораторная сравнивается с лабораторными, практическая — с практическими, индивидуальная практическая — с такими же. Курсовые и дипломные работы сравниваются вместе, в одном пуле.';
$string['limitations'] = 'Текст об ограничениях проверки';
$string['limitations_desc'] = 'Показывается на вкладке «Задание» перед состоянием ответа и на странице отчёта перед таблицей. Пустое поле — текст по умолчанию.';
$string['limitations_heading'] = 'Ограничения системы «Антиплагиат БГУИР».';
$string['limitations_default'] = 'Система не выполняет сравнение изображений и формул. Отчёт и QR-коды доступны только из локальной сети университета.';
$string['err_unavailable'] = 'Сервис проверки временно недоступен. Повторите попытку позже.';
$string['err_timeout'] = 'Превышено время ожидания ответа от системы.';
$string['err_overloaded'] = 'Система перегружена. Работа ожидает проверки.';
$string['err_unsupported_format'] = 'Формат файла не поддерживается. Загрузите PDF или DOCX.';
$string['err_corrupted_file'] = 'Не удалось обработать файл. Проверьте его содержимое.';
$string['err_no_text'] = 'В документе не обнаружен текст для проверки.';
$string['err_extraction_failed'] = 'Не удалось извлечь текст из документа.';
$string['err_internal'] = 'Во время проверки произошла ошибка обработки.';
$string['err_result_transfer'] = 'Не удалось передать результаты проверки в СЭО.';
$string['err_auth'] = 'Не удалось подключиться к сервису проверки.';

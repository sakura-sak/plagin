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
 * HTTP client for Guard (Antiplagiat BGUIR) REST API.
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat\local;

defined('MOODLE_INTERNAL') || die();

final class api_client {
    private string $baseurl;
    private string $apikey;

    public function __construct() {
        $this->baseurl = rtrim((string)plugin_config::get('apiurl', ''), '/');
        $this->apikey = (string)plugin_config::get('apikey', '');
    }

    /**
     * Can the Moodle server reach Guard? Any HTTP response means the host is visible.
     *
     * @return array{ok: bool, code: int, error: string}
     */
    public static function ping(string $url): array {
        global $CFG;
        $url = rtrim(trim($url), '/');
        if ($url === '') {
            return ['ok' => false, 'code' => 0, 'error' => get_string('testconnection_nourl', 'plagiarism_antiplagiat')];
        }
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $options = [
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_TIMEOUT' => 15,
        ];
        $curl->get($url, [], $options);
        $reach = self::curl_result($curl);
        if ($reach['code'] === 0) {
            return ['ok' => false, 'code' => 0, 'error' => $reach['error'] !== '' ? $reach['error'] : 'timeout'];
        }
        return ['ok' => true, 'code' => $reach['code'], 'error' => ''];
    }

    /**
     * Check X-API-Key against Guard POST /api/upload (auth only).
     *
     * @return array{ok: bool, code: int, error: string}
     */
    public static function ping_key(string $url, string $apikey): array {
        global $CFG;
        $url = rtrim(trim($url), '/');
        $apikey = trim($apikey);
        if ($url === '' || $apikey === '') {
            return ['ok' => false, 'code' => 0, 'error' => get_string('missingapikey', 'plagiarism_antiplagiat')];
        }
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $curl->setHeader('X-API-Key: ' . $apikey);
        $curl->setHeader('Authorization: Bearer ' . $apikey);
        $curl->setHeader('X-Moodle-Username: x');
        $options = [
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_TIMEOUT' => 15,
        ];
        $body = $curl->post($url . '/api/upload', ['username' => 'x'], $options);
        $result = self::curl_result($curl);
        $detail = '';
        $decoded = json_decode((string)$body, true);
        if (is_array($decoded) && isset($decoded['error']) && is_string($decoded['error'])) {
            $detail = $decoded['error'];
        }
        // Auth accepted: Guard asks for file/title/content (400) or creates a user (400 username too short is 400 too).
        if ($result['code'] === 400) {
            return ['ok' => true, 'code' => $result['code'], 'error' => ''];
        }
        if ($result['code'] === 401 || $result['code'] === 503) {
            if (stripos($detail, 'Требуется вход') !== false || stripos($detail, 'login required') !== false) {
                $detail = get_string('testconnection_needsenv', 'plagiarism_antiplagiat');
            }
            return ['ok' => false, 'code' => $result['code'], 'error' => $detail !== '' ? $detail : (string)$result['code']];
        }
        if ($result['code'] === 0) {
            return ['ok' => false, 'code' => 0, 'error' => $result['error'] !== '' ? $result['error'] : 'timeout'];
        }
        // Any other HTTP after the host answered: key path reached Guard.
        return ['ok' => $result['code'] < 500, 'code' => $result['code'], 'error' => $detail];
    }

    /**
     * @return array{code: int, error: string}
     */
    private static function curl_result(\curl $curl): array {
        $errno = 0;
        if (method_exists($curl, 'get_errno')) {
            $errno = (int)$curl->get_errno();
        } else if (!empty($curl->errno)) {
            $errno = (int)$curl->errno;
        }
        $curlerror = (string)($curl->error ?? '');
        $info = $curl->get_info();
        $code = (int)($info['http_code'] ?? 0);
        if ($errno && $code === 0) {
            return ['code' => 0, 'error' => $curlerror !== '' ? $curlerror : (string)$errno];
        }
        return ['code' => $code, 'error' => $curlerror];
    }

    /**
     * POST /api/upload — file + extracted text. Connection errors are retryable.
     *
     * @return array decoded JSON (document.id on 202)
     */
    public function submit(\stored_file $file, array $fields): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        if ($this->baseurl === '' || $this->apikey === '') {
            throw new api_exception('missingapikey', null, false);
        }
        $tmp = make_request_directory() . '/' . clean_param($file->get_filename(), PARAM_FILE);
        $file->copy_content_to($tmp);
        $curl = new \curl();
        foreach ($this->headers($fields['username'] ?? '') as $header) {
            $curl->setHeader($header);
        }
        $options = [
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_TIMEOUT' => 60,
        ];
        $payload = [
            'file' => new \CURLFile($tmp, $file->get_mimetype(), $file->get_filename()),
            'title' => (string)$fields['title'],
            'content' => (string)$fields['content'],
            'category' => (string)$fields['category'],
            'username' => (string)$fields['username'],
            'institution' => (string)$fields['institution'],
            'document_type' => (string)$fields['document_type'],
        ];
        if (!empty($fields['fullName'])) {
            $payload['fullName'] = (string)$fields['fullName'];
        }
        if (!empty($fields['lessonType'])) {
            $payload['lessonType'] = (string)$fields['lessonType'];
        }
        return $this->decode(
            $curl->post($this->baseurl . '/api/upload', $payload, $options),
            $curl
        );
    }

    /** GET /api/documents/{id}/analysis */
    public function status(string $documentid, string $username): array {
        return $this->get_json(
            '/api/documents/' . rawurlencode($documentid) . '/analysis',
            $username
        );
    }

    /** GET /api/report/{id}/links — verifyUrl for the Moodle result link. */
    public function report_links(string $documentid, string $username): array {
        return $this->get_json(
            '/api/report/' . rawurlencode($documentid) . '/links',
            $username
        );
    }

    private function get_json(string $path, string $username): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        foreach ($this->headers($username) as $header) {
            $curl->setHeader($header);
        }
        $options = [
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_TIMEOUT' => 30,
        ];
        $url = $this->baseurl . $path . '?username=' . rawurlencode($username);
        return $this->decode($curl->get($url, [], $options), $curl);
    }

    private function headers(string $username): array {
        $headers = [
            'X-API-Key: ' . $this->apikey,
            'Authorization: Bearer ' . $this->apikey,
        ];
        if ($username !== '') {
            $headers[] = 'X-Moodle-Username: ' . $username;
        }
        return $headers;
    }

    private function decode(string $body, \curl $curl): array {
        $errno = 0;
        if (method_exists($curl, 'get_errno')) {
            $errno = (int)$curl->get_errno();
        } else if (!empty($curl->errno)) {
            $errno = (int)$curl->errno;
        }
        $curlerror = (string)($curl->error ?? '');
        $info = $curl->get_info();
        $code = (int)($info['http_code'] ?? 0);

        if ($errno || $code === 0) {
            $detail = $curlerror !== '' ? $curlerror : $code;
            throw new api_exception('connectionerror', $detail, true);
        }
        if ($code === 409) {
            throw new api_exception('apierror', $this->error_detail($code, $body), true);
        }
        if ($code === 408 || $code === 429 || $code >= 500) {
            throw new api_exception('apierror', $code, true);
        }
        if ($code < 200 || $code >= 300) {
            throw new api_exception('apierror', $this->error_detail($code, $body), false);
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new api_exception('invalidapiresponse', null, true);
        }
        return $data;
    }

    private function error_detail(int $code, string $body): string {
        $decoded = json_decode($body, true);
        $detail = '';
        if (is_array($decoded) && isset($decoded['error']) && is_string($decoded['error'])) {
            $detail = $decoded['error'];
        } else if (is_array($decoded) && isset($decoded['detail'])) {
            $detail = is_string($decoded['detail'])
                ? $decoded['detail']
                : json_encode($decoded['detail'], JSON_UNESCAPED_UNICODE);
        }
        $errorcode = '';
        if (is_array($decoded) && isset($decoded['errorCode']) && is_string($decoded['errorCode'])) {
            $errorcode = trim($decoded['errorCode']);
        }
        if ($errorcode !== '' && error_mapper::is_key($errorcode)) {
            return $errorcode;
        }
        $detail = \core_text::substr(trim($detail), 0, 300);
        return $detail !== '' ? $code . ': ' . $detail : (string)$code;
    }
}

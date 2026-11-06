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
 * Extract plain text from DOCX/PDF for Guard POST /api/upload (content ≥ 50 chars).
 *
 * @package   plagiarism_antiplagiat
 * @copyright 2026 BSUIR
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_antiplagiat\local;

defined('MOODLE_INTERNAL') || die();

final class text_extractor {
    /**
     * @return array{text: string, document_type: string, error: ?string}
     *         error is a category key: unsupported_format, corrupted_file, extraction_failed.
     */
    public static function from_stored_file(\stored_file $file): array {
        $filename = strtolower($file->get_filename());
        $tmp = make_request_directory() . '/' . clean_param($file->get_filename(), PARAM_FILE);
        $file->copy_content_to($tmp);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ext !== 'docx' && $ext !== 'pdf') {
            return ['text' => '', 'document_type' => 'bin', 'error' => 'unsupported_format'];
        }
        try {
            if ($ext === 'docx') {
                if (!self::docx_readable($tmp)) {
                    return ['text' => '', 'document_type' => 'word', 'error' => 'corrupted_file'];
                }
                return ['text' => self::from_docx($tmp), 'document_type' => 'word', 'error' => null];
            }
            $raw = @file_get_contents($tmp);
            if (!is_string($raw) || strncmp($raw, '%PDF', 4) !== 0) {
                return ['text' => '', 'document_type' => 'pdf', 'error' => 'corrupted_file'];
            }
            return ['text' => self::from_pdf($tmp), 'document_type' => 'pdf', 'error' => null];
        } catch (\Throwable $e) {
            return ['text' => '', 'document_type' => $ext === 'pdf' ? 'pdf' : 'word', 'error' => 'extraction_failed'];
        }
    }

    private static function docx_readable(string $path): bool {
        if (!class_exists(\ZipArchive::class)) {
            return false;
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return false;
        }
        $ok = $zip->locateName('word/document.xml') !== false;
        $zip->close();
        return $ok;
    }

    public static function from_docx(string $path): string {
        if (!class_exists(\ZipArchive::class)) {
            return '';
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if (!is_string($xml) || $xml === '') {
            return '';
        }
        $xml = preg_replace('/<w:tab[^\\/]*\\/>/', ' ', $xml) ?? $xml;
        $xml = preg_replace('/<w:br[^\\/]*\\/>/', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<w:p[^>]*>/', "\n", $xml) ?? $xml;
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        return self::normalize($text);
    }

    public static function from_pdf(string $path): string {
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return '';
        }
        $parts = [];
        if (preg_match_all('/\\((?:\\\\.|[^\\\\)]){2,}\\)/s', $raw, $m)) {
            foreach ($m[0] as $chunk) {
                $inner = substr($chunk, 1, -1);
                $inner = str_replace(['\\n', '\\r', '\\t', '\\(', '\\)', '\\\\'], ["\n", '', ' ', '(', ')', '\\'], $inner);
                if (preg_match('/[\\p{L}\\p{N}]{3,}/u', $inner)) {
                    $parts[] = $inner;
                }
            }
        }
        return self::normalize(implode(' ', $parts));
    }

    public static function normalize(string $text): string {
        $text = preg_replace('/\\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }
}

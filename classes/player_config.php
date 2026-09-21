<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_videodebate;
/**
 * Resolves video source and player configuration.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class player_config {
    /**
     * Method build.
     *
     * @param \stdClass $activity Parameter activity.
     * @param \context_module $context Parameter context.
     * @return array Return value.
     */
    public static function build(\stdClass $activity, \context_module $context): array {
        $source = (string)$activity->videosource;
        $url = (string)($activity->videourl ?? '');
        if ($source === 'upload') {
            $files = get_file_storage()->get_area_files($context->id,
                'mod_videodebate', 'video', 0, 'itemid, filepath, filename', false);
            $file = reset($files);
            if ($file) {
                $url = \moodle_url::make_pluginfile_url($context->id, 'mod_videodebate', 'video', 0,
                    $file->get_filepath(), $file->get_filename())->out(false);
            }
        }
        $id = '';
        if ($source === 'youtube') {
            $id = self::youtube_id($url);
        } else if ($source === 'vimeo') {
            $id = self::vimeo_id($url);
        }
        return [
            'source' => $source,
            'url' => $url,
            'videoid' => $id,
            'youtube' => $source === 'youtube' && $id !== '',
            'vimeo' => $source === 'vimeo' && $id !== '',
            'html5' => in_array($source, ['upload', 'url'], true),
        ];
    }

    /**
     * Method youtube_id.
     *
     * @param string $url Parameter url.
     * @return string Return value.
     */
    private static function youtube_id(string $url): string {
        if (preg_match('~(?:youtu\\.be/|youtube\\.com/(?:watch\\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return $m[1];
        }
        return clean_param($url, PARAM_ALPHANUMEXT);
    }

    /**
     * Method vimeo_id.
     *
     * @param string $url Parameter url.
     * @return string Return value.
     */
    private static function vimeo_id(string $url): string {
        if (preg_match('~vimeo\\.com/(?:video/)?([0-9]+)~', $url, $m)) {
            return $m[1];
        }
        return preg_replace('/\\D+/', '', $url) ?: '';
    }
}

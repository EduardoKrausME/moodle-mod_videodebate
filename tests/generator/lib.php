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

/**
 * Video Debate test generator.
 *
 * @package mod_videodebate
 * @category test
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Video Debate module generator.
 *
 * @package mod_videodebate
 * @category test
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_videodebate_generator extends testing_module_generator {
    /**
     * Create a Video Debate activity.
     *
     * @param object|array|null $record Record overrides.
     * @param array|null $options Generator options.
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object)(array)$record;
        $defaults = [
            'name' => 'Video Debate',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'question' => 'What position do you defend?',
            'positions' => "Agree\nDisagree",
            'assignmentmode' => 0,
            'blinduntilpost' => 0,
            'videosource' => 'url',
            'videourl' => 'https://example.invalid/video.mp4',
            'durationseconds' => 100,
            'transcript' => '',
            'captionlang' => 'en',
            'resumeplayback' => 1,
            'allowseek' => 1,
            'completionpercent' => 80,
            'completionpost' => 0,
            'completionreplies' => 0,
            'minevidence' => 0,
            'grade' => 100,
            'weightargument' => 35,
            'weightevidence' => 30,
            'weightparticipation' => 20,
            'weightreplies' => 15,
        ];
        foreach ($defaults as $field => $value) {
            if (!isset($record->{$field})) {
                $record->{$field} = $value;
            }
        }
        return parent::create_instance($record, (array)$options);
    }
}

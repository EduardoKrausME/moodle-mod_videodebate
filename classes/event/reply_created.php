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

namespace mod_videodebate\event;

/**
 * reply created event.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reply_created extends \core\event\base {
    /**
     * Initialise event metadata.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'videodebate_posts';
    }

    /**
     * Event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventreplycreated', 'videodebate');
    }

    /**
     * Event description.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' triggered reply_created for Video Debate with id "
            . "'{$this->other['videodebateid']}'.";
    }

    /**
     * Event URL.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/videodebate/view.php', ['id' => $this->contextinstanceid]);
    }
}

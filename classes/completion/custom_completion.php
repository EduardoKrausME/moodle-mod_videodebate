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

namespace mod_videodebate\completion;
/**
 * Custom completion rules.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends \core_completion\activity_custom_completion {
    /**
     * Method get_state.
     *
     * @param string $rule Parameter rule.
     * @return int Return value.
     */
    public function get_state(string $rule): int {
        global $DB;
        $activity = $DB->get_record('videodebate', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $progress = $DB->get_record('videodebate_progress', ['videodebateid' => $activity->id, 'userid' => $this->userid]);
        if ($rule === 'completionpercent') {
            return $progress &&
            (float)$progress->percent >= (float)$activity->completionpercent ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        if ($rule === 'completionpost') {
            return !$activity->completionpost || \mod_videodebate\debate_manager::get_initial_post($activity->id, $this->userid)
                ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        if ($rule === 'completionreplies') {
            return \mod_videodebate\debate_manager::reply_count($activity->id, $this->userid) >= (int)$activity->completionreplies
                ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        return COMPLETION_INCOMPLETE;
    }

    /**
     * Method get_defined_custom_rules.
     *
     * @return array Return value.
     */
    public static function get_defined_custom_rules(): array {
        return ['completionpercent', 'completionpost', 'completionreplies'];
    }

    /**
     * Method get_custom_rule_descriptions.
     *
     * @return array Return value.
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;
        $activity = $DB->get_record('videodebate', ['id' => $this->cm->instance], '*', MUST_EXIST);
        return [
            'completionpercent' => get_string('completiondetail:percent', 'videodebate', $activity->completionpercent),
            'completionpost' => get_string('completiondetail:post', 'videodebate'),
            'completionreplies' => get_string('completiondetail:replies', 'videodebate', $activity->completionreplies),
        ];
    }

    /**
     * Method get_sort_order.
     *
     * @return array Return value.
     */
    public function get_sort_order(): array {
        return ['completionpercent', 'completionpost', 'completionreplies'];
    }
}

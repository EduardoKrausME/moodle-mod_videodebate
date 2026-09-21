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
 * Debate domain service.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class debate_manager {
    /**
     * Method positions_from_text.
     *
     * @param string $text Parameter text.
     * @return array Return value.
     */
    public static function positions_from_text(string $text): array {
        $lines = preg_split('/\R/u', trim($text)) ?: [];
        $positions = [];
        foreach ($lines as $line) {
            $label = trim($line);
            if ($label === '') {
                continue;
            }
            $key = substr(sha1(\core_text::strtolower($label)), 0, 20);
            $positions[$key] = $label;
        }
        return $positions;
    }

    /**
     * Method normalise_positions.
     *
     * @param string $text Parameter text.
     * @return string Return value.
     */
    public static function normalise_positions(string $text): string {
        return implode("\n", array_values(self::positions_from_text($text)));
    }

    /**
     * Method format_timecode.
     *
     * @param float $seconds Parameter seconds.
     * @return string Return value.
     */
    public static function format_timecode(float $seconds): string {
        $total = max(0, (int)round($seconds));
        $hours = intdiv($total, 3600);
        $minutes = intdiv($total % 3600, 60);
        $secs = $total % 60;
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%02d:%02d', $minutes, $secs);
    }

    /**
     * Method get_positions.
     *
     * @param \stdClass $activity Parameter activity.
     * @return array Return value.
     */
    public static function get_positions(\stdClass $activity): array {
        return self::positions_from_text((string)$activity->positions);
    }

    /**
     * Method assigned_position.
     *
     * @param \stdClass $activity Parameter activity.
     * @param int $userid Parameter userid.
     * @param int $groupid Parameter groupid.
     * @return string Return value.
     */
    public static function assigned_position(\stdClass $activity, int $userid, int $groupid = 0): string {
        $positions = self::get_positions($activity);
        if (!$positions) {
            return '';
        }
        $keys = array_keys($positions);
        $cm = get_coursemodule_from_instance('videodebate', $activity->id, $activity->course, false, IGNORE_MISSING);
        if (!$cm) {
            $index = abs((int)crc32($activity->id . ':' . $groupid . ':' . $userid)) % count($keys);
            return $keys[$index];
        }
        $context = \context_module::instance($cm->id);
        $users = get_enrolled_users($context, 'mod/videodebate:participate', 0, 'u.id', 'u.id ASC');
        $userids = array_map('intval', array_keys($users));
        if ($groupid > 0) {
            $members = groups_get_members($groupid, 'u.id');
            $memberids = array_map('intval', array_keys($members));
            $userids = array_values(array_intersect($userids, $memberids));
        }
        sort($userids, SORT_NUMERIC);
        $index = array_search($userid, $userids, true);
        if ($index === false) {
            $index = abs((int)crc32($activity->id . ':' . $groupid . ':' . $userid));
        }
        return $keys[$index % count($keys)];
    }

    /**
     * Method get_initial_post.
     *
     * @param int $activityid Parameter activityid.
     * @param int $userid Parameter userid.
     * @return ?\stdClass Return value.
     */
    public static function get_initial_post(int $activityid, int $userid): ?\stdClass {
        global $DB;
        return $DB->get_record('videodebate_posts', [
            'videodebateid' => $activityid, 'userid' => $userid, 'parentid' => 0,
        ]) ?: null;
    }

    /**
     * Method reply_count.
     *
     * @param int $activityid Parameter activityid.
     * @param int $userid Parameter userid.
     * @return int Return value.
     */
    public static function reply_count(int $activityid, int $userid): int {
        global $DB;
        return $DB->count_records_select('videodebate_posts',
            'videodebateid = :a AND userid = :u AND parentid <> 0', ['a' => $activityid, 'u' => $userid]);
    }

    /**
     * Method evidence_count_for_user.
     *
     * @param int $activityid Parameter activityid.
     * @param int $userid Parameter userid.
     * @return int Return value.
     */
    public static function evidence_count_for_user(int $activityid, int $userid): int {
        global $DB;
        $sql = 'SELECT COUNT(e.id)
                  FROM {videodebate_evidence} e
                  JOIN {videodebate_posts} p ON p.id = e.postid
                 WHERE p.videodebateid = :a AND p.userid = :u';
        return (int)$DB->count_records_sql($sql, ['a' => $activityid, 'u' => $userid]);
    }

    /**
     * Method save_post.
     *
     * @param \stdClass $activity Parameter activity.
     * @param int $userid Parameter userid.
     * @param int $groupid Parameter groupid.
     * @param int $parentid Parameter parentid.
     * @param string $positionkey Parameter positionkey.
     * @param string $message Parameter message.
     * @param array $evidence Parameter evidence.
     * @return int Return value.
     */
    public static function save_post(\stdClass $activity, int $userid, int $groupid, int $parentid,
                                     string    $positionkey, string $message, array $evidence): int {
        global $DB;
        $now = time();
        if ($parentid === 0 && self::get_initial_post($activity->id, $userid)) {
            throw new \moodle_exception('initialpostexists', 'videodebate');
        }
        if ($parentid !== 0) {
            $parent = $DB->get_record('videodebate_posts', ['id' => $parentid, 'videodebateid' => $activity->id], '*', MUST_EXIST);
            if ((int)$parent->userid === $userid) {
                throw new \moodle_exception('cannotreplyself', 'videodebate');
            }
            $positionkey = '';
        }
        if ($parentid === 0 && (int)$activity->assignmentmode === 1) {
            $positionkey = self::assigned_position($activity, $userid, $groupid);
        }
        $positions = self::get_positions($activity);
        if ($parentid === 0 && !isset($positions[$positionkey])) {
            throw new \moodle_exception('invalidposition', 'videodebate');
        }
        if ($parentid === 0 && count($evidence) < (int)$activity->minevidence) {
            throw new \moodle_exception('notenoughevidence', 'videodebate', '', $activity->minevidence);
        }
        $post = (object)[
            'videodebateid' => $activity->id,
            'userid' => $userid,
            'groupid' => $groupid,
            'parentid' => $parentid,
            'positionkey' => $positionkey ?: null,
            'message' => $message,
            'messageformat' => FORMAT_HTML,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $progress = $DB->get_record('videodebate_progress', ['videodebateid' => $activity->id, 'userid' => $userid],
            'id,duration', IGNORE_MISSING);
        $knownduration = $progress ? max(0, (float)$progress->duration) : 0;
        $transaction = $DB->start_delegated_transaction();
        $postid = $DB->insert_record('videodebate_posts', $post);
        foreach ($evidence as $item) {
            $start = max(0, (float)($item['start'] ?? 0));
            $end = max($start, (float)($item['end'] ?? $start));
            if ($knownduration > 0) {
                $start = min($start, $knownduration);
                $end = min(max($start, $end), $knownduration);
            }
            $DB->insert_record('videodebate_evidence', (object)[
                'postid' => $postid,
                'starttime' => $start,
                'endtime' => $end,
                'label' => clean_param((string)($item['label'] ?? ''), PARAM_TEXT),
                'timecreated' => $now,
            ]);
        }
        $transaction->allow_commit();
        return $postid;
    }

    /**
     * Method parse_evidence_json.
     *
     * @param string $json Parameter json.
     * @return array Return value.
     */
    public static function parse_evidence_json(string $json): array {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $item) {
            if (!is_array($item) || !isset($item['start'])) {
                continue;
            }
            $out[] = [
                'start' => max(0, (float)$item['start']),
                'end' => max((float)$item['start'], (float)($item['end'] ?? $item['start'])),
                'label' => clean_param((string)($item['label'] ?? ''), PARAM_TEXT),
            ];
        }
        return $out;
    }
}

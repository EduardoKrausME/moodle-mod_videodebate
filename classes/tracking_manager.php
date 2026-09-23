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
 * Server-authoritative video progress tracker.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tracking_manager {
    /**
     * Method update.
     *
     * @param \stdClass $activity Parameter activity.
     * @param int $userid Parameter userid.
     * @param float $current Parameter current.
     * @param float $duration Parameter duration.
     * @param float $start Parameter start.
     * @param float $end Parameter end.
     * @param float $rate Parameter rate.
     * @return \stdClass Return value.
     */
    public function update(\stdClass $activity, int $userid, float $current, float $duration,
                           float $start, float $end, float $rate): \stdClass {
        global $DB;
        $duration = max(0, $duration);
        $current = min(max(0, $current), $duration ?: $current);
        $start = min(max(0, $start), $duration ?: $start);
        $end = min(max($start, $end), $duration ?: $end);
        $rate = min(4.0, max(0.25, $rate));
        $record = $DB->get_record('videodebate_progress', ['videodebateid' => $activity->id, 'userid' => $userid]);
        $elapsed = $record ? max(1, time() - (int)$record->timemodified) : 5;
        $maxsegment = min(8.0 * $rate, max(4.0, ($elapsed * $rate * 1.75) + 2.0));
        if (($end - $start) > $maxsegment) {
            $end = $start;
        }

        $segments = $record ? (json_decode($record->watchedsegments, true) ?: []) : [];
        if ($end > $start) {
            $segments[] = [$start, $end];
        }
        $segments = $this->merge_segments($segments, $duration);
        $unique = 0.0;
        foreach ($segments as $segment) {
            $unique += max(0, $segment[1] - $segment[0]);
        }
        $percent = $duration > 0 ? min(100, ($unique / $duration) * 100) : 0;
        $data = (object)[
            'videodebateid' => $activity->id,
            'userid' => $userid,
            'duration' => $duration,
            'lastposition' => $current,
            'uniquewatched' => $unique,
            'totalwatchtime' => ($record ? (float)$record->totalwatchtime : 0) + max(0, $end - $start),
            'percent' => $percent,
            'watchedsegments' => json_encode($segments),
            'completed' => 0,
            'timemodified' => time(),
        ];
        $data->completed = $this->is_complete_with_progress($activity, $userid, $data) ? 1 : 0;
        if ($record) {
            $data->id = $record->id;
            $DB->update_record('videodebate_progress', $data);
        } else {
            $data->id = $DB->insert_record('videodebate_progress', $data);
        }
        $this->update_completion($activity, $userid, (bool)$data->completed);
        return $data;
    }

    /**
     * Method is_complete.
     *
     * @param \stdClass $activity Parameter activity.
     * @param int $userid Parameter userid.
     * @return bool Return value.
     */
    public function is_complete(\stdClass $activity, int $userid): bool {
        global $DB;
        $progress = $DB->get_record('videodebate_progress', ['videodebateid' => $activity->id, 'userid' => $userid]);
        if (!$progress) {
            $progress = (object)['percent' => 0];
        }
        return $this->is_complete_with_progress($activity, $userid, $progress);
    }

    /**
     * Method is_complete_with_progress.
     *
     * @param \stdClass $activity Parameter activity.
     * @param int $userid Parameter userid.
     * @param \stdClass $progress Parameter progress.
     * @return bool Return value.
     */
    private function is_complete_with_progress(\stdClass $activity, int $userid, \stdClass $progress): bool {
        $manager = new debate_manager();
        if ((float)$progress->percent + 0.001 < (float)$activity->completionpercent) {
            return false;
        }
        if (!empty($activity->completionpost) && !$manager::get_initial_post($activity->id, $userid)) {
            return false;
        }
        if ((int)$activity->completionreplies > 0 &&
            $manager::reply_count($activity->id, $userid) < (int)$activity->completionreplies) {
            return false;
        }
        return true;
    }

    /**
     * Method update_completion.
     *
     * @param \stdClass $activity Parameter activity.
     * @param int $userid Parameter userid.
     * @param bool $complete Parameter complete.
     * @return void Return value.
     */
    public function update_completion(\stdClass $activity, int $userid, bool $complete): void {
        global $DB;
        $cm = get_coursemodule_from_instance('videodebate', $activity->id, $activity->course, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $course = $DB->get_record('course', ['id' => $activity->course], '*', MUST_EXIST);
        $completion = new \completion_info($course);
        if ($completion->is_enabled($cm) && (int)$cm->completion === COMPLETION_TRACKING_AUTOMATIC) {
            $completion->update_state($cm, $complete ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE, $userid);
        }
    }

    /**
     * Method merge_segments.
     *
     * @param array $segments Parameter segments.
     * @param float $duration Parameter duration.
     * @return array Return value.
     */
    public function merge_segments(array $segments, float $duration = 0): array {
        $clean = [];
        foreach ($segments as $segment) {
            if (!is_array($segment) || count($segment) < 2) {
                continue;
            }
            $start = max(0, (float)$segment[0]);
            $end = max($start, (float)$segment[1]);
            if ($duration > 0) {
                $start = min($start, $duration);
                $end = min($end, $duration);
            }
            if ($end > $start) {
                $clean[] = [$start, $end];
            }
        }
        usort($clean, fn($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($clean as $segment) {
            $last = count($merged) - 1;
            if ($last >= 0 && $segment[0] <= $merged[$last][1] + 0.5) {
                $merged[$last][1] = max($merged[$last][1], $segment[1]);
            } else {
                $merged[] = $segment;
            }
        }
        return $merged;
    }
}

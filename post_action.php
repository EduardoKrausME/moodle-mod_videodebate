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
 * Moderator post actions.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);
$postid = required_param('postid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
require_sesskey();

$cm = get_coursemodule_from_id('videodebate', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videodebate', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videodebate:moderate', $context);

$post = $DB->get_record('videodebate_posts', [
    'id' => $postid,
    'videodebateid' => $activity->id,
], '*', MUST_EXIST);

if ($action === 'hide' || $action === 'show') {
    $DB->set_field('videodebate_posts', 'hidden', $action === 'hide' ? 1 : 0, ['id' => $postid]);
} else if ($action === 'delete') {
    $transaction = $DB->start_delegated_transaction();
    if (empty($post->isreply)) {
        $DB->set_field_select(
            'videodebate_posts',
            'parentid',
            0,
            'videodebateid = :activityid AND parentid = :postid',
            ['activityid' => $activity->id, 'postid' => $postid]
        );
    }
    $DB->delete_records('videodebate_evidence', ['postid' => $postid]);
    $DB->delete_records('videodebate_posts', ['id' => $postid]);
    $transaction->allow_commit();

    $tracker = new mod_videodebate\tracking_manager();
    $tracker->update_completion($activity, (int)$post->userid, $tracker->is_complete($activity, (int)$post->userid));
} else {
    throw new moodle_exception('invalidaction');
}

redirect(new moodle_url('/mod/videodebate/view.php', ['id' => $cm->id]), get_string('moderationupdated', 'videodebate'));

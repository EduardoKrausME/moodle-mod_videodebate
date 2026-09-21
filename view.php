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
 * Main student debate page.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videodebate\debate_manager;
use mod_videodebate\form\post_form;
use mod_videodebate\player_config;

require('../../config.php');

$id = required_param('id', PARAM_INT);
$replyto = optional_param('replyto', 0, PARAM_INT);
$cm = get_coursemodule_from_id('videodebate', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videodebate', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videodebate:view', $context);

$PAGE->set_url('/mod/videodebate/view.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('mod-videodebate');

$completion = new completion_info($course);
if ($completion->is_enabled($cm)) {
    $completion->set_module_viewed($cm);
}
$event = mod_videodebate\event\course_module_viewed::create(['objectid' => $activity->id, 'context' => $context]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('videodebate', $activity);
$event->trigger();

$manager = new debate_manager();
$initial = $manager::get_initial_post($activity->id, $USER->id);
$positions = $manager::get_positions($activity);
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = $groupmode ? groups_get_activity_group($cm, true) : 0;
$groupid = $currentgroup ?: 0;

$canparticipate = has_capability('mod/videodebate:participate', $context);
$canreply = has_capability('mod/videodebate:reply', $context);
$canviewall = has_capability('mod/videodebate:viewall', $context);
$showothers = $canviewall || !$activity->blinduntilpost || $initial;

$parentid = 0;
if ($replyto && $canreply && $showothers) {
    $parent = $DB->get_record('videodebate_posts', ['id' => $replyto, 'videodebateid' => $activity->id], '*', MUST_EXIST);
    $sameuser = (int)$parent->userid === (int)$USER->id;
    $cangroupreply = (int)$parent->groupid === 0
        || groups_is_member((int)$parent->groupid, $USER->id)
        || has_capability('moodle/site:accessallgroups', $context);
    if (!$sameuser && $cangroupreply) {
        $parentid = $parent->id;
        $groupid = (int)$parent->groupid;
    }
}

$form = null;
if (($canparticipate && !$initial && !$parentid) || ($canreply && $parentid)) {
    $assignedkey = (int)$activity->assignmentmode === 1 ? $manager::assigned_position($activity, $USER->id, $groupid) : '';
    $form = new post_form(null, [
        'cmid' => $cm->id,
        'parentid' => $parentid,
        'automatic' => (int)$activity->assignmentmode === 1,
        'positions' => $positions,
        'positionkey' => $assignedkey,
        'positionlabel' => $positions[$assignedkey] ?? '',
    ]);
    if ($form->is_cancelled()) {
        redirect(new moodle_url('/mod/videodebate/view.php', ['id' => $cm->id]));
    } else if ($data = $form->get_data()) {
        require_sesskey();
        $evidence = $manager::parse_evidence_json((string)$data->evidencejson);
        $manager::save_post($activity, $USER->id, $groupid, (int)$data->parentid,
            (string)$data->positionkey, trim((string)$data->message), $evidence);
        (new mod_videodebate\tracking_manager())->update_completion($activity, $USER->id,
            (new mod_videodebate\tracking_manager())->is_complete($activity, $USER->id));
        redirect(new moodle_url('/mod/videodebate/view.php', ['id' => $cm->id]), get_string('postsaved', 'videodebate'));
    }
}

$progress = $DB->get_record('videodebate_progress', ['videodebateid' => $activity->id, 'userid' => $USER->id]);
if (!$progress) {
    $progress = (object)['lastposition' => 0, 'percent' => 0, 'watchedsegments' => '[]', 'duration' => 0, 'uniquewatched' => 0];
}

$params = ['a' => $activity->id];
$where = 'p.videodebateid = :a';
if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
    $where .= ' AND p.groupid = :g';
    $params['g'] = $groupid;
} else if ($groupmode == VISIBLEGROUPS && $currentgroup) {
    $where .= ' AND p.groupid = :g';
    $params['g'] = $groupid;
}
$posts = [];
if ($showothers) {
    $sql = "SELECT p.*, u.firstname, u.lastname, u.picture, u.imagealt, u.email
              FROM {videodebate_posts} p
              JOIN {user} u ON u.id = p.userid
             WHERE {$where}
          ORDER BY p.timecreated ASC";
    $rawposts = $DB->get_records_sql($sql, $params);
    $evidenceby = [];
    if ($rawposts) {
        $postids = array_keys($rawposts);
        [$insql, $inparams] = $DB->get_in_or_equal($postids, SQL_PARAMS_NAMED, 'ep');
        $evs = $DB->get_records_select('videodebate_evidence', "postid {$insql}", $inparams, 'starttime ASC');
        foreach ($evs as $ev) {
            $evidenceby[$ev->postid][] = $ev;
        }
    }
    $children = [];
    foreach ($rawposts as $post) {
        $postuser = (object)[
            'id' => (int)$post->userid,
            'firstname' => $post->firstname,
            'lastname' => $post->lastname,
            'picture' => $post->picture,
            'imagealt' => $post->imagealt,
            'email' => $post->email,
        ];
        $item = [
            'id' => $post->id,
            'userid' => $post->userid,
            'fullname' => fullname($postuser),
            'userpicture' => $OUTPUT->user_picture($postuser, ['size' => 40]),
            'message' => nl2br(s($post->message)),
            'position' => $positions[$post->positionkey] ?? '',
            'date' => userdate($post->timecreated),
            'evidence' => [],
            'canreply' => $canreply && (int)$post->userid !== (int)$USER->id,
            'replyurl' => (new moodle_url('/mod/videodebate/view.php', ['id' => $cm->id, 'replyto' => $post->id]))->out(false),
        ];
        foreach ($evidenceby[$post->id] ?? [] as $ev) {
            $item['evidence'][] = [
                'start' => (float)$ev->starttime,
                'end' => (float)$ev->endtime,
                'timecode' => $manager::format_timecode((float)$ev->starttime),
                'endtimecode' => (float)$ev->endtime > (float)$ev->starttime
                    ? $manager::format_timecode((float)$ev->endtime) : '',
                'hasend' => (float)$ev->endtime > (float)$ev->starttime + 0.5,
                'label' => format_string((string)$ev->label),
            ];
        }
        if ((int)$post->parentid === 0) {
            $posts[$post->id] = $item + ['replies' => []];
        } else {
            $children[$post->parentid][] = $item;
        }
    }
    foreach ($children as $pid => $items) {
        if (isset($posts[$pid])) {
            $posts[$pid]['replies'] = $items;
            $posts[$pid]['hasreplies'] = true;
        }
    }
}

$player = player_config::build($activity, $context);
$trackerconfig = [
    'cmid' => $cm->id,
    'source' => $player['source'],
    'lastposition' => (float)$progress->lastposition,
    'resumeplayback' => (bool)$activity->resumeplayback,
    'allowseek' => (bool)$activity->allowseek,
    'segments' => json_decode((string)$progress->watchedsegments, true) ?: [],
];

$templatedata = [
    'name' => format_string($activity->name),
    'intro' => format_module_intro('videodebate', $activity, $cm->id),
    'hasintro' => trim((string)$activity->intro) !== '',
    'question' => format_text($activity->question, FORMAT_PLAIN),
    'player' => $player,
    'configjson' => json_encode($trackerconfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
    'percent' => (int)round((float)$progress->percent),
    'requiredpercent' => (int)$activity->completionpercent,
    'initialposted' => (bool)$initial,
    'initialposition' => $initial ? ($positions[$initial->positionkey] ?? '') : '',
    'showothers' => (bool)$showothers,
    'lockedothers' => !$showothers,
    'posts' => array_values($posts),
    'hasposts' => (bool)$posts,
    'canviewreport' => has_capability('mod/videodebate:viewreport', $context),
    'reporturl' => (new moodle_url('/mod/videodebate/report.php', ['id' => $cm->id]))->out(false),
    'replying' => (bool)$parentid,
];

$PAGE->requires->strings_for_js([
    'trackingerror', 'seekblocked', 'evidenceadded', 'evidenceempty', 'remove', 'evidencetitle',
    'addcurrentmoment', 'startinterval', 'finishinterval', 'evidencedescription'
], 'videodebate');
$PAGE->requires->js_call_amd('mod_videodebate/tracker', 'init');
$PAGE->requires->js_call_amd('mod_videodebate/debate', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videodebate/view', $templatedata);
if ($form) {
    echo html_writer::start_div('videodebate-form-wrap mt-4', ['id' => 'videodebate-post-form']);
    $form->display();
    echo html_writer::end_div();
}
echo $OUTPUT->footer();

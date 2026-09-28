<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

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
$page = optional_param('page', 0, PARAM_INT);
$perpage = 20;

$cm = get_coursemodule_from_id('videodebate', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videodebate', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videodebate:view', $context);

$PAGE->set_url('/mod/videodebate/view.php', ['id' => $cm->id, 'page' => $page]);
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
$canmoderate = has_capability('mod/videodebate:moderate', $context);
$showothers = $canviewall || !$activity->blinduntilpost || $initial;

$parentid = 0;
if ($replyto && $canreply && $showothers) {
    $parent = $DB->get_record('videodebate_posts', [
        'id' => $replyto,
        'videodebateid' => $activity->id,
    ], '*', MUST_EXIST);
    if (!empty($parent->isreply) || (int)$parent->parentid !== 0) {
        throw new moodle_exception('nestedreplynotallowed', 'videodebate');
    }
    if (!empty($parent->hidden) && !$canmoderate) {
        throw new moodle_exception('replytargethidden', 'videodebate');
    }
    $sameuser = (int)$parent->userid === (int)$USER->id;
    $cangroupreply = (int)$parent->groupid === 0
        || groups_is_member((int)$parent->groupid, $USER->id)
        || has_capability('moodle/site:accessallgroups', $context);
    if (!$sameuser && $cangroupreply) {
        $parentid = (int)$parent->id;
        $groupid = (int)$parent->groupid;
    }
}

$form = null;
if (($canparticipate && !$initial && !$parentid) || ($canreply && $parentid)) {
    $assignedkey = (int)$activity->assignmentmode === 1
        ? $manager::assigned_position($activity, $USER->id, $groupid)
        : '';
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
        $manager::save_post(
            $activity,
            $USER->id,
            $groupid,
            $parentid,
            (string)$data->positionkey,
            trim((string)$data->message),
            $evidence
        );
        $tracker = new mod_videodebate\tracking_manager();
        $tracker->update_completion($activity, $USER->id, $tracker->is_complete($activity, $USER->id));
        redirect(new moodle_url('/mod/videodebate/view.php', ['id' => $cm->id]), get_string('postsaved', 'videodebate'));
    }
}

$progress = $DB->get_record('videodebate_progress', [
    'videodebateid' => $activity->id,
    'userid' => $USER->id,
]);
if (!$progress) {
    $progress = (object)[
        'lastposition' => 0,
        'percent' => 0,
        'watchedsegments' => '[]',
        'duration' => 0,
        'uniquewatched' => 0,
    ];
}

$params = ['activityid' => $activity->id];
$where = 'p.videodebateid = :activityid';
if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
    $where .= ' AND p.groupid = :groupid';
    $params['groupid'] = $groupid;
} else if ($groupmode == VISIBLEGROUPS && $currentgroup) {
    $where .= ' AND p.groupid = :groupid';
    $params['groupid'] = $groupid;
}
if (!$canmoderate) {
    $where .= ' AND p.hidden = 0';
}

$posts = [];
$orphans = [];
$rootcount = 0;
if ($showothers) {
    $rootcount = (int)$DB->count_records_sql(
        "SELECT COUNT(1) FROM {videodebate_posts} p WHERE {$where} AND p.isreply = 0",
        $params
    );
    $sql = "SELECT p.*, u.firstname, u.lastname, u.picture, u.imagealt, u.email
              FROM {videodebate_posts} p
              JOIN {user} u ON u.id = p.userid
             WHERE {$where} AND p.isreply = 0
          ORDER BY p.timecreated ASC";
    $roots = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

    $replies = [];
    if ($roots) {
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($roots), SQL_PARAMS_NAMED, 'parent');
        $replyparams = $params + $inparams;
        $replysql = "SELECT p.*, u.firstname, u.lastname, u.picture, u.imagealt, u.email
                       FROM {videodebate_posts} p
                       JOIN {user} u ON u.id = p.userid
                      WHERE {$where} AND p.isreply = 1 AND p.parentid {$insql}
                   ORDER BY p.timecreated ASC";
        $replies = $DB->get_records_sql($replysql, $replyparams);
    }

    $orphansql = "SELECT p.*, u.firstname, u.lastname, u.picture, u.imagealt, u.email
                    FROM {videodebate_posts} p
                    JOIN {user} u ON u.id = p.userid
                   WHERE {$where} AND p.isreply = 1 AND p.parentid = 0
                ORDER BY p.timecreated ASC";
    $orphanrecords = $DB->get_records_sql($orphansql, $params, 0, $perpage);

    $allposts = $roots + $replies + $orphanrecords;
    $evidenceby = [];
    if ($allposts) {
        [$evidencesql, $evidenceparams] = $DB->get_in_or_equal(array_keys($allposts), SQL_PARAMS_NAMED, 'evidencepost');
        $evidences = $DB->get_records_select(
            'videodebate_evidence',
            "postid {$evidencesql}",
            $evidenceparams,
            'starttime ASC'
        );
        foreach ($evidences as $evidence) {
            $evidenceby[$evidence->postid][] = $evidence;
        }
    }

    $builditem = static function($post, bool $isreply = false) use (
        $OUTPUT,
        $USER,
        $cm,
        $canreply,
        $canmoderate,
        $positions,
        $evidenceby,
        $manager
    ): array {
        $postuser = (object)[
            'id' => (int)$post->userid,
            'firstname' => $post->firstname,
            'lastname' => $post->lastname,
            'picture' => $post->picture,
            'imagealt' => $post->imagealt,
            'email' => $post->email,
        ];
        $item = [
            'id' => (int)$post->id,
            'userid' => (int)$post->userid,
            'fullname' => fullname($postuser),
            'userpicture' => $OUTPUT->user_picture($postuser, ['size' => 40]),
            'message' => nl2br(s($post->message)),
            'position' => $post->positionlabel ?: ($positions[$post->positionkey] ?? ''),
            'date' => userdate($post->timecreated),
            'evidence' => [],
            'hidden' => !empty($post->hidden),
            'canreply' => !$isreply && empty($post->hidden) && $canreply && (int)$post->userid !== (int)$USER->id,
            'replyurl' => (new moodle_url('/mod/videodebate/view.php', [
                'id' => $cm->id,
                'replyto' => $post->id,
            ]))->out(false),
            'canmoderate' => $canmoderate,
        ];
        if ($canmoderate) {
            $item['editurl'] = (new moodle_url('/mod/videodebate/editpost.php', [
                'id' => $cm->id,
                'postid' => $post->id,
            ]))->out(false);
            $action = !empty($post->hidden) ? 'show' : 'hide';
            $item['visibilityurl'] = (new moodle_url('/mod/videodebate/post_action.php', [
                'id' => $cm->id,
                'postid' => $post->id,
                'action' => $action,
                'sesskey' => sesskey(),
            ]))->out(false);
            $item['visibilitylabel'] = get_string($action . 'post', 'videodebate');
            $item['deleteurl'] = (new moodle_url('/mod/videodebate/post_action.php', [
                'id' => $cm->id,
                'postid' => $post->id,
                'action' => 'delete',
                'sesskey' => sesskey(),
            ]))->out(false);
        }
        foreach ($evidenceby[$post->id] ?? [] as $evidence) {
            $item['evidence'][] = [
                'start' => (float)$evidence->starttime,
                'end' => (float)$evidence->endtime,
                'timecode' => $manager::format_timecode((float)$evidence->starttime),
                'endtimecode' => (float)$evidence->endtime > (float)$evidence->starttime
                    ? $manager::format_timecode((float)$evidence->endtime)
                    : '',
                'hasend' => (float)$evidence->endtime > (float)$evidence->starttime + 0.5,
                'label' => format_string((string)$evidence->label),
            ];
        }
        return $item;
    };

    foreach ($roots as $root) {
        $posts[$root->id] = $builditem($root) + ['replies' => []];
    }
    foreach ($replies as $reply) {
        if (isset($posts[$reply->parentid])) {
            $posts[$reply->parentid]['replies'][] = $builditem($reply, true);
            $posts[$reply->parentid]['hasreplies'] = true;
        }
    }
    foreach ($orphanrecords as $orphan) {
        $item = $builditem($orphan, true);
        $item['orphaned'] = true;
        $orphans[] = $item;
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

$usergrade = $DB->get_record('videodebate_grades', [
    'videodebateid' => $activity->id,
    'userid' => $USER->id,
]);

$pagingbar = '';
if ($rootcount > $perpage) {
    $pagingbar = $OUTPUT->paging_bar(
        $rootcount,
        $page,
        $perpage,
        new moodle_url('/mod/videodebate/view.php', ['id' => $cm->id])
    );
}

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
    'initialposition' => $initial ? ($initial->positionlabel ?: ($positions[$initial->positionkey] ?? '')) : '',
    'showothers' => (bool)$showothers,
    'lockedothers' => !$showothers,
    'posts' => array_values($posts),
    'hasposts' => (bool)$posts,
    'orphans' => $orphans,
    'hasorphans' => (bool)$orphans,
    'pagingbar' => $pagingbar,
    'haspagingbar' => $pagingbar !== '',
    'canviewreport' => has_capability('mod/videodebate:viewreport', $context),
    'reporturl' => (new moodle_url('/mod/videodebate/report.php', ['id' => $cm->id]))->out(false),
    'replying' => (bool)$parentid,
    'hasgrade' => (bool)$usergrade,
    'usergrade' => $usergrade ? format_float((float)$usergrade->finalgrade, 2) : '',
    'maxgrade' => format_float((float)$activity->grade, 2),
    'hasfeedback' => $usergrade && trim((string)$usergrade->feedback) !== '',
    'gradefeedback' => $usergrade
        ? format_text((string)$usergrade->feedback, (int)$usergrade->feedbackformat)
        : '',
];

$PAGE->requires->strings_for_js([
    'trackingerror', 'seekblocked', 'evidenceadded', 'evidenceempty', 'remove', 'evidencetitle',
    'addcurrentmoment', 'startinterval', 'finishinterval', 'evidencedescription',
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

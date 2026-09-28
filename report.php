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
 * Teacher participation report.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videodebate', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videodebate', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videodebate:viewreport', $context);

$PAGE->set_url('/mod/videodebate/report.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('report', 'videodebate'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(get_string('report', 'videodebate'));

$event = mod_videodebate\event\report_viewed::create([
    'objectid' => $activity->id,
    'context' => $context,
    'other' => ['videodebateid' => (int)$activity->id],
]);
$event->add_record_snapshot('videodebate', $activity);
$event->trigger();

$positions = mod_videodebate\debate_manager::get_positions($activity);
$currentgroup = groups_get_activity_group($cm, true);
$users = get_enrolled_users(
    $context,
    'mod/videodebate:participate',
    0,
    "u.id,u.firstname,u.lastname,u.email,u.picture,u.imagealt,u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename",
    'u.lastname ASC, u.firstname ASC'
);
if ($currentgroup > 0) {
    $members = groups_get_members($currentgroup, 'u.id');
    $users = array_intersect_key($users, $members);
}

$initialbyuser = [];
$progressbyuser = [];
$gradebyuser = [];
$replycount = [];
$evidencebyuser = [];

if ($users) {
    $userids = array_map('intval', array_keys($users));
    [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'user');

    $initials = $DB->get_records_select(
        'videodebate_posts',
        "videodebateid = :activityid AND isreply = 0 AND userid {$usersql}",
        ['activityid' => $activity->id] + $userparams
    );
    foreach ($initials as $initial) {
        $initialbyuser[$initial->userid] = $initial;
    }

    $progresses = $DB->get_records_select(
        'videodebate_progress',
        "videodebateid = :activityid AND userid {$usersql}",
        ['activityid' => $activity->id] + $userparams
    );
    foreach ($progresses as $progress) {
        $progressbyuser[$progress->userid] = $progress;
    }

    $grades = $DB->get_records_select(
        'videodebate_grades',
        "videodebateid = :activityid AND userid {$usersql}",
        ['activityid' => $activity->id] + $userparams
    );
    foreach ($grades as $grade) {
        $gradebyuser[$grade->userid] = $grade;
    }

    $replies = $DB->get_records_sql(
        "SELECT userid AS id, userid, COUNT(1) AS replycount
           FROM {videodebate_posts}
          WHERE videodebateid = :activityid
            AND isreply = 1
            AND userid {$usersql}
       GROUP BY userid",
        ['activityid' => $activity->id] + $userparams
    );
    foreach ($replies as $row) {
        $replycount[$row->userid] = (int)$row->replycount;
    }

    $evidence = $DB->get_records_sql(
        "SELECT e.id, p.userid, e.starttime, e.endtime, e.label
           FROM {videodebate_evidence} e
           JOIN {videodebate_posts} p ON p.id = e.postid
          WHERE p.videodebateid = :activityid
            AND p.userid {$usersql}
       ORDER BY p.userid, e.starttime",
        ['activityid' => $activity->id] + $userparams
    );
    foreach ($evidence as $item) {
        $evidencebyuser[$item->userid][] = $item;
    }
}

$cangrade = has_capability('mod/videodebate:grade', $context);
$rows = [];
foreach ($users as $user) {
    $initial = $initialbyuser[$user->id] ?? null;
    $progress = $progressbyuser[$user->id] ?? null;
    $grade = $gradebyuser[$user->id] ?? null;
    $evidenceitems = [];
    foreach ($evidencebyuser[$user->id] ?? [] as $evidence) {
        $hasend = (float)$evidence->endtime > (float)$evidence->starttime + 0.5;
        $evidenceitems[] = [
            'timecode' => mod_videodebate\debate_manager::format_timecode((float)$evidence->starttime)
                . ($hasend ? '–' . mod_videodebate\debate_manager::format_timecode((float)$evidence->endtime) : ''),
            'label' => format_string((string)$evidence->label),
            'haslabel' => trim((string)$evidence->label) !== '',
        ];
    }
    $replies = $replycount[$user->id] ?? 0;
    $rows[] = [
        'userid' => $user->id,
        'fullname' => fullname($user),
        'userpicture' => $OUTPUT->user_picture($user, ['size' => 35]),
        'position' => $initial
            ? ($initial->positionlabel ?: ($positions[$initial->positionkey] ?? ''))
            : get_string('notpublished', 'videodebate'),
        'arguments' => $initial ? 1 : 0,
        'evidenceitems' => $evidenceitems,
        'hasevidence' => (bool)$evidenceitems,
        'replies' => $replies,
        'participation' => ($initial ? 1 : 0) + $replies,
        'percent' => $progress ? round((float)$progress->percent, 1) : 0,
        'lastaccess' => $progress ? userdate($progress->timemodified) : get_string('never'),
        'grade' => $grade ? format_float($grade->finalgrade, 2) : '—',
        'cangrade' => $cangrade,
        'gradeurl' => $cangrade
            ? (new moodle_url('/mod/videodebate/grade.php', ['id' => $cm->id, 'userid' => $user->id]))->out(false)
            : '',
    ];
}

$data = [
    'name' => format_string($activity->name),
    'groupselector' => groups_print_activity_menu($cm, $PAGE->url, true),
    'rows' => $rows,
    'hasrows' => (bool)$rows,
    'backurl' => (new moodle_url('/mod/videodebate/view.php', ['id' => $cm->id]))->out(false),
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videodebate/report', $data);
echo $OUTPUT->footer();

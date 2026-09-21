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

$positions = mod_videodebate\debate_manager::get_positions($activity);
$currentgroup = groups_get_activity_group($cm, true);
$users = get_enrolled_users($context, 'mod/videodebate:participate', 0,
    "u.id,u.firstname,u.lastname,u.email,u.picture,u.imagealt,u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename",
    'u.lastname ASC, u.firstname ASC');
if ($currentgroup > 0) {
    $members = groups_get_members($currentgroup, 'u.id');
    $users = array_intersect_key($users, $members);
}
$rows = [];
foreach ($users as $user) {
    $initial = mod_videodebate\debate_manager::get_initial_post($activity->id, $user->id);
    $progress = $DB->get_record('videodebate_progress', ['videodebateid' => $activity->id, 'userid' => $user->id]);
    $grade = $DB->get_record('videodebate_grades', ['videodebateid' => $activity->id, 'userid' => $user->id]);
    $evidences = $DB->get_records_sql(
        'SELECT e.* FROM {videodebate_evidence} e JOIN {videodebate_posts} p ON p.id = e.postid '
        . 'WHERE p.videodebateid = :activityid AND p.userid = :userid ORDER BY e.starttime ASC',
        ['activityid' => $activity->id, 'userid' => $user->id]
    );
    $evidenceitems = [];
    foreach ($evidences as $evidence) {
        $start = mod_videodebate\debate_manager::format_timecode((float)$evidence->starttime);
        $hasend = (float)$evidence->endtime > (float)$evidence->starttime + 0.5;
        $evidenceitems[] = [
            'timecode' => $start . ($hasend
                    ? '–' . mod_videodebate\debate_manager::format_timecode((float)$evidence->endtime) : ''),
            'label' => format_string((string)$evidence->label),
            'haslabel' => trim((string)$evidence->label) !== '',
        ];
    }
    $rows[] = [
        'userid' => $user->id,
        'fullname' => fullname($user),
        'userpicture' => $OUTPUT->user_picture($user, ['size' => 35]),
        'position' => $initial ? ($positions[$initial->positionkey] ?? '') : get_string('notpublished', 'videodebate'),
        'arguments' => $initial ? 1 : 0,
        'evidence' => count($evidenceitems),
        'evidenceitems' => $evidenceitems,
        'hasevidence' => (bool)$evidenceitems,
        'replies' => mod_videodebate\debate_manager::reply_count($activity->id, $user->id),
        'participation' => ($initial ? 1 : 0) + mod_videodebate\debate_manager::reply_count($activity->id, $user->id),
        'percent' => $progress ? round((float)$progress->percent, 1) : 0,
        'lastaccess' => $progress ? userdate($progress->timemodified) : get_string('never'),
        'grade' => $grade ? format_float($grade->finalgrade, 2) : '—',
        'gradeurl' => (new moodle_url('/mod/videodebate/grade.php', ['id' => $cm->id, 'userid' => $user->id]))->out(false),
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

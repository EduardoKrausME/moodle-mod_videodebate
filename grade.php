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
 * Teacher grading page.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videodebate\form\grade_form;

require('../../config.php');
$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$cm = get_coursemodule_from_id('videodebate', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videodebate', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videodebate:grade', $context);
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
if (!is_enrolled($context, $userid, '', true) || !has_capability('mod/videodebate:participate', $context, $userid)) {
    throw new moodle_exception('invaliduser', 'error');
}

$PAGE->set_url('/mod/videodebate/grade.php', ['id' => $cm->id, 'userid' => $userid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('gradeparticipant', 'videodebate', fullname($user)));
$PAGE->set_heading(format_string($course->fullname));

$existing = $DB->get_record('videodebate_grades', ['videodebateid' => $activity->id, 'userid' => $userid]);
$form = new grade_form(null, ['cmid' => $cm->id, 'userid' => $userid]);
if ($existing) {
    $form->set_data($existing);
}
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/videodebate/report.php', ['id' => $cm->id]));
} else if ($data = $form->get_data()) {
    require_sesskey();
    $score = (
            ((float)$data->argumentation * (int)$activity->weightargument) +
            ((float)$data->evidence * (int)$activity->weightevidence) +
            ((float)$data->participation * (int)$activity->weightparticipation) +
            ((float)$data->replies * (int)$activity->weightreplies)
        ) / 100;
    $final = $score * ((float)$activity->grade / 100);
    $record = (object)[
        'videodebateid' => $activity->id,
        'userid' => $userid,
        'graderid' => $USER->id,
        'argumentation' => $data->argumentation,
        'evidence' => $data->evidence,
        'participation' => $data->participation,
        'replies' => $data->replies,
        'finalgrade' => $final,
        'feedback' => trim((string)$data->feedback),
        'feedbackformat' => FORMAT_PLAIN,
        'timemodified' => time(),
    ];
    if ($existing) {
        $record->id = $existing->id;
        $DB->update_record('videodebate_grades', $record);
    } else {
        $DB->insert_record('videodebate_grades', $record);
    }
    videodebate_update_grades($activity, $userid, false);
    redirect(new moodle_url('/mod/videodebate/report.php', ['id' => $cm->id]), get_string('gradesaved', 'videodebate'));
}

$initial = mod_videodebate\debate_manager::get_initial_post($activity->id, $userid);
$summary = (object)[
    'fullname' => fullname($user),
    'argument' => $initial ? format_text($initial->message, FORMAT_PLAIN) : get_string('notpublished', 'videodebate'),
    'evidence' => mod_videodebate\debate_manager::evidence_count_for_user($activity->id, $userid),
    'replies' => mod_videodebate\debate_manager::reply_count($activity->id, $userid),
];
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('gradeparticipant', 'videodebate', $summary->fullname));
echo html_writer::div($summary->argument, 'alert alert-light border');
echo html_writer::tag('p',
    get_string('gradingsummary', 'videodebate',
        (object)['evidence' => $summary->evidence, 'replies' => $summary->replies]));
$form->display();
echo $OUTPUT->footer();

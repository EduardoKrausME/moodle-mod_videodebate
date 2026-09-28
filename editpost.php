<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Moderator post editor.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videodebate\form\moderate_post_form;

require('../../config.php');

$id = required_param('id', PARAM_INT);
$postid = required_param('postid', PARAM_INT);
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

$PAGE->set_url('/mod/videodebate/editpost.php', ['id' => $cm->id, 'postid' => $postid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('editpost', 'videodebate'));
$PAGE->set_heading(format_string($course->fullname));

$form = new moderate_post_form(null, ['cmid' => $cm->id, 'postid' => $postid]);
$form->set_data($post);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/videodebate/view.php', ['id' => $cm->id]));
} else if ($data = $form->get_data()) {
    require_sesskey();
    $post->message = trim((string)$data->message);
    $post->messageformat = FORMAT_PLAIN;
    $post->timemodified = time();
    $DB->update_record('videodebate_posts', $post);
    redirect(new moodle_url('/mod/videodebate/view.php', ['id' => $cm->id]), get_string('postupdated', 'videodebate'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editpost', 'videodebate'));
$form->display();
echo $OUTPUT->footer();

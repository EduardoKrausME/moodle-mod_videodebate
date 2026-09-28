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
 * Core callbacks for Video Debate.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videodebate\tracking_manager;

/**
 * videodebate_supports
 *
 * @param $feature
 * @return int|string|true|null
 */
function videodebate_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_ASSIGNMENT;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * videodebate_add_instance
 *
 * @param stdClass $data
 * @param mod_videodebate_mod_form|null $mform
 * @return int
 * @throws dml_exception
 */
function videodebate_add_instance(stdClass $data, ?mod_videodebate_mod_form $mform = null): int {
    global $DB;
    $now = time();
    $data->timecreated = $now;
    $data->timemodified = $now;
    $data->positions = mod_videodebate\debate_manager::normalise_positions($data->positions ?? '');
    $id = $DB->insert_record('videodebate', $data);
    $data->id = $id;
    videodebate_save_files($data);
    videodebate_grade_item_update($data);
    return $id;
}

/**
 * videodebate_update_instance
 *
 * @param stdClass $data
 * @param mod_videodebate_mod_form|null $mform
 * @return bool
 * @throws dml_exception
 */
function videodebate_update_instance(stdClass $data, ?mod_videodebate_mod_form $mform = null): bool {
    global $DB;
    $oldactivity = $DB->get_record('videodebate', ['id' => $data->instance], '*', MUST_EXIST);
    $resetmedia = !empty($data->resetvideodata);
    $gradeformulaaffected = (float)$oldactivity->grade !== (float)$data->grade
        || (int)$oldactivity->weightargument !== (int)$data->weightargument
        || (int)$oldactivity->weightevidence !== (int)$data->weightevidence
        || (int)$oldactivity->weightparticipation !== (int)$data->weightparticipation
        || (int)$oldactivity->weightreplies !== (int)$data->weightreplies;

    $data->id = $data->instance;
    $data->timemodified = time();
    $data->positions = mod_videodebate\debate_manager::normalise_positions($data->positions ?? '');
    $ok = $DB->update_record('videodebate', $data);
    videodebate_save_files($data);

    $activity = $DB->get_record('videodebate', ['id' => $data->id], '*', MUST_EXIST);
    if ($resetmedia) {
        videodebate_reset_media_data($activity);
    } else if ($gradeformulaaffected) {
        videodebate_recalculate_grades($activity);
    }
    videodebate_grade_item_update($activity);
    return $ok;
}

/**
 * videodebate_delete_instance
 *
 * @param int $id
 * @return bool
 * @throws coding_exception
 * @throws dml_exception
 * @throws dml_transaction_exception
 */
function videodebate_delete_instance(int $id): bool {
    global $DB;
    $activity = $DB->get_record('videodebate', ['id' => $id]);
    if (!$activity) {
        return false;
    }
    $transaction = $DB->start_delegated_transaction();
    $postids = $DB->get_fieldset_select('videodebate_posts', 'id', 'videodebateid = :id', ['id' => $id]);
    if ($postids) {
        [$insql, $params] = $DB->get_in_or_equal($postids, SQL_PARAMS_NAMED, 'post');
        $DB->delete_records_select('videodebate_evidence', "postid {$insql}", $params);
    }
    $DB->delete_records('videodebate_posts', ['videodebateid' => $id]);
    $DB->delete_records('videodebate_progress', ['videodebateid' => $id]);
    $DB->delete_records('videodebate_grades', ['videodebateid' => $id]);
    $DB->delete_records('videodebate', ['id' => $id]);
    $transaction->allow_commit();
    videodebate_grade_item_delete($activity);
    return true;
}

/**
 * videodebate_save_files
 *
 * @param stdClass $activity
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 */
function videodebate_save_files(stdClass $activity): void {
    global $DB;
    $cmid = !empty($activity->coursemodule) ? (int)$activity->coursemodule : 0;
    if (!$cmid) {
        $cm = get_coursemodule_from_instance('videodebate', $activity->id, $activity->course, false, IGNORE_MISSING);
        $cmid = $cm ? (int)$cm->id : 0;
    }
    if (!$cmid) {
        return;
    }
    $context = context_module::instance($cmid);
    if (($activity->videosource ?? '') === 'upload' && isset($activity->videofile)) {
        file_save_draft_area_files((int)$activity->videofile, $context->id, 'mod_videodebate', 'video', 0,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['video']]);
    } else if (($activity->videosource ?? '') !== 'upload') {
        get_file_storage()->delete_area_files($context->id, 'mod_videodebate', 'video', 0);
    }
    if (isset($activity->captionfile)) {
        file_save_draft_area_files((int)$activity->captionfile, $context->id, 'mod_videodebate', 'captions', 0, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.vtt'],
        ]);
    }
    $DB->set_field('videodebate', 'timemodified', time(), ['id' => $activity->id]);
}

/**
 * mod_videodebate_pluginfile
 *
 * @param $course
 * @param $cm
 * @param $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 * @throws coding_exception
 * @throws moodle_exception
 * @throws require_login_exception
 * @throws required_capability_exception
 */
function mod_videodebate_pluginfile($course, $cm, $context, string $filearea, array $args,
                                    bool $forcedownload, array $options = []): bool {
    if ($context->contextlevel !== CONTEXT_MODULE || !in_array($filearea, ['video', 'captions'], true)) {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/videodebate:view', $context);
    $filename = array_pop($args);
    $filepath = '/' . ($args ? implode('/', $args) . '/' : '');
    $file = get_file_storage()->get_file($context->id, 'mod_videodebate', $filearea, 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, false, $options);
}

/**
 * videodebate_get_file_areas
 *
 * @param $course
 * @param $cm
 * @param $context
 * @return array
 * @throws coding_exception
 */
function videodebate_get_file_areas($course, $cm, $context): array {
    return [
        'video' => get_string('videofile', 'videodebate'),
        'captions' => get_string('captionfile', 'videodebate'),
    ];
}

/**
 * videodebate_grade_item_update
 *
 * @param stdClass $activity
 * @param array|null $grades
 * @return int
 * @throws coding_exception
 */
function videodebate_grade_item_update(stdClass $activity, array|null $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $item = [
        'itemname' => clean_param($activity->name, PARAM_NOTAGS),
        'gradetype' => (float)$activity->grade > 0 ? GRADE_TYPE_VALUE : GRADE_TYPE_NONE,
        'grademin' => 0,
        'grademax' => max(0, (float)$activity->grade),
    ];
    return grade_update('mod/videodebate', $activity->course, 'mod', 'videodebate', $activity->id, 0, $grades, $item);
}

/**
 * videodebate_update_grades
 *
 * @param stdClass $activity
 * @param int $userid
 * @param bool $nullifnone
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 */
function videodebate_update_grades(stdClass $activity, int $userid = 0, bool $nullifnone = true): void {
    global $DB;
    $conditions = ['videodebateid' => $activity->id];
    if ($userid) {
        $conditions['userid'] = $userid;
    }
    $records = $DB->get_records('videodebate_grades', $conditions);
    $grades = [];
    foreach ($records as $record) {
        $grades[$record->userid] = (object)[
            'userid' => $record->userid,
            'rawgrade' => $record->finalgrade,
            'feedback' => $record->feedback,
            'feedbackformat' => $record->feedbackformat,
        ];
    }
    if (!$grades && $userid && $nullifnone) {
        $grades[$userid] = (object)['userid' => $userid, 'rawgrade' => null];
    }
    videodebate_grade_item_update($activity, $grades);
}

/**
 * videodebate_grade_item_delete
 *
 * @param stdClass $activity
 * @return int
 */
function videodebate_grade_item_delete(stdClass $activity): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update('mod/videodebate', $activity->course, 'mod', 'videodebate', $activity->id, 0, null, ['deleted' => 1]);
}

/**
 * videodebate_get_coursemodule_info
 *
 * @param stdClass $cm
 * @return cached_cm_info|null
 * @throws dml_exception
 */
function videodebate_get_coursemodule_info(stdClass $cm): ?cached_cm_info {
    global $DB;
    $activity = $DB->get_record('videodebate', ['id' => $cm->instance],
        'id,name,intro,introformat,completionpercent,completionpost,completionreplies');
    if (!$activity) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($cm->showdescription) {
        $info->content = format_module_intro('videodebate', $activity, $cm->id, false);
    }
    if ((int)$cm->completion === COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules'] = [
            'completionpercent' => (int)$activity->completionpercent,
            'completionpost' => (bool)$activity->completionpost,
            'completionreplies' => (int)$activity->completionreplies,
        ];
    }
    return $info;
}

/**
 * videodebate_get_completion_active_rule_descriptions
 *
 * @param cached_cm_info $cm
 * @return array
 * @throws coding_exception
 */
function videodebate_get_completion_active_rule_descriptions(cached_cm_info $cm): array {
    if ((int)$cm->completion !== COMPLETION_TRACKING_AUTOMATIC || empty($cm->customdata['customcompletionrules'])) {
        return [];
    }
    $rules = $cm->customdata['customcompletionrules'];
    $out = [get_string('completiondetail:percent', 'videodebate', $rules['completionpercent'])];
    if (!empty($rules['completionpost'])) {
        $out[] = get_string('completiondetail:post', 'videodebate');
    }
    if (!empty($rules['completionreplies'])) {
        $out[] = get_string('completiondetail:replies', 'videodebate', $rules['completionreplies']);
    }
    return $out;
}

/**
 * videodebate_get_completion_state
 *
 * @param $course
 * @param $cm
 * @param int $userid
 * @param bool $type
 * @return bool
 * @throws dml_exception
 */
function videodebate_get_completion_state($course, $cm, int $userid, bool $type): bool {
    global $DB;
    $activity = $DB->get_record('videodebate', ['id' => $cm->instance], '*', MUST_EXIST);
    return (new tracking_manager())->is_complete($activity, $userid);
}


/**
 * Recalculate stored final grades after the grading formula changes.
 *
 * @param stdClass $activity Activity record.
 * @return void
 */
function videodebate_recalculate_grades(stdClass $activity): void {
    global $DB;

    $records = $DB->get_records('videodebate_grades', ['videodebateid' => $activity->id]);
    foreach ($records as $record) {
        $score = (
            ((float)$record->argumentation * (int)$activity->weightargument)
            + ((float)$record->evidence * (int)$activity->weightevidence)
            + ((float)$record->participation * (int)$activity->weightparticipation)
            + ((float)$record->replies * (int)$activity->weightreplies)
        ) / 100;
        $record->finalgrade = $score * ((float)$activity->grade / 100);
        $record->timemodified = time();
        $DB->update_record('videodebate_grades', $record);
    }
    videodebate_update_grades($activity);
}

/**
 * Clear data which becomes invalid when the configured video changes.
 *
 * @param stdClass $activity Activity record.
 * @return void
 */
function videodebate_reset_media_data(stdClass $activity): void {
    global $DB, $CFG;

    $postids = $DB->get_fieldset_select(
        'videodebate_posts',
        'id',
        'videodebateid = :activityid',
        ['activityid' => $activity->id]
    );
    if ($postids) {
        [$insql, $params] = $DB->get_in_or_equal($postids, SQL_PARAMS_NAMED, 'post');
        $DB->delete_records_select('videodebate_evidence', "postid {$insql}", $params);
    }
    $DB->delete_records('videodebate_progress', ['videodebateid' => $activity->id]);
    $DB->delete_records('videodebate_grades', ['videodebateid' => $activity->id]);

    require_once($CFG->libdir . '/gradelib.php');
    grade_update(
        'mod/videodebate',
        $activity->course,
        'mod',
        'videodebate',
        $activity->id,
        0,
        null,
        ['reset' => true]
    );

    $cm = get_coursemodule_from_instance('videodebate', $activity->id, $activity->course, false, IGNORE_MISSING);
    if ($cm) {
        $course = get_course($activity->course);
        (new completion_info($course))->reset_all_state($cm);
    }
}

/**
 * Add Video Debate options to the course reset form.
 *
 * @param MoodleQuickForm $mform Reset form.
 * @return void
 */
function videodebate_reset_course_form_definition(&$mform): void {
    $mform->addElement('header', 'videodebateheader', get_string('modulenameplural', 'videodebate'));
    $mform->addElement('checkbox', 'reset_videodebate_posts', get_string('resetposts', 'videodebate'));
    $mform->addElement('checkbox', 'reset_videodebate_progress', get_string('resetprogress', 'videodebate'));
    $mform->addElement('checkbox', 'reset_videodebate_grades', get_string('resetgrades', 'videodebate'));
}

/**
 * Default values for course reset.
 *
 * @param stdClass $course Course record.
 * @return array
 */
function videodebate_reset_course_form_defaults($course): array {
    return [
        'reset_videodebate_posts' => 1,
        'reset_videodebate_progress' => 1,
        'reset_videodebate_grades' => 1,
    ];
}

/**
 * Reset participant data for Video Debate activities in a course.
 *
 * @param stdClass $data Course reset data.
 * @return array
 */
function videodebate_reset_userdata($data): array {
    global $DB, $CFG;

    $status = [];
    $activities = $DB->get_records('videodebate', ['course' => $data->courseid]);
    if (!$activities) {
        return $status;
    }

    foreach ($activities as $activity) {
        if (!empty($data->reset_videodebate_posts)) {
            $postids = $DB->get_fieldset_select(
                'videodebate_posts',
                'id',
                'videodebateid = :activityid',
                ['activityid' => $activity->id]
            );
            if ($postids) {
                [$insql, $params] = $DB->get_in_or_equal($postids, SQL_PARAMS_NAMED, 'post');
                $DB->delete_records_select('videodebate_evidence', "postid {$insql}", $params);
            }
            $DB->delete_records('videodebate_posts', ['videodebateid' => $activity->id]);
        }
        if (!empty($data->reset_videodebate_progress)) {
            $DB->delete_records('videodebate_progress', ['videodebateid' => $activity->id]);
        }
        if (!empty($data->reset_videodebate_grades)) {
            $DB->delete_records('videodebate_grades', ['videodebateid' => $activity->id]);
            require_once($CFG->libdir . '/gradelib.php');
            grade_update(
                'mod/videodebate',
                $activity->course,
                'mod',
                'videodebate',
                $activity->id,
                0,
                null,
                ['reset' => true]
            );
        }
        if (!empty($data->reset_videodebate_posts) || !empty($data->reset_videodebate_progress)) {
            $cm = get_coursemodule_from_instance('videodebate', $activity->id, $activity->course, false, IGNORE_MISSING);
            if ($cm) {
                $course = get_course($activity->course);
                (new completion_info($course))->reset_all_state($cm);
            }
        }
    }

    if (!empty($data->reset_videodebate_posts)) {
        $status[] = [
            'component' => get_string('modulenameplural', 'videodebate'),
            'item' => get_string('resetposts', 'videodebate'),
            'error' => false,
        ];
    }
    if (!empty($data->reset_videodebate_progress)) {
        $status[] = [
            'component' => get_string('modulenameplural', 'videodebate'),
            'item' => get_string('resetprogress', 'videodebate'),
            'error' => false,
        ];
    }
    if (!empty($data->reset_videodebate_grades)) {
        $status[] = [
            'component' => get_string('modulenameplural', 'videodebate'),
            'item' => get_string('resetgrades', 'videodebate'),
            'error' => false,
        ];
    }
    return $status;
}

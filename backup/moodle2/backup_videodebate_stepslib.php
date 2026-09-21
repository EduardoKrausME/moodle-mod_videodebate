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
 * Backup structure.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_videodebate_activity_structure_step extends backup_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return mixed Return value.
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');
        $activity = new backup_nested_element('videodebate', ['id'], [
            'course', 'name', 'intro', 'introformat', 'question', 'positions', 'assignmentmode', 'blinduntilpost',
            'videosource', 'videourl', 'resumeplayback', 'allowseek', 'completionpercent', 'completionpost',
            'completionreplies', 'minevidence', 'grade', 'weightargument', 'weightevidence', 'weightparticipation',
            'weightreplies', 'timecreated', 'timemodified'
        ]);
        $posts = new backup_nested_element('posts');
        $post = new backup_nested_element('post', ['id'], [
            'userid', 'groupid', 'parentid', 'positionkey', 'message', 'messageformat', 'timecreated', 'timemodified'
        ]);
        $evidences = new backup_nested_element('evidences');
        $evidence = new backup_nested_element('evidence', ['id'], ['starttime', 'endtime', 'label', 'timecreated']);
        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], [
            'userid', 'duration', 'lastposition', 'uniquewatched', 'totalwatchtime', 'percent', 'watchedsegments',
            'completed', 'timemodified'
        ]);
        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', ['id'], [
            'userid', 'graderid', 'argumentation', 'evidence', 'participation', 'replies', 'finalgrade', 'feedback',
            'feedbackformat', 'timemodified'
        ]);

        $activity->add_child($posts);
        $posts->add_child($post);
        $post->add_child($evidences);
        $evidences->add_child($evidence);
        $activity->add_child($progresses);
        $progresses->add_child($progress);
        $activity->add_child($grades);
        $grades->add_child($grade);

        $activity->set_source_table('videodebate', ['id' => backup::VAR_ACTIVITYID]);
        if ($userinfo) {
            $post->set_source_table('videodebate_posts', ['videodebateid' => backup::VAR_PARENTID]);
            $evidence->set_source_table('videodebate_evidence', ['postid' => backup::VAR_PARENTID]);
            $progress->set_source_table('videodebate_progress', ['videodebateid' => backup::VAR_PARENTID]);
            $grade->set_source_table('videodebate_grades', ['videodebateid' => backup::VAR_PARENTID]);
            $post->annotate_ids('user', 'userid');
            $post->annotate_ids('group', 'groupid');
            $progress->annotate_ids('user', 'userid');
            $grade->annotate_ids('user', 'userid');
            $grade->annotate_ids('user', 'graderid');
        }
        $activity->annotate_files('mod_videodebate', 'intro', null);
        $activity->annotate_files('mod_videodebate', 'video', 0);
        return $this->prepare_activity_structure($activity);
    }
}

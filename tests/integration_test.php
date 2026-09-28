<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace mod_videodebate;

use core_privacy\local\request\approved_contextlist;
use mod_videodebate\completion\custom_completion;
use mod_videodebate\external\update_progress;
use mod_videodebate\privacy\provider;

/**
 * Integration tests for Video Debate.
 *
 * @package mod_videodebate
 * @covers \mod_videodebate\debate_manager
 * @covers \mod_videodebate\tracking_manager
 * @covers \mod_videodebate\external\update_progress
 * @covers \mod_videodebate\completion\custom_completion
 * @covers \mod_videodebate\privacy\provider
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class integration_test extends \advanced_testcase {
    /**
     * Create a course, activity and three enrolled students.
     *
     * @param array $activitydata Activity overrides.
     * @return array
     */
    private function fixture(array $activitydata = []): array {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $users = [$generator->create_user(), $generator->create_user(), $generator->create_user()];
        foreach ($users as $user) {
            $generator->enrol_user($user->id, $course->id, 'student');
        }
        $activitydata['course'] = $course->id;
        $module = $generator->get_plugin_generator('mod_videodebate')->create_instance($activitydata);
        $activity = $DB->get_record('videodebate', ['id' => $module->id], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('videodebate', $activity->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        return [$course, $activity, $cm, $context, $users];
    }

    /**
     * Historical position labels survive later position edits and nested replies are rejected.
     */
    public function test_position_history_and_nested_reply_protection(): void {
        global $DB;

        [$course, $activity, $cm, $context, $users] = $this->fixture();
        [$user1, $user2, $user3] = $users;
        $positions = debate_manager::get_positions($activity);
        $positionkey = array_key_first($positions);

        $postid = debate_manager::save_post(
            $activity,
            $user1->id,
            0,
            0,
            $positionkey,
            'Initial argument',
            []
        );
        $post = $DB->get_record('videodebate_posts', ['id' => $postid], '*', MUST_EXIST);
        $this->assertSame('Agree', $post->positionlabel);

        $activity->positions = "Strongly agree\nDisagree";
        $DB->update_record('videodebate', $activity);
        $post = $DB->get_record('videodebate_posts', ['id' => $postid], '*', MUST_EXIST);
        $this->assertSame('Agree', $post->positionlabel);

        $replyid = debate_manager::save_post($activity, $user2->id, 0, $postid, '', 'Reply', []);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('nestedreplynotallowed', 'videodebate'));
        debate_manager::save_post($activity, $user3->id, 0, $replyid, '', 'Nested reply', []);
    }

    /**
     * Blind-before-viewing is enforced on the server, not only by the page UI.
     */
    public function test_blind_until_post_is_server_enforced(): void {
        [$course, $activity, $cm, $context, $users] = $this->fixture(['blinduntilpost' => 1]);
        [$user1, $user2] = $users;
        $positionkey = array_key_first(debate_manager::get_positions($activity));
        $postid = debate_manager::save_post($activity, $user1->id, 0, 0, $positionkey, 'Initial', []);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('publishbeforeview', 'videodebate'));
        debate_manager::save_post($activity, $user2->id, 0, $postid, '', 'Attempted reply', []);
    }

    /**
     * Group membership is validated again when saving a reply.
     */
    public function test_reply_group_is_server_enforced(): void {
        [$course, $activity, $cm, $context, $users] = $this->fixture();
        [$user1, $user2] = $users;
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $user1->id]);
        $positionkey = array_key_first(debate_manager::get_positions($activity));
        $postid = debate_manager::save_post($activity, $user1->id, $group->id, 0, $positionkey, 'Grouped', []);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('cannotreplygroup', 'videodebate'));
        debate_manager::save_post($activity, $user2->id, 0, $postid, '', 'Wrong group', []);
    }

    /**
     * Browser-supplied duration cannot shrink the completion denominator.
     */
    public function test_tracking_uses_authoritative_duration(): void {
        [$course, $activity, $cm, $context, $users] = $this->fixture(['durationseconds' => 100, 'allowseek' => 1]);
        $user = $users[0];
        $manager = new tracking_manager();
        $progress = $manager->update($activity, $user->id, 5, 5, 0, 5, 1);

        $this->assertEquals(100.0, (float)$progress->duration);
        $this->assertEqualsWithDelta(5.0, (float)$progress->percent, 0.01);
    }

    /**
     * The external endpoint also uses the authoritative duration.
     */
    public function test_external_progress_ignores_spoofed_duration(): void {
        [$course, $activity, $cm, $context, $users] = $this->fixture(['durationseconds' => 100, 'allowseek' => 1]);
        $user = $users[0];
        $this->setUser($user);

        $result = update_progress::execute($cm->id, 3, 3, 0, 3, 1);
        $this->assertEqualsWithDelta(3.0, (float)$result['percent'], 0.01);
    }

    /**
     * Custom completion reflects watched percentage and initial argument.
     */
    public function test_custom_completion_rules(): void {
        [$course, $activity, $cm, $context, $users] = $this->fixture([
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionpercent' => 5,
            'completionpost' => 1,
        ]);
        $user = $users[0];
        (new tracking_manager())->update($activity, $user->id, 5, 100, 0, 5, 1);
        $positionkey = array_key_first(debate_manager::get_positions($activity));
        debate_manager::save_post($activity, $user->id, 0, 0, $positionkey, 'Completed argument', []);

        $cminfo = get_fast_modinfo($course)->get_cm($cm->id);
        $completion = new custom_completion($cminfo, $user->id);
        $this->assertSame(COMPLETION_COMPLETE, $completion->get_state('completionpercent'));
        $this->assertSame(COMPLETION_COMPLETE, $completion->get_state('completionpost'));
    }

    /**
     * Gradebook receives both the calculated grade and teacher feedback.
     */
    public function test_gradebook_receives_feedback(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/mod/videodebate/lib.php');

        [$course, $activity, $cm, $context, $users] = $this->fixture();
        $user = $users[0];
        $DB->insert_record('videodebate_grades', (object)[
            'videodebateid' => $activity->id,
            'userid' => $user->id,
            'graderid' => 2,
            'argumentation' => 90,
            'evidence' => 80,
            'participation' => 70,
            'replies' => 60,
            'finalgrade' => 80,
            'feedback' => 'Strong argument.',
            'feedbackformat' => FORMAT_PLAIN,
            'timemodified' => time(),
        ]);
        videodebate_update_grades($activity, $user->id, false);

        $grades = grade_get_grades($course->id, 'mod', 'videodebate', $activity->id, $user->id);
        $grade = $grades->items[0]->grades[$user->id];
        $this->assertEqualsWithDelta(80.0, (float)$grade->grade, 0.01);
        $this->assertSame('Strong argument.', $grade->feedback);
    }

    /**
     * Resetting media keeps the argument but clears video-dependent data.
     */
    public function test_media_reset_clears_video_dependent_data(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/videodebate/lib.php');

        [$course, $activity, $cm, $context, $users] = $this->fixture();
        $user = $users[0];
        $positionkey = array_key_first(debate_manager::get_positions($activity));
        $postid = debate_manager::save_post($activity, $user->id, 0, 0, $positionkey, 'Keep this', []);
        $DB->insert_record('videodebate_evidence', (object)[
            'postid' => $postid,
            'starttime' => 1,
            'endtime' => 2,
            'label' => 'Old video',
            'timecreated' => time(),
        ]);
        (new tracking_manager())->update($activity, $user->id, 5, 100, 0, 5, 1);
        $DB->insert_record('videodebate_grades', (object)[
            'videodebateid' => $activity->id,
            'userid' => $user->id,
            'graderid' => 2,
            'argumentation' => 100,
            'evidence' => 100,
            'participation' => 100,
            'replies' => 100,
            'finalgrade' => 100,
            'feedback' => '',
            'feedbackformat' => FORMAT_PLAIN,
            'timemodified' => time(),
        ]);

        videodebate_reset_media_data($activity);
        $this->assertTrue($DB->record_exists('videodebate_posts', ['id' => $postid]));
        $this->assertFalse($DB->record_exists('videodebate_evidence', ['postid' => $postid]));
        $this->assertFalse($DB->record_exists('videodebate_progress', ['videodebateid' => $activity->id]));
        $this->assertFalse($DB->record_exists('videodebate_grades', ['videodebateid' => $activity->id]));
    }

    /**
     * Privacy deletion detaches other users' replies rather than orphaning or deleting them.
     */
    public function test_privacy_deletion_preserves_other_users_replies(): void {
        global $DB;

        [$course, $activity, $cm, $context, $users] = $this->fixture();
        [$user1, $user2] = $users;
        $positionkey = array_key_first(debate_manager::get_positions($activity));
        $postid = debate_manager::save_post($activity, $user1->id, 0, 0, $positionkey, 'Remove me', []);
        $replyid = debate_manager::save_post($activity, $user2->id, 0, $postid, '', 'Keep me', []);

        provider::delete_data_for_user(new approved_contextlist(
            $user1,
            'mod_videodebate',
            [$context->id]
        ));

        $this->assertFalse($DB->record_exists('videodebate_posts', ['id' => $postid]));
        $reply = $DB->get_record('videodebate_posts', ['id' => $replyid], '*', MUST_EXIST);
        $this->assertSame(0, (int)$reply->parentid);
        $this->assertSame(1, (int)$reply->isreply);
    }

    /**
     * Activity backup and restore preserves the new video configuration fields.
     */
    public function test_backup_restore_preserves_video_configuration(): void {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $module = $generator->get_plugin_generator('mod_videodebate')->create_instance([
            'course' => $course1->id,
            'name' => 'Backup debate',
            'durationseconds' => 321,
            'transcript' => 'Accessible transcript',
            'captionlang' => 'pt-BR',
        ]);

        $bc = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $module->cmid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        $rc = new \restore_controller(
            $backupid,
            $course2->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id,
            \backup::TARGET_CURRENT_ADDING
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        $restored = $DB->get_record('videodebate', ['course' => $course2->id], '*', MUST_EXIST);
        $this->assertEqualsWithDelta(321.0, (float)$restored->durationseconds, 0.01);
        $this->assertSame('Accessible transcript', $restored->transcript);
        $this->assertSame('pt-BR', $restored->captionlang);
    }
}

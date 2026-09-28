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

namespace mod_videodebate\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe stored personal data.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videodebate_posts', [
            'userid' => 'privacy:metadata:posts:userid',
            'groupid' => 'privacy:metadata:posts:groupid',
            'parentid' => 'privacy:metadata:posts:parentid',
            'positionkey' => 'privacy:metadata:posts:position',
            'positionlabel' => 'privacy:metadata:posts:positionlabel',
            'message' => 'privacy:metadata:posts:message',
            'hidden' => 'privacy:metadata:posts:hidden',
            'timecreated' => 'privacy:metadata:posts:timecreated',
            'timemodified' => 'privacy:metadata:posts:timemodified',
        ], 'privacy:metadata:posts');
        $collection->add_database_table('videodebate_evidence', [
            'starttime' => 'privacy:metadata:evidence:starttime',
            'endtime' => 'privacy:metadata:evidence:endtime',
            'label' => 'privacy:metadata:evidence:label',
            'timecreated' => 'privacy:metadata:evidence:timecreated',
        ], 'privacy:metadata:evidence');
        $collection->add_database_table('videodebate_progress', [
            'userid' => 'privacy:metadata:progress:userid',
            'duration' => 'privacy:metadata:progress:duration',
            'lastposition' => 'privacy:metadata:progress:lastposition',
            'uniquewatched' => 'privacy:metadata:progress:uniquewatched',
            'totalwatchtime' => 'privacy:metadata:progress:totalwatchtime',
            'watchedsegments' => 'privacy:metadata:progress:segments',
            'percent' => 'privacy:metadata:progress:percent',
            'completed' => 'privacy:metadata:progress:completed',
            'timemodified' => 'privacy:metadata:progress:timemodified',
        ], 'privacy:metadata:progress');
        $collection->add_database_table('videodebate_grades', [
            'userid' => 'privacy:metadata:grades:userid',
            'graderid' => 'privacy:metadata:grades:graderid',
            'argumentation' => 'privacy:metadata:grades:argumentation',
            'evidence' => 'privacy:metadata:grades:evidence',
            'participation' => 'privacy:metadata:grades:participation',
            'replies' => 'privacy:metadata:grades:replies',
            'feedback' => 'privacy:metadata:grades:feedback',
            'finalgrade' => 'privacy:metadata:grades:finalgrade',
            'timemodified' => 'privacy:metadata:grades:timemodified',
        ], 'privacy:metadata:grades');
        return $collection;
    }

    /**
     * Get module contexts containing data for a user.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $list = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videodebate} vd ON vd.id = cm.instance
             LEFT JOIN {videodebate_posts} p ON p.videodebateid = vd.id AND p.userid = :u1
             LEFT JOIN {videodebate_progress} pr ON pr.videodebateid = vd.id AND pr.userid = :u2
             LEFT JOIN {videodebate_grades} g ON g.videodebateid = vd.id AND (g.userid = :u3 OR g.graderid = :u4)
                 WHERE p.id IS NOT NULL OR pr.id IS NOT NULL OR g.id IS NOT NULL";
        $list->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'videodebate',
            'u1' => $userid,
            'u2' => $userid,
            'u3' => $userid,
            'u4' => $userid,
        ]);
        return $list;
    }

    /**
     * Add users with data in a module context.
     *
     * @param userlist $userlist User list.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videodebate', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $users = $DB->get_fieldset_select('videodebate_posts', 'userid', 'videodebateid = :id', ['id' => $cm->instance]);
        $users = array_merge($users, $DB->get_fieldset_select(
            'videodebate_progress',
            'userid',
            'videodebateid = :id',
            ['id' => $cm->instance]
        ));
        $users = array_merge($users, $DB->get_fieldset_select(
            'videodebate_grades',
            'userid',
            'videodebateid = :id',
            ['id' => $cm->instance]
        ));
        $users = array_merge($users, $DB->get_fieldset_select(
            'videodebate_grades',
            'graderid',
            'videodebateid = :id',
            ['id' => $cm->instance]
        ));
        foreach (array_unique(array_map('intval', $users)) as $userid) {
            if ($userid > 0) {
                $userlist->add_user($userid);
            }
        }
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videodebate', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $activity = $DB->get_record('videodebate', ['id' => $cm->instance], '*', MUST_EXIST);
            $posts = $DB->get_records('videodebate_posts', [
                'videodebateid' => $activity->id,
                'userid' => $userid,
            ]);
            $evidence = [];
            if ($posts) {
                [$insql, $params] = $DB->get_in_or_equal(array_keys($posts), SQL_PARAMS_NAMED, 'post');
                $evidence = $DB->get_records_select('videodebate_evidence', "postid {$insql}", $params);
            }
            $progress = $DB->get_record('videodebate_progress', [
                'videodebateid' => $activity->id,
                'userid' => $userid,
            ]);
            $grade = $DB->get_record('videodebate_grades', [
                'videodebateid' => $activity->id,
                'userid' => $userid,
            ]);
            $graded = $DB->get_records('videodebate_grades', [
                'videodebateid' => $activity->id,
                'graderid' => $userid,
            ]);
            $data = (object)[
                'posts' => array_values($posts),
                'evidence' => array_values($evidence),
                'progress' => $progress,
                'grade' => $grade,
                'graded' => array_values($graded),
            ];
            writer::with_context($context)->export_data([get_string('pluginname', 'videodebate')], $data);
        }
    }

    /**
     * Delete all user data in a context.
     *
     * @param \context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videodebate', $context->instanceid, 0, false, IGNORE_MISSING);
        if ($cm) {
            self::delete_activity_user_data($cm->instance, 0);
        }
    }

    /**
     * Delete data for one user.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videodebate', $context->instanceid, 0, false, IGNORE_MISSING);
            if ($cm) {
                self::delete_activity_user_data($cm->instance, $userid);
            }
        }
    }

    /**
     * Delete data for a list of users.
     *
     * @param approved_userlist $userlist Approved users.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videodebate', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        foreach ($userlist->get_userids() as $userid) {
            self::delete_activity_user_data($cm->instance, (int)$userid);
        }
    }

    /**
     * Delete activity data belonging to one user, or all users when userid is zero.
     *
     * Replies written by other users are preserved when their parent is removed. They are
     * detached from the deleted parent but remain marked as replies.
     *
     * @param int $activityid Activity id.
     * @param int $userid User id, or zero for all users.
     * @return void
     */
    private static function delete_activity_user_data(int $activityid, int $userid): void {
        global $DB;

        $params = ['activityid' => $activityid];
        $userwhere = '';
        if ($userid) {
            $userwhere = ' AND userid = :userid';
            $params['userid'] = $userid;
        }

        $postids = $DB->get_fieldset_select(
            'videodebate_posts',
            'id',
            'videodebateid = :activityid' . $userwhere,
            $params
        );
        if ($postids) {
            [$insql, $inparams] = $DB->get_in_or_equal($postids, SQL_PARAMS_NAMED, 'post');
            if ($userid) {
                $detachparams = $inparams + ['activityid' => $activityid, 'userid' => $userid];
                $DB->set_field_select(
                    'videodebate_posts',
                    'parentid',
                    0,
                    "videodebateid = :activityid AND userid <> :userid AND parentid {$insql}",
                    $detachparams
                );
            }
            $DB->delete_records_select('videodebate_evidence', "postid {$insql}", $inparams);
            $DB->delete_records_select('videodebate_posts', "id {$insql}", $inparams);
        }

        $DB->delete_records_select('videodebate_progress', 'videodebateid = :activityid' . $userwhere, $params);
        $DB->delete_records_select('videodebate_grades', 'videodebateid = :activityid' . $userwhere, $params);
        if ($userid) {
            $DB->set_field_select(
                'videodebate_grades',
                'graderid',
                0,
                'videodebateid = :activityid AND graderid = :graderid',
                ['activityid' => $activityid, 'graderid' => $userid]
            );
        }
    }
}

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
     * Method get_metadata.
     *
     * @param collection $collection Parameter collection.
     * @return collection Return value.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videodebate_posts', [
            'userid' => 'privacy:metadata:posts:userid',
            'message' => 'privacy:metadata:posts:message',
            'positionkey' => 'privacy:metadata:posts:position',
            'timecreated' => 'privacy:metadata:posts:timecreated',
        ], 'privacy:metadata:posts');
        $collection->add_database_table('videodebate_evidence', [
            'starttime' => 'privacy:metadata:evidence:starttime',
            'endtime' => 'privacy:metadata:evidence:endtime',
            'label' => 'privacy:metadata:evidence:label',
        ], 'privacy:metadata:evidence');
        $collection->add_database_table('videodebate_progress', [
            'userid' => 'privacy:metadata:progress:userid',
            'lastposition' => 'privacy:metadata:progress:lastposition',
            'watchedsegments' => 'privacy:metadata:progress:segments',
            'percent' => 'privacy:metadata:progress:percent',
        ], 'privacy:metadata:progress');
        $collection->add_database_table('videodebate_grades', [
            'userid' => 'privacy:metadata:grades:userid',
            'graderid' => 'privacy:metadata:grades:graderid',
            'feedback' => 'privacy:metadata:grades:feedback',
            'finalgrade' => 'privacy:metadata:grades:finalgrade',
        ], 'privacy:metadata:grades');
        return $collection;
    }

    /**
     * Method get_contexts_for_userid.
     *
     * @param int $userid Parameter userid.
     * @return contextlist Return value.
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
        $list->add_from_sql($sql, ['contextlevel' => CONTEXT_MODULE, 'modname' => 'videodebate',
            'u1' => $userid, 'u2' => $userid, 'u3' => $userid, 'u4' => $userid]);
        return $list;
    }

    /**
     * Method get_users_in_context.
     *
     * @param userlist $userlist Parameter userlist.
     * @return void Return value.
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
        $users = $DB->get_fieldset_select('videodebate_posts', 'userid', 'videodebateid = :id',
            ['id' => $cm->instance]);
        $users = array_merge($users,
            $DB->get_fieldset_select('videodebate_progress', 'userid', 'videodebateid = :id',
                ['id' => $cm->instance]));
        $users = array_merge($users,
            $DB->get_fieldset_select('videodebate_grades', 'userid', 'videodebateid = :id',
                ['id' => $cm->instance]));
        $users = array_merge($users,
            $DB->get_fieldset_select('videodebate_grades', 'graderid', 'videodebateid = :id',
                ['id' => $cm->instance]));
        foreach (array_unique(array_map('intval', $users)) as $userid) {
            if ($userid > 0) {
                $userlist->add_user($userid);
            }
        }
    }

    /**
     * Method export_user_data.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
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
            $posts = $DB->get_records('videodebate_posts', ['videodebateid' => $activity->id, 'userid' => $userid]);
            $progress = $DB->get_record('videodebate_progress', ['videodebateid' => $activity->id, 'userid' => $userid]);
            $grade = $DB->get_record('videodebate_grades', ['videodebateid' => $activity->id, 'userid' => $userid]);
            $graded = $DB->get_records('videodebate_grades', ['videodebateid' => $activity->id, 'graderid' => $userid]);
            $data = (object)['posts' => array_values($posts), 'progress' => $progress, 'grade' => $grade,
                'graded' => array_values($graded)];
            writer::with_context($context)->export_data([get_string('pluginname', 'videodebate')], $data);
        }
    }

    /**
     * Method delete_data_for_all_users_in_context.
     *
     * @param \context $context Parameter context.
     * @return void Return value.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videodebate', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        self::delete_activity_user_data($cm->instance, 0);
    }

    /**
     * Method delete_data_for_user.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
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
     * Method delete_data_for_users.
     *
     * @param approved_userlist $userlist Parameter userlist.
     * @return void Return value.
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
     * Method delete_activity_user_data.
     *
     * @param int $activityid Parameter activityid.
     * @param int $userid Parameter userid.
     * @return void Return value.
     */
    private static function delete_activity_user_data(int $activityid, int $userid): void {
        global $DB;
        $params = ['a' => $activityid];
        $userwhere = '';
        if ($userid) {
            $userwhere = ' AND userid = :u';
            $params['u'] = $userid;
        }
        $postids = $DB->get_fieldset_select('videodebate_posts', 'id', 'videodebateid = :a' . $userwhere, $params);
        if ($postids) {
            [$insql, $inparams] = $DB->get_in_or_equal($postids, SQL_PARAMS_NAMED, 'p');
            $DB->delete_records_select('videodebate_evidence', "postid {$insql}", $inparams);
            $DB->delete_records_select('videodebate_posts', "id {$insql}", $inparams);
        }
        $DB->delete_records_select('videodebate_progress', 'videodebateid = :a' . $userwhere, $params);
        $DB->delete_records_select('videodebate_grades', 'videodebateid = :a' . $userwhere, $params);
        if ($userid) {
            $DB->set_field_select('videodebate_grades', 'graderid', 0,
                'videodebateid = :a AND graderid = :graderid', ['a' => $activityid, 'graderid' => $userid]);
        }
    }
}

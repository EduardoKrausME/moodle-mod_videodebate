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
 * Restore structure.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_videodebate_activity_structure_step extends restore_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return array Return value.
     */
    protected function define_structure(): array {
        $paths = [new restore_path_element('videodebate', '/activity/videodebate')];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('videodebate_post', '/activity/videodebate/posts/post');
            $paths[] = new restore_path_element('videodebate_evidence', '/activity/videodebate/posts/post/evidences/evidence');
            $paths[] = new restore_path_element('videodebate_progress', '/activity/videodebate/progresses/progress');
            $paths[] = new restore_path_element('videodebate_grade', '/activity/videodebate/grades/grade');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Method process_videodebate.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videodebate($data): void {
        global $DB;
        $data = (object)$data;
        $data->course = $this->get_courseid();
        $newid = $DB->insert_record('videodebate', $data);
        $this->apply_activity_instance($newid);
    }

    /**
     * Method process_videodebate_post.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videodebate_post($data): void {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->videodebateid = $this->get_new_parentid('videodebate');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        $data->groupid = $data->groupid ? $this->get_mappingid('group', $data->groupid, 0) : 0;
        if ($data->parentid) {
            $data->parentid = $this->get_mappingid('videodebate_post', $data->parentid, 0);
        }
        $newid = $DB->insert_record('videodebate_posts', $data);
        $this->set_mapping('videodebate_post', $oldid, $newid, true);
    }

    /**
     * Method process_videodebate_evidence.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videodebate_evidence($data): void {
        global $DB;
        $data = (object)$data;
        $data->postid = $this->get_new_parentid('videodebate_post');
        $DB->insert_record('videodebate_evidence', $data);
    }

    /**
     * Method process_videodebate_progress.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videodebate_progress($data): void {
        global $DB;
        $data = (object)$data;
        $data->videodebateid = $this->get_new_parentid('videodebate');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if ($data->userid) {
            $DB->insert_record('videodebate_progress', $data);
        }
    }

    /**
     * Method process_videodebate_grade.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videodebate_grade($data): void {
        global $DB;
        $data = (object)$data;
        $data->videodebateid = $this->get_new_parentid('videodebate');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        $data->graderid = $this->get_mappingid('user', $data->graderid, 0);
        if ($data->userid) {
            $DB->insert_record('videodebate_grades', $data);
        }
    }

    /**
     * Method after_execute.
     *
     * @return void Return value.
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_videodebate', 'intro', null);
        $this->add_related_files('mod_videodebate', 'video', 0);
    }
}

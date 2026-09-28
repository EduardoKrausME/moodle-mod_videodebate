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
 * Upgrade steps.
 *
 * @package   mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade mod_videodebate.
 *
 * @param int $oldversion Previous plugin version.
 * @return bool
 */
function xmldb_videodebate_upgrade(int $oldversion): bool {
    global $DB;

    if ($oldversion < 2026092800) {
        $dbman = $DB->get_manager();

        $table = new xmldb_table('videodebate');
        $fields = [
            new xmldb_field('durationseconds', XMLDB_TYPE_NUMBER, '12, 3', null, XMLDB_NOTNULL, null, '0', 'videourl'),
            new xmldb_field('transcript', XMLDB_TYPE_TEXT, null, null, null, null, null, 'durationseconds'),
            new xmldb_field('captionlang', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'en', 'transcript'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $posttable = new xmldb_table('videodebate_posts');
        $postfields = [
            new xmldb_field('positionlabel', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'positionkey'),
            new xmldb_field('isreply', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'positionlabel'),
            new xmldb_field('hidden', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'isreply'),
        ];
        foreach ($postfields as $field) {
            if (!$dbman->field_exists($posttable, $field)) {
                $dbman->add_field($posttable, $field);
            }
        }

        $replyindex = new xmldb_index('activity_reply_ix', XMLDB_INDEX_NOTUNIQUE, ['videodebateid', 'isreply']);
        if (!$dbman->index_exists($posttable, $replyindex)) {
            $dbman->add_index($posttable, $replyindex);
        }

        $DB->execute('UPDATE {videodebate_posts} SET isreply = 1 WHERE parentid <> 0');

        $activities = $DB->get_records('videodebate', null, '', 'id,positions');
        foreach ($activities as $activity) {
            $labels = [];
            $lines = preg_split('/\\R/u', trim((string)$activity->positions)) ?: [];
            foreach ($lines as $line) {
                $label = trim($line);
                if ($label !== '') {
                    $labels[substr(sha1(core_text::strtolower($label)), 0, 20)] = $label;
                }
            }
            foreach ($labels as $key => $label) {
                $DB->set_field('videodebate_posts', 'positionlabel', $label, [
                    'videodebateid' => $activity->id,
                    'positionkey' => $key,
                    'isreply' => 0,
                ]);
            }
        }

        $durations = $DB->get_records_sql(
            'SELECT videodebateid AS id, MAX(duration) AS duration
               FROM {videodebate_progress}
           GROUP BY videodebateid'
        );
        foreach ($durations as $row) {
            if ((float)$row->duration > 0) {
                $DB->set_field('videodebate', 'durationseconds', (float)$row->duration, ['id' => $row->id]);
            }
        }

        upgrade_mod_savepoint(true, 2026092800, 'videodebate');
    }

    if ($oldversion < 2026092801) {
        // Refresh capabilities and message providers added with the production hardening release.
        upgrade_mod_savepoint(true, 2026092801, 'videodebate');
    }

    return true;
}

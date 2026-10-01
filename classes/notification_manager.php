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

namespace mod_videodebate;

use core\message\message;
use html_writer;
use moodle_url;
use stdClass;

/**
 * Sends Video Debate notifications through Moodle messaging.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_manager {
    /**
     * Notify a participant that somebody replied to their argument.
     *
     * @param stdClass $activity Activity.
     * @param stdClass $cm Course module.
     * @param int $postid Reply id.
     * @param stdClass $parent Parent post.
     * @param int $authorid Reply author.
     * @return void
     */
    public static function notify_reply(stdClass $activity, stdClass $cm, int $postid,
                                        stdClass $parent, int $authorid): void {
        global $DB;

        if ((int)$parent->userid === $authorid) {
            return;
        }
        $author = $DB->get_record('user', ['id' => $authorid], '*', MUST_EXIST);
        $recipient = $DB->get_record('user', ['id' => $parent->userid, 'deleted' => 0], '*', IGNORE_MISSING);
        if (!$recipient) {
            return;
        }

        $url = new moodle_url('/mod/videodebate/view.php', ['id' => $cm->id], 'post-' . $postid);
        $a = (object)[
            'author' => fullname($author),
            'activity' => format_string($activity->name),
        ];
        self::send(
            'replynotification',
            $author,
            $recipient,
            get_string('messagesubject:reply', 'videodebate', $activity->name),
            get_string('messagebody:reply', 'videodebate', $a),
            $url
        );
    }

    /**
     * Notify a participant that their debate was graded.
     *
     * @param stdClass $activity Activity.
     * @param stdClass $cm Course module.
     * @param stdClass $grader Grader.
     * @param stdClass $recipient Student.
     * @param float $grade Final grade.
     * @return void
     */
    public static function notify_grade(stdClass $activity, stdClass $cm, stdClass $grader,
                                        stdClass $recipient, float $grade): void {
        $url = new moodle_url('/mod/videodebate/view.php', ['id' => $cm->id]);
        $a = (object)[
            'activity' => format_string($activity->name),
            'grade' => format_float($grade, 2),
            'maximum' => format_float((float)$activity->grade, 2),
        ];
        self::send(
            'gradenotification',
            $grader,
            $recipient,
            get_string('messagesubject:grade', 'videodebate', $activity->name),
            get_string('messagebody:grade', 'videodebate', $a),
            $url
        );
    }

    /**
     * Send a Moodle notification.
     *
     * @param string $name Message provider name.
     * @param stdClass $from Sender.
     * @param stdClass $to Recipient.
     * @param string $subject Subject.
     * @param string $body Body.
     * @param moodle_url $url Context URL.
     * @return void
     */
    private static function send(string $name, stdClass $from, stdClass $to, string $subject,
                                 string $body, moodle_url $url): void {
        $message = new message();
        $message->component = 'mod_videodebate';
        $message->name = $name;
        $message->userfrom = $from;
        $message->userto = $to;
        $message->subject = $subject;
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = html_writer::tag('p', s($body));
        $message->smallmessage = $body;
        $message->notification = 1;
        $message->contexturl = $url->out(false);
        $message->contexturlname = get_string('pluginname', 'videodebate');
        message_send($message);
    }
}

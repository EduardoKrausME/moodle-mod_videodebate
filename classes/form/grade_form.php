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

namespace mod_videodebate\form;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->libdir}/formslib.php");

/**
 * Teacher grading form.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_form extends \moodleform {
    /**
     * Method definition.
     *
     * @return void Return value.
     */
    public function definition(): void {
        $mform = $this->_form;
        $custom = $this->_customdata;
        $mform->addElement('hidden', 'id', $custom['cmid']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $custom['userid']);
        $mform->setType('userid', PARAM_INT);
        foreach (['argumentation', 'evidence', 'participation', 'replies'] as $criterion) {
            $mform->addElement('text', $criterion, get_string('criterion:' . $criterion, 'videodebate'), ['size' => 6]);
            $mform->setType($criterion, PARAM_FLOAT);
            $mform->addRule($criterion, null, 'required', null, 'client');
        }
        $mform->addElement('textarea', 'feedback', get_string('feedback', 'videodebate'), ['rows' => 6, 'cols' => 70]);
        $mform->setType('feedback', PARAM_TEXT);
        $this->add_action_buttons(true, get_string('savegrade', 'videodebate'));
    }

    /**
     * Method validation.
     *
     * @param mixed $data Parameter data.
     * @param mixed $files Parameter files.
     * @return array Return value.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        foreach (['argumentation', 'evidence', 'participation', 'replies'] as $criterion) {
            $value = (float)($data[$criterion] ?? -1);
            if ($value < 0 || $value > 100) {
                $errors[$criterion] = get_string('scorebetween', 'videodebate');
            }
        }
        return $errors;
    }
}

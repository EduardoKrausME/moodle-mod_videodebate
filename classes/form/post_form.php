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
 * Student argument/reply form.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post_form extends \moodleform {
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
        $mform->addElement('hidden', 'parentid', $custom['parentid']);
        $mform->setType('parentid', PARAM_INT);
        if ((int)$custom['parentid'] === 0) {
            if ($custom['automatic']) {
                $mform->addElement('static', 'assignedposition',
                    get_string('yourposition', 'videodebate'), $custom['positionlabel']);
                $mform->addElement('hidden', 'positionkey', $custom['positionkey']);
                $mform->setType('positionkey', PARAM_ALPHANUMEXT);
            } else {
                $mform->addElement('select', 'positionkey', get_string('yourposition', 'videodebate'), $custom['positions']);
                $mform->setType('positionkey', PARAM_ALPHANUMEXT);
                $mform->addRule('positionkey', null, 'required', null, 'client');
            }
        } else {
            $mform->addElement('hidden', 'positionkey', '');
            $mform->setType('positionkey', PARAM_ALPHANUMEXT);
        }
        $label = (int)$custom['parentid'] === 0 ? get_string('argument', 'videodebate') : get_string('reply', 'videodebate');
        $mform->addElement('textarea', 'message', $label, ['rows' => 8, 'cols' => 80]);
        $mform->setType('message', PARAM_TEXT);
        $mform->addRule('message', null, 'required', null, 'client');
        $mform->addElement('hidden', 'evidencejson', '[]');
        $mform->setType('evidencejson', PARAM_RAW);
        $mform->addElement('html', \html_writer::div('', 'videodebate-evidence-form', ['data-region' => 'evidence-form']));
        $mform->addElement('submit', 'submitbutton', (int)$custom['parentid'] === 0
            ? get_string('publishargument', 'videodebate') : get_string('publishreply', 'videodebate'));
    }
}

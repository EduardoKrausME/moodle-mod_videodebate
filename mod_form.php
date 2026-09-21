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
 * Activity configuration form.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Class mod_videodebate_mod_form.
 */
class mod_videodebate_mod_form extends moodleform_mod {
    /**
     * Method definition.
     *
     * @return void Return value.
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videodebatename', 'videodebate'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'videoheader', get_string('videoheader', 'videodebate'));
        $mform->addElement('select', 'videosource', get_string('videosource', 'videodebate'), [
            'upload' => get_string('sourceupload', 'videodebate'),
            'url' => get_string('sourceurl', 'videodebate'),
            'youtube' => get_string('sourceyoutube', 'videodebate'),
            'vimeo' => get_string('sourcevimeo', 'videodebate'),
        ]);
        $mform->setDefault('videosource', 'url');
        $mform->setType('videosource', PARAM_ALPHA);

        $mform->addElement('filemanager', 'videofile', get_string('videofile', 'videodebate'), null, [
            'subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['video'],
        ]);
        $mform->hideIf('videofile', 'videosource', 'neq', 'upload');
        $mform->addElement('text', 'videourl', get_string('videourl', 'videodebate'), ['size' => 80]);
        $mform->setType('videourl', PARAM_RAW_TRIMMED);
        $mform->hideIf('videourl', 'videosource', 'eq', 'upload');

        $mform->addElement('selectyesno', 'resumeplayback', get_string('resumeplayback', 'videodebate'));
        $mform->setDefault('resumeplayback', 1);
        $mform->addElement('selectyesno', 'allowseek', get_string('allowseek', 'videodebate'));
        $mform->setDefault('allowseek', 0);

        $mform->addElement('header', 'debateheader', get_string('debateheader', 'videodebate'));
        $mform->addElement('textarea', 'question', get_string('debatequestion', 'videodebate'), ['rows' => 4, 'cols' => 80]);
        $mform->setType('question', PARAM_TEXT);
        $mform->addRule('question', null, 'required', null, 'client');
        $mform->addElement('textarea', 'positions', get_string('positions', 'videodebate'), ['rows' => 5, 'cols' => 60]);
        $mform->setType('positions', PARAM_TEXT);
        $mform->setDefault('positions', get_string('defaultpositions', 'videodebate'));
        $mform->addRule('positions', null, 'required', null, 'client');
        $mform->addHelpButton('positions', 'positions', 'videodebate');

        $mform->addElement('select', 'assignmentmode', get_string('assignmentmode', 'videodebate'), [
            0 => get_string('assignmentfree', 'videodebate'),
            1 => get_string('assignmentautomatic', 'videodebate'),
        ]);
        $mform->setDefault('assignmentmode', 0);
        $mform->addElement('selectyesno', 'blinduntilpost', get_string('blinduntilpost', 'videodebate'));
        $mform->setDefault('blinduntilpost', 1);
        $mform->addElement('text', 'minevidence', get_string('minevidence', 'videodebate'), ['size' => 5]);
        $mform->setType('minevidence', PARAM_INT);
        $mform->setDefault('minevidence', 1);

        $mform->addElement('header', 'gradingheader', get_string('gradingheader', 'videodebate'));
        $mform->addElement('text', 'grade', get_string('maximumgrade', 'videodebate'), ['size' => 6]);
        $mform->setType('grade', PARAM_FLOAT);
        $mform->setDefault('grade', 100);
        foreach (['argument' => 35, 'evidence' => 30, 'participation' => 20, 'replies' => 15] as $key => $default) {
            $name = 'weight' . $key;
            $mform->addElement('text', $name, get_string($name, 'videodebate'), ['size' => 5]);
            $mform->setType($name, PARAM_INT);
            $mform->setDefault($name, $default);
        }

        $mform->addElement('header', 'completionrules', get_string('completionrules', 'completion'));
        $mform->addElement('text', 'completionpercent', get_string('completionpercent', 'videodebate'), ['size' => 5]);
        $mform->setType('completionpercent', PARAM_INT);
        $mform->setDefault('completionpercent', 80);
        $mform->addElement('advcheckbox', 'completionpost', get_string('completionpost', 'videodebate'));
        $mform->setDefault('completionpost', 1);
        $mform->addElement('text', 'completionreplies', get_string('completionreplies', 'videodebate'), ['size' => 5]);
        $mform->setType('completionreplies', PARAM_INT);
        $mform->setDefault('completionreplies', 0);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Method data_preprocessing.
     *
     * @param mixed $defaultvalues Parameter defaultvalues.
     * @return void Return value.
     */
    public function data_preprocessing(&$defaultvalues): void {
        parent::data_preprocessing($defaultvalues);
        if ($this->current && !empty($this->current->id)) {
            $cm = get_coursemodule_from_instance('videodebate', $this->current->id, $this->current->course, false, IGNORE_MISSING);
            if ($cm) {
                $context = context_module::instance($cm->id);
                $draftid = file_get_submitted_draft_itemid('videofile');
                file_prepare_draft_area($draftid, $context->id, 'mod_videodebate', 'video', 0,
                    ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['video']]);
                $defaultvalues['videofile'] = $draftid;
            }
        }
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
        $source = $data['videosource'] ?? '';
        $sourcevalue = trim((string)($data['videourl'] ?? ''));
        if ($source === 'upload') {
            $draftid = (int)($data['videofile'] ?? 0);
            $draftinfo = $draftid ? file_get_draft_area_info($draftid) : ['filecount' => 0];
            if (empty($draftinfo['filecount'])) {
                $errors['videofile'] = get_string('errorvideorequired', 'videodebate');
            }
        } else if ($sourcevalue === '') {
            $errors['videourl'] = get_string('required');
        } else if ($source === 'url' && !preg_match('~^https?://~i', $sourcevalue)) {
            $errors['videourl'] = get_string('errorinvalidurl', 'videodebate');
        } else if ($source === 'youtube'
            && !preg_match('~^[A-Za-z0-9_-]{6,}$~', $sourcevalue)
            && !preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))[A-Za-z0-9_-]{6,}~i', $sourcevalue)) {
            $errors['videourl'] = get_string('errorinvalidyoutube', 'videodebate');
        } else if ($source === 'vimeo'
            && !preg_match('~^[0-9]+$~', $sourcevalue)
            && !preg_match('~vimeo\.com/(?:video/)?[0-9]+~i', $sourcevalue)) {
            $errors['videourl'] = get_string('errorinvalidvimeo', 'videodebate');
        }
        $positions = mod_videodebate\debate_manager::positions_from_text($data['positions'] ?? '');
        if (count($positions) < 2) {
            $errors['positions'] = get_string('errorpositions', 'videodebate');
        }
        $sum = 0;
        foreach (['weightargument', 'weightevidence', 'weightparticipation', 'weightreplies'] as $field) {
            $value = (int)($data[$field] ?? 0);
            $sum += $value;
            if ($value < 0 || $value > 100) {
                $errors[$field] = get_string('errorpercent', 'videodebate');
            }
        }
        if ($sum !== 100) {
            $errors['weightargument'] = get_string('errorweights', 'videodebate');
        }
        $percent = (int)($data['completionpercent'] ?? 0);
        if ($percent < 0 || $percent > 100) {
            $errors['completionpercent'] = get_string('errorpercent', 'videodebate');
        }
        if ((int)($data['minevidence'] ?? 0) < 0) {
            $errors['minevidence'] = get_string('errornonnegative', 'videodebate');
        }
        if ((int)($data['completionreplies'] ?? 0) < 0) {
            $errors['completionreplies'] = get_string('errornonnegative', 'videodebate');
        }
        $grade = (float)($data['grade'] ?? 0);
        if ($grade < 0 || $grade > 100) {
            $errors['grade'] = get_string('errorgrade', 'videodebate');
        }
        return $errors;
    }

    /**
     * Method add_completion_rules.
     *
     * @return array Return value.
     */
    public function add_completion_rules(): array {
        return ['completionpercent', 'completionpost', 'completionreplies'];
    }

    /**
     * Method completion_rule_enabled.
     *
     * @param mixed $data Parameter data.
     * @return bool Return value.
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data['completionpercent']) || !empty($data['completionpost']) || !empty($data['completionreplies']);
    }
}

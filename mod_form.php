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

global $CFG;
defined('MOODLE_INTERNAL') || die;

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

        $mform->addElement('html', '<h3>' . get_string('videoheader', 'videodebate') . '</h3>');
        $mform->addElement('select', 'videosource', get_string('videosource', 'videodebate'), [
            'upload' => get_string('sourceupload', 'videodebate'),
            'url' => get_string('sourceurl', 'videodebate'),
            'youtube' => get_string('sourceyoutube', 'videodebate'),
            'vimeo' => get_string('sourcevimeo', 'videodebate'),
        ]);
        $mform->setDefault('videosource', 'url');
        $mform->setType('videosource', PARAM_ALPHA);

        $mform->addElement('filemanager', 'videofile', get_string('videofile', 'videodebate'), null, [
            'subdirs' => 0, 'accepted_types' => ['video'],
        ]);
        $mform->hideIf('videofile', 'videosource', 'neq', 'upload');
        $mform->addElement('text', 'videourl', get_string('videourl', 'videodebate'), ['size' => 80]);
        $mform->setType('videourl', PARAM_RAW_TRIMMED);
        $mform->hideIf('videourl', 'videosource', 'eq', 'upload');

        $mform->addElement('text', 'durationseconds', get_string('durationseconds', 'videodebate'), ['size' => 10]);
        $mform->setType('durationseconds', PARAM_FLOAT);
        $mform->addHelpButton('durationseconds', 'durationseconds', 'videodebate');

        $mform->addElement('selectyesno', 'resumeplayback', get_string('resumeplayback', 'videodebate'));
        $mform->setDefault('resumeplayback', 1);
        $mform->addElement('selectyesno', 'allowseek', get_string('allowseek', 'videodebate'));
        $mform->setDefault('allowseek', 0);

        $mform->addElement('html', '<h3>' . get_string('accessibilityheader', 'videodebate') . '</h3>');
        $mform->addElement('textarea', 'transcript', get_string('transcript', 'videodebate'), ['rows' => 8, 'cols' => 80]);
        $mform->setType('transcript', PARAM_TEXT);
        $mform->addElement('text', 'captionlang', get_string('captionlang', 'videodebate'), ['size' => 10]);
        $mform->setType('captionlang', PARAM_ALPHANUMEXT);
        $mform->setDefault('captionlang', 'en');
        $mform->addElement('filemanager', 'captionfile', get_string('captionfile', 'videodebate'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.vtt'],
        ]);

        if ($this->current && !empty($this->current->id)) {
            $mform->addElement('advcheckbox', 'resetvideodata', get_string('resetvideodata', 'videodebate'));
            $mform->addHelpButton('resetvideodata', 'resetvideodata', 'videodebate');
        }

        $mform->addElement('html', '<h3>' . get_string('debateheader', 'videodebate') . '</h3>');
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

        $mform->addElement('html', '<h3>' . get_string('gradingheader', 'videodebate') . '</h3>');
        $mform->addElement('text', 'grade', get_string('maximumgrade', 'videodebate'), ['size' => 6]);
        $mform->setType('grade', PARAM_FLOAT);
        $mform->setDefault('grade', 100);
        foreach (['argument' => 35, 'evidence' => 30, 'participation' => 20, 'replies' => 15] as $key => $default) {
            $name = 'weight' . $key;
            $mform->addElement('text', $name, get_string($name, 'videodebate'), ['size' => 5]);
            $mform->setType($name, PARAM_INT);
            $mform->setDefault($name, $default);
        }

        $mform->addElement('html', '<h3>' . get_string('completionrules', 'videodebate') . '</h3>');
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

                $captiondraftid = file_get_submitted_draft_itemid('captionfile');
                file_prepare_draft_area($captiondraftid, $context->id, 'mod_videodebate', 'captions', 0, [
                    'subdirs' => 0,
                    'maxfiles' => 1,
                    'accepted_types' => ['.vtt'],
                ]);
                $defaultvalues['captionfile'] = $captiondraftid;
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
        } else if ($source === 'url') {
            $cleanurl = clean_param($sourcevalue, PARAM_URL);
            if ($cleanurl !== $sourcevalue || !preg_match('~^https?://~i', $cleanurl)) {
                $errors['videourl'] = get_string('errorinvalidurl', 'videodebate');
            }
        } else if ($source === 'youtube'
            && !preg_match('~^[A-Za-z0-9_-]{6,}$~', $sourcevalue)
            && !preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))[A-Za-z0-9_-]{6,}~i', $sourcevalue)) {
            $errors['videourl'] = get_string('errorinvalidyoutube', 'videodebate');
        } else if ($source === 'vimeo'
            && !preg_match('~^[0-9]+$~', $sourcevalue)
            && !preg_match('~vimeo\.com/(?:video/)?[0-9]+~i', $sourcevalue)) {
            $errors['videourl'] = get_string('errorinvalidvimeo', 'videodebate');
        }
        $duration = (float)($data['durationseconds'] ?? 0);
        if ($duration <= 0) {
            $errors['durationseconds'] = get_string('errordurationrequired', 'videodebate');
        }
        $captionlang = trim((string)($data['captionlang'] ?? ''));
        if ($captionlang === '' || !preg_match('/^[A-Za-z0-9_-]{2,20}$/', $captionlang)) {
            $errors['captionlang'] = get_string('errorcaptionlang', 'videodebate');
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
        foreach (['videofile', 'captionfile'] as $field) {
            $draftid = (int)($data[$field] ?? 0);
            if ($draftid > 0) {
                $draftinfo = file_get_draft_area_info($draftid);
                if ((int)$draftinfo['filecount'] > 1) {
                    $errors[$field] = get_string('errormaxfiles', 'videodebate');
                }
            }
        }
        if ($this->current && !empty($this->current->id)
            && $this->video_changed($data)
            && $this->has_user_data((int)$this->current->id)
            && empty($data['resetvideodata'])) {
            $errors['resetvideodata'] = get_string('errorresetvideodata', 'videodebate');
        }

        return $errors;
    }

    /**
     * Whether the configured video differs from the existing activity.
     *
     * @param array $data Submitted form data.
     * @return bool
     */
    private function video_changed(array $data): bool {
        global $DB;

        $current = $DB->get_record('videodebate', ['id' => (int)$this->current->id], '*', MUST_EXIST);
        if ((string)$current->videosource !== (string)($data['videosource'] ?? '')
            || trim((string)$current->videourl) !== trim((string)($data['videourl'] ?? ''))
            || abs((float)$current->durationseconds - (float)($data['durationseconds'] ?? 0)) > 0.01) {
            return true;
        }
        if (($data['videosource'] ?? '') !== 'upload') {
            return false;
        }

        $cm = get_coursemodule_from_instance('videodebate', $current->id, $current->course, false, IGNORE_MISSING);
        if (!$cm) {
            return false;
        }
        $context = context_module::instance($cm->id);
        $stored = get_file_storage()->get_area_files(
            $context->id,
            'mod_videodebate',
            'video',
            0,
            'filename',
            false
        );
        $storedhashes = array_map(static fn($file) => $file->get_contenthash(), $stored);
        sort($storedhashes);

        $draftid = (int)($data['videofile'] ?? 0);
        $drafthashes = [];
        if ($draftid) {
            $draftcontext = context_user::instance($GLOBALS['USER']->id);
            $draftfiles = get_file_storage()->get_area_files(
                $draftcontext->id,
                'user',
                'draft',
                $draftid,
                'filename',
                false
            );
            $drafthashes = array_map(static fn($file) => $file->get_contenthash(), $draftfiles);
            sort($drafthashes);
        }
        return $storedhashes !== $drafthashes;
    }

    /**
     * Whether the activity already contains participant data.
     *
     * @param int $activityid Activity id.
     * @return bool
     */
    private function has_user_data(int $activityid): bool {
        global $DB;
        return $DB->record_exists('videodebate_posts', ['videodebateid' => $activityid])
            || $DB->record_exists('videodebate_progress', ['videodebateid' => $activityid])
            || $DB->record_exists('videodebate_grades', ['videodebateid' => $activityid]);
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

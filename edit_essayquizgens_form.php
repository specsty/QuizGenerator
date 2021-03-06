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
 * Defines the editing form for the essay question type for quiz generator.
 * Extended from Jamie Pratt's essay question code (2007).
 *
 * @package    qtype
 * @subpackage essayquizgens
 * @copyright  2017 Rian Fakhrusy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Essay question type editing form.
 * Extended from Jamie Pratt's essay question code (2007).
 *
 * @copyright  2017 Rian Fakhrusy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayquizgens_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        $qtype = question_bank::get_qtype('essayquizgens');

        // 2nd segment
        $mform->addElement('header', 'questionattributes', get_string('questionattributes', 'qtype_essayquizgens'));
        $mform->setExpanded('questionattributes');

        $mform->addElement('text', 'time', get_string('time', 'qtype_essayquizgens'),
                array('size' => 7));
        $mform->setType('time', PARAM_INT);
        $mform->setDefault('time', 5);
        $mform->addRule('time', null, 'required', null, 'client');

        $mform->addElement('text', 'difficulty', get_string('difficulty', 'qtype_essayquizgens'),
                array('size' => 7));
        $mform->setType('difficulty', PARAM_INT);
        $mform->setDefault('difficulty', 5);
        $mform->addRule('difficulty', null, 'required', null, 'client');

        $mform->addElement('text', 'chapter', get_string('chapter', 'qtype_essayquizgens'),
                array('size' => 50, 'maxlength' => 255));
        $mform->setType('chapter', PARAM_TEXT);
        $mform->addRule('chapter', null, 'required', null, 'client');

        // 3rd segment
        $mform->addElement('header', 'responseoptions', get_string('responseoptions', 'qtype_essayquizgens'));
        $mform->setExpanded('responseoptions');

        $mform->addElement('select', 'responseformat',
                get_string('responseformat', 'qtype_essayquizgens'), $qtype->response_formats());
        $mform->setDefault('responseformat', 'editor');

        $mform->addElement('select', 'responserequired',
                get_string('responserequired', 'qtype_essayquizgens'), $qtype->response_required_options());
        $mform->setDefault('responserequired', 1);
        $mform->disabledIf('responserequired', 'responseformat', 'eq', 'noinline');

        $mform->addElement('select', 'responsefieldlines',
                get_string('responsefieldlines', 'qtype_essayquizgens'), $qtype->response_sizes());
        $mform->setDefault('responsefieldlines', 15);
        $mform->disabledIf('responsefieldlines', 'responseformat', 'eq', 'noinline');

        $mform->addElement('select', 'attachments',
                get_string('allowattachments', 'qtype_essayquizgens'), $qtype->attachment_options());
        $mform->setDefault('attachments', 0);

        $mform->addElement('select', 'attachmentsrequired',
                get_string('attachmentsrequired', 'qtype_essayquizgens'), $qtype->attachments_required_options());
        $mform->setDefault('attachmentsrequired', 0);
        $mform->addHelpButton('attachmentsrequired', 'attachmentsrequired', 'qtype_essayquizgens');
        $mform->disabledIf('attachmentsrequired', 'attachments', 'eq', 0);

        // 4th segment
        $mform->addElement('header', 'responsetemplateheader', get_string('responsetemplateheader', 'qtype_essayquizgens'));
        $mform->addElement('editor', 'responsetemplate', get_string('responsetemplate', 'qtype_essayquizgens'),
                array('rows' => 10),  array_merge($this->editoroptions, array('maxfiles' => 0)));
        $mform->addHelpButton('responsetemplate', 'responsetemplate', 'qtype_essayquizgens');

        // 5th segment
        $mform->addElement('header', 'graderinfoheader', get_string('graderinfoheader', 'qtype_essayquizgens'));
        $mform->setExpanded('graderinfoheader');
        $mform->addElement('editor', 'graderinfo', get_string('graderinfo', 'qtype_essayquizgens'),
                array('rows' => 10), $this->editoroptions);
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }

        $question->chapter = $question->options->chapter;
        $question->time = $question->options->time;
        $question->difficulty = $question->options->difficulty;

        $question->responseformat = $question->options->responseformat;
        $question->responserequired = $question->options->responserequired;
        $question->responsefieldlines = $question->options->responsefieldlines;
        $question->attachments = $question->options->attachments;
        $question->attachmentsrequired = $question->options->attachmentsrequired;

        $draftid = file_get_submitted_draft_itemid('graderinfo');
        $question->graderinfo = array();
        $question->graderinfo['text'] = file_prepare_draft_area(
            $draftid,                       // Draftid
            $this->context->id,             // context
            'qtype_essayquizgens',     // component
            'graderinfo',                   // filarea
            !empty($question->id) ? (int) $question->id : null, // itemid
            $this->fileoptions, // options
            $question->options->graderinfo // text.
        );
        $question->graderinfo['format'] = $question->options->graderinfoformat;
        $question->graderinfo['itemid'] = $draftid;

        $question->responsetemplate = array(
            'text' => $question->options->responsetemplate,
            'format' => $question->options->responsetemplateformat,
        );

        return $question;
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        // Don't allow both 'no inline response' and 'no attachments' to be selected,
        // as these options would result in there being no input requested from the user.
        if ($fromform['responseformat'] == 'noinline' && !$fromform['attachments']) {
            $errors['attachments'] = get_string('mustattach', 'qtype_essayquizgens');
        }

        // If 'no inline response' is set, force the teacher to require attachments;
        // otherwise there will be nothing to grade.
        if ($fromform['responseformat'] == 'noinline' && !$fromform['attachmentsrequired']) {
            $errors['attachmentsrequired'] = get_string('mustrequire', 'qtype_essayquizgens');
        }

        // Don't allow the teacher to require more attachments than they allow; as this would
        // create a condition that it's impossible for the student to meet.
        if ($fromform['attachments'] != -1 && $fromform['attachments'] < $fromform['attachmentsrequired'] ) {
            $errors['attachmentsrequired']  = get_string('mustrequirefewer', 'qtype_essayquizgens');
        }

        return $errors;
    }

    public function qtype() {
        return 'essayquizgens';
    }
}

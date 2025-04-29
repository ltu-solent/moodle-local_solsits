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

namespace local_solsits\forms;

use core\context;
use core\exception\moodle_exception;
use core\task\manager;
use core\url;
use core_form\dynamic_form;
use local_solsits\sitsassign;
use local_solsits\task\new_duedate_task;

/**
 * Class new_duedate_form
 *
 * @package    local_solsits
 * @copyright  2025 Southampton Solent University {@link https://www.solent.ac.uk}
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class new_duedate_form extends dynamic_form {
    /**
     * Sitsassign record
     *
     * @var sitsassign
     */
    private $sitsassign;

    /**
     * Gets the sitsassign persistent for the given sitsref
     *
     * @return sitsassign
     */
    protected function get_sitsassign(): sitsassign {
        if ($this->sitsassign === null) {
            $this->sitsassign = sitsassign::get_record(['sitsref' => $this->_ajaxformdata['sitsref']], MUST_EXIST);
        }
        return $this->sitsassign;
    }

    /**
     * Form definite for the due date email.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $mform->setDisableShortforms();
        $strftimedatetimeaccurate = '%d %B %Y';
        $mform->addElement('header', 'hdr', '');
        $mform->addElement('hidden', 'sitsref');
        $mform->setType('sitsref', PARAM_RAW);

        $name = 'message';
        $title = '';
        $description = get_string('assignmentduedatechange_description', 'local_solsits', [
            'title' => $this->get_sitsassign()->get('title'),
        ]);
        $mform->addElement('static', 'messageinfo', $title, $description);
        $duedate = $this->get_sitsassign()->get('duedate');
        $title = get_string('existingduedate', 'local_solsits');
        $mform->addElement('static', 'duedate', $title, userdate($duedate, $strftimedatetimeaccurate));
        $mform->addElement('date_selector', 'newduedate', get_string('newduedate', 'local_solsits'));
    }

    /**
     * Returns context where this form is used.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context\module::instance($this->get_sitsassign()->get('cmid'));
    }

    /**
     * Checks if current user has access to this form, otherwise throws exception
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('mod/assign:grade', $this->get_context_for_dynamic_submission());
    }

    /**
     * Process the form submission
     *
     * @return array
     */
    public function process_dynamic_submission(): array {
        global $USER;
        $data = $this->get_data();
        $strftimedatetimeaccurate = '%d %B %Y';
        $newduedate = $data->newduedate; // Timestamp.
        $existingduedate = $this->get_sitsassign()->get('duedate'); // Timestamp.

        // Are they the same?
        $formattedduedate = userdate($existingduedate, $strftimedatetimeaccurate);
        $formattednewduedate = userdate($newduedate, $strftimedatetimeaccurate);
        $detail = [
            'title' => $this->get_sitsassign()->get('title'),
            'sitsref' => $this->get_sitsassign()->get('sitsref'),
        ];
        if ($formattedduedate == $formattednewduedate) {
            $detail['error'] = 'samedates';
            return $detail;
        }

        $customdata = [
            'sitsref' => $this->get_sitsassign()->get('sitsref'),
            'newduedate' => $newduedate,
        ];
        $task = new new_duedate_task();
        $task->set_userid($USER->id);
        $task->set_custom_data($customdata);
        $status = manager::queue_adhoc_task($task, true);
        if (!$status) {
            $detail['error'] = 'failedtoqueue';
        }
        return $detail;
    }

    /**
     * Load in existing data as form defaults
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        $this->set_data($this->_ajaxformdata);
    }

    /**
     * Returns url
     *
     * @return url
     */
    protected function get_page_url_for_dynamic_submission(): url {
        return new url('/course/view.php', ['id' => $this->sitsassign->get('courseid')]);
    }
}

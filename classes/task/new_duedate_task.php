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

namespace local_solsits\task;

use core\exception\coding_exception;
use core\exception\moodle_exception;
use core\task\adhoc_task;
use local_solsits\sitsassign;

/**
 * Class new_duedate_task
 *
 * @package    local_solsits
 * @copyright  2025 Southampton Solent University {@link https://www.solent.ac.uk}
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class new_duedate_task extends adhoc_task {

    /**
     * Sits assignment record
     *
     * @var sitsassign
     */
    private $sitsassign;

    /**
     * Run the task
     *
     * @return void
     */
    public function execute() {
        $body = get_config('local_solsits', 'assignmentduedatechange_body');
        $strftimedatetimeaccurate = '%d %B %Y';
        $customdata = $this->get_custom_data();
        if (!isset($customdata->newduedate)) {
            throw new coding_exception('The customdata \'newduedate\' is required');
        }
        $newduedate = $customdata->newduedate; // Timestamp.
        $existingduedate = $this->get_sitsassign()->get('duedate'); // Timestamp.
        $formattedduedate = userdate($existingduedate, $strftimedatetimeaccurate);
        $formattednewduedate = userdate($newduedate, $strftimedatetimeaccurate);
        $userid = $this->get_userid();
        $requester = \core_user::get_user($userid);
        $tutor = fullname($requester);
        $htmlbody = str_replace([
            '{TUTOR}',
            '{TITLE}',
            '{SITSREF}',
            '{NEWDUEDATE}',
            '{OLDDUEDATE}',
        ], [
            $tutor,
            $this->get_sitsassign()->get('title'),
            $this->get_sitsassign()->get('sitsref'),
            $formattednewduedate,
            $formattedduedate,
        ], $body);
        $noreplyuser = \core_user::get_noreply_user();
        $subject = get_string('assignmentduedatechange_subject', 'local_solsits', $this->sitsassign->get('sitsref'));
        $textbody = html_to_text($htmlbody);
        email_to_user($requester, $noreplyuser, $subject, $textbody, $htmlbody);
        $mailinglist = get_config('local_solsits', 'assignmentduedatechange_mailinglist') ?? '';
        $mailinglist = explode(',', $mailinglist);
        foreach ($mailinglist as $username) {
            $user = \core_user::get_user_by_username($username);
            if ($user) {
                email_to_user($user, $requester, $subject, $textbody, $htmlbody);
            }
        }
    }

    /**
     * Load the solsits record, if not already loaded
     *
     * @return sitsassign
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function get_sitsassign(): sitsassign {
        if ($this->sitsassign === null) {
            $customdata = $this->get_custom_data();
            if (!isset($customdata->sitsref)) {
                throw new coding_exception('The customdata \'sitsref\' is required');
            }
            $this->sitsassign = sitsassign::get_record(['sitsref' => $customdata->sitsref], MUST_EXIST);
        }
        return $this->sitsassign;
    }
}

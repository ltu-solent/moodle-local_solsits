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

use context_course;
use core\task\scheduled_task;
use local_solsits\sitsassign;
use stdClass;

/**
 * Class send_assign_config_errors_messsage_task
 *
 * @package    local_solsits
 * @copyright  2025 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_assign_config_errors_messsage_task extends scheduled_task {
    /**
     * Returns the name of this task displayed in the task list.
     *
     * @return string
     */
    public function get_name() {
        return get_string('send_assign_config_errors_messsage_task', 'local_solsits');
    }

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute() {
        global $OUTPUT;
        $config = get_config('local_solsits');
        // Prefixed with r because get_string will convert into an object and you can't have properties starting with
        // a number.
        $ranges = [
            'r0-1weeks' => [
                'start' => time(),
                'end' => strtotime('+1 week'),
            ],
            'r1-2weeks' => [
                'start' => strtotime('+1 week'),
                'end' => strtotime('+2 weeks'),
            ],
            'r2-3weeks' => [
                'start' => strtotime('+2 week'),
                'end' => strtotime('+3 weeks'),
            ],
        ];
        // We are going to send these emails from 'noreplyaddress'.
        $noreplyuser = \core_user::get_noreply_user();
        $mailinglist = $config->assignmentconfigwarning_mailinglist ?? '';
        $mailinglist = explode(',', $mailinglist);
        $gatewayusers = [];
        foreach ($mailinglist as $username) {
            $user = \core_user::get_user_by_username($username);
            if ($user) {
                $gatewayusers[] = $user;
            }
        }

        foreach ($ranges as $key => $range) {
            $moduleleaders = [];
            $gatewaylist = [];
            $unconfigured = sitsassign::get_unconfigured_assignments($range['start'], $range['end']);
            foreach ($unconfigured as $sitsassign) {
                // Get the course's module leader(s). Add The assignment to that ML's list.
                $context = context_course::instance($sitsassign->courseid);
                $mls = get_enrolled_users($context, 'local/solsits:releasegrades', 0, 'u.*', null, 0, 0, true);
                foreach ($mls as $ml) {
                    if (!isset($moduleleaders[$ml->id])) {
                        $moduleleaders[$ml->id] = [];
                    }
                    $moduleleaders[$ml->id][] = $sitsassign;
                }
                // Add assignment to Gateway list.
                $gatewaylist[] = $sitsassign;
            }
            $subject = get_string($key, 'local_solsits');
            $data = new stdClass();
            $data->title = $subject;
            foreach ($moduleleaders as $userid => $assignments) {
                $data->assignments = $assignments;
                $body = $config->assignmentconfigwarning_body ?? '';
                $user = \core_user::get_user($userid);
                $moduleleader = fullname($user);
                $body = str_replace([
                    '{MODULELEADER}',
                ], [
                    $moduleleader,
                ], $body);
                $data->message = $body;
                $htmlbody = $OUTPUT->render_from_template('local_solsits/assign_config_errors', $data);
                $textbody = html_to_text($htmlbody);
                email_to_user($user, $noreplyuser, $subject, $textbody, $htmlbody);
            }
            // Gateway content.
            mtrace(count($gatewaylist) . ' items found for range ' .
                date('Y-m-d', $range['start']) . ' - ' . date('Y-m-d', $range['end']));
            if (count($gatewaylist) > 0) {
                $data->assignments = $gatewaylist;
                foreach ($gatewayusers as $gatewayuser) {
                    $body = $config->assignmentconfigwarning_body ?? '';
                    $moduleleader = fullname($gatewayuser);
                    $body = str_replace([
                        '{MODULELEADER}',
                    ], [
                        $moduleleader,
                    ], $body);
                    $data->message = $body;
                    $htmlbody = $OUTPUT->render_from_template('local_solsits/assign_config_errors', $data);
                    $textbody = html_to_text($htmlbody);
                    email_to_user($gatewayuser, $noreplyuser, $subject, $textbody, $htmlbody);
                }
            }
        }
    }
}

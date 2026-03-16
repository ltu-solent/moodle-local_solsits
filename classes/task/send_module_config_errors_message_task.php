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

use core\task\scheduled_task;
use core\user;
use local_solsits\helper;
use stdClass;

/**
 * Class send_module_config_errors_message_task
 *
 * @package    local_solsits
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_module_config_errors_message_task extends scheduled_task {
    /**
     * Returns the name of this task displayed in the task list.
     *
     * @return string
     */
    public function get_name() {
        return get_string('send_module_config_errors_message_task', 'local_solsits');
    }

    /**
     * Send emails
     *
     * @return void
     */
    public function execute() {
        global $OUTPUT;
        $lastrun = get_config('local_solsits', 'module_config_errors_lastrun') ?? 0;
        // Undercut the 7 days by a bit so we don't find ourselves with a 2 week interval.
        $mininterval = ((DAYSECS * 7) - HOURSECS);
        $now = time();
        if ($now < ($lastrun + $mininterval)) {
            mtrace("This task can only run once a week. Last run " . date('Y-m-d H:i:s', $lastrun));
            return;
        }
        set_config('module_config_errors_lastrun', $now, 'local_solsits');
        // Prefixed with r because get_string will convert into an object and you can't have properties starting with
        // a number.
        $ranges = self::get_ranges();
        if (empty($ranges)) {
            mtrace("No ranges selected. Nothing to do.");
            return;
        }
        // We are going to send these emails from 'noreplyaddress'.
        $noreplyuser = user::get_noreply_user();
        $mailinglist = get_config('local_solsits', 'moduleconfigurationwarning_mailinglist') ?? '';
        $mailinglist = explode(',', $mailinglist);
        $gatewayusers = [];
        foreach ($mailinglist as $username) {
            $user = user::get_user_by_username($username);
            if ($user) {
                $gatewayusers[] = $user;
            }
        }
        foreach ($ranges as $range) {
            $moduleleaders = [];
            $gatewaylist = [];
            $modules = helper::get_modules_with_config_issues($range['start'], $range['end']);
            if (empty($modules)) {
                mtrace("No modules starting between " . date('Y-m-d', $range['start']) . " and " . date('Y-m-d', $range['end']) .
                    " with config issues.");
                continue;
            }
            foreach ($modules as $module) {
                $context = \core\context\course::instance($module->id);
                $mls = get_enrolled_users(
                    context: $context,
                    withcapability: 'local/solsits:releasegrades',
                    onlyactive: true
                );
                if (count($mls) == 0) {
                    mtrace("No module leaders found for course " . $module->id);
                    continue;
                }
                foreach ($mls as $ml) {
                    if (!isset($moduleleaders[$ml->id])) {
                        $moduleleaders[$ml->id] = [];
                    }
                    $moduleleaders[$ml->id][] = $module;
                }
                $gatewaylist[] = $module;
            }
            $startdate = date('Y-m-d', $range['start']);
            $enddate = date('Y-m-d', $range['end']);
            $subject = get_string('forinformation', 'local_solsits') . get_string('modulerangedates', 'local_solsits', [
                'start' => $startdate,
                'end' => $enddate,
                'range' => $range['label'],
            ]);
            $data = new stdClass();
            $data->title = $subject;
            foreach ($moduleleaders as $mlid => $modules) {
                $user = user::get_user($mlid);
                $data->modules = $modules;
                $moduleleader = fullname($user);
                $body = get_config('local_solsits', 'moduleconfigurationwarning_body') ?? '';
                $body = str_replace([
                    '{MODULELEADER}',
                    '{PERIOD}',
                ], [
                    $moduleleader,
                    $range['label'],
                ], $body);
                $data->message = $body;
                $htmlbody = $OUTPUT->render_from_template('local_solsits/email_module_config_warning', $data);
                $textbody = html_to_text($htmlbody);
                email_to_user($user, $noreplyuser, $subject, $textbody, $htmlbody);
                mtrace("Email sent to $moduleleader for modules starting between $startdate and $enddate");
            }
        }
    }

    /**
     * Ranges to check for config settings
     * @return array $ranges
     */
    public static function get_ranges() {
        $ranges = [];
        $selected = get_config('local_solsits', 'moduleconfigurationwarning_ranges') ?? '';
        if (empty($selected)) {
            return $ranges;
        }
        $items = explode(',', trim($selected));
        foreach ($items as $item) {
            $ranges[$item] = helper::map_range($item);
        }
        return $ranges;
    }
}

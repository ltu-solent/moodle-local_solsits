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
 * Get newly released grades from grade items for sits assignments
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\task;

use core\task\scheduled_task;
use local_solsits\ais_client;
use local_solsits\sitsassign;
use stdClass;

/**
 * Get newly released grades. Used in conjunction with export_grades.
 */
class export_grades_task extends scheduled_task {
    /**
     * Name of the task
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('exportgradestask', 'local_solsits');
    }

    /**
     * Get ais_client (curl)
     *
     * @return ais_client
     */
    public function get_client(): ais_client {
        // In the test, mock get_client and set the response to whatever we want.
        $config = get_config('local_solsits');
        // Set token, urls.
        $client = new ais_client([], $config->ais_exportgrades_url, $config->ais_exportgrades_key);
        return $client;
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public function execute() {
        $config = get_config('local_solsits');
        $maxassignments = isset($config->marksuploads_maxassignments) ? $config->marksuploads_maxassignments : 1;
        $batchgrades = isset($config->marksuploads_batchgrades) ? $config->marksuploads_batchgrades : -1;

        $retrylist = sitsassign::get_retry_list($maxassignments);
        if (empty($retrylist)) {
            mtrace("No grades to export to SITS");
            return;
        }

        foreach ($retrylist as $sitsassignid) {
            $sitsassign = new sitsassign($sitsassignid);
            if (!$sitsassign) {
                mtrace("Sitsassign doesn't exist");
                continue;
            }
            [$course, $cm] = get_course_and_cm_from_cmid($sitsassign->get('cmid'));
            mtrace("Begin Marks upload {$course->shortname} and assignment {$sitsassign->get('sitsref')}");
            $grades = $sitsassign->get_queued_grades_for_export();
            if (count($grades['grades']) == 0) {
                continue;
            }
            // The batch is the number of grades to export.
            if ($batchgrades == -1) {
                $batchgrades = count($grades['grades']);
            }
            $batches = array_chunk($grades['grades'], $batchgrades);
            $pagetotal = count($batches);
            $pagecount = 0;
            foreach ($batches as $batch) {
                $pagecount++;
                $grades['assignment']['page'] = get_string('poft', 'local_solsits', ['page' => $pagecount, 'total' => $pagetotal]);
                $grades['grades'] = $batch;
                // In the test, mock get_client and set the response to whatever we want.
                $client = $this->get_client();
                $this->export_grades($grades, $client, $sitsassign);
            }
            mtrace("End Marks upload {$course->shortname} and assignment {$sitsassign->get('sitsref')}");
        }
        \core\task\manager::clear_static_caches();
    }

    /**
     * Export grades (or subset) for assignment
     *
     * @param array $grades ['module', 'assignment', 'unitleader', 'grades']
     * @param ais_client $client
     * @param sitsassign $sitsassign
     * @return boolean
     */
    private function export_grades(array $grades, ais_client $client, sitsassign $sitsassign): bool {
        global $DB;
        // Post grades to SITS and receive response.
        $start = time();
        $response = $client->export_grades($grades);
        $totaltime = time() - $start;
        mtrace("Request took {$totaltime} seconds");
        if (!$response) {
            mtrace("Error! unable to export grades for {$sitsassign->get('sitsref')}");
            return false;
        }
        // Update grade records with individual responses.
        if ($client->errno == 0) {
            $response = json_decode($response);
        } else {
            mtrace("The following error has been received: {$client->error} ({$client->errno})");
            return false;
        }
        $sitsref = $response->sitsref;
        // Check we're getting the correct assignment info back.
        if ($sitsref != $sitsassign->get('sitsref')) {
            if ($sitsref == '') {
                mtrace("Sitsref not returned in result. Presumed timeout.");
                foreach ($grades['grades'] as $grade) {
                    $gradeitem = $sitsassign->get_grade($grade['moodlestudentid']);
                    if (!$gradeitem) {
                        // This shouldn't happen.
                        mtrace("Grade item not found for userid({$grade['moodlestudentid']}) in local_solsits_assign_grades");
                        continue;
                    }
                    $gradeitem->message = get_string('muptimeoutmessage', 'local_solsits');
                    $gradeitem->response = ais_client::TIMEOUT;
                    $sitsassign->update_grade($gradeitem);
                }
            } else {
                mtrace("Something has gone horribly wrong with '{$sitsref}' trying to update {$sitsassign->get('sitsref')}");
            }
            return false;
        }
        $grades = $response->grades;
        if ($response->status == 'FAILED') {
            mtrace("- FAILED ($response->errorcode). {$response->message}");
        }
        foreach ($grades as $grade) {
            $gradeitem = $sitsassign->get_grade($grade->moodlestudentid);
            if (!$gradeitem) {
                // This shouldn't happen.
                mtrace("Grade item not found for userid({$grade->moodlestudentid}) in local_solsits_assign_grades");
                continue;
            }
            $gradeitem->message = $grade->message;
            $gradeitem->response = $grade->response;
            $sitsassign->update_grade($gradeitem);
            $student = $DB->get_record('user', ['id' => $grade->moodlestudentid]);
            if ($response->status == ais_client::FAILED) {
                // Don't output individual results.
                continue;
            }
            if ($grade->response == ais_client::SUCCESS) {
                mtrace("- {$student->firstname} {$student->lastname} " .
                "({$student->idnumber}): SUCCESS");
            } else if ($grade->response == ais_client::FAILED) {
                mtrace("- {$student->firstname} {$student->lastname} " .
                "({$student->idnumber}): FAILED - {$grade->message}");
            } else {
                mtrace("An invalid response was received {$grade->response}");
            }
        }
        return true;
    }
}

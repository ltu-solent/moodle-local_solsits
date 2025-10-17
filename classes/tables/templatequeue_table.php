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
 * Template queue table
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\tables;

use core\lang_string;
use core\output\html_writer;
use core\url;
use core_table\sql_table;
use local_solsits\helper;
use local_solsits\soltemplate;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/tablelib.php");

/**
 * Table to list courses that haven't yet had the template applied.
 */
class templatequeue_table extends sql_table {
    /**
     * Set up the page and sql
     *
     * @param string $uniqueid
     * @param array $filters
     */
    public function __construct($uniqueid, $filters) {
        global $DB;
        parent::__construct($uniqueid);
        $columns = [
            'id' => 'id',
            'coursename' => new lang_string('coursename', 'local_solsits'),
            'shortname' => new lang_string('shortname', 'local_solsits'),
            'startdate' => new lang_string('startdate', 'local_solsits'),
            'enddate' => new lang_string('enddate', 'local_solsits'),
            'visibility' => new lang_string('visibility', 'local_solsits'),
            'timecreated' => new lang_string('timecreated'),
            'pagetype' => new lang_string('pagetype', 'local_solsits'),
            'session' => new lang_string('session', 'local_solsits'),
            'allocatedtemplate' => new lang_string('allocatedtemplate', 'local_solsits'),
            'info' => new lang_string('info', 'local_solsits'),
        ];

        $this->define_columns(array_keys($columns));
        $this->define_headers(array_values($columns));
        $this->collapsible(false);

        // Filter validation.
        $pagetype = $filters['pagetype'] ?? '';
        $pagetypemenu = helper::get_pagetypes_menu();
        if ($pagetype != '') {
            if (!isset($pagetypemenu[$pagetype])) {
                $pagetype = '';
            }
        }
        $session = $filters['session'] ?? '';
        $sessionmenu = helper::get_session_menu();
        if ($session != '') {
            if (!isset($sessionmenu[$session])) {
                $session = '';
            }
        }
        $selectedcourses = $filters['selectedcourses'] ?? [];

        // Ensure the filter form elements are replicated for paging links.
        $baseurl = new url("/local/solsits/templatequeue.php");
        $baseurl->param('pagetype', $pagetype);
        $baseurl->param('session', $session);
        foreach ($selectedcourses as $selectedcourse) {
            // The moodle_url object doesn't accept params like selectedcourses[]=12&selectedcourses[]=13,
            // so inject the courseid as part of the key name to make unique params.
            $baseurl->param('selectedcourses[' . $selectedcourse . ']', $selectedcourse);
        }
        $this->define_baseurl($baseurl);

        [$select, $from, $where, $params] = soltemplate::get_templateapplied_sql($pagetype, $session);
        if ($selectedcourses) {
            [$insql, $inparams] = $DB->get_in_or_equal($selectedcourses, SQL_PARAMS_NAMED);
            if ($where != '') {
                $where .= " AND c.id $insql ";
            } else {
                $where = "c.id $insql";
            }
            $params += $inparams;
        }
        $this->set_sql($select, $from, $where, $params);
    }

    /**
     * Link to allocated template, if it exists
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_allocatedtemplate($row) {
        global $DB;
        $templatecourses = soltemplate::get_records(['pagetype' => $row->pagetype, 'session' => $row->academic_year]);
        $templatecourse = reset($templatecourses);
        $link = get_string('notemplate', 'local_solsits');
        if ($templatecourse) {
            $template = $DB->get_record('course', ['id' => $templatecourse->get('courseid')]);
            if ($template) {
                $link = html_writer::link(
                    new url('/course/view.php', ['id' => $template->id]),
                    $template->fullname
                );
            }
        }
        return $link;
    }

    /**
     * Link to the course
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_coursename($row) {
        $link = html_writer::link(
            new url('/course/view.php', ['id' => $row->id]),
            $row->fullname
        );
        return $link;
    }

    /**
     * Output course enddate column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_enddate($row) {
        if ($row->enddate) {
            return userdate($row->enddate);
        }
        return '';
    }

    /**
     * Output info column, specifying reasons why a template might not be able to be applied.
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_info($row) {
        global $DB;
        $infos = [];
        if ($row->visible) {
            $infos[] = get_string('error:coursevisible', 'local_solsits', '');
        }
        $activities = $DB->get_records('course_modules', ['course' => $row->id]);
        if (count($activities) > 1) {
            // Course is always created with a forum.
            $infos[] = get_string('error:courseedited', 'local_solsits', '');
        }
        $enrolledusers = enrol_get_course_users($row->id);
        if (count($enrolledusers) > 0) {
            $infos[] = get_string('error:usersenrolledalready', 'local_solsits', '');
        }
        return html_writer::alist($infos);
    }

    /**
     * Output academic year column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_session($row) {
        return $row->academic_year;
    }

    /**
     * Output course startdate column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_startdate($row) {
        return userdate($row->startdate);
    }

    /**
     * Output course creation time
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_timecreated($row) {
        return userdate($row->timecreated);
    }

    /**
     * Output course startdate column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_visibility($row) {
        return $row->visible ? get_string('visible', 'local_solsits') : get_string('notvisible', 'local_solsits');
    }
}

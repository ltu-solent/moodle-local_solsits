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
 * Solassignment queue table
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\tables;

use core_user;
use Exception;
use html_writer;
use lang_string;
use local_solsits\helper;
use moodle_url;
use stdClass;
use table_sql;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

/**
 * Table of assignments from SITS and references to course modules.
 */
class solassign_table extends table_sql {
    /**
     * Scales available
     *
     * @var array
     */
    private array $scales;
    /**
     * Constructor
     *
     * @param string $uniqueid
     * @param array $filters Filters to apply to the query.
     */
    public function __construct($uniqueid, $filters) {
        global $DB;
        parent::__construct($uniqueid);
        $this->useridfield = 'modifiedby';
        $this->scales = helper::get_scales_menu();
        $columns = [
            'id',
            'course',
            'title',
            'sitsref',
            'cmid',
            'reattempt',
            'weighting',
            'duedate',
            'visible',
            'scale',
            'grademarkexempt',
            'availablefrom',
            'timemodified',
            'actions',
        ];

        $columnheadings = [
            'id',
            new lang_string('coursename', 'local_solsits'),
            new lang_string('assignmenttitle', 'local_solsits'),
            new lang_string('sitsreference', 'local_solsits'),
            new lang_string('cmid', 'local_solsits'),
            new lang_string('reattempt', 'local_solsits'),
            new lang_string('weighting', 'local_solsits'),
            new lang_string('duedate', 'local_solsits'),
            new lang_string('visibility', 'local_solsits'),
            new lang_string('scale', 'local_solsits'),
            new lang_string('grademarkexempt', 'local_solsits'),
            new lang_string('availablefrom', 'local_solsits'),
            new lang_string('timemodified', 'local_solsits'),
            new lang_string('actions', 'local_solsits'),
        ];
        $this->define_columns($columns);
        $this->define_headers($columnheadings);
        $this->no_sorting('actions');
        $this->sortable(true, 'sitsref', SORT_ASC);
        $urlparams = [
            'showerrorsonly' => $filters['showerrorsonly'],
            'session' => $filters['session'],
        ];
        $sc = [];
        $baseurl = new moodle_url('/local/solsits/manageassignments.php', $urlparams);
        foreach ($filters['selectedcourses'] as $key => $selectedcourse) {
            $sc['selectedcourses[' . $key . ']'] = $selectedcourse;
        }
        $baseurl->params($sc);
        $this->define_baseurl($baseurl);
        $wherestring = '1=1';
        // Do left joins in case the course or activities have been deleted.
        $from = "{local_solsits_assign} ssa
        LEFT JOIN {course} c ON c.id = ssa.courseid
        LEFT JOIN {course_modules} cm ON cm.id = ssa.cmid";
        $params = [];
        $wheres = [];
        if ($filters['session']) {
            $sessionmenu = helper::get_session_menu();
            if (in_array($filters['session'], $sessionmenu)) {
                $like = $DB->sql_like('c.shortname', ':session');
                $wheres[] = $like;
                $params['session'] = '%' . $DB->sql_like_escape($filters['session']) . '%';
            }
        }
        if ($filters['selectedcourses']) {
            [$insql, $inparams] = $DB->get_in_or_equal($filters['selectedcourses'], SQL_PARAMS_NAMED);
            $wheres[] = "c.id $insql";
            $params += $inparams;
        }
        if ($filters['showerrorsonly']) {
            // Only show as an error if this is a hidden first attempt (don't count reattempts), or if
            // the course module has been deleted, or the due date has not been set.
            $wheres[] = "(cm.id IS NULL OR ssa.duedate = 0 OR ((cm.visible = 0 OR c.visible = 0) AND ssa.reattempt = 0))";
        }
        if (!empty($wheres)) {
            $wherestring = join(' AND ', $wheres);
        }
        $this->set_sql('ssa.*, c.fullname, c.idnumber course_idnumber, cm.visible cmvisible, c.visible cvisible',
            $from, $wherestring, $params);
    }

    /**
     * Output actions column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_actions($row) {
        $links = [];
        $params = ['id' => $row->id];
        if ($row->cmid > 0) {
            try {
                [$course, $cm] = get_course_and_cm_from_cmid($row->cmid, 'assign');
            } catch (Exception $ex) {
                // Only allow actions if the assignment has already been deleted.
                $params['action'] = 'delete';
                $delete = new moodle_url('/local/solsits/manageassignments.php', $params);
                $links[] = html_writer::link($delete, get_string('delete'));

                $params['action'] = 'recreate';
                $recreate = new moodle_url('/local/solsits/manageassignments.php', $params);
                $links[] = html_writer::link($recreate, get_string('recreate', 'local_solsits'));
            }
        } else {
            // Only allow delete if the assignment has not yet been created.
            $params['action'] = 'delete';
            $delete = new moodle_url('/local/solsits/manageassignments.php', $params);
            $links[] = html_writer::link($delete, get_string('delete'));
        }

        return join(" | ", $links);
    }

    /**
     * Output available from date column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_availablefrom($row) {
        if ($row->availablefrom > 0) {
            return userdate($row->availablefrom);
        }
        return get_string('immediately', 'local_solsits');
    }

    /**
     * Coursemodule info
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_cmid($row) {
        if ($row->cmid > 0) {
            try {
                [$course, $cm] = get_course_and_cm_from_cmid($row->cmid, 'assign');
                $url = new moodle_url('/mod/assign/view.php', ['id' => $row->cmid]);
                return html_writer::link($url, $cm->name);
            } catch (Exception $ex) {
                return get_string('nolongerexists', 'local_solsits');
            }
        } else {
            return '-';
        }
    }

    /**
     * Course info
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_course($row) {
        $url = new moodle_url('/course/view.php', ['id' => $row->courseid]);
        return html_writer::link($url, $row->fullname) . '<br><small>' . $row->course_idnumber . '</small>';
    }

    /**
     * Output assignment duedate column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_duedate($row) {
        if ($row->duedate > 0) {
            return userdate($row->duedate);
        }
        return '-';
    }

    /**
     * Output grademark exempt column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_grademarkexempt($row) {
        return ($row->grademarkexempt) ? get_string('yes') : get_string('no');
    }

    /**
     * Output scale used
     *
     * @param stdClass $row
     * @return string HTML for row's column
     */
    public function col_scale($row) {
        $config = get_config('local_solsits');
        if (empty($row->scale)) {
            return '';
        }
        if (!isset($config->{$row->scale})) {
            return ucwords($row->scale);
        }
        return ucwords($this->scales[$config->{$row->scale}]);
    }

    /**
     * Output timemodified column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_timemodified($row) {
        return userdate($row->timemodified, get_string('strftimedatetimeshort', 'core_langconfig'));
    }

    /**
     * Output assignment title column. Links title if activity exists.
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_title($row) {
        if (!$row->cmid) {
            return $row->title;
        }
        return html_writer::link(new moodle_url('/mod/assign/view.php', ['id' => $row->cmid]), $row->title);
    }

    /**
     * Output visible column. Combines course and course module visibility.
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_visible($row) {
        // Perhaps do an eye and eye-slash.
        $visibility = ($row->cmvisible + $row->cvisible);
        if ($visibility < 2) {
            return get_string('notvisible', 'local_solsits');
        }
        return get_string('visible', 'local_solsits');
    }

    /**
     * Output weighting column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_weighting($row) {
        return $row->weighting . '%';
    }
}

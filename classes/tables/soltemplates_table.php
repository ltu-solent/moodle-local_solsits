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
 * Sol templates listing table
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_solsits\tables;

use core_user;
use html_writer;
use lang_string;
use moodle_url;
use table_sql;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/tablelib.php");

/**
 * Table for listing soltemplate persistent records.
 */
class soltemplates_table extends table_sql {
    /**
     * Constructor to set up table
     *
     * @param string $uniqueid
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $this->useridfield = 'modifiedby';
        $columns = [
            'id',
            'templatename',
            'pagetype',
            'session',
            'enabled',
            'usermodified',
            'timemodified',
            'actions'
        ];

        $columnheadings = [
            'id',
            new lang_string('templatename', 'local_solsits'),
            new lang_string('pagetype', 'local_solsits'),
            new lang_string('session', 'local_solsits'),
            new lang_string('enabled', 'local_solsits'),
            new lang_string('modifiedby', 'local_solsits'),
            new lang_string('lastmodified', 'local_solsits'),
            new lang_string('actions', 'local_solsits'),
        ];

        $this->define_columns($columns);
        $this->define_headers($columnheadings);
        $this->no_sorting('actions');
        $this->sortable(true, 'session', SORT_DESC);
        $this->collapsible(false);

        $this->define_baseurl(new moodle_url("/local/solsits/managetemplates.php"));
        $where = '1=1';
        $this->set_sql('*', "{local_solsits_templates}", $where);
    }

    /**
     * Output actions column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_actions($row) {
        $params = ['action' => 'edit', 'id' => $row->id];
        $edit = new moodle_url('/local/solsits/edittemplate.php', $params);
        $html = html_writer::link($edit, get_string('edit'));

        $params['action'] = 'delete';
        $delete = new moodle_url('/local/solsits/edittemplate.php', $params);
        $html .= " " . html_writer::link($delete, get_string('delete'));
        return $html;
    }

    /**
     * Output enabled column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_enabled($row) {
        return ($row->enabled) ? new lang_string('enabled', 'local_solsits')
            : new lang_string('notenabled', 'local_solsits');
    }

    /**
     * Output pagetype column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_pagetype($row) {
        return ucfirst($row->pagetype);
    }

    /**
     * Output session column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_session($row) {
        return $row->session;
    }

    /**
     * Output templatename column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_templatename($row) {
        global $DB;
        $course = $DB->get_field('course', 'fullname', ['id' => $row->courseid]);
        if (!$course) {
            return get_string('checkcoursedeleted', 'local_solsits');
        }
        $url = new moodle_url('/course/view.php', ['id' => $row->courseid]);
        return html_writer::link($url, $course);
    }

    /**
     * Output usermodified column
     *
     * @param stdClass $row
     * @return string HTML for row's column value
     */
    public function col_usermodified($row) {
        $modifiedby = core_user::get_user($row->usermodified);
        if (!$modifiedby || $modifiedby->deleted) {
            return get_string('deleteduser', 'local_solsits');
        }
        return fullname($modifiedby);
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

}

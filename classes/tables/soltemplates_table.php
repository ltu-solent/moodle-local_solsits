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

require_once("$CFG->libdir/tablelib.php");

class soltemplates_table extends table_sql {
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $this->useridfield = 'modifiedby';
        // $this->pagetypes = \local_solsits\api::pagetypes_menu();
        // $this->systemroles = \local_solsits\api::availableroles(CONTEXT_SYSTEM);
        // $this->courseroles = \local_solsits\api::availableroles(CONTEXT_COURSE);
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
        $this->sortable(true, 'id');
        $this->collapsible(false);

        $this->define_baseurl(new moodle_url("/local/solsits/managetemplates.php"));
        $where = '1=1';
        $this->set_sql('*', "{local_solsits_templates}", $where);
    }

    public function col_actions($col) {
        $params = ['action' => 'edit', 'id' => $col->id];
        $edit = new moodle_url('/local/solsits/edittemplate.php', $params);
        $html = html_writer::link($edit, get_string('edit'));

        $params['action'] = 'delete';
        $delete = new moodle_url('/local/solsits/edittemplate.php', $params);
        $html .= " " . html_writer::link($delete, get_string('delete'));
        return $html;
    }

    public function col_enabled($col) {
        return ($col->enabled) ? new lang_string('enabled', 'local_solsits')
            : new lang_string('notenabled', 'local_solsits');
    }

    public function col_pagetype($col) {
        return ucfirst($col->pagetype);
    }

    public function col_session($col) {
        return $col->session;
    }

    public function col_templatename($col) {
        global $DB;
        $course = $DB->get_field('course', 'fullname', ['id' => $col->courseid]);
        return $course;
    }

    public function col_usermodified($col) {
        $modifiedby = core_user::get_user($col->usermodified);
        if (!$modifiedby || $modifiedby->deleted) {
            return get_string('deleteduser', 'local_solsits');
        }
        return fullname($modifiedby);
    }

    public function col_timemodified($col) {
        return userdate($col->timemodified, get_string('strftimedatetimeshort', 'core_langconfig'));
    }

}


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
 * Templates content typwe
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use core\persistent;
use lang_string;

/**
 * Soltemplate persistent record.
 */
class soltemplate extends persistent {
    /**
     * Table name for templates.
     */
    const TABLE = 'local_solsits_templates';
    /**
     * Module pagetype
     */
    public const MODULE = 'module';
    /**
     * Course pagetype
     */
    public const COURSE = 'course';


    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'pagetype' => [
                'type' => PARAM_ALPHA,
                'default' => self::MODULE,
                'options' => [
                    self::MODULE,
                    self::COURSE
                ]
            ],
            'courseid' => [
                'type' => PARAM_INT,
            ],
            'session' => [
                'type' => PARAM_TEXT
            ],
            'enabled' => [
                'type' => PARAM_BOOL,
                'default' => false
            ]
        ];
    }

    /**
     * Validate pagetypes
     *
     * @param string $value Expected pagetype
     * @return bool|lang_string True success; string on failure.
     */
    protected function validate_pagetype($value) {
        $valid = helper::get_pagetypes_menu();
        if (!in_array($value, $valid)) {
            return new lang_string('invalidpagetype', 'local_solsits');
        }
        return true;
    }

    /**
     * Validate the session
     *
     * @param string $value The expected format is 2023/24 - but check.
     * @return bool|lang_string True success; string on failure.
     */
    protected static function validate_session($value) {
        $options = helper::get_session_menu();
        if (!isset($options[$value])) {
            return new lang_string('invalidsession', 'local_solsits');
        }
        return true;
    }

    /**
     * Validate the courseid
     *
     * @param string $value The courseid
     * @return bool|lang_string True success; string on failure.
     */
    protected function validate_courseid($value) {
        global $DB;
        if (!is_numeric($value)) {
            return new lang_string('invalidcourseid', 'local_solsits');
        }
        if (!$DB->record_exists('course', ['id' => $value])) {
            return new lang_string('invalidcourseid', 'local_solsits');
        }
        return true;
    }

    /**
     * Get courses and their templateapplied status
     *
     * @param string $pagetype course or module
     * @param string $session
     * @param integer $templateapplied
     * @return array Records details the relevant customfields for courses.
     */
    public static function get_templateapplied_records($pagetype = '', $session = '', $templateapplied = 0) {
        global $DB;
        // Search the customfields for any records that match these variables.
        // Pages that don't have the pagetype, session and templateapplied field set are not interesting to us.
        // Get the pagetype, academic_year and templateapplied field ids
        // I've broken this up into two queries, just to make it a bit easier to read.
        [$insql, $inparams] = $DB->get_in_or_equal(['pagetype', 'academic_year', 'templateapplied']);
        $sql = "SELECT cf.id, cf.shortname
            FROM {customfield_field} cf
            JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.name = 'Student Records System'
            WHERE cf.shortname {$insql}";
        $fields = $DB->get_records_sql($sql, $inparams);

        $params = [];
        foreach ($fields as $field) {
            $params[$field->shortname . 'id'] = $field->id;
        }
        $where = [];
        if ($pagetype) {
            $where[] = 'cfdpt.value = :pagetypevalue';
            $params['pagetypevalue'] = $pagetype;
        }
        if ($session) {
            $where[] = 'cfday.value = :sessionvalue';
            $params['sessionvalue'] = $session;
        }
        if ($templateapplied !== null) {
            $where[] = 'cfdt.value = :templateappliedvalue';
            $params['templateappliedvalue'] = $templateapplied;
        }
        $where = count($where) > 0 ? ' WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT c.id, c.visible, c.shortname,
            cfdpt.value pagetype, cfdt.value templateapplied, cfday.value academic_year
        FROM {course} c
        JOIN {customfield_data} cfdpt ON cfdpt.instanceid = c.id AND cfdpt.fieldid = :pagetypeid
            AND cfdpt.value IN ('module', 'course')
        JOIN {customfield_data} cfdt ON cfdt.instanceid = c.id AND cfdt.fieldid = :templateappliedid
        JOIN {customfield_data} cfday ON cfday.instanceid = c.id AND cfday.fieldid = :academic_yearid
        {$where}";

        $records = $DB->get_records_sql($sql, $params);
        return $records;
    }
}

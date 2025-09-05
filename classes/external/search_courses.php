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

namespace local_solsits\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_solsits\helper;

/**
 * Class search_courses
 *
 * @package    local_solsits
 * @copyright  2024 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_courses extends external_api {
    /**
     * Search courses parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'Search string'),
            'session' => new external_value(PARAM_TEXT, 'Only include courses in session in results'),
        ]);
    }

    /**
     * Search courses
     *
     * @param string $query
     * @param string $session
     * @return array
     */
    public static function execute($query, $session): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), ['session' => $session, 'query' => $query]);

        $select = "SELECT id courseid, CONCAT(shortname, ': ', fullname) label FROM {course} ";
        $wheres = [];
        $qparams = [];
        if ($params['session']) {
            $sessionmenu = helper::get_session_menu();
            if (in_array($params['session'], $sessionmenu)) {
                $like = $DB->sql_like('shortname', ':session');
                $wheres[] = $like;
                $qparams['session'] = '%' . $DB->sql_like_escape($params['session']) . '%';
            }
        }
        if ($params['query']) {
            $likeshortname = $DB->sql_like("shortname", ':shortname', false, false);
            $likefullname = $DB->sql_like("fullname", ':fullname', false, false);
            $qparams['shortname'] = '%' . $DB->sql_like_escape($params['query']) . '%';
            $qparams['fullname'] = '%' . $DB->sql_like_escape($params['query']) . '%';
            $wheres[] = " ($likeshortname OR $likefullname) ";
        }

        $where = " WHERE 1=1 ";
        if (!empty($wheres)) {
            $where = " WHERE " . join(' AND ', $wheres);
        }

        $courses = $DB->get_records_sql($select . $where, $qparams, 0, 100);
        return $courses;
    }

    /**
     * Defines the returned structure of the array.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'courseid'),
                'label' => new external_value(PARAM_RAW, 'User friendly label - Shortname'),
            ])
        );
    }
}

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
 * Course selector adaptor for auto-complete form element.
 *
 * @module     local_solsits/form-course-selector
 * @copyright  2016 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['core/ajax', 'jquery'], function(ajax, $) {

    return {
        // Public variables and functions.
        processResults: function(selector, data) {
            // Mangle the results into an array of objects.
            var results = [];
            var i = 0;
            var excludelist = String($(selector).data('exclude')).split(',');

            for (i = 0; i < data.length; i++) {
                if (excludelist.indexOf(String(data[i].courseid)) === -1) {
                    results.push({value: data[i].courseid, label: data[i].label});
                }
            }
            return results;
        },

        transport: function(selector, query, success, failure) {
            let currentcourses = document.querySelector('#id_currentcourses');
            var promises = null;
            if (!currentcourses) {
                currentcourses = false;
            } else {
                currentcourses = currentcourses.checked;
            }
            if (typeof query === "undefined") {
                query = '';
            }

            var searchargs = {
                query: query,
                currentcourses: currentcourses
            };

            var calls = [{
                methodname: 'local_solsits_search_courses', args: searchargs
            }];

            // Go go go!
            promises = ajax.call(calls);
            $.when.apply($.when, promises).done(function(data) {
                success(data);
            }).fail(failure);
        }
    };
});

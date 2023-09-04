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
 * Simple ajax to test connection
 *
 * @module     local_solsits/ais-testconnection
 * @author     Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright  2023 Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Prefetch from 'core/prefetch';
import Notification from 'core/notification';

export const init = () => {
    Prefetch.prefetchStrings('local_solsits', ['success', 'failure']);
    document.addEventListener('click', e => {
        if (e.target.id == 'ais_testconnection') {
            e.preventDefault();
            e.target.disable = true;
            const resultdiv = document.querySelector('#ais_testconnection_response');
            Ajax.call([{
                methodname: 'local_solsits_ais_testconnection',
                'args': {'something': 'interesting'}
            }])[0].done(function(data) {
                if (data.result == 200) {
                    e.target.disable = false;
                    resultdiv.innerHTML = '<i class="fa fa-solid fa-check-circle" style="color: var(--success)"></i> Success';
                } else {
                    e.target.disable = false;
                    resultdiv.innerHTML = '<i class="fa fa-solid fa-times-circle" style="color: var(--danger)"></i> Failure: '
                        + data.result;
                }
            }).fail(Notification.exception);
        }
    });
};

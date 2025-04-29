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
 * TODO describe module assignments
 *
 * @module     local_solsits/assignments
 * @copyright  2025 Southampton Solent University {@link https://www.solent.ac.uk}
 * @author     Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import {add as addToast} from 'core/toast';


export const init = () => {
    const duedates = document.querySelectorAll('[data-action="sol-new-duedate"]');
    duedates.forEach(duedate => {
        duedate.addEventListener('click', newDueDate);
    });
};

const newDueDate = (e) => {
    e.preventDefault();
    const element = e.currentTarget;
    const modal = new ModalForm({
        formClass: 'local_solsits\\forms\\new_duedate_form',
        args: {
            sitsref: element.getAttribute('data-sitsref'),
        },
        modalConfig: {
            title: getString('updateduedate', 'local_solsits', {
                title: element.getAttribute('data-title')
            }),
        },
        saveButtonText: getString('sendmessage', 'local_solsits'),
        returnFocus: element,
    });
    modal.addEventListener(modal.events.FORM_SUBMITTED, event => {
        const data = event.detail;
        if (data.error) {
            addToast(getString(data.error, 'local_solsits'));
        } else {
            addToast(getString('duedatethankyou', 'local_solsits'));
        }

    });
    modal.show();
};

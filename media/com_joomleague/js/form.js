/**
 * @package     Klucon
 * @subpackage  com_joomleague
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz)
 * @license     GNU General Public License version 2 or later
 */

(function () {
	'use strict';

	const syncEditors = () => {
		if (!window.Joomla || !Joomla.editors || !Joomla.editors.instances) {
			return;
		}

		Object.keys(Joomla.editors.instances).forEach((id) => {
			const editor = Joomla.editors.instances[id];

			if (editor && typeof editor.save === 'function') {
				editor.save();
			}

			if (editor && typeof editor.getValue === 'function') {
				const textarea = document.getElementById(id);

				if (textarea) {
					textarea.value = editor.getValue();
				}
			}
		});
	};

	document.addEventListener('submit', (event) => {
		if (!event.target || event.target.name !== 'adminForm') {
			return;
		}

		syncEditors();
	}, true);
}());

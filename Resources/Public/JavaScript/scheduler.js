/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Crontab module: list-view interactions
 *
 * Handles the "check all" button per task group. Group collapse, tooltips,
 * modals and multi-record selection are provided by TYPO3 core / Bootstrap 5
 * and require no extra JS.
 */
class CrontabScheduler {
  constructor() {
    document.addEventListener('click', (event) => {
      const trigger = event.target.closest('.checkall');
      if (trigger === null) {
        return;
      }
      event.preventDefault();
      this.toggleAllCheckboxes(trigger);
    });
  }

  /**
   * Toggle all task checkboxes inside the surrounding task-group panel.
   *
   * @param {HTMLElement} trigger element that was clicked (the checkall button)
   */
  toggleAllCheckboxes(trigger) {
    const group = trigger.closest('.tx_scheduler_mod1_table');
    if (group === null) {
      return;
    }
    const checkboxes = group.querySelectorAll('input[type="checkbox"]');
    const shouldCheck = Array.from(checkboxes).some((cb) => !cb.checked);
    checkboxes.forEach((cb) => {
      cb.checked = shouldCheck;
    });
  }
}

export default new CrontabScheduler();

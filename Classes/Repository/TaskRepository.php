<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Repository;

use Helhum\TYPO3\Crontab\Error\TaskNotFound;
use Helhum\TYPO3\Crontab\Task\TaskDefinition;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Helmut Hummel <info@helhum.io>
 *  All rights reserved
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class TaskRepository
{
    /**
     * @var array
     */
    private $taskConfiguration;

    public function __construct(array $taskConfiguration = null)
    {
        $this->taskConfiguration = $taskConfiguration ?? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crontab'];
    }

    public function getGroupedTasks(): array
    {
        $groupedTasks = [];
        foreach ($this->taskConfiguration as $identifier => $taskConfig) {
            $groupName = $taskConfig['group'] ?? 'N/A';
            $groupedTasks[$groupName][$identifier] = TaskDefinition::createFromConfig($identifier, $taskConfig);
        }

        return $groupedTasks;
    }

    public function hasTask(string $identifier): bool
    {
        return isset($this->taskConfiguration[$identifier]);
    }

    public function findByIdentifier(string $identifier): TaskDefinition
    {
        if (!isset($this->taskConfiguration[$identifier])) {
            throw new TaskNotFound(sprintf('Task with identifier "%s" is not defined', $identifier), 1542737003);
        }

        return TaskDefinition::createFromConfig($identifier, $this->taskConfiguration[$identifier]);
    }
}

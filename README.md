# TYPO3 Command scheduling (crontab)

## Disclaimer

This package is made with the intention to at some point maybe replace the current functionality
of TYPO3 scheduler extension. 
It is in early development stage, which means that not all features (especially UI wise) 
are implemented yet, but the implemented functionality is know to work reliably.
Currently this package does not work in Windows environments, as no POSIX functions (`posix_getpgid`) are available,
which are required to check whether the processes are actually running.

## Key features
* Tasks are configured via configuration, thus are deployable as code
* Any TYPO3 Console command and any shell script can be added as task
* Whether a task is show as running in the UI, always reflects the real state of the process. 
No more marking tasks not running in case they crash with an error.
* Stopping a task via the UI actually stops the process, instead of just marking it as not running.
* The crontab command (`crontab:run`) to execute the scheduled tasks can optionally be a long running process,
constantly looking for tasks to be executed (the option `--timeout`) and allows parallel execution of
of different due tasks (the option `--forks`)
* Tasks can be configured to be re-scheduled for immediate execution in case they failed.

## Installation

1. Add the extension to your TYPO3 Project: `composer require helhum/typo3-crontab`
2. Configure the system cron to run the command: `/path/to/vendor/bin/typo3cms crontab:run`
3. Configure tasks in your TYPO3 configuration file(s)

## Configuring schedules for commands (and scheduler tasks)

Configuring schedules it purely done through configuration (aka. `TYPO3_CONF_VARS`),
typically in `LocalConfiguration.php` or `AdditionalConfiguration.php`

This means:
1. Extensions could add tasks as well (through ext_localconf.php files)
1. It is possible to add different tasks in different environments (by providing different configuration for different environments)
1. Deploy same tasks in all environments

Example (in yaml notation):

```yaml
EXTCONF:
    crontab:
        update_refindex:
            group: 'Demo'
            multiple: false
            retryOnFailure: false
            title: 'Updates reference index'
            cron: '*/30 * * * *'
            process:
                type: command
                command: cleanup:updatereferenceindex
                arguments:
                    - '--quiet'
                    - '--no-interaction'
        shell_script:
            group: 'Demo'
            multiple: false
            retryOnFailure: true
            title: 'Executing a shell script'
            description: 'Path relative to composer root or absolute'
            cron: '*/12 * * * *'
            process:
                type: script
                script: res/scripts/test.sh
                arguments:
                    - 'foo'
        php_script:
            group: 'Demo'
            multiple: false
            retryOnFailure: false
            title: 'Executing a php script'
            description: '"@php" placeholder can be used to reference the PHP binary used by the crontab:run command'
            cron: '*/42 * * * *'
            process:
                type: script
                script: '@php res/scripts/test.php'
                arguments:
                    - 'bar'
        test:
            group: 'Demo'
            multiple: true
            retryOnFailure: false
            description: 'Just for demo purposes'
            cron: '*/1 * * * *'
            process:
                type: scheduler
                className: TYPO3\CMS\Scheduler\Example\TestTask
                arguments:
                    email: test@test.test
        solr_indexer:
            group: 'Demo'
            multiple: false
            retryOnFailure: false
            description: 'Run early, run often'
            cron: '*/5 * * * *'
            process:
                type: scheduler
                className: ApacheSolrForTypo3\Solr\Task\IndexQueueWorkerTask
                arguments:
                    site: 1
                    documentsToIndexLimit: 20
                    forcedWebRoot: null
```

## Current state

* There is no user interface for adding tasks or removing tasks. You have to provide the configuration manually.
* TYPO3 Console is a hard requirement
* TYPO3 9.5.x is a hard requirement. This may change later on in the development process (allowing TYPO3 8.7).
* The extension only comes for Composer installations. TYPO3 in non Composer mode is not supported.
* start times, end times for scheduled tasks do not exist. If you have such requirement,
* The command to execute scheduled tasks is `crontab:run`
you could build some automation around the `crontab:schedule` command to automate enabling/disabling tasks at appropriate times.

## Concepts (especially in relation to TYPO3 scheduler)

### Everything is a command or script
The main purpose of this extension is to execute different units of work as background task on a regular schedule.
Similar to Unix crontab, a unit of work is limited to be a TYPO3 Console command or shell script. To provide backwards compatibility,
Scheduler tasks are wrapped into a special command, so that they can be used as unit of work, too.
So every command that is available as command in TYPO3 Console and every shell script, will be a valid unit of work to be scheduled.
Whether it makes sense to do schedule certain commands, is up to you to decide.

### Just a crontab
Just like with a Unix crontab, there is no possibility for defining start or end time or for setting a unit of work to only execute once.

It is possible to configure commands to be scheduled, but remove them from regular scheduled executions.
Similar to adding commands to your crontab but commenting them out, so that they are ignored.

Such commands will appear in the UI as "disabled".
On first deployment on a target system all configured commands are disabled and must be enabled to be scheduled,
either through the UI, or by using the `crontab:schedule` command.

## Security considerations
Since conceptually it is possible to execute any TYPO3 Console command, it becomes pretty clear, users with access to this module
must be considered to have access to the complete TYPO3 installation (system maintainer) and to an extent also to the underlying OS.
Just similar to having access to extension manager (which allows integrating PHP code) or install tool (where you can create admin users).

## Contribution
Every contribution is valuable. Please check it out and test it, give feedback by creating feature requests or suggestions,
create pull requests with change suggestions, reach out to me via [Twitter](https://twitter.com/helhum) or any other channel.

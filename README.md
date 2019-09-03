# TYPO3 Command scheduling (crontab)

## Disclaimer

This package is made with the intention to at some point replace the current functionality
in TYPO3 scheduler extension. 
It is in very early development stage, which means that not all features (especially UI wise) 
are implemented yet.
Also currently this package does not work in Windows environments, as no POSIX functions (`posix_getpgid`) are available
in this environment.

Testing and feedback is highly appreciated, but (as always) properly test the extension before relying on it for production use.

## Current state

* There is no user interface for adding tasks or removing tasks. You have to provide the configuration manually.
* TYPO3 Console is a hard requirement
* TYPO3 >=9.5.0 is a hard requirement. This may change later on in the development process (allowing TYPO3 8.7).
* The extension only comes for composer installation. TYPO3 in non composer mode is currently not supported.
* The command to execute scheduled tasks is `crontab:run`
* start times, end times for scheduled tasks do not exist and likely will never be implemented

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

## Configuring schedules for commands (and scheduler tasks)

Configuring schedules it purely done through configuration (aka. `TYPO3_CONF_VARS`).
This means:
1. Extensions could add schedules (through ext_localconf.php files)
1. It is possible to add different schedules in different environments (by providing different configuration for different environments)
1. Deploy same schedules in all environments

Example (in yaml notation):

```yaml
EXTCONF:
    crontab:
        update_refindex:
            group: 'Demo'
            multiple: false
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

## Contribution
Every contribution is valuable. Please check it out and test it, give feedback by creating feature requests or suggestions,
create pull requests with change suggestions, reach out to me via [Twitter](https://twitter.com/helhum) or any other channel.

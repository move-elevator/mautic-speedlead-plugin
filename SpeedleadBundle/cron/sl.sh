#!/bin/bash
# execute every hour
/usr/bin/php71 -d memory_limit=512M bin/console --env=prod speedlead:import-contacts -c-2hours -u-4hours > var/logs/cron__sl-import-contacts.log 2>&1;

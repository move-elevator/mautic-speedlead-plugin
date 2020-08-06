#!/bin/bash
# execute every hour
/usr/bin/php71 -d memory_limit=512M app/console speedlead:import-contacts -c-2hours -u-4hours --env=prod > var/logs/cron__sl-import-contacts.log 2>&1;

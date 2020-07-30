# mautic-speedlead-plugin
Plugin for Mautic which can import leads from speedlead to Mautic

## Requirements
Mautic version > 3.0

## Installation
* download zip from https://github.com/move-elevator/mautic-speedlead-plugin
* extract and move folder 'SpeedleadBundle' to directory
```path/to/mautic/plugins/```
* clear cache
* in Mautic, go to  cogwheel(upper right corner) > plugins and click 'install/update plugins'
* you should now see the plugin in the list

## Configuration
* click the plugin icon to open the configuration-form
* fill the fields to connect with your speedlead-installation
* in the tab 'Features' you can configure some specifications on how your imported leads should behave after the import, this is optional

## Run the plugin
### automatically
* if you configured the plugin to run automatically, you have to setup the cronjob that runs the underlying symfony-command
* you can take the script from ```path/to/mautic/plugins/SpeadleadBundle/cron/sl.sh``` and apply that to the cron-routine on your system
or you can take the symfony-command ```speedlead:import-contacts``` by itself and incorporate it by yourself. this step can differ per mautic-installation.
* for further instructions on how to setup cronjobs with mautic, please refer to  https://docs.mautic.org/en/setup/cron-jobs
* after that the plugin should run automatically
* to change the behavior, just go to your plugin config and disable the automatic import, no further steps needed
### manually
* you can always trigger the import manually via the mautic main-menu on the left
* after the plugin has been installed sucessfully, there should be a new menu item 'speedlead' on the bottom
* this runs the same logic that the command would run and after success, prints out how many reports from speedlead were handled
* this way you can always be sure, your reports (speedlead-wise) are in a good shape before they are imported to Mautic

##Features
* the command ```speedlead:import-contacts``` offers two optional options to customize how the filters for the speedlead-API to fetch the contacts
will behave:
```
-c, --createdBefore[=CREATEDBEFORE]  only get reports that were created before given string. [default: "now"]
-u, --updatedAfter[=UPDATEDAFTER]    only get reports that were updated after given string. [default: "now"]
```
* the value needs to be a string that also can be set in the constructor-method of a DateTime object
* refer to https://www.php.net/manual/de/class.datetime.php for instructions on how to set up those strings
* currently, this only works if you run the plugin automatically

# Infofizik Student Profile

Moodle plugin for tracking student progress in EGE physics preparation with Telegram bot integration

## Features

- Detailed student statistics and progress tracking
- Homework tracking with weekly deadlines
- Grade conversion to EGE primary and secondary scores
- Self-study and exam week management
- Student rating system based on solved problems
- Telegram bot integration for automated notifications
- Weekly progress reports and debt tracking

## Installing via uploaded ZIP file

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/local/studentprofile

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.


# Changelog Ident switch plugin

## Release 1.0.42

- Fixed: php8.1-fpm.sock break down resulting in missing return records

## Release 1.0.41

- Fixed: PHP warning regarding missing special folders

## Release 1.0.40

- Fixed: PHP warning regarding missing special folders

## Release 1.0.39

- Fixed: PHP warning regarding missing special folders

## Release 1.0.38

- Fixed: Unssen counter return from catch casted to integer.

## Release 1.0.37

- Fixed: INSTALL_PATH in identity_switch_newmails.php

## Release 1.0.36

- Fixed: In classic skin, selection list of identities was not in foreground

## Release 1.0.35

- Fixed: INSTALL_PATH in identity_switch_newmails.php
- Changed: Position of dropdown in classic skin

## Release 1.0.34

- Added: Some more comments in config.inc.php.dist
- Fixed: In some cases "special folders" were empty. Fix will handle.

## Release 1.0.33

- Fixed: PHP Warning in identy_switch.php:363

## Release 1.0.32

- Changed: "imap_pwd" now 128 bytes long

## Release 1.0.31

- Fixed: Hostname instead of identity label in desktop test notification shown

## Release 1.0.30

- Added: Debug support extended
- Added: New mail check now waiting for data file

## Release 1.0.29

- Fixed: Loop error in ident_switch_newmail.php
- Fixed: Usage of special characters '%' for SMTP hosts in config/config.inc.php

## Release 1.0.28

- Added: Mentioning limitiations in README.md
 
## Release 1.0.27

- Fixed: 'interval' not loaded when creating new identity
- Fixed: Some config.php.dist parameter not set as default for identity

## Release 1.0.26

- Added: IMAP folder delimiter configuration parameter
- Added: Wildcard for domain in configuration parameter
- Fixed: Typo in README.md

## Release 1.0.25

- French translation provided by @rglemaire

## Release 1.0.24

- Fixed: Bug in preferences (identy edit)
- Fixed: Bug when trying to send mails with default identity

## Release 1.0.23

- Skipped

## Release 1.0.22

- Skipped

## Release 1.0.21

- Fixed: Minor bug in refresh interval
- Added: Debug configuration option

## Release 1.0.20

- Fixed: Special folder handling
- Fxied: "Check all folders" flag not accepted

## Release 1.0.19

- Fixed isse #14: Bug in composer.json

## Release 1.0.18

- Fixed issue #8: CodeShakingSheep pull request merged to fix MySQL DB table creation for migration from ident_switch plugin
- Fixed issue #9: Protocol selection for SMTP now possible
- Fixed issue #10: CodeShakingSheep pull request merged to fix ident_switch migration DB INSERT statement
- Fixed issue #13: PHP 8.1 warnings for 'show_real_foldernames'
- Fixed issue #13: PHP 8.1 warnings for unknown special folder names

## Release 1.0.17

- Identyswitch menu is now automatically closed if user clicks somewhere on screen

## Release 1.0.15

- Bug fixed for 'show_real_foldernames'.
- Special message before record has been created added.

## Release 1.0.14

- Error message prefixed by "idsw".
- If identy is set as default, hen disable identy_switch handling.
- If a new record is created, it is not possible to return any error message due to RoundCube design.
- Fixed some error messages.

## Release 1.0.13

- Thank to https://github.com/HLFH .
- type 'tsl' fixed to 'tls'.
- some README.MD types fixed.
- support for SMTP array in config.inc.php ($config['smtp_host']).

## Release 1.0.12

- 'dont_override' option documented.
- Bug fixed when changing standard identy name.
- Some bugs fixed when trying to edit identy record.

## Release 1.0.11

- Some typos fixed.

## Release 1.0.10

- When switching identity unseen counter update is forced.

## Release 1.0.9

- Notification handling slightly modified.

## Release 1.0.8

- Some fixes to README.md.

## Release 1.0.7

- Unseen count on active account moved to identy_switch_do_switch().

## Release 1.0.6

- Fixing some unread counter problems.
- Bug in SQlite and postgres SQL files.

## Release 1.0.5

- Some more changes to CSS file.
- Changed CSS and JS compressor.
- Creation of identity selection menu restricted to mail template.

## Release 1.0.4

- Function create_menu() moved back to identy_switch.php.

## Release 1.0.3

- Updates to CSS files.

## Release 1.0.2

- Updates to CSS files.

## Release 1.0.1

- SMTP not working properly for default account.

## Release 1.0.0

- Code completly rewritten.
- New-mail check added.
- Ntification about new mail added.


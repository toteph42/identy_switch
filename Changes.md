# Changelog Ident switch plugin

## Release 1.0.17
.
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


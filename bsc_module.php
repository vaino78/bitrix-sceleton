<?php

$OPTIONS = getopt(
	'd::l::c::v::vd::a::h::db::o::',
	array(
		'dir::',
		'lang::',
		'version::',
		'version-date::',
		'admin::',
		'class::',
		'database::',
		'js::',
		'help::',
		'rights::',
		'option::'
	)
);

$help = <<<HELP
Bitrix module sceleton constructor.

Usage:
	php -f bsc_module.php module_name [args]

Args:
	-d, --dir
		Output directory, current dir by default.

	-v, --version
		Module version, "1.0.0" by default.

	-vd, --vesrion-date
		Module version date, current date as default.

	-l, --lang
		Names of langs used in the module, default: "ru".

	-a, --admin
		Names of admin files: entry points and scripts.

	-c, --class
		Names of classes used in the module.

	-db, --database
		Names of db types used in the module.

	-o, --option
		Names of module options.

	--js
		Names of javascript files in the module.

	--rights
		Enabling MODULE_GROUP_RIGHTS parameter in module install class.

	-h, --help
		Displaying this help.

HELP;

if($argv[0] == basename(__FILE__))
	array_shift($argv);

if(isset($OPTIONS['h']) || isset($OPTIONS['help']) || sizeof($argv)==0)
	die($help);

$module_name = $argv[0];
if(!preg_match('@^[a-z\._]+$@',$module_name))
	die('Error: Incorrect module name');

$dir = (
	($OPTIONS['d'])
	? $OPTIONS['dir']
	: (
		($OPTIONS['dir'])
		? $OPTIONS['dir']
		: getcwd()
	)
);

if(!is_string($dir))
	die('Error: Incorrect output dir');

/**
 * Templates
 */

$PHP_OPEN = '<?'.'php' . PHP_EOL;
$PHP_CLOSE = '?'.'>';

$BLANK_PHP_TMPL = $PHP_OPEN . PHP_EOL . $PHP_CLOSE;


?>
<?php

$help = <<<HELP
Bitrix module sceleton constructor.

Usage:
	php -f bsc_module.php -m module_name [args]

Args:
	-m, --module
		Module id

	-d, --dir
		Output directory, current dir by default.

	-v, --version
		Module version, "1.0.0" by default.

	-w, --vesrion-date
		Module version date, current date as default.

	-l, --lang
		Names of langs used in the module, default: "ru".

	-a, --admin
		Names of admin files: entry points and scripts.

	-c, --class
		Names of classes used in the module.

	-b, --database
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

if(basename($argv[0]) == basename(__FILE__))
	array_shift($argv);

$OPTIONS = getopt(
	'd:l:c:v:w:a:b:o:m:h::',
	array(
		'module:',
		'dir:',
		'lang:',
		'version:',
		'version-date:',
		'admin:',
		'class:',
		'database:',
		'js:',
		'help::',
		'rights::',
		'option:'
	)
);

if(isset($OPTIONS['h']) || isset($OPTIONS['help']) || sizeof($argv)==0)
	die($help);

$module_name = (
	($OPTIONS['m'])
	? $OPTIONS['m']
	: (
		($OPTIONS['module'])
		? $OPTIONS['module']
		: false
	)
);

if(!preg_match('@^[a-z\._]+$@',$module_name))
	die('Error: Incorrect module name');

$dir = (
	($OPTIONS['d'])
	? $OPTIONS['d']
	: (
		($OPTIONS['dir'])
		? $OPTIONS['dir']
		: getcwd()
	)
);

if(!is_string($dir))
	die('Error: Incorrect output dir');

$module_name_translated = strtr($module_name, array('.' => '_'));
$module_dir = realpath($dir) . '/' . $module_name;
if(file_exists($module_dir))
	die('Module directory already exists');


/**
 * Templates
 */

$PHP_OPEN = '<?'.'php' . PHP_EOL;
$PHP_CLOSE = '?'.'>';

$UTF8_BOM = pack('CCC', 239, 187, 191);

$BLANK_PHP_TMPL = $PHP_OPEN . PHP_EOL . $PHP_CLOSE;

$ADMIN_REQ_TMPL = $PHP_OPEN . PHP_EOL
	. 'require_once($_SERVER[\'DOCUMENT_ROOT\'] . \'/bitrix/modules/' . $module_name . '/admin/%s.php\');' . PHP_EOL
	. $PHP_CLOSE;

$ADMIN_FILE_TMPL = $PHP_OPEN . PHP_EOL
	. 'IncludeModuleLangFile(__FILE__);' . PHP_EOL
	. 'require_once(realpath(dirname(__FILE__) . \'/../prolog.php\'));' . PHP_EOL
	. str_repeat(PHP_EOL, 5)
	. $PHP_CLOSE;



$blank_filter = create_function('$var','return $var!=false;');

function get_params_array($short_key, $long_key)
{
	global $OPTIONS, $blank_filter;

	$res = (
		($OPTIONS[$short_key])
		? $OPTIONS[$short_key]
		: (
			($OPTIONS[$long_key])
			? $OPTIONS[$long_key]
			: false
		)
	);
	if(!is_array($res))
		$res = array($res);
	$res = array_filter($res, $blank_filter);

	return $res;
}

$langs = get_params_array('l', 'lang');
if(empty($langs))
	$langs=array('ru');

$classes = get_params_array('c', 'class');

$databases = get_params_array('b', 'database');

$module_options = get_params_array('o', 'option');

$admin_files = get_params_array('a', 'admin');

$js_files = (!empty($options['js'])) ? $options['js'] : false;
if(!is_array($js_files))
	$js_files = array($js_files);
$js_files = array_filter($js_files, $blank_filter);

$version = get_params_array('v', 'version');
$version = (!empty($version)) ? $version[0] : false;
if(!$version)
	$version = '1.0.0';
$version_date = get_params_array('w', 'version-date');
$version_date = (!empty($version_date)) ? $version_date[0] : false;
if(!$version_date)
	$version_date = date('Y-m-d H:i:s');



/**
 * Making dirs
 */
mkdir($module_dir, 0644) or die('Can not create module dir: ' . $module_dir);
mkdir(($module_dir . '/lang'), 0644) or die("Can not create dir: $module_dir/lang");
foreach($langs as $lang)
	mkdir(($module_dir . '/lang/' . $lang), 0644) or die("Cannot create dir: $module_dir/lang/$lang");
mkdir(($module_dir . '/admin'), 0644) or die("Can not create dir: $module_dir/admin");
mkdir(($module_dir . '/install'), 0644) or die("Can not create dir: $module_dir/install");
if(!empty($admin_files))
{
	mkdir(($module_dir . '/install/admin'), 0644) or die("Can not create dir: $module_dir/install/admin");
	foreach($langs as $lang)
		mkdir(($module_dir . '/lang/' . $lang . '/admin'), 0644) or die("Can not create dir: $module_dir/lang/$lang/admin");
}
if(!empty($js_files))
	mkdir(($module_dir . '/install/js'), 0644) or die("Can not create dir: $module_dir/install/js");
mkdir(($module_dir . '/classes'), 0644) or die("Can not create dir: $module_dir/classes");
mkdir(($module_dir . '/classes/general'), 0644) or die("Can not create dir: $module_dir/classes/general");
if(!empty($databases))
{
	mkdir(($module_dir . '/install/db'), 0644) or die("Can not create dir: $module_dir/install/db");
	foreach($databases as $db)
	{
		mkdir(($module_dir . '/install/db/' . $db), 0644) or die("Can not create dir: $module_dir/install/db/$db");
		mkdir(($module_dir . '/classes/' . $db), 0644) or die("Can not create dir: $module_dir/classes/$db");
	}
}



/**
 * Writing install/index.php
 */
file_put_contents(
	($module_dir . '/install/index.php'),
	(
		$PHP_OPEN . PHP_EOL
		. sprintf('if class_exists(\'%s\')%sreturn;', $module_name_translated, (PHP_EOL . "\t")) . PHP_EOL
		. PHP_EOL
		. '$PathInstall = str_replace(' . "'\\\\', '/', " . 'dirname(__FILE__));' . PHP_EOL
		. 'global $MESS;' . PHP_EOL
		. 'IncludeModuleLangFile($PathInstall . \'/install.php\');' . PHP_EOL
		. PHP_EOL
		. sprintf('class %s extends CModule', $module_name_translated) . PHP_EOL
		. '{' . PHP_EOL
		. PHP_EOL
		. sprintf('%spublic $MODULE_ID = \'%s\';', "\t", $module_name) . PHP_EOL
		. sprintf('%spublic $MODULE_NAME;', "\t") . PHP_EOL
		. sprintf('%spublic $MODULE_DESCRIPTION;', "\t") . PHP_EOL
		. sprintf('%spublic $MODULE_VERSION;', "\t") . PHP_EOL
		. sprintf('%spublic $MODULE_VERSION_DATE;', "\t") . PHP_EOL
		. sprintf('%spublic $MODULE_GROUP_RIGHTS = \'%s\';', "\t", (!empty($options['rights']) ? 'Y' : 'N')) . PHP_EOL
		. PHP_EOL
		. "\t" . 'public function __construct()' . PHP_EOL
		. "\t" . '{' . PHP_EOL
		. "\t\t" . '$PathInstall = str_replace(' . "'\\\\', '/', " . 'dirname(__FILE__));' . PHP_EOL
		. "\t\t" . 'include($PathInstall . \'/version.php\');' . PHP_EOL
		. "\t\t" . '$this->MODULE_NAME = GetMessage(\'' . strtoupper($module_name_translated) . 'MODULE_NAME\');' . PHP_EOL
		. "\t\t" . '$this->MODULE_DESCRIPTION = GetMessage(\'' . strtoupper($module_name_translated) . 'MODULE_DESCRIPTION\');' . PHP_EOL
		. "\t\t" . 'if(is_array($arModuleVersion) && !empty($arModuleVersion[\'VERSION\']))' . PHP_EOL
		. "\t\t" . '{' . PHP_EOL
		. "\t\t\t" . '$this->MODULE_VERSION = $arModuleVersion[\'VERSION\'];' . PHP_EOL
		. "\t\t\t" . '$this->MODULE_VERSION_DATE = $arModuleVersion[\'VERSION_DATE\'];' . PHP_EOL
		. "\t\t" . '}' . PHP_EOL
		. "\t" . '}' . PHP_EOL
		. PHP_EOL
		. "\t" . 'public function DoInstall()' . PHP_EOL
		. "\t" . '{' . PHP_EOL
		. "\t\t" . '$this->InstallFiles();' . PHP_EOL
		. (
			(!empty($databases))
			? ("\t\t" . '$this->RunSQL(\'install\');' . PHP_EOL)
			: ''
		)
		. "\t\t" . '$this->InstallEvents();' . PHP_EOL
		. "\t\t" . '$this->InstallAgents();' . PHP_EOL
		. "\t\t" . 'RegisterModule($this->MODULE_ID);' . PHP_EOL
		. "\t" . '}' . PHP_EOL
		. PHP_EOL
		. "\t" . 'public function DoInstall()' . PHP_EOL
		. "\t" . '{' . PHP_EOL
		. "\t\t" . '$this->UninstallFiles();' . PHP_EOL
		. (
			(!empty($databases))
			? ("\t\t" . '$this->RunSQL(\'unstall\');' . PHP_EOL)
			: ''
		)
		. "\t\t" . '$this->UninstallEvents();' . PHP_EOL
		. "\t\t" . '$this->UninstallAgents();' . PHP_EOL
		. "\t\t" . 'UnRegisterModule($this->MODULE_ID);' . PHP_EOL
		. "\t" . '}' . PHP_EOL
		. PHP_EOL
		. (
			(!empty($options['rights']))
			? (
				"\t" . 'public function GetModuleRightList()' . PHP_EOL
				. "\t" . '{' . PHP_EOL
				. "\t" . '}' . PHP_EOL
				. PHP_EOL
			)
			: ''
		)
		. "\t" . 'private function InstallFiles()' . PHP_EOL
		. "\t" . '{' . PHP_EOL
		. (
			(!empty($js_files))
			? (
				"\t\t" . '$dir_to = sprintf(\'%s/bitrix/js/' . $module_name_translated . '\', $_SERVER[\'DOCUMENT_ROOT\']);' . PHP_EOL
				. "\t\t" . 'if(!file_exists($dir_to))' . PHP_EOL
				. "\t\t\t" . 'mkdir($dir_to, 0644) or die("Can not create dir $dir_to");' . PHP_EOL
				. "\t\t" . 'CopyDirFiles((dirname(__FILE__) . \'/js\'), $dir_to);' . PHP_EOL
				. PHP_EOL
			)
			: ''
		)
		. (
			(!empty($admin_files))
			? (
				"\t\t" . '$dir_to = sprintf(\'%s/bitrix/admin\', $_SERVER[\'DOCUMENT_ROOT\']);' . PHP_EOL
				. "\t\t" . 'CopyDirFiles((dirname(__FILE__) . \'/admin\'), $dir_to);' . PHP_EOL
				. PHP_EOL
			)
			: ''
		)
		. "\t" . '}' . PHP_EOL
		. PHP_EOL
		. "\t" . 'private function InstallEvents()' . PHP_EOL
		. "\t" . '{' . PHP_EOL
		. "\t" . '}' . PHP_EOL
		. PHP_EOL
		. "\t" . 'private function InstallAgents()' . PHP_EOL
		. "\t" . '{' . PHP_EOL
		. "\t" . '}' . PHP_EOL
		. PHP_EOL
		. "\t" . 'private function UninstallFiles()' . PHP_EOL
		. "\t" . '{' . PHP_EOL
		. (
			(!empty($js_files))
			? (
				"\t\t" . 'DeleteDirFiles(' . PHP_EOL
				. "\t\t\t" . '(dirname(__FILE__) . \'/js\'),' . PHP_EOL
				. "\t\t\t" . 'sprintf(\'%s/bitrix/js/' . $module_name_translated . '\', $_SERVER[\'DOCUMENT_ROOT\'])' . PHP_EOL
				. "\t\t" . ');' . PHP_EOL
				. PHP_EOL
			)
			: ''
		)
		. (
			(!empty($admin_files))
			? (
				"\t\t" . 'DeleteDirFiles(' . PHP_EOL
				. "\t\t\t" . '(dirname(__FILE__) . \'/admin\'),' . PHP_EOL
				. "\t\t\t" . 'sprintf(\'%s/bitrix/admin\', $_SERVER[\'DOCUMENT_ROOT\'])' . PHP_EOL
				. "\t\t" . ');' . PHP_EOL
				. PHP_EOL
			)
			: ''
		)
		. "\t" . '}' . PHP_EOL
		. PHP_EOL
		. "\t" . 'private function UninstallEvents()' . PHP_EOL
		. "\t" . '{' . PHP_EOL
		. "\t" . '}' . PHP_EOL
		. PHP_EOL
		. "\t" . 'private function UninstallAgents()' . PHP_EOL
		. "\t" . '{' . PHP_EOL
		. "\t" . '}' . PHP_EOL
		. PHP_EOL
		. "\t" . 'private function RunSQL($filename)' . PHP_EOL
		. "\t" . '{' . PHP_EOL
		. "\t\t" . 'global $APPLICATION, $DBType, $DB;' . PHP_EOL
		. "\t\t" . '$filename = sprintf(' . PHP_EOL
		. "\t\t\t" . '\'%s/db/%s/%s.sql\',' . PHP_EOL
		. "\t\t\t" . 'dirname(__FILE__),' . PHP_EOL
		. "\t\t\t" . '$DBType,' . PHP_EOL
		. "\t\t\t" . '$filename' . PHP_EOL
		. "\t\t" . ');' . PHP_EOL
		. "\t\t" . 'if(!file_exists($filename))' . PHP_EOL
		. "\t\t\t" . 'return false;' . PHP_EOL
		. "\t\t" . '$errors = $DB->RunSQLBatch($filename);' . PHP_EOL
		. "\t\t" . 'if(!empty($errors))' . PHP_EOL
		. "\t\t" . '{' . PHP_EOL
		. "\t\t\t" . '$APPLICATION->ThrowException(implode(\'\', $errors));' . PHP_EOL
		. "\t\t\t" . 'return false;' . PHP_EOL
		. "\t\t" . '}' . PHP_EOL
		. "\t\t" . 'return true;' . PHP_EOL
		. "\t" . '}' . PHP_EOL
		. PHP_EOL
		. '}' . PHP_EOL
	)
);


/**
 * Writing install/version.php
 */
file_put_contents(
	($module_dir . '/install/version.php'),
	(
		$PHP_OPEN . PHP_EOL
		. '$arModuleVersion = array(' . PHP_EOL
		. "\t" . '\'VERSION\' => \'' . $version . '\',' . PHP_EOL
		. "\t" . '\'VERSION_DATE\' => \'' . $version_date . '\',' . PHP_EOL
		. ');' . PHP_EOL
	)
);


/**
 * Writing lang/[lang]/install.php
 */
foreach($langs as $lang)
{
	file_put_contents(
		($module_dir . '/lang/' . $lang . '/install.php'),
		(
			  $UTF8_BOM
			. $PHP_OPEN . PHP_EOL
			. '$MESS[\'' . strtoupper($module_name_translated) . 'MODULE_NAME\'] = "";' . PHP_EOL
			. '$MESS[\'' . strtoupper($module_name_translated) . 'MODULE_DESCRIPTION\'] = "";' . PHP_EOL
		)
	);
}


/**
 * Writing install/db/[db] files
 */
if(!empty($databases))
{
	foreach($databases as $db)
	{
		file_put_contents(
			($module_dir . '/install/db/' . $db . '/install.sql'),
			''
		);
		file_put_contents(
			($module_dir . '/install/db/' . $db . '/uninstall.sql'),
			''
		);
	}
}


/**
 * Writing admin/menu.php
 */
file_put_contents(
	($module_dir . '/admin/menu.php'),
	(
		$PHP_OPEN . PHP_EOL
		. '$aMenu[] = array(' . PHP_EOL
		. ');' . PHP_EOL
		. PHP_EOL
	)
);

/**
 * Writing *.js files
 */
if(!empty($js_files))
{
	foreach($js_files as $js)
	{
		file_put_contents(
			($module_dir . '/install/js/' . $js . '.js'),
			''
		);
	}
}

/**
 * Writing admin files
 */
if(!empty($admin_files))
{
	foreach($admin_files as $aff)
	{
		file_put_contents(
			($module_dir . '/admin/' . $aff . '.php'),
			$ADMIN_FILE_TMPL
		);

		foreach($langs as $lang)
		{
			file_put_contents(
				($module_dir . '/lang/' . $lang . '/admin/' . $aff . '.php'),
				(
					$UTF8_BOM
					. $BLANK_PHP_TMPL
				)
			);
		}

		file_put_contents(
			($module_dir . '/install/admin/' . $module_name_translated . '___' . $aff . '.php'),
			sprintf($ADMIN_REQ_TMPL, $aff)
		);
	}
}


/**
 * Writing classes and include.php
 */
$include_classes = array();
if(!empty($classes))
{
	foreach($classes as $clss)
	{
		file_put_contents(
			($module_dir . '/classes/general/' . $clss . '.php'),
			$BLANK_PHP_TMPL
		);
		$include_classes[] = ('classes/general/' . $clss . '.php');

		if(!empty($databases))
		{
			foreach($databases as $db)
			{
				file_put_contents(
					($module_dir . '/classes/' . $db . '/' . $clss . '.php'),
					$BLANK_PHP_TMPL
				);
			}

			$include_classes[] = ('classes/\' . $DBType . \'/' . $clss . '.php');
		}
	}
}

$include_file_contents = $PHP_OPEN . PHP_EOL
	. 'global $DBType;' . PHP_EOL . PHP_EOL
	. sprintf('CModule::AddAutoloadClasses("%s", array(', $module_name)
	. PHP_EOL;
if(!empty($include_classes))
{
	$include_file_contents .= "\t'' => '" . implode(("'," . PHP_EOL . "\t'' => '"), $include_classes)."'" . PHP_EOL;
}
$include_file_contents .= '));' . PHP_EOL;
file_put_contents(
	($module_dir . '/include.php'),
	$include_file_contents
);


/**
 * Writing options.php and default_options.php
 */
file_put_contents(
	($module_dir . '/options.php'),
	$BLANK_PHP_TMPL
);

$default_options = array();
if(!empty($module_options))
	$default_options = array_combine($module_options, array_pad(array(), count($module_options), ''));
file_put_contents(
	($module_dir . '/default_option.php'),
	(
		$PHP_OPEN . PHP_EOL
		. sprintf('$%s_default_option = %s;', $module_name_translated, var_export($default_options, true)) . PHP_EOL
		. PHP_EOL
		. $PHP_CLOSE
	)
);


/**
 * Writing prolog.php
 */
file_put_contents(
	($module_dir . '/prolog.php'),
	(
		$PHP_OPEN . PHP_EOL
		. 'define(\'ADMIN_MODULE_NAME\', \''.$module_name.'\');' . PHP_EOL
		. 'define(\'ADMIN_MODULE_ICON\', \'\');' . PHP_EOL
		. PHP_EOL
		. $PHP_CLOSE
	)
);

foreach($langs as $lang)
{
	file_put_contents(
		($module_dir . '/lang/' . $lang . '/options.php'),
		($UTF8_BOM . $BLANK_PHP_TMPL)
	);
	file_put_contents(
		($module_dir . '/lang/' . $lang . '/default_option.php'),
		($UTF8_BOM . $BLANK_PHP_TMPL)
	);
	file_put_contents(
		($module_dir . '/lang/' . $lang . '/prolog.php'),
		($UTF8_BOM . $BLANK_PHP_TMPL)
	);
}


echo 'done!', PHP_EOL;

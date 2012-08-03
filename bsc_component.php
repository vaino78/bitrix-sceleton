<?php

$options = getopt(
	'c:b:h::l::t::',
	array(
		'component:',
		'bitrix:',
		'help::',
		'lang::',
		'templates::',
		'script::',
		'style::'
	)
);

$help = <<<HELP

Bitrix component skeleton constructor.
Usage:
	php -f bsc_component.php -b /path/to/bitrix -c component.name [args]

Args:
	-c, --component
		* Name of the bitrix component

	-b, --bitrix
		* Path to "site/document_root/bitrix" dir

	-l, --lang
		Names of language directories to make. Default: en, ru

	-t, --templates
		Names of template directories to make. Default: .default

	--script
		Name of the js file in template dir to make.
		If name is not set, default value is "script.js".

	--style
		Name of the css file in template dir to make.
		If name is not set,  default value is "style.css".

	-h, --help
		Displaying this help.

HELP;

if(isset($options['h']) || isset($options['help']) || empty($options))
{
	echo $help;
	exit;
}

$PHP_OPEN = '<?'.'php' . PHP_EOL;
$PHP_CLOSE = '?'.'>';

$BLANK_PHP_TMPL = $PHP_OPEN . PHP_EOL . $PHP_CLOSE;

$COMPONENT_TMPL = $PHP_OPEN
	. 'if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();' . PHP_EOL
	. PHP_EOL
	. 'IncludeTemplateLangFile(__FILE__);' . PHP_EOL
	. PHP_EOL
	. '$this->IncludeComponentTemplate();' . PHP_EOL
	. $PHP_CLOSE;

$PARAMETERS_TMPL = $PHP_OPEN
	. 'if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();' . PHP_EOL
	. PHP_EOL
	. 'IncludeTemplateLangFile(__FILE__);' . PHP_EOL
	. PHP_EOL
	. '$arComponentParameters = array(' . PHP_EOL
	. "\t'GROUPS' => array(" . PHP_EOL
	. "\t)," . PHP_EOL
	. "\t'PARAMETERS' => array(" . PHP_EOL
	. "\t)" . PHP_EOL
	. ');' . PHP_EOL
	. PHP_EOL
	. $PHP_CLOSE;

$DESCRIPTION_TMPL = $PHP_OPEN
	. 'if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();' . PHP_EOL
	. PHP_EOL
	. 'IncludeTemplateLangFile(__FILE__);' . PHP_EOL
	. PHP_EOL
	. '$arComponentDescription = array(' . PHP_EOL
	. "\t'NAME' => ''," . PHP_EOL
	. "\t'DESCRIPTION' => ''," . PHP_EOL
	. "\t'ICON' => ''," . PHP_EOL
	. "\t'SORT' => 100," . PHP_EOL
	. "\t'CACHE_PATH' => 'Y'," . PHP_EOL
	. "\t'PATH' => array(" . PHP_EOL
	. "\t)" . PHP_EOL
	. ');' . PHP_EOL
	. PHP_EOL
	. $PHP_CLOSE;

$TEMPLATE_TMPL = $PHP_OPEN
	. 'if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();' . PHP_EOL
	. $PHP_CLOSE . PHP_EOL;

if(!$options['b'] && !$options['bitrix'])
	die('Error: Bitrix dir is not set');

$bitrix_dir = ($options['b'])
	? $options['b']
	: $options['bitrix'];

if(is_array($bitrix_dir))
	die('Error: Incorrect path to bitrix dir. Array given');

$bitrix_dir = realpath($bitrix_dir);

if(!$bitrix_dir)
	die('Error: Can not find bitrix dir');

if(!file_exists(sprintf('%s/components', $bitrix_dir)))
	die('Error: Can not find components dir in '.$bitrix_dir);

chdir(sprintf('%s/components', $bitrix_dir));

$blank_filter = create_function('$var','return $var!=false;');

$components = ($options['c'])
	? $options['c']
	: $options['component'];
if(!is_array($components))
	$components = array($components);
$components = array_filter($components, $blank_filter);
if(empty($components))
	die('Error: Empty component list');


$langs = ($options['l'])
	? $options['l']
	: (
		($options['lang'])
		? $options['lang']
		: false
	);
if(!is_array($langs))
	$langs = array($langs);
$langs = array_filter($langs, $blank_filter);
if(empty($langs))
	$langs=array('en','ru');


$templates = ($options['t'])
	? $options['t']
	: (
		($options['template'])
		? $options['template']
		: false
	);
if(!is_array($templates))
	$templates = array($templates);
$templates = array_filter($templates, $blank_filter);
if(empty($templates))
	$templates=array('.default');


if(isset($options['style']))
{
	$styles = (is_array($options['style'])) ? $options['style'] : array($options['style']);
	$styles = array_filter($styles, $blank_filter);
	if(empty($styles))
		$styles = array('style.css');
}
else
	$styles = false;


if(isset($options['script']))
{
	$scripts = (is_array($options['script'])) ? $options['script'] : array($options['script']);
	$scripts = array_filter($scripts, $blank_filter);
	if(empty($scripts))
		$scripts = array('script.js');
}
else
	$scripts = false;

$files = array();
if($styles!==false)
	$files = array_merge($files, $styles);
if($scripts!==false)
	$files = array_merge($files, $scripts);

foreach($components as $component)
{
	list($comp_namespace, $comp_name) = explode(':', $component);

	if($comp_namespace)
	{
		if(!file_exists(sprintf('%s/components/%s', $bitrix_dir, $comp_namespace)))
			mkdir(sprintf('%s/components/%s', $bitrix_dir, $comp_namespace), 0644);

		$comp_dir = sprintf('%s/components/%s/%s', $bitrix_dir, $comp_namespace, $comp_name);
	}
	else
		$comp_dir = sprintf('%s/components/%s', $bitrix_dir, $comp_name);

	if(!file_exists($comp_dir))
		mkdir($comp_dir, 0644);

	if(!file_exists(sprintf('%s/component.php', $comp_dir)))
		file_put_contents(sprintf('%s/component.php', $comp_dir), $COMPONENT_TMPL);

	if(!file_exists(sprintf('%s/.description.php', $comp_dir)))
		file_put_contents(sprintf('%s/.description.php', $comp_dir), $DESCRIPTION_TMPL);

	if(!file_exists(sprintf('%s/.parameters.php', $comp_dir)))
		file_put_contents(sprintf('%s/.parameters.php', $comp_dir), $PARAMETERS_TMPL);

	if(!file_exists(sprintf('%s/images', $comp_dir)))
		mkdir(sprintf('%s/images', $comp_dir), 0644);

	if(!file_exists(sprintf('%s/lang', $comp_dir)))
		mkdir(sprintf('%s/lang', $comp_dir), 0644);

	if(!file_exists(sprintf('%s/templates', $comp_dir)))
		mkdir(sprintf('%s/templates', $comp_dir), 0644);

	foreach($langs as $lang)
	{
		if(!file_exists(sprintf('%s/lang/%s', $comp_dir, $lang)))
			mkdir(sprintf('%s/lang/%s', $comp_dir, $lang), 0644);

		if(!file_exists(sprintf('%s/lang/%s/component.php', $comp_dir, $lang)))
			file_put_contents(sprintf('%s/lang/%s/component.php', $comp_dir, $lang), $BLANK_PHP_TMPL);

		if(!file_exists(sprintf('%s/lang/%s/.description.php', $comp_dir, $lang)))
			file_put_contents(sprintf('%s/lang/%s/.description.php', $comp_dir, $lang), $BLANK_PHP_TMPL);

		if(!file_exists(sprintf('%s/lang/%s/.parameters.php', $comp_dir, $lang)))
			file_put_contents(sprintf('%s/lang/%s/.parameters.php', $comp_dir, $lang), $BLANK_PHP_TMPL);
	}

	foreach($templates as $template)
	{
		if(!file_exists(sprintf('%s/templates/%s', $comp_dir, $template)))
			mkdir(sprintf('%s/templates/%s', $comp_dir, $template), 0644);

		if(!file_exists(sprintf('%s/templates/%s/template.php', $comp_dir, $template)))
			file_put_contents(sprintf('%s/templates/%s/template.php', $comp_dir, $template), $TEMPLATE_TMPL);

		if(!file_exists(sprintf('%s/templates/%s/lang', $comp_dir, $template)))
			mkdir(sprintf('%s/templates/%s/lang', $comp_dir, $template), 0644);

		foreach($langs as $lang)
		{
			if(!file_exists(sprintf('%s/templates/%s/lang/%s', $comp_dir, $template, $lang)))
				mkdir(sprintf('%s/templates/%s/lang/%s', $comp_dir, $template, $lang), 0644);

			if(!file_exists(sprintf('%s/templates/%s/lang/%s/template.php', $comp_dir, $template, $lang)))
				file_put_contents(sprintf('%s/templates/%s/lang/%s/template.php', $comp_dir, $template, $lang), $BLANK_PHP_TMPL);
		}

		if(!empty($files))
		{
			foreach($files as $file)
			{
				if(!file_exists(sprintf('%s/templates/%s/%s', $comp_dir, $template, $file)))
					file_put_contents(sprintf('%s/templates/%s/%s', $comp_dir, $template, $file), '');
			}
		}
		
	}

	echo $component, PHP_EOL;
}

?>
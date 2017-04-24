<?php

use Bitrix\Iblock;
use Bitrix\Main;

$help = <<<HELP

Bitrix iblock struct export.

Usage:
    php iblock_export.php -i <IBlock ID> [args]
    php iblock_export.php -c <IBlock code> [-t <IBlock type>] [args]


HELP;

$tmpl_type = <<<TMPL

\$obType = new %s;
\$result = \$obType->Add(%s);
if(!\$result) {
  throw new Exception(\$obType->LAST_ERROR);
}

TMPL;

$tmpl_iblock = <<<TMPL

\$obIblock = new %s;
\$iblockId = \$obIblock->Add(%s);
if(!\$iblockId) {
  throw new Exception(\$obIblock->LAST_ERROR);
}

TMPL;

$tmpl_prop = <<<TMPL

\$obProp = new %s;
\$propId = \$obProp->Add(array_merge(
  array('IBLOCK_ID' => \$iblockId)
  %s
));
if(!\$propId) {
  throw new Exception(\$propId->LAST_ERROR);
}

TMPL;

$tmpl_uf = <<<TMPL

\$obUf = new %s;
\$ufField = \$obUf->Add(array_merge(
  array('ENTITY_ID' => sprintf('IBLOCK_%u_SECTION', \$iblockId)),
  %s
));
if(!\$ufField) {
  \$ex = \$GLOBALS['APPLICATION']->GetException();
  throw new Exception(!empty(\$ex) ? \$ex->GetMessage() : 'User field add error');
}

TMPL;

$options = getopt(
  'b:c:h::i:t:',
  array(
    'bitrix:',
    'code:',
    'help::',
    'id:',
    'type:',
    'exclude_type:',
    'exclude_iblock:',
    'exclude_props:',
    'exclude_section_uf:'
  )
);

if(basename($argv[0]) == basename(__FILE__)) {
	array_shift($argv);
}

if(isset($options['h']) || isset($options['help']) || sizeof($argv)==0) {
	die($help);
}

try {
  $id = @($options['i'] ?? $options['id']);
  $code = @($options['c'] ?? $options['code']);
  $type = @($options['t'] ?? $options['type']);
  $bitrix_dir = @($options['b'] ?? $options['bitrix']);

  if(empty($id) && empty($code)) {
    throw new Exception('Iblock id are not set. See help');
  }

  if(empty($bitrix_dir)) {
    $bitrix_dir = __DIR__;
  }

  $_SERVER['DOCUMENT_ROOT'] = realpath($bitrix_dir . '/..');
  $prolog_before = sprintf('%s/modules/main/include/prolog_before.php', realpath($bitrix_dir));
  if(!file_exists($prolog_before)) {
    throw new Exception(sprintf('Can not find %s', $prolog_before));
  }

  include($prolog_before);
  if(!Main\Loader::includeModule('iblock')) {
    throw new Exception('Can not include iblock module');
  }

  $iblock_condition = (
    !empty($id)
    ? array(
      '=ID' => $id
    )
    : (
      !empty($type)
      ? array(
        '=CODE' => $code,
        '=IBLOCK_TYPE.ID' => $type
      )
      : array(
        '=CODE' => $code
      )
    )
  );

  $iblock = Iblock\IblockTable::getList(array(
    'filter' => $iblock_condition,
    'select' => array('*')
  ))->fetch();

  if(empty($iblock)) {
    throw new Exception('Can not find requested iblock');
  }

  if(!array_key_exists('exclude_type', $options)) {
    $typeData = Iblock\TypeTable::getList(array(
      'filter' => array(
        '=ID' => $iblock['IBLOCK_TYPE_ID']
      ),
      'select' => array('*')
    ))->fetch();
    if(empty($typeData)) {
      throw new Exception('Can not find type data, try --exclude_type to export iblock');
    }

    $q = Iblock\TypeLanguageTable::getList(array(
      'filter' => array(
        '=IBLOCK_TYPE_ID' => $typeData['ID']
      ),
      'select' => array('*')
    ));
    $typeLangData = array();
    while($d = $q->fetch()) {
      $typeLangData[$d['LANGUAGE_ID']] = array_diff_key($d, array_flip(array('IBLOCK_TYPE_ID', 'LANGUAGE_ID')));
    }

    printf(
      $tmpl_type,
      \CIBlockType::class,
      var_export(array_merge($typeData, array('LANG' => $typeLangData)), true)
    );
  }

  if(!array_key_exists('exclude_iblock', $options)) {
    $q = Iblock\IblockGroupTable::getList(array(
      'filter' => array(
        '=IBLOCK_ID' => $iblock['ID']
      ),
      'select' => array('GROUP_ID', 'PERMISSION')
    ));
    $iblockGroupsData = array();
    while($d = $q->fetch()) {
      $iblockGroupsData[$d['GROUP_ID']] = $d['PERMISSION'];
    }

    $q = Iblock\IblockMessageTable::getList(array(
      'filter' => array(
        '=IBLOCK_ID' => $iblock['ID']
      ),
      'select' => array('MESSAGE_ID', 'MESSAGE_TEXT')
    ));
    $iblockMessageData = array();
    while($d = $q->fetch()) {
      $iblockMessageData[$d['MESSAGE_ID']] = $d['MESSAGE_TEXT'];
    }

    $q = Iblock\IblockSiteTable::getList(array(
      'filter' => array(
        '=IBLOCK_ID' => $iblock['ID']
      ),
      'select' => array('SITE_ID')
    ));
    $iblockSiteData = array();
    while($d = $q->fetch()) {
      $iblockSiteData[] = $d['SITE_ID'];
    }

    printf(
      $tmpl_iblock,
      \CIBlock::class,
      var_export(array_merge(
        array_diff_key($iblock, array_flip(array('ID', 'TIMESTAMP_X', 'TMP_ID', 'XML_ID'))),
        array(
          'GROUP_ID' => $iblockGroupsData,
          'SITE_ID' => $iblockSiteData
        ),
        $iblockMessageData
      ), true)
    );
  }

  if(!array_key_exists('exclude_props', $options)) {
    $q = Iblock\PropertyEnumerationTable::getList(array(
      'filter' => array(
        '=IBLOCK_ID' => $iblock['ID']
      ),
      array('*')
    ));
    $iblockPropValuesData = array();
    while($d = $q->fetch()) {
      if(empty($iblockPropValuesData[$d['PROPERTY_ID']])) {
        $iblockPropValuesData[$d['PROPERTY_ID']] = array();
      }
      $iblockPropValuesData[$d['PROPERTY_ID']][] = array_intersect_key($d, array_flip(array('VALUE', 'DEF', 'XML_ID', 'SORT')));
    }

    $q = Iblock\PropertyTable::getList(array(
      'filter' => array(
        '=IBLOCK_ID' => $iblock['ID']
      ),
      'select' => array('*')
    ));
    while($d = $q->fetch()) {
      $values = array();
      if($d['PROPERTY_TYPE'] == PropertyTable::TYPE_LIST && !empty($iblockPropValuesData[$d['ID']])) {
        $values = $iblockPropValuesData[$d['ID']];
      }

      printf(
        $tmpl_prop,
        \CIBlockProperty::class,
        var_export(array_merge(
          array_diff_key($d, array_flip(array('ID', 'TIMESTAMP_X', 'IBLOCK_ID'))),
          (
            !empty($values)
            ? array('VALUES' => $values)
            : array()
          )
        ), true)
      );
    }
  }

  if(!array_keys_exists('exclude_section_uf', $options)) {
    $q = \CUserTypeEntity::GetList(
      array(
        "SORT" => "ASC",
        "ID" => "ASC"
      ),
      array(
        "ENTITY_ID" => sprintf('IBLOCK_%u_SECTION', $iblock['ID'])
      )
    );
    while($d = $q->Fetch()) {
      $dd = \CUserTypeEntity::GetById($d['ID']);
      printf(
        $tmpl_uf,
        \CUserTypeEntity::class,
        array_diff_key($dd, array_flip(array('ID', 'ENTITY_ID')))
      );
    }
  }

} catch(Exception $e) {
  fwrite(STDERR, ($e->getMessage() . PHP_EOL));
  exit($e->getCode() ?? 1);
}

echo '# Done!', PHP_EOL;

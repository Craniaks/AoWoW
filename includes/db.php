<?php

// Настройки
require_once 'configs/config.php';
// Библиотека для работы с БД - http://dklab.ru/lib/DbSimple
require_once 'includes/DbSimple/Generic.php';

// Массив настроек
global $AoWoWconf;

// Подключение к БД мангос (версия 3 :) )
$DB = DbSimple_Generic::connect("mysql://".$AoWoWconf['oregon']['user'].":".$AoWoWconf['oregon']['pass']."@".$AoWoWconf['oregon']['host']."/".$AoWoWconf['oregon']['db']);
$DB->setErrorHandler('databaseErrorHandler');
$DB->setIdentPrefix($AoWoWconf['oregon']['aowow']);
$DB->query('SET NAMES ?', 'utf8');
// Подключение к БД realmd
if($AoWoWconf['realmd'])
{
	$rDB = DbSimple_Generic::connect("mysql://".$AoWoWconf['realmd']['user'].":".$AoWoWconf['realmd']['pass']."@".$AoWoWconf['realmd']['host']."/".$AoWoWconf['realmd']['db']);
	$rDB->setErrorHandler('databaseErrorHandler');
	$rDB->query('SET NAMES ?', 'utf8');
}
// Код обработчика ошибок SQL.
function databaseErrorHandler($message, $info)
{
	// Если использовалась @, ничего не делать.
	if (!error_reporting()) return;
	// Выводим подробную информацию об ошибке.
	echo "SQL Error: $message<br><pre>"; 
	print_r($info);
	echo "</pre>";
	exit();
}
// Для отладки разкомментировать строку ниже
if($AoWoWconf['debug'])
	$DB->setLogger('myLogger');
function myLogger($db, $sql)
{
	global $smarty;
	$smarty->uDebug('!DbSimple', $sql,5000);
}

// КЕШИРОВАНИЕ // PRECACHING
/*
Содержание файла:
=========================
cache_delete_timestamp
serialized data
serialized allitems
serialized allspells
serialized exdata
serialized zonedata
=========================
*/
$cache_types = array(
	1	=> 'npc_page',
	2	=> 'npc_listing',

	3	=> 'object_page',
	4	=> 'object_listing',

	5	=> 'item_page',
	6	=> 'item_tooltip',
	7	=> 'item_listing',

	8	=> 'itemset_page',
	9	=> 'itemset_listing',

	10	=> 'quest_page',
	11	=> 'quest_tooltip',
	12	=> 'quest_listing',

	13	=> 'spell_page',
	14	=> 'spell_tooltip',
	15	=> 'spell_listing',

	16	=> 'zone_page',
	17	=> 'zone_listing',

	18	=> 'faction_page',
	19	=> 'faction_listing'
);
function save_cache($type, $type_id, $data, $prefix = '')
{
	global $cache_types, $allitems, $allspells, $AoWoWconf, $exdata, $zonedata;

	$type_str = $cache_types[$type];

	if(empty($type_str))
		return false;

	// {$type_str}_{$type_id}.aww
	$file = fopen($prefix.'cache/oregon/'.$type_str.'_'.$type_id.'_'.$_SESSION['locale'].'.aww', 'w+');

	$time = time()+$AoWoWconf['aowow']['cache_time'];

	if(!$file)
		return false;

	// записываем дату в файл
	fputs($file, $time);
	fputs($file, "\n".serialize($data)."\n");
	if($allitems)
		fputs($file, serialize($allitems));
	fputs($file, "\n");
	if($allspells)
		fputs($file, serialize($allspells));
	fputs($file, "\n");
	if($exdata)
		fputs($file, serialize($exdata));
	fputs($file, "\n");
	if($zonedata)
		fputs($file, serialize($zonedata));

	fclose($file);
	
	return true;
}
function load_cache($type, $type_id)
{
	global $cache_types, $smarty, $allitems, $allspells, $exdata, $zonedata;

	$type_str = $cache_types[$type];

	if(empty($type_str))
		return false;

	$data = @file_get_contents($prefix.'cache/oregon/'.$type_str.'_'.$type_id.'_'.$_SESSION['locale'].'.aww');
	if(!$data)
		return false;

	$data = explode("\n", $data);

	if($data[0] < time())
		return false;

	if($data[2])
		$allitems = unserialize($data[2]);
	if($data[3])
		$allspells = unserialize($data[3]);
	if($data[4])
		$smarty->assign('exdata', unserialize($data[4]));
	if($data[5])
		$smarty->assign('zonedata', unserialize($data[5]));

	return unserialize($data[1]);
}
?>
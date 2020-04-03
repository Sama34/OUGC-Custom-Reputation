<?php

/***************************************************************************
 *
 *	OUGC Custom Reputation plugin (/inc/plugins/ougc_customrep.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012 - 2014 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Allow users rate posts with custom post reputations.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('This file cannot be accessed directly.');

// Add our hooks
if(defined('IN_ADMINCP'))
{
	// Add menu to ACP
	$plugins->add_hook('admin_config_menu', create_function('&$args', 'global $lang, $customrep;	if($customrep->active) {$customrep->lang_load();	$args[] = array(\'id\' => \'ougc_customrep\', \'title\' => $lang->ougc_customrep, \'link\' => \'index.php?module=config-ougc_customrep\');}'));

	// Add our action handler to config module
	$plugins->add_hook('admin_config_action_handler', create_function('&$args', '$args[\'ougc_customrep\'] = array(\'active\' => \'ougc_customrep\', \'file\' => \'ougc_customrep.php\');'));

	// Insert our plugin into the admin permissions page
	$plugins->add_hook('admin_config_permissions', create_function('&$args', 'global $lang, $customrep;	$customrep->lang_load();	$args[\'ougc_customrep\'] = $lang->ougc_customrep_perm;'));

	// Users merge
	$plugins->add_hook('admin_user_users_merge_commit', 'ougc_customrep_users_merge');

	// xThreads setting
	$plugins->add_hook('admin_formcontainer_output_row', 'ougc_customrep_admin_formcontainer_output_row');
	$plugins->add_hook('admin_config_settings_change', 'ougc_customrep_admin_config_settings_change');

	// Cache manager
	$funct = create_function('', '
			control_object($GLOBALS[\'cache\'], \'
			function update_ougc_customrep()
			{
				$GLOBALS[\\\'customrep\\\']->update_cache();
			}
		\');
	');
	$plugins->add_hook('admin_tools_cache_start', $funct);
	$plugins->add_hook('admin_tools_cache_rebuild', $funct);
	unset($funct);
}
else
{
	global $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	switch(THIS_SCRIPT)
	{
		case 'forumdisplay.php':
		case 'portal.php':
		case 'reputation.php':
		case 'showthread.php':
		case 'member.php':
			$plugins->add_hook('forumdisplay_before_thread', 'ougc_customrep_forumdisplay_before_thread');
			$plugins->add_hook('forumdisplay_thread_end', 'ougc_customrep_forumdisplay_thread_end');

			$plugins->add_hook('portal_announcement', 'ougc_customrep_portal_announcement');

			$plugins->add_hook('reputation_start', 'ougc_customrep_delete_reputation');

			$plugins->add_hook('showthread_start', 'ougc_customrep_request', -1);
			$plugins->add_hook('postbit', 'ougc_customrep_postbit');

			$plugins->add_hook('member_profile_end', 'ougc_customrep_member_profile_end');

			// Moderation
			$plugins->add_hook('class_moderation_delete_thread_start', 'ougc_customrep_delete_thread');
			$plugins->add_hook('class_moderation_delete_post_start', 'ougc_customrep_delete_post');
			$plugins->add_hook('class_moderation_merge_posts', 'ougc_customrep_merge_posts');
			#$plugins->add_hook('class_moderation_merge_threads', 'ougc_customrep_merge_threads'); // seems like posts are updated instead of "re-created", good, less work
			#$plugins->add_hook('class_moderation_split_posts', 'ougc_customrep_merge_threads'); // no sure what happens here

			$templatelist .= 'ougccustomrep_headerinclude, ougccustomrep_headerinclude_fa, ougccustomrep_rep_number, ougccustomrep_rep_img, ougccustomrep_rep_img_fa, ougccustomrep_rep, ougccustomrep_rep_fa, ougccustomrep, ougccustomrep_rep_voted';
			break;
	}
}

$plugins->add_hook('datahandler_user_delete_content', 'ougc_customrep_user_delete_content');

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Plugin API
function ougc_customrep_info()
{
	global $lang, $customrep;
	$customrep->lang_load();

	return array(
		'name'          => 'OUGC Custom Reputation',
		'description'   => $lang->ougc_customrep_d,
		'website'		=> 'https://ougc.network',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'https://ougc.network',
		'version'		=> '1.8.22',
		'versioncode'	=> 1802,
		'compatibility'	=> '18*',
		'codename'		=> 'ougc_customrep',
		'pl'			=> array(
			'version'	=> 13,
			'url'		=> 'https://community.mybb.com/mods.php?action=view&pid=573'
		)
	);
}

// _activate function
function ougc_customrep_activate()
{
	global $lang, $customrep, $PL;
	$customrep->lang_load();
	$customrep->meets_requirements() or $customrep->admin_redirect($customrep->message, true);

	$PL->stylesheet('ougc_customrep', '/***************************************************************************
 *
 *   OUGC Custom Reputation (CACHE FILE)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2012 Omar Gonzalez
 *   
 *   Website: http://community.mybb.com/user-25096.html
 *
 *   Allow users rate posts with custom post reputations.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

.customrep .number {
	font-weight: bold;
	text-decoration: none;
	color: black;
}

.customrep img {
	vertical-align: middle;
}', 'showthread.php|forumdisplay.php|portal.php|member.php');

	// Modify some templates.
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'button_rep\']}').'#i', '{$post[\'button_rep\']}{$post[\'customrep\']}');
	find_replace_templatesets('postbit', '#'.preg_quote('{$deleted_bit}').'#i', '{$deleted_bit}{$post[\'customrep_ignorebit\']}');
	find_replace_templatesets('postbit', '#'.preg_quote('{$post_visibility}').'#i', '{$post_visibility}{$post[\'customrep_post_visibility\']}');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'button_rep\']}').'#i', '{$post[\'button_rep\']}{$post[\'customrep\']}');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$deleted_bit}').'#i', '{$deleted_bit}{$post[\'customrep_ignorebit\']}');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post_visibility}').'#i', '{$post_visibility}{$post[\'customrep_post_visibility\']}');
	find_replace_templatesets('postbit_reputation', '#'.preg_quote('{$post[\'userreputation\']}').'#i', '<span id="customrep_rep_{$post[\'pid\']}">{$post[\'userreputation\']}</span>', 0);
	find_replace_templatesets('forumdisplay_thread', '#'.preg_quote('{$attachment_count}').'#i', '{$attachment_count}{$thread[\'customrep\']}');
	find_replace_templatesets('portal_announcement', '#'.preg_quote('{$senditem}').'#i', '{$senditem}{$announcement[\'customrep\']}');
	find_replace_templatesets('member_profile', '#'.preg_quote('{$modoptions}').'#i', '{$modoptions}{$memprofile[\'customrep\']}');

	// Add our settings
	$PL->settings('ougc_customrep', $lang->ougc_customrep, $lang->ougc_customrep_d, array(
		'firstpost'	=> array(
			'title'			=> $lang->setting_ougc_customrep_firstpost,
			'description'	=> $lang->setting_ougc_customrep_firstpost_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'delete'	=> array(
			'title'			=> $lang->setting_ougc_customrep_delete,
			'description'	=> $lang->setting_ougc_customrep_delete_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'perpage'	=> array(
			'title'			=> $lang->setting_ougc_customrep_perpage,
			'description'	=> $lang->setting_ougc_customrep_perpage_desc,
			'optionscode'	=> 'text',
			'value'			=> 10,
		),
		'fontawesome'	=> array(
			'title'			=> $lang->setting_ougc_customrep_fontawesome,
			'description'	=> $lang->setting_ougc_customrep_fontawesome_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 0,
		),
		'threadlist'	=> array(
			'title'			=> $lang->setting_ougc_customrep_threadlist,
			'description'	=> $lang->setting_ougc_customrep_threadlist_desc,
			'optionscode'	=> 'forumselect',
			'value'			=> -1,
		),
		'portal'	=> array(
			'title'			=> $lang->setting_ougc_customrep_portal,
			'description'	=> $lang->setting_ougc_customrep_portal_desc,
			'optionscode'	=> 'forumselect',
			'value'			=> -1,
		),
		'xthreads_hide'	=> array(
			'title'			=> $lang->setting_ougc_xthreads_hide,
			'description'	=> $lang->setting_ougc_xthreads_hide_desc,
			'optionscode'	=> 'text',
			'value'			=> '',
		),
		'stats_profile'	=> array(
			'title'			=> $lang->setting_ougc_stats_profile,
			'description'	=> $lang->setting_ougc_stats_profile_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
	));

	// Fill cache
	$customrep->update_cache();

	// Insert template/group
	$PL->templates('ougccustomrep', $lang->ougc_customrep, array(
		''						=> '<div class="customrep float_right" id="customrep_{$customrep->post[\'pid\']}">{$reputations}</div>',
		'headerinclude' 		=> '<script src="{$mybb->settings[\'bburl\']}/jscripts/ougc_customrep.js" type="text/javascript"></script>',
		'headerinclude_fa' 		=> '<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">',
		'misc'				=> '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder" style="text-align: left;">
	<tr><td class="thead" colspan="2"><strong>{$title}</strong></td></tr>
	<tr><td class="tcat" colspan="2"><strong>{$lang->ougc_customrep_popup_latest}</strong></td></tr>
	{$content}
	{$multipage}
</table>',
		'misc_multipage'		=> '<tr><td class="tfoot" colspan="2">{$multipage}</td></tr>',
		'misc_error'			=> '<tr><td class="trow1" colspan="2">{$error_message}</td></tr>',
		'misc_row'				=> '<tr>
<td class="{$trow}" width="60%">{$log[\'profilelink_f\']}</td>
<td class="{$trow}" width="40%" align="center">{$date}</td>
</tr>',
		'rep'					=> '{$image}{$number}&nbsp;',
		'rep_img'				=> '<img src="{$reputation[\'image\']}" title="{$lang_val}" />',
		'rep_img_fa'				=> '<i class="fa fa-{$reputation[\'image\']}" aria-hidden="true"></i>',
		'rep_number'			=> '&nbsp;<a href="javascript:MyBB.popupWindow(\'/{$popupurl}\');" rel="nofollow" title="{$lang->ougc_customrep_viewall}" class="number" title="{$lang->ougc_customrep_viewlatest}" id="ougccustomrep_view_{$customrep->post[\'pid\']}">x {$number}</a>',
		'rep_voted'				=> '<a href="{$link}" class="voted {$classextra}">{$image}</a>',
		'postbit_reputation'				=> '<span id="customrep_rep_{$post[\'pid\']}">{$post[\'userreputation\']}</span>',
		'modal'					=> '<div class="modal"><div style="overflow-y: auto; max-height: 400px;">{$page}</div></div>'
	));

	change_admin_permission('config', 'ougc_customrep', 1);

	global $cache;

	// Insert version code into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_customrep_info();
	if(isset($plugins['customrep']))
	{
		if((int)$plugins['customrep'] < 1100)
		{
			global $db;

			$db->modify_column('ougc_customrep', 'rid', "int UNSIGNED NOT NULL AUTO_INCREMENT");
			$db->modify_column('ougc_customrep_log', 'lid', "int UNSIGNED NOT NULL AUTO_INCREMENT");
			$db->modify_column('ougc_customrep_log', 'pid', "int NOT NULL DEFAULT '0'");
			$db->modify_column('ougc_customrep_log', 'uid', "int NOT NULL DEFAULT '0'");
			$db->modify_column('ougc_customrep_log', 'rid', "int NOT NULL DEFAULT '0'");
			$db->modify_column('reputation', 'lid', 'int NOT NULL DEFAULT \'0\'');

			if(!$db->index_exists('ougc_customrep_log', 'piduid'))
			{
				$db->write_query('ALTER TABLE '.TABLE_PREFIX.'ougc_customrep_log ADD UNIQUE KEY piduid (pid,uid)');
			}
			if(!$db->index_exists('ougc_customrep_log', 'pidrid'))
			{
				$db->write_query('CREATE INDEX pidrid ON '.TABLE_PREFIX.'ougc_customrep_log (pid,rid)');
			}
		}

		if((int)$plugins['customrep'] < 1822)
		{
			global $db;

			$db->field_exists('points', 'ougc_customrep') || $db->add_column('ougc_customrep', 'points', "DECIMAL(16,2) NOT NULL default '0'");
			$db->field_exists('ignorepoints', 'ougc_customrep') || $db->add_column('ougc_customrep', 'ignorepoints', "smallint(5) NOT NULL DEFAULT '0'");
			$db->field_exists('points', 'ougc_customrep_log') || $db->add_column('ougc_customrep_log', 'points', "DECIMAL(16,2) NOT NULL default '0'");
		}
	}
	$plugins['customrep'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// _deactivate function
function ougc_customrep_deactivate()
{
	global $customrep, $PL;
	$customrep->meets_requirements() or $customrep->admin_redirect($customrep->message, true);

	// Remove stylesheet
	$PL->stylesheet_deactivate('ougc_customrep');

	// Revert template edits
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'customrep\']}').'#i', '', 0);
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'customrep_ignorebit\']}').'#i', '', 0);
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'customrep_post_visibility\']}').'#i', '', 0);
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'customrep\']}').'#i', '', 0);
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'customrep_ignorebit\']}').'#i', '', 0);
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'customrep_post_visibility\']}').'#i', '', 0);
	find_replace_templatesets('postbit_reputation', '#'.preg_quote('<span id="customrep_rep_{$post[\'pid\']}">{$post[\'userreputation\']}</span>').'#i', '{$post[\'userreputation\']}', 0);
	find_replace_templatesets('forumdisplay_thread', '#'.preg_quote('{$thread[\'customrep\']}').'#i', '', 0);
	find_replace_templatesets('portal_announcement', '#'.preg_quote('{$announcement[\'customrep\']}').'#i', '', 0);
	find_replace_templatesets('member_profile', '#'.preg_quote('{$memprofile[\'customrep\']}').'#i', '', 0);

	change_admin_permission('config', 'ougc_customrep', 0);
}

// _install function
function ougc_customrep_install()
{
	global $customrep, $db;
	ougc_customrep_uninstall();

	// Add our tables
	$collation = $db->build_create_table_collation();
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_customrep` (
			`rid` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(100) NOT NULL DEFAULT '',
			`image` varchar(255) NOT NULL DEFAULT '',
			`groups` text NOT NULL,
			`forums` text NOT NULL,
			`disporder` smallint(5) NOT NULL DEFAULT '0',
			`visible` smallint(1) NOT NULL DEFAULT '1',
			`reptype` varchar(3) NOT NULL DEFAULT '',
			`points` DECIMAL(16,2) NOT NULL default '0',
			`ignorepoints` smallint(5) NOT NULL DEFAULT '0',
			PRIMARY KEY (`rid`)
		) ENGINE=MyISAM{$collation};"
	);
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_customrep_log` (
			`lid` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`pid` int NOT NULL DEFAULT '0',
			`uid` int NOT NULL DEFAULT '0',
			`rid` int NOT NULL DEFAULT '0',
			`points` DECIMAL(16,2) NOT NULL default '0',
			`dateline` int(10) NOT NULL DEFAULT '0',
			PRIMARY KEY (`lid`),
			UNIQUE KEY piduid (pid,uid),
			INDEX pidrid (pid,rid)
		) ENGINE=MyISAM{$collation};"
	);

	$db->add_column('reputation', 'lid', 'int NOT NULL DEFAULT \'0\'');

	// Add a default reputation type
	$customrep->insert_rep(array(
		'name'	=> 'Like',
		'image'	=> '{bburl}/images/ougc_customrep/default.png',
		'groups'	=> -1,
		'forums'	=> -1,
		'disporder'	=> 1,
		'visible'	=> 1,
		'points'	=> 0,
		'ignorepoints'	=> 0,
	));
}

// _is_installed function
function ougc_customrep_is_installed()
{
	global $db;

	return $db->table_exists('ougc_customrep');
}

// _uninstall function
function ougc_customrep_uninstall()
{
	global $customrep, $db, $PL;
	$customrep->meets_requirements() or $customrep->admin_redirect($customrep->message, true);

	// Drop our tables
	$db->drop_table('ougc_customrep');
	$db->drop_table('ougc_customrep_log');

	// Drop reputation field
	if($db->field_exists('lid', 'reputation'))
	{
		$db->drop_column('reputation', 'lid');
	}

	// Delete the cache.
	$PL->cache_delete('ougc_customrep');

	// Delete stylesheet
	$PL->stylesheet_delete('ougc_customrep');

	// Delete settings
	$PL->settings_delete('ougc_customrep');

	// Delete template/group
	$PL->templates_delete('ougccustomrep');

	change_admin_permission('config', 'ougc_customrep', -1);

	global $cache;

	// Remove version code from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['customrep']))
	{
		unset($plugins['customrep']);
	}

	if($plugins)
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$PL->cache_delete('ougc_plugins');
	}
}

//Merging two accounts, update data propertly
function ougc_customrep_users_merge()
{
	global $db, $destination_user, $source_user;

	$fromuid = (int)$source_user['uid'];
	$touid = (int)$destination_user['uid'];

	// Query all logs that belong to the $fromuid user and update them
	$query = $db->simple_select('ougc_customrep_log', 'lid', 'uid=\''.$fromuid.'\'');
	while($lid = $db->fetch_field($query, 'lid'))
	{
		$customrep->update_log($lid, array('uid' => $touid));
	}
}

// Fetch a list of xThreads fields to build the setting
function ougc_customrep_admin_formcontainer_output_row(&$args)
{
	global $lang, $cache, $form, $mybb;

	if($args['title'] != $lang->setting_ougc_xthreads_hide)
	{
		return;
	}

	$threadfields = $cache->read('threadfields');

	if(!($xthreads = function_exists('xthreads_gettfcache') && !empty($threadfields)))
	{
		$args['content'] = $lang->setting_ougc_xthreads_information;
		return;
	}

	$selected_list = $mybb->settings['ougc_customrep_xthreads_hide'];
	if(isset($mybb->input['upsetting']['ougc_customrep_xthreads_hide']))
	{
		$selected_list = $mybb->input['upsetting']['ougc_customrep_xthreads_hide'];
	}

	$option_list = $selected_list = array();
	foreach($threadfields as $tf)
	{
		if(strpos(','.$mybb->settings['ougc_customrep_xthreads_hide'].',', $tf['field']) !== false)
		{
			$selected_list[] = $tf['field'];
		}
		$option_list[$tf['field']] = $tf['field'];
	}
	
	$args['content'] = $form->generate_select_box('upsetting[ougc_customrep_xthreads_hide][]', $option_list, $selected_list, array('id' => 'row_setting_ougc_customrep_xthreads_hide', 'size' => 5, 'multiple' => true));
}

// Propertly save the settings
function ougc_customrep_admin_config_settings_change()
{
	global $mybb;

	if($mybb->request_method != 'post' || !isset($mybb->input['upsetting']['ougc_customrep_firstpost']))
	{
		return;
	}

	$mybb->input['upsetting']['ougc_customrep_xthreads_hide'] = implode(',', (array)$mybb->input['upsetting']['ougc_customrep_xthreads_hide']);
	//_dump($mybb->input['upsetting']['ougc_customrep_xthreads_hide']);
}

// Delete logs from users which are being deleted
function ougc_customrep_user_delete_content(&$dh)
{
	global $db, $customrep;

	$query = $db->simple_select('ougc_customrep_log', 'lid', 'uid IN('.$dh->delete_uids.')');
	while($lid = $db->fetch_field($query, 'lid'))
	{
		$customrep->delete_log($lid);
	}
}

// Display ratings on forum display
function ougc_customrep_forumdisplay_before_thread(&$args)
{
	global $fid, $customrep, $mybb, $db, $plugins, $headerinclude, $templates;

	if(!$mybb->settings['ougc_customrep_threadlist'] || !is_member($mybb->settings['ougc_customrep_threadlist'], array('usergroup' => $fid)))
	{
		return;
	}

	$customrep->set_forum($fid);

	if(!$customrep->allowed_forum)
	{
		$plugins->remove_hook('forumdisplay_thread_end', 'ougc_customrep_forumdisplay_thread_end');

		return;
	}

	//_dump($customrep->allowed_forum, $mybb->settings['ougc_customrep_threadlist']);

	if($mybb->settings['use_xmlhttprequest'])
	{
		$font_awesome = '';
		if($mybb->settings['ougc_customrep_fontawesome'])
		{
			eval('$font_awesome .= "'.$templates->get('ougccustomrep_headerinclude_fa').'";');
		}

		eval('$headerinclude .= "'.$templates->get('ougccustomrep_headerinclude').'";');
	}

	$pids = array();
	foreach($args['threadcache'] as $thread)
	{
		$pids[(int)$thread['firstpost']] = (int)$thread['firstpost'];
	}

	if(empty($pids))
	{
		return;
	}

	$pids = "pid IN ('".implode("','", $pids)."')";

	$query = $db->simple_select('ougc_customrep_log', '*', $pids.' AND rid IN (\''.implode('\',\'', $customrep->rids).'\')');
	while($rep = $db->fetch_array($query))
	{
		$customrep->cache['query'][$rep['rid']][$rep['pid']][$rep['lid']][$rep['uid']] = 1;
	}
}

// Parse forum display
function ougc_customrep_forumdisplay_thread_end(&$args)
{
	global $thread, $customrep;

	$customrep->set_post(array('tid' => $thread['tid'], 'pid' => $thread['firstpost'], 'uid' => $thread['uid'], 'fid' => $thread['fid']));

	$customrep->set_url(get_thread_link($thread['tid']));

	ougc_customrep_parse_postbit($thread['customrep']);

	//_dump($thread);
}

// Display ratings on portal
function ougc_customrep_portal_announcement()
{
	global $fid, $customrep, $mybb, $db, $plugins, $headerinclude, $templates, $tids, $annfidswhere, $tunviewwhere, $numannouncements, $announcement;

	if(!$mybb->settings['ougc_customrep_portal'])
	{
		$plugins->remove_hook('portal_announcement', 'ougc_customrep_portal_announcement');
		return;
	}

	static $portal_cache = null;
	if($portal_cache === null)
	{
		$portal_cache = array();

		$query = $db->simple_select('threads t', 't.firstpost, t.fid', "t.tid IN (0{$tids}){$annfidswhere}{$tunviewwhere} AND t.visible='1' AND t.closed NOT LIKE 'moved|%'");

		$pids = array();
		while($thread = $db->fetch_array($query))
		{
			$fids[(int)$thread['fid']] = (int)$thread['fid'];
			$pids[(int)$thread['firstpost']] = (int)$thread['firstpost'];
		}

		if(empty($pids))
		{
			return;
		}

		foreach($fids as $fid)
		{
			$customrep->set_forum($fid);

			$portal_cache[$fid] = $customrep->allowed_forum;
		}

		if(empty($portal_cache))
		{
			$plugins->remove_hook('portal_announcement', 'ougc_customrep_portal_announcement');
			return;
		}

		$pids = "pid IN ('".implode("','", $pids)."')";

		$query = $db->simple_select('ougc_customrep_log', '*', $pids.' AND rid IN (\''.implode('\',\'', $customrep->rids).'\')');
		while($rep = $db->fetch_array($query))
		{
			$customrep->cache['query'][$rep['rid']][$rep['pid']][$rep['lid']][$rep['uid']] = 1;
		}

		if($mybb->settings['use_xmlhttprequest'])
		{
			$font_awesome = '';
			if($mybb->settings['ougc_customrep_fontawesome'])
			{
				eval('$font_awesome .= "'.$templates->get('ougccustomrep_headerinclude_fa').'";');
			}

			eval('$headerinclude .= "'.$templates->get('ougccustomrep_headerinclude').'";');
		}
	}

	if(empty($portal_cache[$announcement['fid']]))
	{
		return;
	}

	$customrep->set_post(array('tid' => $announcement['tid'], 'pid' => $announcement['firstpost'], 'uid' => $announcement['uid'], 'fid' => $announcement['fid']));

	$customrep->set_url(get_thread_link($announcement['tid']));

	// Now we build the reputation bit
	ougc_customrep_parse_postbit($announcement['customrep']);
}

// Postbit
function ougc_customrep_postbit(&$post)
{
	global $fid, $customrep, $tid, $templates, $thread;

	if($customrep->firstpost_only && $post['pid'] != $thread['firstpost'])
	{
		return;
	}

	if($customrep->firstpost_only)
	{
		global $plugins;

		$plugins->remove_hook('postbit', 'ougc_customrep_postbit');
	}

	static $ignore_rules = null;

	if(!isset($customrep->cache['query']))
	{
		global $mybb;

		$customrep->set_forum($fid);

		if(!$customrep->allowed_forum)
		{
			global $plugins;

			$plugins->remove_hook('postbit', 'ougc_customrep_postbit');

			return;
		}

		if($mybb->settings['use_xmlhttprequest'])
		{
			global $headerinclude;

			$font_awesome = '';
			if($mybb->settings['ougc_customrep_fontawesome'])
			{
				eval('$font_awesome .= "'.$templates->get('ougccustomrep_headerinclude_fa').'";');
			}

			eval('$headerinclude .= "'.$templates->get('ougccustomrep_headerinclude').'";');
		}

		global $db, $thread;
		$customrep->cache['query'] = array();

		if($customrep->firstpost_only)
		{
			$pids = "pid='{$thread['firstpost']}'";
		}
		// Bug: http://mybbhacks.zingaburga.com/showthread.php?tid=1587&pid=12762#pid12762
		elseif($mybb->get_input('mode') == 'threaded')
		{
			$pids = "pid='{$mybb->get_input('pid', 1)}'";
		}
		elseif(isset($GLOBALS['pids']))
		{
			$pids = $GLOBALS['pids'];
		}
		else
		{
			$pids = "pid='{$post['pid']}'";
		}
	
		$query = $db->simple_select('ougc_customrep_log', '*', $pids.' AND rid IN (\''.implode('\',\'', $customrep->rids).'\')');
		while($rep = $db->fetch_array($query))
		{
			// > The ougc_customrep_log table seems to mostly query on the rid,pid columns - there really should be indexes on these; one would presume that pid,uid should be a unique key as you can't vote for more than once for each post.  The good thing about identifying these uniques is that it could help one simplify something like
			$customrep->cache['query'][$rep['rid']][$rep['pid']][$rep['lid']][$rep['uid']] = 1; //TODO
			// > where the 'lid' key seems to be unnecessary
		}

		$ignore_rules = array();
		foreach($customrep->cache['_reps'] as $rid => $rep)
		{
			if((int)$rep['ignorepoints'] && isset($customrep->cache['query'][$rid]))
			{
				$ignore_rules[$rid] = (int)$rep['ignorepoints'];
			}
		}
	}

	$customrep->set_post(array('tid' => $post['tid'], 'pid' => $post['pid'], 'uid' => $post['uid'], 'fid' => $fid));

	if(!empty($ignore_rules))
	{
		foreach($ignore_rules as $rid => $ignorepoints)
		{
			if(isset($customrep->cache['query'][$rid][$post['pid']]) && count($customrep->cache['query'][$rid][$post['pid']]) >= $ignorepoints)
			{
				global $lang, $ignored_message, $ignore_bit, $post_visibility;

				$ignored_message = $lang->sprintf($lang->ougc_customrep_postbit_ignoredbit, $post['username']);
				eval("\$post['customrep_ignorebit'] = \"".$templates->get("postbit_ignored")."\";");
				$post['customrep_post_visibility'] = "display: none;";
				break;
			}
		}
	}

	// Now we build the reputation bit
	ougc_customrep_parse_postbit($post['customrep']);
}

// Display user stats in profiles.
function ougc_customrep_member_profile_end()
{
	global $db, $customrep, $mybb, $templates, $memprofile, $lang, $theme, $headerinclude;

	if(!$mybb->settings['ougc_customrep_stats_profile'])
	{
		return;
	}

	if($mybb->settings['use_xmlhttprequest'])
	{
		$font_awesome = '';
		if($mybb->settings['ougc_customrep_fontawesome'])
		{
			eval('$font_awesome .= "'.$templates->get('ougccustomrep_headerinclude_fa').'";');
		}

		eval('$headerinclude .= "'.$templates->get('ougccustomrep_headerinclude').'";');
	}

	$customrep->lang_load();

	$where = array();

	// get forums user cannot view
	$unviewable = get_unviewable_forums(true);
	if($unviewable)
	{
		$where[] = "t.fid NOT IN ($unviewable)";
		$where[] = "t.fid NOT IN ($unviewable)";
	}

	// get inactive forums
	$inactive = get_inactive_forums();
	if($inactive)
	{
		$where[] .= "t.fid NOT IN ($inactive)";
		$where[] .= "t.fid NOT IN ($inactive)";
	}

	$where[] = "t.visible='1' AND t.closed NOT LIKE 'moved|%' AND p.visible='1'";

	$reps = (array)$mybb->cache->read('ougc_customrep');

	$where[] = "l.rid IN ('".implode("','", array_keys($reps))."')";

	$memprofile['uid'] = (int)$memprofile['uid'];

	$where['q'] = "l.uid='{$memprofile['uid']}'";

	$query = $db->simple_select('ougc_customrep_log l LEFT JOIN '.TABLE_PREFIX.'posts p ON (p.pid=l.pid) LEFT JOIN '.TABLE_PREFIX.'threads t ON (t.tid=p.tid)', 'l.rid', implode(' AND ', $where));
	while($rid = $db->fetch_field($query, 'rid'))
	{
		++$stats_given[$rid];
	}

	$rates_received = $rates_given = $reputations = '';

	$where['q'] = "p.uid='{$memprofile['uid']}'";

	$query = $db->simple_select('ougc_customrep_log l LEFT JOIN '.TABLE_PREFIX.'posts p ON (p.pid=l.pid) LEFT JOIN '.TABLE_PREFIX.'threads t ON (t.tid=p.tid)', 'l.rid', implode(' AND ', $where));
	while($rid = $db->fetch_field($query, 'rid'))
	{
		++$stats_received[$rid];
	}
	foreach($reps as $rid => &$reputation)
	{
		if(!isset($stats_received[$rid]) || empty($stats_received[$rid]))
		{
			continue;
		}

		$trow = alt_trow();

		$reputation['name'] = $lang_val = htmlspecialchars_uni($reputation['name']);

		$number = my_number_format($stats_received[$rid]);
		eval('$number = "'.$templates->get('ougccustomrep_profile_number').'";');

		$tmplt_img = 'ougccustomrep_rep_img';
		if($mybb->settings['ougc_customrep_fontawesome'])
		{
			$tmplt_img = 'ougccustomrep_rep_img_fa';
		}

		eval('$image = "'.$templates->get($tmplt_img, 1, 0).'";');

		eval('$reputations .= "'.$templates->get('ougccustomrep_rep').'";');
	}

	if(!$reputations)
	{
		eval('$rates_received = "'.$templates->get('ougccustomrep_profile_empty').'";');
	}
	else
	{
		eval('$rates_received = "'.$templates->get('ougccustomrep_profile_row').'";');
	}

	$reputations = '';

	foreach($reps as $rid => &$reputation)
	{
		if(!isset($stats_given[$rid]) || empty($stats_given[$rid]))
		{
			continue;
		}

		$trow = alt_trow();

		$reputation['name'] = $lang_val = htmlspecialchars_uni($reputation['name']);

		$number = my_number_format($stats_given[$rid]);
		eval('$number = "'.$templates->get('ougccustomrep_profile_number').'";');

		$tmplt_img = 'ougccustomrep_rep_img';
		if($mybb->settings['ougc_customrep_fontawesome'])
		{
			$tmplt_img = 'ougccustomrep_rep_img_fa';
		}

		eval('$image = "'.$templates->get($tmplt_img, 1, 0).'";');

		eval('$reputations .= "'.$templates->get('ougccustomrep_rep').'";');
	}

	if(!$reputations)
	{
		eval('$rates_given = "'.$templates->get('ougccustomrep_profile_empty').'";');
	}
	else
	{
		eval('$rates_given = "'.$templates->get('ougccustomrep_profile_row').'";');
	}

	$lang->ougc_customrep_profile_stats = $lang->sprintf($lang->ougc_customrep_profile_stats, $memprofile['username']);

	eval('$memprofile[\'customrep\'] = "'.$templates->get('ougccustomrep_profile').'";');
}

// Delete logs when deleting a thread
function ougc_customrep_delete_thread(&$tid)
{
	global $db;

	$pids = array();

	// First we get a list of all posts in this thread, we need this to later get a list of logs
	$query = $db->simple_select('posts', 'pid', 'tid=\''.(int)$tid.'\'');
	while($pid = $db->fetch_field($query, 'pid'))
	{
		$pids[] = (int)$pid;
	}

	if($pids)
	{
		global $customrep;

		// get log ids and delete them all, this may take some time
		$query = $db->simple_select('ougc_customrep_log', 'lid', 'pid IN (\''.implode('\',\'', $pids).'\')');
		while($lid = $db->fetch_field($query, 'lid'))
		{
			$customrep->delete_log($lid);
		}
	}
}

// Delete logs upon post deletion
function ougc_customrep_delete_post(&$pid)
{
	global $customrep;

	// get log ids and delete them all, this may take some time
	$query = $db->simple_select('ougc_customrep_log', 'lid', 'pid=\''.(int)$pid.'\'');
	while($lid = $db->fetch_field($query, 'lid'))
	{
		$customrep->delete_log($lid);
	}
}

// Merging post, what a pain!
function ougc_customrep_merge_posts(&$args)
{
	global $db, $customrep;
	$where = 'pid IN (\''.implode('\',\'', array_map('intval', $args['pids'])).'\')';

	// Now get the master PID, which MyBB doesn't offer..
	$masterpid = (int)$db->fetch_field($db->simple_select('posts', 'pid', $where, array('limit' => 1, 'order_by' => 'dateline', 'order_dir' => 'asc')), 'pid');

	// First get all the logs attached to these posts
	$query = $db->simple_select('ougc_customrep_log', 'lid', $where);
	while($lid = $db->fetch_field($query, 'lid'))
	{
		// Update this log
		$customrep->update_log($lid, array('pid' => $masterpid));
	}
}

// When deleting a reputation delete any log assigned to it.
// Partially MyBB's Code
function ougc_customrep_delete_reputation()
{
	global $mybb;

	if($mybb->get_input('action') == 'delete')
	{
		// Verify incoming POST request
		verify_post_check($mybb->get_input('my_post_key'));

		global $db;

		// Fetch the existing reputation for this user given by our current user if there is one.
		$query = $db->simple_select('reputation r LEFT JOIN '.TABLE_PREFIX.'users u ON (u.uid=r.adduid)', 'r.adduid, r.lid, u.uid, u.username', 'r.rid=\''.$mybb->get_input('rid', 1).'\'');
		$reputation = $db->fetch_array($query);

		// Only administrators, super moderators, as well as users who gave a specifc vote can delete one.
		if(!$mybb->usergroup['cancp'] && !$mybb->usergroup['issupermod'] && $reputation['adduid'] != $mybb->user['uid'])
		{
			error_no_permission();
		}

		global $customrep;

		// Delete the specified reputation log
		if((int)$reputation['lid'] > 0)
		{
			// Delete the specified reputation & log
			$customrep->delete_log($reputation['lid']);

			global $uid, $user, $lang;

			// Create moderator log
			log_moderator_action(array('uid' => $user['uid'], 'username' => $user['username']), $lang->sprintf($lang->delete_reputation_log, $reputation['username'], $reputation['adduid']));

			redirect('reputation.php?uid='.$uid, $lang->vote_deleted_message);
		}
	}
}

// Parse posbit content output
function ougc_customrep_parse_postbit(&$var, $div=true)
{
	global $mybb, $customrep;

	$reputations = '';

	// Has this current user voted for this custom reputation?
	$voted = false;
	foreach($customrep->rids as $rid)
	{
		if(isset($customrep->cache['query'][$rid][$customrep->post['pid']]))
		{
			//TODO
			foreach($customrep->cache['query'][$rid][$customrep->post['pid']] as $votes)
			{
				if(isset($votes[$mybb->user['uid']]))
				{
					$voted = true;
					break;
				}
			}
		}
	}
	unset($rid);

	global $templates, $lang;
	$customrep->lang_load();

	$post_url = get_post_link($customrep->post['pid'], $customrep->post['tid']);

	$input = array(
		'pid' => $customrep->post['pid'],
		'my_post_key' => (isset($mybb->post_code) ? $mybb->post_code : generate_post_check()),
	);

	if(!$mybb->settings['use_xmlhttprequest'])
	{
		$lang->ougc_customrep_viewlatest = $lang->ougc_customrep_viewlatest_noajax;
	}

	foreach($customrep->cache['_reps'] as $rid => $reputation)
	{
		if(!is_member($reputation['forums'], array('usergroup' => $customrep->post['fid'])))
		{
			continue;
		}
		$reputation['name'] = htmlspecialchars_uni($reputation['name']);
		$input['action'] = 'customrep';
		$input['rid'] = $rid;
		$link = $customrep->build_url($input);
		$input['action'] = 'customreppu';
		$popupurl = $customrep->build_url($input);

		$number = 0;
		$classextra = '';
		if($mybb->settings['use_xmlhttprequest'])
		{
			$link = "javascript:OUGC_CustomReputation.Add('{$customrep->post['tid']}', '{$customrep->post['pid']}', '{$mybb->post_code}', '{$rid}', '0');";
			if($customrep->allow_delete)
			{
				$link_delete = "javascript:OUGC_CustomReputation.Add('{$customrep->post['tid']}', '{$customrep->post['pid']}', '{$mybb->post_code}', '{$rid}', '1');";
			}
		}

		// Count the votes for this reputation in this post
		if(isset($customrep->cache['query'][$rid][$customrep->post['pid']]))
		{
			$number = count($customrep->cache['query'][$rid][$customrep->post['pid']]);
		}
		$number = my_number_format($number);

		eval('$number = "'.$templates->get('ougccustomrep_rep_number', 1, 0).'";');

		$tmplt_img = 'ougccustomrep_rep_img';
		if($mybb->settings['ougc_customrep_fontawesome'])
		{
			$tmplt_img = 'ougccustomrep_rep_img_fa';
		}

		$lang_val = '';
		if($voted && $customrep->post['uid'] != $mybb->user['uid'])
		{
			// Check if this user has voted for this spesific reputation
			$voted_this = false;
			if($voted && !empty($customrep->cache['query'][$rid][$customrep->post['pid']]))
			{
				foreach($customrep->cache['query'][$rid][$customrep->post['pid']] as $votes)
				{
					if(isset($votes[$mybb->user['uid']]))
					{
						$voted_this = true;
						break;
					}
				}
			}
			if($voted_this && $customrep->allow_delete)
			{
				$link = $mybb->settings['use_xmlhttprequest'] ? $link_delete : $link.'&amp;delete=1';

				$classextra = '_delete';
				$lang_val = $lang->sprintf($lang->ougc_customrep_delete, $reputation['name']);
				eval('$image = "'.$templates->get($tmplt_img, 1, 0).'";');
				eval('$image = "'.$templates->get('ougccustomrep_rep_voted', 1, 0).'";');
			}
			elseif($voted_this)
			{
				$lang_val = $lang->sprintf($lang->ougc_customrep_voted, $reputation['name']);
				eval('$image = "'.$templates->get($tmplt_img, 1, 0).'";');
			}
			else
			{
				$lang_val = $lang->ougc_customrep_voted_undo;
				eval('$image = "'.$templates->get($tmplt_img, 1, 0).'";');
			}
		}
		elseif(($reputation['groups'] == -1 || ($reputation['groups'] && $customrep->is_member($reputation['groups']))) && $customrep->post['uid'] != $mybb->user['uid'])
		{//TODO
			$lang_val = $lang->sprintf($lang->ougc_customrep_vote, $reputation['name']);
			eval('$image = "'.$templates->get($tmplt_img, 1, 0).'";');
			eval('$image = "'.$templates->get('ougccustomrep_rep_voted', 1, 0).'";');
		}
		else
		{
			$lang_val = $reputation['name'];
			eval('$image = "'.$templates->get($tmplt_img, 1, 0).'";');
		}

		//_dump($number);
		eval('$reputations .= "'.$templates->get('ougccustomrep_rep', 1, 0).'";');
	}
	unset($rid, $reputation);

	if($div)
	{
		$reputations = trim($reputations);
		eval('$var = "'.$templates->get('ougccustomrep').'";');
	}
	else
	{
		$var = trim($reputations);
	}
}

// Plugin request
function ougc_customrep_request()
{
	global $customrep, $mybb, $tid, $templates;

	$customrep->set_url(get_thread_link($tid)); //TODO

	if(!$customrep->active || !in_array($mybb->get_input('action'), array('customrep', 'customreppu')))
	{
		return;
	}

	if($mybb->get_input('action') == 'customreppu')
	{
		global $thread, $fid, $lang;

		$error = 'error';

		// Good bay guests :)
		if(!$mybb->user['uid'])
		{
			$error($lang->ougc_customrep_error_nopermission);
		}

		$customrep->set_forum($fid);

		$customrep->allowed_forum or $error($lang->ougc_customrep_error_invalidforum);

		$post = get_post($mybb->get_input('pid', 1));
		$customrep->set_post(array('tid' => $post['tid'], 'pid' => $post['pid'], 'uid' => $post['uid'], 'subject' => $post['subject'], 'fid' => $fid));
		unset($post);

		!empty($customrep->post) or $error($lang->ougc_customrep_error_invlidadpost);

		if(!($reputation = $customrep->get_rep($mybb->get_input('rid', 1))))
		{
			$error($lang->ougc_customrep_error_invalidrep);
		}

		$reputation['visible'] or $error($lang->ougc_customrep_error_invalidrep);

		if($customrep->firstpost_only && $customrep->post['pid'] != $thread['firstpost'])
		{
			$error($lang->ougc_customrep_error_invlidadpost); // somehow
		}

		global $db, $templates, $theme, $headerinclude, $parser;
		if(!is_object($parser))
		{
			require_once MYBB_ROOT.'inc/class_parser.php';
			$parser = new postParser;
		}

		// Save four queries here
		$templates->cache('ougccustomrep_misc_row, ougccustomrep_misc_error, ougccustomrep_misc_multipage, ougccustomrep_misc, ougccustomrep_postbit_reputation, ougccustomrep_modal');

		$popupurl = $customrep->build_url(array(
			'pid' => $customrep->post['pid'],
			'my_post_key' => (isset($mybb->post_code) ? $mybb->post_code : generate_post_check()),
			'action' => 'customreppu',
			'rid' => $reputation['rid']
		));

		// Build multipage
		$query = $db->simple_select('ougc_customrep_log', 'COUNT(lid) AS logs', "pid='{$customrep->post['pid']}' AND rid='{$reputation['rid']}'");
		$count = (int)$db->fetch_field($query, 'logs');

		$page = $mybb->get_input('page', 1);

		$perpage = (int)$mybb->settings['ougc_customrep_perpage'];
		if($page > 0)
		{
			$start = ($page-1)*$perpage;
			$pages = ceil($count/$perpage);
			if($page > $pages)
			{
				$start = 0;
				$page = 1;
			}
		}
		else
		{
			$start = 0;
			$page = 1;
		}

		$customrep->set_url(get_post_link($customrep->post['pid'], $customrep->post['tid']));
		$multipage_url = $customrep->build_url(false, array('page', 'pid', 'tid'));
		$multipage = multipage($count, $perpage, $page, "javascript:MyBB.popupWindow('/{$popupurl}&amp;page={page}');");
		if(!$multipage)
		{
			$multipage = '';
		}

		$customrep->lang_load();

		$query = $db->query('SELECT r.*, u.username, u.usergroup, u.displaygroup, u.avatar, u.avatartype, u.avatardimensions
			FROM '.TABLE_PREFIX.'ougc_customrep_log r 
			LEFT JOIN '.TABLE_PREFIX.'users u ON (u.uid=r.uid)
			WHERE r.pid=\''.$customrep->post['pid'].'\' AND r.rid=\''.$reputation['rid'].'\'
			ORDER BY r.dateline DESC
			LIMIT '.$start.', '.$perpage
		);

		$content = '';
		while($log = $db->fetch_array($query))
		{
			$trow = alt_trow();

			$log['username'] = htmlspecialchars_uni($log['username']);
			$log['username_f'] = format_name($log['username'], $log['usergroup'], $log['displaygroup']);
			$log['profilelink'] = build_profile_link($log['username'], $log['uid'], '_blank');
			$log['profilelink_f'] = build_profile_link($log['username_f'], $log['uid'], '_blank');

			$log['date'] = my_date($mybb->settings['dateformat'], $log['dateline']);
			$log['time'] = my_date($mybb->settings['timeformat'], $log['dateline']);
			$date = $lang->sprintf($lang->ougc_customrep_popup_date, $log['date'], $log['time']);

			$log['avatar'] = format_avatar($log['avatar'], $log['avatardimensions']);

			eval('$content .= "'.$templates->get('ougccustomrep_misc_row').'";');
		}

		$reputation['name'] = htmlspecialchars_uni($reputation['name']);
		$customrep->post['subject'] = $parser->parse_badwords($customrep->post['subject']);

		if(!$content)
		{
			$error_message = $lang->ougc_customrep_popup_empty;
			eval('$content = "'.$templates->get('ougccustomrep_misc_error').'";');
		}

		$title = $lang->sprintf($lang->ougc_customrep_popuptitle, $reputation['name'], $customrep->post['subject']);

		$lang->ougc_customrep_popup_latest = $lang->sprintf($lang->ougc_customrep_popup_latest, my_number_format($count));
		if($multipage)
		{
			eval('$multipage = "'.$templates->get('ougccustomrep_misc_multipage').'";');
		}
		eval('$page = "'.$templates->get('ougccustomrep_misc', 1, 0).'";');
		eval('$modal = "'.$templates->get('ougccustomrep_modal', 1, 0).'";');
		echo $modal;

		exit;
	}

	global $lang;
	$customrep->lang_load();

	// Good bay guests :)
	if(!$mybb->user['uid'])
	{
		error($lang->ougc_customrep_error_nopermission);
	}

	verify_post_check($mybb->get_input('my_post_key'));

	$customrep->set_post(get_post($mybb->get_input('pid', 1)));

	if(empty($customrep->post))
	{
		error($lang->ougc_customrep_error_invlidadpost);
	}

	if($mybb->user['uid'] == $customrep->post['uid'])
	{
		error($lang->ougc_customrep_error_selftrating);
	}

	global $fid, $thread;

	$customrep->set_forum($fid);

	if(!$customrep->allowed_forum)
	{
		error($lang->ougc_customrep_error_invalidforum);
	}

	if(!$customrep->allowed_forum)
	{
		ougc_customrep_ajax_error($lang->ougc_customrep_error_invalidforum);
	}

	if(!($reputation = $customrep->get_rep($mybb->get_input('rid', 1))))
	{
		ougc_customrep_ajax_error($lang->ougc_customrep_error_invalidrep);
	}

	if(!$reputation['visible'] || !array_key_exists($reputation['rid'], $customrep->cache['_reps']))
	{
		ougc_customrep_ajax_error($lang->ougc_customrep_error_invalidrep);
	}

	if($reputation['groups'] == '' || ($reputation['groups'] != -1 && !$customrep->is_member($reputation['groups'])))
	{
		ougc_customrep_ajax_error($lang->ougc_customrep_error_nopermission);
	}

	if($customrep->post['tid'] != $thread['tid'] || $customrep->firstpost_only && $customrep->post['pid'] != $thread['firstpost'])
	{
		ougc_customrep_ajax_error($lang->ougc_customrep_error_invlidadpost); // somehow
	}

	global $db;

	if($mybb->get_input('delete', 1) == 1)
	{
		if(!$customrep->allow_delete)
		{
			ougc_customrep_ajax_error($lang->ougc_customrep_error_nopermission);
		}

		$query = $db->simple_select('ougc_customrep_log', '*', 'pid=\''.$customrep->post['pid'].'\' AND uid=\''.$mybb->user['uid'].'\' AND rid=\''.$reputation['rid'].'\'');

		if($db->num_rows($query) < 1)
		{
			ougc_customrep_ajax_error($lang->ougc_customrep_error_invalidrating);
		}

		while($log = $db->fetch_array($query))
		{
			if(function_exists('newpoints_addpoints') && $mybb->settings['newpoints_main_enabled'])
			{
				newpoints_addpoints($log['uid'], $log['points']);
			}

			$customrep->delete_log($log['lid']);
		}
	}
	else
	{
		$query = $db->simple_select('ougc_customrep_log', 'lid', "pid='{$customrep->post['pid']}' AND uid='{$mybb->user['uid']}' AND rid='{$reputation['rid']}'", array('limit' => 1));
		if($db->fetch_field($query, 'lid'))
		{
			ougc_customrep_ajax_error($lang->ougc_customrep_error_multiple); // TODO: Allow multiple ratings?
		}

		if(function_exists('newpoints_addpoints') && $mybb->settings['newpoints_main_enabled'])
		{
			$reputation['points'] = (float)$reputation['points'];

			if(!($forumrules = newpoints_getrules('forum', $thread['fid'])))
			{
				$forumrules['rate'] = 1;
			}

			if(!($grouprules = newpoints_getrules('group', $mybb->user['usergroup'])))
			{
				$grouprules['rate'] = 1;
			}

			if($forumrules['rate'] && $grouprules['rate'])
			{
				$points = floatval(round($reputation['points']*$forumrules['rate']*$grouprules['rate'], intval($mybb->settings['newpoints_main_decimal'])));

				if($points > $mybb->user['newpoints'])
				{
					ougc_customrep_ajax_error($lang->sprintf($lang->ougc_customrep_error_points, newpoints_format_points($points)));
				}
				else
				{
					newpoints_addpoints($mybb->user['uid'], -$reputation['points'], $forumrules['rate'], $grouprules['rate']);
				}
			}
		}

		$customrep->insert_log($reputation['rid'], $reputation['reptype'], !empty($points) ? $points : 0);
	}

	$mybb->settings['use_xmlhttprequest'] or $customrep->redirect(get_post_link($customrep->post['pid'], $customrep->post['tid']).'#'.$customrep->post['tid'], true);

	// > On postbit, the plugin loads ALL votes, and does a summation + check for current user voting on this.  This can potentially be problematic if there happens to be a large number of votes.
	$query = $db->simple_select('ougc_customrep_log', '*', "pid='{$customrep->post['pid']}' AND rid='{$reputation['rid']}'");
	while($reputation = $db->fetch_array($query))
	{
		$customrep->cache['query'][$reputation['rid']][$reputation['pid']][$reputation['lid']][$reputation['uid']] = 1;
	}

	$post = array(
		'pid'				=> $customrep->post['pid'],
		'userreputation'	=> get_reputation((int)$db->fetch_field($db->simple_select('users', 'reputation', 'uid=\''.$customrep->post['uid'].'\''), 'reputation'), $customrep->post['uid']),
		'content'	=> ''
	);

	ougc_customrep_parse_postbit($post['content']);

	eval('$post[\'userreputation\'] = "'.$templates->get('ougccustomrep_postbit_reputation').'";');

	header("Content-type: application/json; charset={$lang->settings['charset']}");
	echo json_encode(array(
		'success'			=> 1,
		'pid'				=> $customrep->post['pid'],
		'content'			=> $post['content'],
		'userreputation'	=> $post['userreputation'],
	));

	exit;
}

function ougc_customrep_ajax_error($error)
{
	header("Content-type: application/json; charset={$lang->settings['charset']}");
	echo json_encode(array(
		'errors'			=> $error
	));
	exit;
}

// Our awesome class
class OUGC_CustomRep
{
	// Define our ACP url
	public $url = 'index.php?module=config-plugins';

	// Set the cache
	public $cache = array(
		'reps' => array(),
		'logs' => array(),
		'images' => array(),
		'_reps' => array(),
		'query' => null
	);

	// RID which has just been updated/inserted/deleted
	public $rid = 0;

	// Is the current forum allowed?
	public $allowed_forum = false;

	// Set current handling post
	public $post = array();

	// Is the plugin active? Default is false
	public $active = false;

	// Construct the data (?)
	function __construct()
	{
		global $mybb;

		// Fix: PHP warning on MyBB installation/upgrade
		if(is_object($mybb->cache))
		{
			$plugins = $mybb->cache->read('plugins');

			// Is plugin active?
			$this->active = isset($plugins['active']['ougc_customrep']);
		}

		$this->rids = array();

		$this->firstpost_only = (bool)$mybb->settings['ougc_customrep_firstpost'];

		$this->allow_delete = (bool)$mybb->settings['ougc_customrep_delete'];
	}

	// Load our language file if neccessary
	function lang_load()
	{
		global $lang;

		if(!isset($lang->ougc_customrep))
		{
			$lang->load(defined('IN_ADMINCP') ? 'config_ougc_customrep' : 'ougc_customrep');
		}
	}

	// Set url
	function set_url($url)
	{
		if(($url = trim($url)))
		{
			$this->url = $url;
		}
	}

	// Check PL requirements
	function meets_requirements()
	{
		global $PL;

		$info = ougc_customrep_info();

		if(!file_exists(PLUGINLIBRARY))
		{
			global $lang;
			$this->lang_load();

			$this->message = $lang->sprintf($lang->ougc_customrep_plreq, $info['pl']['url'], $info['pl']['version']);
			return false;
		}

		$PL or require_once PLUGINLIBRARY;

		if($PL->version < $info['pl']['version'])
		{
			global $lang;
			$this->lang_load();

			$this->message = $lang->sprintf($lang->ougc_customrep_plold, $PL->version, $info['pl']['version'], $info['pl']['url']);
			return false;
		}

		return true;
	}

	// Redirect normal users
	function redirect($url, $quick=false)
	{
		if($quick)
		{
			global $settings;

			$settings['redirects'] = 0;
		}

		redirect($url);
		exit;
	}

	// Redirect admin help function
	function admin_redirect($message='', $error=false)
	{
		if($message)
		{
			flash_message($message, ($error ? 'error' : 'success'));
		}

		admin_redirect($this->build_url());
		exit;
	}

	// Build an url parameter
	function build_url($urlappend=array(), $fetch_input_url=false)
	{
		global $PL;

		if(!is_object($PL))
		{
			return $this->url;
		}

		if($fetch_input_url === false)
		{
			if($urlappend && !is_array($urlappend))
			{
				$urlappend = explode('=', $urlappend);
				$urlappend = array($urlappend[0] => $urlappend[1]);
			}
		}
		else
		{
			$urlappend = $this->fetch_input_url($fetch_input_url);
		}

		return $PL->url_append($this->url, $urlappend, '&amp;', true);
	}

	// Fetch current url inputs, for multipage mostly
	function fetch_input_url($ignore=false)
	{
		$location = parse_url(get_current_location());
		while(my_strpos($location['query'], '&amp;'))
		{
			$location['query'] = html_entity_decode($location['query']);
		}
		$location = explode('&', $location['query']);

		if($ignore !== false)
		{
			if(!is_array($ignore))
			{
				$ignore = array($ignore);
			}
			foreach($location as $key => $input)
			{
				$input = explode('=', $input);
				if(in_array($input[0], $ignore))
				{
					unset($location[$key]);
				}
			}
		}

		$url = array();
		foreach($location as $input)
		{
			$input = explode('=', $input);
			$url[$input[0]] = $input[1];
		}

		return $url;
	}

	// Get the reputation icon
	function get_image($image, $rid)
	{
		if(!isset($this->cache['images'][$rid]))
		{
			global $settings, $theme;
			$this->cache['images'][$rid] = false;

			$replaces = array(
				'{bburl}'	=> $settings['bburl'],
				'{homeurl}'	=> $settings['homeurl'],
				'{imgdir}'	=> $theme['imgdir']
			);

			$this->cache['images'][$rid] = str_replace(array_keys($replaces), array_values($replaces), $image);
		}

		return $this->cache['images'][$rid];
	}

	// Log admin action
	function log_action()
	{
		if($this->rid)
		{
			log_admin_action($this->rid);
		}
		else
		{
			log_admin_action();
		}
	}

	// Update the cache
	function update_cache()
	{
		global $db, $cache;

		$d = array();
		$query = $db->simple_select('ougc_customrep', '*', 'visible=\'1\'', array('order_by' => 'disporder'));
		while($rep = $db->fetch_array($query))
		{
			$rid = $rep['rid'];
			unset($rep['rid'], $rep['disporder'], $rep['visible']);
			$d[$rid] = $rep;
		}

		if($d)
		{
			$cache->update('ougc_customrep', $d);
		}

		return (bool)$d;
	}

	// Clean a string/array and return it
	function clean_array($array, $implode=true)
	{
		if(!is_array($array))
		{
			$array = explode(',', $array);
		}

		$array = array_unique(array_map('intval', $array));

		if($implode)
		{
			return implode(',', $array);
		}

		return $array;
	}

	// Insert a new custom reputation to the DB
	function insert_rep($data=array(), $update=false, $rid=0)
	{
		global $db;

		$insert_data = array();

		if(isset($data['name']))
		{
			$insert_data['name'] = $db->escape_string($data['name']);
		}

		if(isset($data['image']))
		{
			$insert_data['image'] = $db->escape_string($data['image']);
		}

		if(isset($data['groups']))
		{
			if(is_array($data['groups']))
			{
				$data['groups'] = $this->clean_array($data['groups']);
			}

			$insert_data['groups'] = $db->escape_string($data['groups']);
		}

		if(isset($data['forums']))
		{
			if(is_array($data['forums']))
			{
				$data['forums'] = $this->clean_array($data['forums']);
			}

			$insert_data['forums'] = $db->escape_string($data['forums']);
		}

		if(isset($data['disporder']))
		{
			$insert_data['disporder'] = (int)$data['disporder'];
		}

		if(isset($data['visible']))
		{
			$insert_data['visible'] = (int)$data['visible'];
		}

		if(isset($data['points']))
		{
			$insert_data['points'] = (int)$data['points'];
		}

		if(isset($data['ignorepoints']))
		{
			$insert_data['ignorepoints'] = (int)$data['ignorepoints'];
		}

		$insert_data['reptype'] = '';
		if($data['reptype'] != '')
		{
			$insert_data['reptype'] = (int)$data['reptype'];
		}

		if($insert_data)
		{
			global $plugins;

			if($update)
			{
				$this->rid = (int)$rid;
				$db->update_query('ougc_customrep', $insert_data, 'rid=\''.$this->rid.'\'');

				$plugins->run_hooks('ouc_customrep_update_rep', $this);
			}
			else
			{
				$this->rid = (int)$db->insert_query('ougc_customrep', $insert_data);

				$plugins->run_hooks('ouc_customrep_insert_rep', $this);
			}
		}
	}

	// Update espesific custom reputation
	function update_rep($data=array(), $rid=0)
	{
		$this->insert_rep($data, true, $rid);
	}

	// Set reputation data
	function set_rep_data($rid=null)
	{
		if(isset($rid) && ($reputation = $this->get_rep($rid)))
		{
			$this->rep_data = array(
				'name'		=> $reputation['name'],
				'image'		=> $reputation['image'],
				'groups'	=> explode(',', $reputation['groups']),
				'forums'	=> explode(',', $reputation['forums']),
				'disporder'	=> $reputation['disporder'],
				'visible'	=> $reputation['visible'],
				'points'	=> $reputation['points'],
				'ignorepoints'	=> $reputation['ignorepoints'],
				'reptype'	=> $reputation['reptype'],
			);
		}
		else
		{
			global $db;

			$query = $db->simple_select('ougc_customrep', 'MAX(disporder) as max_disporder');
			$disporder = (int)$db->fetch_field($query, 'max_disporder');

			$this->rep_data = array(
				'name'		=> '',
				'image'		=> '',
				'groups'	=> array(),
				'forums'	=> array(),
				'disporder'	=> ++$disporder,
				'visible'	=> 1,
				'points'	=> 0,
				'ignorepoints'	=> 0,
				'reptype'	=> '',
			);
		}

		global $mybb;

		if($mybb->request_method == 'post')
		{
			foreach((array)$mybb->input as $key => $value)
			{
				if(isset($this->rep_data[$key]))
				{
					$this->rep_data[$key] = $value;
					if($key == 'groups' || $key == 'forums')
					{
						$this->rep_data[$key] = $this->clean_array($this->rep_data[$key]);
					}
				}
			}
		}
	}

	// Validate a reputation data to insert into the DB
	function validate_rep_data()
	{
		global $lang;
		$valid = true;

		$this->validate_errors = array();
		$name = trim($this->rep_data['name']);
		if(!$name || $name > 100)
		{
			$this->validate_errors[] = $lang->ougc_customrep_error_invalidname;
			$valid = false;
		}

		$name = trim($this->rep_data['image']);
		if($name && $name > 255)
		{
			$this->validate_errors[] = $lang->ougc_customrep_error_invalidimage;
			$valid = false;
		}

		if(my_strlen((string)$this->rep_data['disporder']) > 5)
		{
			$this->validate_errors[] = $lang->ougc_customrep_error_invaliddisporder;
			$valid = false;
		}

		if($this->rep_data['reptype'] !== '' && !is_numeric($this->rep_data['reptype']) || $this->rep_data['reptype']{3})
		{
			$this->validate_errors[] = $lang->ougc_customrep_error_invalidreptype;
			$valid = false;
		}

		return $valid;
	}

	// Get a custom reputation from the DB
	function get_rep($rid=0)
	{
		$rid = (int)$rid;
		if(!isset($this->cache['reps'][$rid]))
		{
			$this->cache['reps'][$rid] = false;

			global $db;

			$query = $db->simple_select('ougc_customrep', '*', 'rid=\''.$rid.'\'');
			$reputation = $db->fetch_array($query);

			if(isset($reputation['rid']))
			{
				$this->cache['reps'][$rid] = $reputation;
			}
		}

		return $this->cache['reps'][$rid];
	}

	// Get a log from the DB
	function get_log($lid=0)
	{
		$lid = (int)$lid;
		if(!isset($this->cache['logs'][$lid]))
		{
			$this->cache['logs'][$lid] = false;

			global $db;

			$query = $db->simple_select('ougc_customrep_log', '*', 'lid=\''.$lid.'\'');
			$log = $db->fetch_array($query);

			if(isset($log['lid']))
			{
				$this->cache['logs'][$lid] = $log;
			}
		}

		return $this->cache['logs'][$lid];
	}

	// Ge espesific forum to affect
	function set_forum($fid)
	{
		global $settings;

		global $PL;
		$PL or require_once PLUGINLIBRARY;

		$reps = (array)$PL->cache_read('ougc_customrep');

		foreach($reps as $rid => &$rep)
		{
			if($rep['forums'] == '' || ($rep['forums'] != -1 && !in_array($fid, $this->clean_array($rep['forums'], false))))
			{
				unset($reps[$rid]);
				continue;
			}

			if(($name = $this->get_name($rid)))
			{
				$rep['name'] = $name;
			}

			$rep['name'] = htmlspecialchars_uni($rep['name']);
			$rep['image'] = $this->get_image($rep['image'], $rid);
			$rep['groups'] = $this->clean_array($rep['groups']);

			$this->cache['_reps'][$rid] = $rep;
		}

		if(!is_array($this->rids))
		{
			$this->rids = array();
		}

		$this->rids = array_merge($this->rids, array_keys($reps));

		$this->allowed_forum = (bool)$reps;
	}

	// Set post data
	function set_post($post=array())
	{
		if(is_array($post))
		{
			$this->post = $post;
		}
	}

	// We want multi-lang support (this doesn't work for ACP, to avoud confussions)
	function get_name($rid)
	{
		global $lang;
		$this->lang_load();

		$lang_val = 'ougc_customrep_name_'.(int)$rid;

		if(!empty($lang->$lang_val))
		{
			return $lang->$lang_val;
		}

		return false;
	}

	// is_member custom method..
	function is_member($groups, $empty=true)
	{
		if(!$groups && $empty)
		{
			return true;
		}
	
		global $PL;
		$PL or require_once PLUGINLIBRARY;

		return (bool)$PL->is_member($groups);
	}

	// Delete a complete custom reputation and any possible data related to it
	function delete_rep($rid)
	{
		global $db, $plugins;

		$args = array(
			'this'	=> &$this,
			'rid'	=> $rid,
			'logs'	=> array()
		);

		// Delete all logs.
		$query = $db->simple_select('ougc_customrep_log', 'lid', 'rid=\''.(int)$rid.'\'');
		while($lid = $db->fetch_field($query, 'lid'))
		{
			$args['logs'][$lid] = 1;
			$this->delete_log($lid);
		}

		// Now delete this custom reputation.
		$db->delete_query('ougc_customrep', 'rid=\''.(int)$rid.'\'');

		$plugins->run_hooks('ouc_customrep_delete_rep', $args);

		return true;
	}

	// Delete a reputation log. This may take up some time.
	function delete_log($lid)
	{
		global $db, $plugins;

		$args = array(
			'this'	=> &$this,
			'lid'	=> $lid,
			'uids'	=> array(),
			'rids'	=> array()
		);
		$query = $db->simple_select('reputation', 'rid, uid, pid', 'lid=\''.(int)$lid.'\'');
		while($rep = $db->fetch_array($query))
		{
			$args['uids'][(int)$rep['uid']] = 1;
			$args['rids'][(int)$rep['rid']] = 1;

			// Delete reputation
			$db->delete_query('reputation', 'rid=\''.(int)$rep['rid'].'\'');

			// Recount the reputation of this user - keep it in sync.
			$this->sync_reputation($rep['uid']);

			// MyAlerts compatibility
			if($rep['rid'] && isset($Alerts) && is_object($Alerts) && method_exists($Alerts, 'addAlert'))
			{
				$db->delete_query('alerts', 'uid =\''.(int)$rep['uid'].'\' AND from_id=\''.(int)$mybb->user['uid'].'\' AND alert_type=\'rep\' AND from_id=\''.(int)$rep['pid'].'\'');
			}
		}

		$plugins->run_hooks('ouc_customrep_delete_log', $args);

		// Now delete this log.
		$db->delete_query('ougc_customrep_log', 'lid=\''.(int)$lid.'\'');
	}

	// Insert a log into the DB
	function insert_log($rid, $reptype='', $points=0) // default = disabled
	{
		if(!isset($rid) || !isset($this->post['pid']))
		{
			die('Invalid log insertion attempt.');
		}

		global $db, $mybb, $plugins;

		$lid = (int)$db->insert_query('ougc_customrep_log', array(
			'pid'	=> (int)$this->post['pid'],
			'uid'	=> (int)$mybb->user['uid'],
			'rid'	=> (int)$rid,
			'points'	=> (float)$points,
			'dateline'	=> TIME_NOW,
		));

		$args = array(
			'this'		=> &$this,
			'reptype'	=> &$reptype,
			'lid'		=> $lid
		);
		$plugins->run_hooks('ouc_customrep_insert_log', $args);

		if($reptype !== '')
		{
			global $Alerts;
			$reptype = (int)$reptype;

			$rip = $db->insert_query('reputation', array(
				'pid'			=> (int)$this->post['pid'],
				'uid'			=> (int)$this->post['uid'],
				'adduid'		=> (int)$mybb->user['uid'],
				'reputation'	=> $reptype,
				'comments'		=> '',
				'lid'			=> $lid,
				'dateline'		=> TIME_NOW
			));

			// MyAlerts compatibility
			if($rip && isset($Alerts) && is_object($Alerts) && method_exists($Alerts, 'addAlert'))
			{
				$query = $db->simple_select('users', 'myalerts_settings', 'uid = '.(int) $reputation['uid'], 1);
				$settings = $db->fetch_field($query, 'myalerts_settings');
				$settings = json_decode($settings, true);

				if(isset($settings['rep']) || $settings['rep'] == 'on')
				{
					$Alerts->addAlert($this->post['uid'], 'rep', $this->post['pid'], $mybb->user['uid']);
				}
			}

			if($reptype != 0) // we don't add neutral reputations, so don't sync
			{
				// Recount the reputation of this user - keep it in sync.
				$this->sync_reputation($this->post['uid']);
			}
		}

		return $lid;
	}

	// Update a log
	function update_log($lid, $data=array())
	{
		global $db;
		$lid = (int)$lid;

		$update_data = array();
		if(isset($data['pid']))
		{
			$update_data['pid'] = (int)$data['pid'];
		}
		if(isset($data['uid']))
		{
			$update_data['uid'] = (int)$data['uid'];
		}
		if(isset($data['rid']))
		{
			$update_data['rid'] = (int)$data['rid'];
		}
		if(isset($data['dateline']))
		{
			$update_data['dateline'] = (int)$data['dateline'];
		}

		if($update_data)
		{
			$db->update_query('ougc_customrep_log', $update_data, 'lid=\''.$lid.'\'');

			// Since we are updating the pid, we need to update any user reputation as well
			if(isset($update_data['pid']))
			{
				$query = $db->simple_select('reputation', 'rid, uid', 'lid=\''.(int)$lid.'\'');
				while($rep = $db->fetch_array($query))
				{
					// Actually update reputation
					$db->update_query('reputation', array(
						'pid'	=> $update_data['pid'],
					), 'rid=\''.(int)$rep['rid'].'\'');

					// Recount the reputation of this user - keep it in sync.
					$this->sync_reputation($rep['uid']);
				}
			}
			return true;
		}
		return false;
	}

	// Recount the reputation of this user - keep it in sync.
	function sync_reputation($uid)
	{
		global $db;
		$uid = (int)$uid;

		$query = $db->simple_select('reputation', 'SUM(reputation) AS reputation_count', 'uid=\''.$uid.'\'');
		$reputation_count = (int)$db->fetch_field($query, 'reputation_count');

		$db->update_query('users', array('reputation' => $reputation_count), 'uid=\''.$uid.'\'');
	}
}

$GLOBALS['customrep'] = new OUGC_CustomRep;

// control_object by Zinga Burga from MyBBHacks ( mybbhacks.zingaburga.com ), 1.62
if(!function_exists('control_object'))
{
	function control_object(&$obj, $code)
	{
		static $cnt = 0;
		$newname = '_objcont_'.(++$cnt);
		$objserial = serialize($obj);
		$classname = get_class($obj);
		$checkstr = 'O:'.strlen($classname).':"'.$classname.'":';
		$checkstr_len = strlen($checkstr);
		if(substr($objserial, 0, $checkstr_len) == $checkstr)
		{
			$vars = array();
			// grab resources/object etc, stripping scope info from keys
			foreach((array)$obj as $k => $v)
			{
				if($p = strrpos($k, "\0"))
				{
					$k = substr($k, $p+1);
				}
				$vars[$k] = $v;
			}
			if(!empty($vars))
			{
				$code .= '
					function ___setvars(&$a) {
						foreach($a as $k => &$v)
							$this->$k = $v;
					}
				';
			}
			eval('class '.$newname.' extends '.$classname.' {'.$code.'}');
			$obj = unserialize('O:'.strlen($newname).':"'.$newname.'":'.substr($objserial, $checkstr_len));
			if(!empty($vars))
			{
				$obj->___setvars($vars);
			}
		}
		// else not a valid object or PHP serialize has changed
	}
}

if(!function_exists('ougc_print_selection_javascript'))
{
	function ougc_print_selection_javascript()
	{
		static $already_printed = false;

		if($already_printed)
		{
			return;
		}

		$already_printed = true;

		echo "<script type=\"text/javascript\">
		function checkAction(id)
		{
			var checked = '';

			$('.'+id+'_forums_groups_check').each(function(e, val)
			{
				if($(this).prop('checked') == true)
				{
					checked = $(this).val();
				}
			});

			$('.'+id+'_forums_groups').each(function(e)
			{
				$(this).hide();
			});

			if($('#'+id+'_forums_groups_'+checked))
			{
				$('#'+id+'_forums_groups_'+checked).show();
			}
		}
	</script>";
	}
}

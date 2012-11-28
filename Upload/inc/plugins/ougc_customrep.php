<?php

/***************************************************************************
 *
 *   OUGC Custom Reputation plugin (/inc/plugins/ougc_customrep.php)
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
}
elseif(defined('THIS_SCRIPT'))
{
	switch(THIS_SCRIPT)
	{
		case 'showthread.php':
			$plugins->add_hook('showthread_start', 'ougc_customrep_request', -1);
			$plugins->add_hook('postbit', 'ougc_customrep_postbit');

			// Moderation
			$plugins->add_hook('class_moderation_delete_thread_start', 'ougc_customrep_delete_thread');
			$plugins->add_hook('class_moderation_delete_post_start', 'ougc_customrep_delete_post');
			$plugins->add_hook('class_moderation_merge_posts', 'ougc_customrep_merge_posts');
			#$plugins->add_hook('class_moderation_merge_threads', 'ougc_customrep_merge_threads'); // seems like posts are updated instead of "re-created", good, less work
			#$plugins->add_hook('class_moderation_split_posts', 'ougc_customrep_merge_threads'); // no sure what happens here

			global $templatelist;

			if(isset($templatelist))
			{
				$templatelist .= ',';
			}
			else
			{
				$templatelist = '';
			}

			$templatelist .= 'ougccustomrep, ougccustomrep_rep, ougccustomrep_rep_voted, ougccustomrep_rep_number, ougccustomrep_rep_img, ougccustomrep_headerinclude, postbit, postbit_classic'; // somehow the postbit templates are not being cached
			break;
		case 'reputation.php':
			$plugins->add_hook('reputation_start', 'ougc_customrep_delete_reputation');
			break;
		/*case 'misc.php':
			$plugins->add_hook('goodbyespammer_options', 'ougc_customrep_goodbyespammer');
			$plugins->add_hook('goodbyespammer_actions', 'ougc_customrep_goodbyespammer_do');
			break;*/
	}
}

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
		'website'		=> 'http://mods.mybb.com/view/ougc-custom-reputation',
		'author'		=> 'Omar Gonzalez',
		'authorsite'	=> 'http://community.mybb.com/user-25096.html',
		'version'		=> '1.1',
		'versioncode'	=> 1100,
		'compatibility'	=> '16*',
		'guid' 			=> '9c6ae7c76e57f5edea5aa4697e8b064c',
		'pl_version' 	=> 11,
		'pl_url'		=> 'http://mods.mybb.com/view/pluginlibrary'
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
}

.customrep_popup {
	top: -10000px;
	display: none;
	position: fixed;
	z-index: 5;
	text-align: left;
	top: 0;
	bottom: 0;
	left: 0;
	right: 0;
	text-align: left;
	top: 0;
	width: 100%;
	height: 100%;
	background: rgba(0, 0, 0, 0.6);
	margin: auto auto;
	padding: auto auto;
}

.customrep_popup table {
	width: 350px;
	margin: 10% auto auto;
	padding: auto auto;
}', 'showthread.php');

	// Modify some templates.
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('postbit', '#'.preg_quote('<div class="post_body" id="pid_{$post[\'pid\']}">').'#i', '{$post[\'customrep\']}<div class="post_body" id="pid_{$post[\'pid\']}">');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('<div id="pid_{$post[\'pid\']}" class="post_body">').'#i', '{$post[\'customrep\']}<div id="pid_{$post[\'pid\']}" class="post_body">');
	find_replace_templatesets('postbit_reputation', '#'.preg_quote('{$post[\'userreputation\']}').'#i', '<span id="customrep_rep_{$post[\'pid\']}">{$post[\'userreputation\']}</span>');

	// Add our settings
	$PL->settings('ougc_customrep', $lang->ougc_customrep, $lang->ougc_customrep_d, array(
		'groups'	=> array(
			'title'			=> $lang->ougc_customrep_s_groups,
			'description'	=> $lang->ougc_customrep_s_groups_d,
			'optionscode'	=> 'text',
			'value'			=> '7,1,5',
		),
		'forums'	=> array(
			'title'			=> $lang->ougc_customrep_s_forums,
			'description'	=> $lang->ougc_customrep_s_forums_d,
			'optionscode'	=> 'text',
			'value'			=> '',
		),
		'firstpost'	=> array(
			'title'			=> $lang->ougc_customrep_s_firstpost,
			'description'	=> $lang->ougc_customrep_s_firstpost_d,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'delete'	=> array(
			'title'			=> $lang->ougc_customrep_s_delete,
			'description'	=> $lang->ougc_customrep_s_delete_d,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
		'ajax'	=> array(
			'title'			=> $lang->ougc_customrep_s_ajax,
			'description'	=> $lang->ougc_customrep_s_ajax_d,
			'optionscode'	=> 'yesno',
			'value'			=> 1,
		),
	));

	// Fill cache
	$customrep->update_cache();

	// Insert template/group
	$PL->templates('ougccustomrep', $lang->ougc_customrep, array(
		''						=> '<div class="customrep" id="customrep_{$customrep->post[\'pid\']}" style="float: right;">{$reputations}</div>',
		'headerinclude' 		=> '<script src="{$settings[\'bburl\']}/jscripts/ougc_customrep.js" type="text/javascript"></script>',
		'misc'					=> '<html>
	<head>
		<title>{$title} - {$mybb->settings[\'bbname\']}</title>
		{$headerinclude}
	</head>
	<body class="misc_buddypopup">
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder" style="text-align: left;">
			<tr><td class="thead" colspan="2"><strong>{$title}</strong></td></tr>
			{$content}
			<tr><td class="tfoot" colspan="2" align="center"><a onclick="window.close();">[{$lang->ougc_customrep_close}]</a></td></tr>
		</table><br />
		{$multipage}
	</body>
</html>',
		'misc_ajax'				=> '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder" style="text-align: left;">
	<tr><td class="thead" colspan="2"><strong>{$title}</strong></td></tr>
	<tr><td class="tcat" colspan="2"><strong>{$lang->ougc_customrep_popup_latest}</strong></td></tr>
	{$content}
	<tr><td class="tfoot" colspan="2"><span onclick="OUGC_CustomReputationPopUpClose()">[{$lang->ougc_customrep_close}]</span>{$fullview}</td></tr>
</table>',
		'misc_ajax_fullview'	=> '<a href="{$multipage_url}" onclick="OUGC_CustomReputationPopUpClose(); MyBB.popupWindow(\'{$multipage_url}\', \'OUGC_CustomReputationPopUp\', 350, 350); return false;" style="float: right;">{$lang->ougc_customrep_popup_fullview}</a>',
		'misc_error'			=> '<tr><td class="trow1" colspan="2">{$error_message}</td></tr>',
		'misc_row'				=> '<tr>
<td class="{$trow}" width="60%">{$log[\'profilelink_f\']}</td>
<td class="{$trow}" width="40%" align="center">{$date}</td>
</tr>',
		'rep'					=> '{$image}{$number}&nbsp;',
		'rep_img'				=> '<img src="{$reputation[\'image\']}" title="{$reputation[\'name\']}" />',
		'rep_number'			=> '&nbsp;<a href="{$mybb->settings[\'bburl\']}/{$popupurl}" onclick="return OUGC_CustomReputationPopUp(\'{$customrep->post[\'tid\']}\', \'{$customrep->post[\'pid\']}\', \'{$rid}\'); MyBB.popupWindow(\'{$mybb->settings[\'bburl\']}/{$popupurl}\', \'OUGC_CustomReputationPopUp\', 350, 350); return false;" rel="nofollow" title="{$lang->ougc_customrep_viewall}" class="number">x {$number}</a>',
		'rep_voted'				=> '<a href="{$link}" class="voted {$classextra}"{$onclick}>{$image}</a>',
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
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'customrep\']}').'#i', '', 0);
	find_replace_templatesets('postbit_reputation', '#'.preg_quote('<span id="customrep_rep_{$post[\'pid\']}">{$post[\'userreputation\']}</span>').'#i', '{$post[\'userreputation\']}', 0);

	change_admin_permission('config', 'ougc_customrep', 0);
}

// _install function
function ougc_customrep_install()
{
	global $customrep, $db;
	$customrep->meets_requirements() or $customrep->admin_redirect($customrep->message, true);

	// Drop our tables
	$db->drop_table('ougc_customrep');
	$db->drop_table('ougc_customrep_log');

	// Drop reputation field
	/*if($db->field_exists('lid', 'reputation'))
	{
		$db->drop_column('reputation', 'lid');
	}*/

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
			PRIMARY KEY (`rid`)
		) ENGINE=MyISAM{$collation};"
	);
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_customrep_log` (
			`lid` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`pid` int NOT NULL DEFAULT '0',
			`uid` int NOT NULL DEFAULT '0',
			`rid` int NOT NULL DEFAULT '0',
			`dateline` int(10) NOT NULL DEFAULT '0',
			PRIMARY KEY (`lid`)
		) ENGINE=MyISAM{$collation};"
	);

	$db->add_column('reputation', 'lid', 'int NOT NULL DEFAULT \'0\'');
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

// Postbit
function ougc_customrep_postbit(&$post)
{
	global $fid, $customrep, $tid, $templates;

	if($customrep->firstpost_only)
	{
		// useless
		/*global $thread;

		if($post['pid'] != $thread['firstpost'])
		{
			return;
		}*/

		global $plugins;

		$plugins->remove_hook('postbit', 'ougc_customrep_postbit');
	}

	if(!isset($customrep->cache['query']))
	{
		global $settings;

		$customrep->set_forum($fid);

		if(!isset($templates->cache['postbit'.($settings['postlayout'] == 'classic' ? '_classic' : '')]))
		{
			$templates->get('postbit'.($settings['postlayout'] == 'classic' ? '_classic' : ''));
		}
		#_dump($templates->cache['postbit'.($settings['postlayout'] == 'classic' ? '_classic' : '')]);

		if(!$customrep->allowed_forum || !my_strpos($templates->cache['postbit'.($settings['postlayout'] == 'classic' ? '_classic' : '')], '{$post[\'customrep\']}'))
		{
			global $plugins;

			$plugins->remove_hook('postbit', 'ougc_customrep_postbit');
			return;
		}

		if($customrep->ajax_request)
		{
			global $headerinclude;

			eval('$headerinclude .= "'.$templates->get('ougccustomrep_headerinclude').'";');
		}

		global $db, $thread;
		$customrep->cache['query'] = array();

		if($customrep->firstpost_only)
		{
			$pids = "pid='{$thread['firstpost']}'";
		}
		// Bug: http://mybbhacks.zingaburga.com/showthread.php?tid=1587&pid=12762#pid12762
		elseif($mybb->input['mode'] == 'threaded')
		{
			$mybb->input['pid'] = (int)$mybb->input['pid'];
			$pids = "pid='{$mybb->input['pid']}'";
		}
		else
		{
			$pids = $GLOBALS['pids'];
		}
	
		$query = $db->simple_select('ougc_customrep_log', '*', $pids.' AND rid IN (\''.implode('\',\'', $customrep->rids).'\')');
		while($rep = $db->fetch_array($query))
		{
			$customrep->cache['query'][$rep['rid']][$rep['pid']][$rep['lid']][$rep['uid']] = 1; //TODO
		}
	}

	$customrep->set_post(array('tid' => $post['tid'], 'pid' => $post['pid'], 'uid' => $post['uid']));

	// Now we build the reputation bit
	ougc_customrep_parse_postbit($post['customrep']);
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

	if($mybb->input['action'] == 'delete')
	{
		// Verify incoming POST request
		verify_post_check($mybb->input['my_post_key']);

		global $db;

		// Fetch the existing reputation for this user given by our current user if there is one.
		$query = $db->simple_select('reputation r LEFT JOIN '.TABLE_PREFIX.'users u ON (u.uid=r.adduid)', 'r.adduid, r.lid, u.uid, u.username', 'r.rid=\''.(int)$mybb->input['rid'].'\'');
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

			global $uid, $user;

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

	global $templates;

	$post_url = get_post_link($customrep->post['pid'], $customrep->post['tid']);

	$input = array(
		'pid' => $customrep->post['pid'],
		'my_post_key' => (isset($mybb->post_code) ? $mybb->post_code : generate_post_check()),
	);

	foreach($customrep->cache['_reps'] as $rid => $reputation)
	{
		$input['action'] = 'customrep';
		$input['rid'] = $rid;
		$link = $customrep->build_url($input);
		$input['action'] = 'customreppu';
		$popupurl = $customrep->build_url($input);

		$number = 0;
		$onclick = $onclick_delete = $classextra = '';
		if($customrep->ajax_request)
		{
			$onclick = ' onclick="return OUGC_CustomReputation(\''.$tid.'\', \''.$customrep->post['pid'].'\', \''.$mybb->post_code.'\', \''.$rid.'\');"';
			if($customrep->allow_delete)
			{
				$onclick_delete = ' onclick="return OUGC_CustomReputation(\''.$tid.'\', \''.$customrep->post['pid'].'\', \''.$mybb->post_code.'\', \''.$rid.'\', \'1\');"';
			}
		}

		// Count the votes for this reputation in this post
		if(isset($customrep->cache['query'][$rid][$customrep->post['pid']]))
		{
			$number = count($customrep->cache['query'][$rid][$customrep->post['pid']]);
		}
		$number = my_number_format($number);

		eval('$number = "'.$templates->get('ougccustomrep_rep_number', 1, 0).'";');
		eval('$image = "'.$templates->get('ougccustomrep_rep_img', 1, 0).'";');
		$image = trim($image);

		if($customrep->allow_delete && !$customrep->is_member($mybb->settings['ougc_customrep_groups']) && $customrep->is_member($reputation['groups']) && $customrep->post['uid'] != $mybb->user['uid'])
		{//TODO
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

			if($voted && $voted_this)
			{
				$onclick = $onclick_delete;
				$link .= '&amp;delete=1';
				$classextra = '_delete';
				eval('$image = "'.$templates->get('ougccustomrep_rep_voted', 1, 0).'";');
			}
			elseif(!$voted)
			{
				eval('$image = "'.$templates->get('ougccustomrep_rep_voted', 1, 0).'";');
			}
		}
		elseif(!$voted && !$customrep->is_member($mybb->settings['ougc_customrep_groups']) && $customrep->is_member($reputation['groups']) && $customrep->post['uid'] != $mybb->user['uid'])
		{//TODO
			eval('$image = "'.$templates->get('ougccustomrep_rep_voted', 1, 0).'";');
		}
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
	global $customrep, $mybb;

	$mybb->input['action'] = isset($mybb->input['action']) ? $mybb->input['action'] : '';
	if(!$customrep->active || !in_array($mybb->input['action'], array('customrep', 'customreppu')))
	{
		return;
	}

	global $tid;

	$customrep->set_url(get_thread_link($tid)); //TODO

	$mybb->input['ajax'] = ($customrep->ajax_request && isset($mybb->input['ajax']) && $mybb->input['ajax'] == 1 ? true : false);

	if($mybb->input['action'] == 'customreppu')
	{
		global $thread, $fid, $lang;

		$error = 'error';
		$mybb->input['ajax'] or ($error = 'ougc_customrep_misc_error');

		// Good bay guests :)
		if(!$mybb->user['uid'])
		{
			$error($lang->ougc_customrep_error_nopermission);
		}

		$customrep->set_forum($fid);

		$customrep->allowed_forum or $error($lang->ougc_customrep_error_invalidforum);

		$post = get_post($mybb->input['pid']);
		$customrep->set_post(array('tid' => $post['tid'], 'pid' => $post['pid'], 'uid' => $post['uid']));
		unset($post);

		!empty($customrep->post) or $error($lang->ougc_customrep_error_invlidadpost);

		if(!($reputation = $customrep->get_rep($mybb->input['rid'])))
		{
			$error($lang->ougc_customrep_error_invalidrep);
		}

		$reputation['visible'] or $error($lang->ougc_customrep_error_invalidrep);

		if($customrep->firstpost_only && $customrep->post['pid'] != $thread['firstpost'])
		{
			error($lang->ougc_customrep_error_invlidadpost); // somehow
		}

		global $db, $templates, $theme, $headerinclude, $parser;
		if(!is_object($parser))
		{
			require_once MYBB_ROOT.'inc/class_parser.php';
			$parser = new postParser;
		}

		// Save four queries here
		$templates->cache('ougccustomrep_misc_row, ougccustomrep_misc_error, ougccustomrep_misc_ajax_fullview, ougccustomrep_misc_ajax, ougccustomrep_misc, ougccustomrep_popup_error, ougccustomrep_popup_ajax, ougccustomrep_popup');

		// the ide here is to allow multipage on both, the ajax window as well as the no-ajax one.
		// Ajax one mulipage works replacing the table content with the new query result, this of course means a new template that probably will end being using it for the not-ajax version as well..
		//...
		// Probably should leave popup ajax for next version.

		// Build multipage
		$query = $db->simple_select('ougc_customrep_log', 'COUNT(lid) AS logs', "pid='{$customrep->post['pid']}' AND rid='{$reputation['rid']}'");
		$count = (int)$db->fetch_field($query, 'logs');

		$page = (int)$mybb->input['page'];

		$perpage = 10; // hard coded by now
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
		$multipage = multipage($count, $perpage, $page, $multipage_url);
		if(!$multipage)
		{
			$multipage = '';
		}

		$customrep->lang_load();

		$query = $db->query('SELECT r.*, u.username, u.usergroup, u.displaygroup
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

		if($mybb->input['ajax'])
		{
			$lang->ougc_customrep_popup_latest = $lang->sprintf($lang->ougc_customrep_popup_latest, my_number_format($perpage));
			if($multipage)
			{
				$multipage_url = str_replace('&amp;ajax=1', '', $multipage_url);
				$trow = alt_trow();
				eval('$fullview = "'.$templates->get('ougccustomrep_misc_ajax_fullview').'";');
			}
			eval('$page = "'.$templates->get('ougccustomrep_misc_ajax', 1, 0).'";');
			echo 'success|-_-|'.$page;
		}
		else
		{
			eval('$page = "'.$templates->get('ougccustomrep_misc').'";');
			output_page($page);
		}
		exit;
	}

	global $lang;
	$customrep->lang_load();

	// Good bay guests :)
	if(!$mybb->user['uid'])
	{
		error($lang->ougc_customrep_error_nopermission);
	}

	verify_post_check($mybb->input['my_post_key']);

	// Check global setting for groups
	if($customrep->is_member($mybb->settings['ougc_customrep_groups']))
	{
		error($lang->ougc_customrep_error_nopermission);
	}

	$customrep->set_post(get_post($mybb->input['pid']));

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

	if(!($reputation = $customrep->get_rep($mybb->input['rid'])))
	{
		error($lang->ougc_customrep_error_invalidrep);
	}

	if(!$reputation['visible'] || !array_key_exists($reputation['rid'], $customrep->cache['_reps']))
	{
		error($lang->ougc_customrep_error_invalidrep);
	}

	if(!$customrep->is_member($reputation['groups']))
	{
		error($lang->ougc_customrep_error_nopermission);
	}

	if($customrep->post['tid'] != $thread['tid'] || $customrep->firstpost_only && $customrep->post['pid'] != $thread['firstpost'])
	{
		error($lang->ougc_customrep_error_invlidadpost); // somehow
	}

	global $db;

	if(isset($mybb->input['delete']) && $mybb->input['delete'] == 1)
	{
		if(!$customrep->allow_delete)
		{
			error($lang->ougc_customrep_error_nopermission);
		}

		$query = $db->simple_select('ougc_customrep_log', 'lid', 'pid=\''.$customrep->post['pid'].'\' AND uid=\''.$mybb->user['uid'].'\' AND rid=\''.$reputation['rid'].'\'');

		if($db->num_rows($query) < 1)
		{
			error($lang->ougc_customrep_error_invalidrating);
		}

		while($lid = $db->fetch_field($query, 'lid'))
		{
			$customrep->delete_log($lid);
		}
	}
	else
	{
		$query = $db->simple_select('ougc_customrep_log', 'lid', "pid='{$customrep->post['pid']}' AND uid='{$mybb->user['uid']}' AND rid='{$reputation['rid']}'", array('limit' => 1));
		if($db->fetch_field($query, 'lid'))
		{
			error($lang->ougc_customrep_error_multiple); // TODO: Allow multiple ratings?
		}

		$customrep->insert_log($reputation['rid'], $reputation['reptype']);
	}

	$customrep->ajax_request or $customrep->redirect(get_post_link($customrep->post['pid'], $customrep->post['tid']));

	$query = $db->simple_select('ougc_customrep_log', '*', "pid='{$customrep->post['pid']}' AND rid='{$reputation['rid']}'");
	while($reputation = $db->fetch_array($query))
	{
		$customrep->cache['query'][$reputation['rid']][$reputation['pid']][$reputation['lid']][$reputation['uid']] = 1;
	}

	ougc_customrep_parse_postbit($post, false);
	echo 'success|-_-|'.$customrep->post['pid'].'|-_-|'.$post.'|-_-|'.get_reputation((int)$db->fetch_field($db->simple_select('users', 'reputation', 'uid=\''.$customrep->post['uid'].'\''), 'reputation'), $customrep->post['uid']);
	exit;
}

// Workaround
function ougc_customrep_misc_error()
{
	global $plugins, $mybb, $templates, $theme, $lang, $headerinclude, $ajax;

	$title = $lang->ougc_customrep_error;
	$error_message = $plugins->run_hooks('error', $error_message);
	$error_message = ($error_message ? $error_message : $lang->unknown_error);

	if($customrep->ajax_request)
	{
		echo "<error>{$error_message}</error>";
		exit;
	}

	eval('$content = "'.$templates->get('ougccustomrep_popup_error').'";');
	eval('$content = "'.$templates->get('ougccustomrep_popup_ajax', 1, 0).'";');
	eval('$page = "'.$templates->get('ougccustomrep_popup').'";');
	output_page($page);
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

	// Ajax requests
	public $ajax_request = false;

	// Construct the data (?)
	function __construct()
	{
		global $mybb;

		$plugins = $mybb->cache->read('plugins');

		// Is plugin active?
		$this->active = isset($plugins['active']['ougc_customrep']);

		if($mybb->settings['use_xmlhttprequest'] && $mybb->settings['ougc_customrep_ajax'])
		{
			$this->ajax_request = true;
		}

		$this->rids = array();

		$this->firstpost_only = (bool)$mybb->settings['ougc_customrep_firstpost'];

		$this->allow_delete = (bool)$mybb->settings['ougc_customrep_firstpost'];
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

		if(!file_exists(PLUGINLIBRARY))
		{
			global $lang;
			$this->lang_load();

			$info = ougc_customrep_info();
			$this->message = $lang->sprintf($lang->ougc_customrep_plreq, $info['pl_url'], $info['pl_version']);
			return false;
		}

		$info = ougc_customrep_info();

		$PL or require_once PLUGINLIBRARY;

		if($PL->version < $info['pl_version'])
		{
			global $lang;
			$this->lang_load();

			$this->message = $lang->sprintf($lang->ougc_customrep_plold, $PL->version, $info['pl_version'], $info['pl_url']);
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
			global $settings;
			$this->cache['images'][$rid] = false;

			// The image is external.
			if(my_strpos($image, 'ttp:/') || my_strpos($image, 'ttps:/')) 
			{
				$this->cache['images'][$rid] = $image;
			}
			elseif($image && !my_strpos($image, '/') && file_exists(MYBB_ROOT.'/images/ougc_customrep/'.$image))
			{
				$this->cache['images'][$rid] = $settings['bburl'].'/images/ougc_customrep/'.$image;
			}
			elseif(my_strpos($image, '/') && file_exists(MYBB_ROOT.$image))
			{
				$this->cache['images'][$rid] = $settings['bburl'].'/'.$image;
			}
			else
			{
				$this->cache['images'][$rid] = $settings['bburl'].'/images/ougc_customrep/default.png';
			}
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
			unset($rep['rid'], $rep['disporder'], $rep['visible'], $rep['reptype']);
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

		if(isset($data['groups']) && is_array($data['groups']))
		{
			$insert_data['groups'] = $db->escape_string($this->clean_array($data['groups']));
		}

		if(isset($data['forums']) && is_array($data['forums']))
		{
			$insert_data['forums'] = $db->escape_string($this->clean_array($data['forums']));
		}

		if(isset($data['disporder']))
		{
			$insert_data['disporder'] = (int)$data['disporder'];
		}

		if(isset($data['visible']))
		{
			$insert_data['visible'] = (int)$data['visible'];
		}

		if($data['reptype'] !== '')
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

		if($this->rep_data['reptype'] !== '' && !is_numeric($this->rep_data['reptype']))
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

		if(!in_array($fid, $this->clean_array($settings['ougc_customrep_forums'], false)))
		{
			global $PL;
			$PL or require_once PLUGINLIBRARY;

			if(!($this->cache['_reps'] = $PL->cache_read('ougc_customrep')))
			{
				$this->cache['_reps'] = array();
			}

			foreach($this->cache['_reps'] as $rid => &$rep)
			{
				if($rep['forums'] && !in_array($fid, $this->clean_array($rep['forums'], false)))
				{
					unset($this->cache['_reps'][$rid]);
					continue;
				}
				unset($this->cache['_reps'][$rid]['forums']);
				if(($name = $this->get_name($rid)))
				{
					$rep['name'] = $name;
				}
				$rep['name'] = htmlspecialchars_uni($rep['name']);
				$rep['image'] = htmlspecialchars_uni($this->get_image($rep['image'], $rid));
				$rep['groups'] = $this->clean_array($rep['groups']);
			}

			$this->rids = array_keys($this->cache['_reps']);
			$this->allowed_forum = (bool)$this->cache['_reps'];
		}
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
		$query = $db->simple_select('reputation', 'rid, uid', 'lid=\''.(int)$lid.'\'');
		#$query = $db->simple_select('reputation', 'rid, reputation, uid', 'lid=\''.(int)$lid.'\'');
		while($rep = $db->fetch_array($query))
		{
			$args['uids'][(int)$rep['uid']] = 1;
			$args['rids'][(int)$rep['rid']] = 1;
			/*$rep['reputation'] = (int)$rep['reputation'];
			if($rep['reputation'] > 0) // positive
			{
				$rrep = '-'.str_replace(array('+', '-'), '', $rep['reputation']);
			}
			elseif($rep['reputation'] < 0) // negative
			{
				$rrep = '+'.str_replace(array('+', '-'), '', $rep['reputation']);
			}
			else
			{
				$db->delete_query('reputation', 'rid=\''.(int)$rep['rid'].'\'');
				continue;
			}
			$db->update_query('users', array('reputation' => "`reputation`{$rrep}"), 'uid=\''.$rep['uid'].'\'', 1, true);// limit - no quotes*/

			// Delete reputation
			$db->delete_query('reputation', 'rid=\''.(int)$rep['rid'].'\'');

			// Recount the reputation of this user - keep it in sync.
			$this->sync_reputation($rep['uid']);
		}

		$plugins->run_hooks('ouc_customrep_delete_log', $args);

		// Now delete this log.
		$db->delete_query('ougc_customrep_log', 'lid=\''.(int)$lid.'\'');
	}

	// Insert a log into the DB
	function insert_log($rid, $reptype='') // default = disabled
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
			$reptype = (int)$reptype;
			/*if($reptype > 0) // positive
			{
				$rrep = '+'.str_replace(array('+', '-'), '', $reptype);
			}
			elseif($reptype < 0) // negative
			{
				$rrep = '-'.str_replace(array('+', '-'), '', $reptype);
			}*/

			$db->insert_query('reputation', array(
				'pid'			=> (int)$this->post['pid'],
				'uid'			=> (int)$this->post['uid'],
				'adduid'		=> (int)$mybb->user['uid'],
				'reputation'	=> $reptype,
				'lid'			=> $lid,
				'dateline'		=>	TIME_NOW
			));

			if($reptype != 0) // we don't add neutral reputations, so don't sync
			{
				// Recount the reputation of this user - keep it in sync.
				$this->sync_reputation($this->post['uid']);
				#$db->update_query('users', array('reputation' => "`reputation`{$rrep}"), "uid='{$this->post['uid']}'", 1, true);
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
				$post = get_post($update_data['pid']);
				if(!($uid = (int)$post['uid']))
				{
					return false;
				}

				$query = $db->simple_select('reputation', 'rid, uid', 'lid=\''.(int)$lid.'\'');
				while($rep = $db->fetch_array($query))
				{
					// Actually update reputation
					$db->update_query('reputation', array('pid' => $update_data['pid'], 'uid' => $uid), 'rid=\''.(int)$rep['rid'].'\'');

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
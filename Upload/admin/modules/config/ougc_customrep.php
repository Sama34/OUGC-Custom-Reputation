<?php

/***************************************************************************
 *
 *   OUGC Custom Reputation plugin (/inc/plugins/ougc_annbars.php)
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
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// Check requirements
$customrep->meets_requirements() or $customrep->admin_redirect($customrep->message, true);

$db->delete_query('reputation');
$db->update_query('users', array('reputation' => 0));
// Set current url
$customrep->set_url('index.php?module=config-ougc_customrep');

// Set/load defaults
$mybb->input['action'] = isset($mybb->input['action']) ? trim($mybb->input['action']) : '';
$mybb->input['rid'] = isset($mybb->input['rid']) ? (int)$mybb->input['rid'] : 0;
$mybb->input['page'] = (int)(isset($mybb->input['page']) ? (int)$mybb->input['page'] : 0);
$customrep->lang_load();

// Page tabs
$sub_tabs['ougc_customrep_view'] = array(
	'title'			=> $lang->ougc_customrep_tab_view,
	'link'			=> $customrep->build_url(),
	'description'	=> $lang->ougc_customrep_tab_view_d
);
$sub_tabs['ougc_customrep_add'] = array(
	'title'			=> $lang->ougc_customrep_tab_add,
	'link'			=> $customrep->build_url('action=add'),
	'description'	=> $lang->ougc_customrep_tab_add_d
);
if($mybb->input['action'] == 'edit')
{
	$sub_tabs['ougc_customrep_edit'] = array(
		'title'			=> $lang->ougc_customrep_tab_edit,
		'link'			=> $customrep->build_url(array('action' => 'edit', 'aid' => $mybb->input['aid'])),
		'description'	=> $lang->ougc_customrep_tab_edit_d
	);
}
$sub_tabs['ougc_customrep_updatecache'] = array(
	'title'			=> $lang->ougc_customrep_tab_updatecache,
	'link'			=> $customrep->build_url('action=rebuilt_cache')
);

$page->add_breadcrumb_item($lang->ougc_customrep, $sub_tabs['ougc_customrep_view']['link']);

// Update the cache
if($mybb->input['action'] == 'rebuilt_cache')
{
	$customrep->log_action();
	$customrep->admin_redirect($lang->ougc_customrep_message_updatecache, !$customrep->update_cache());
}
elseif($mybb->input['action'] == 'add' || $mybb->input['action'] == 'edit')
{
	$add = ($mybb->input['action'] == 'add' ? true : false);

	if($add)
	{
		$customrep->set_rep_data();

		$page->add_breadcrumb_item($sub_tabs['ougc_customrep_add']['title'], $sub_tabs['ougc_customrep_add']['link']);
		$page->output_header($lang->ougc_customrep_tab_add);
		$page->output_nav_tabs($sub_tabs, 'ougc_customrep_add');
	}
	else
	{
		if(!($reputation = $customrep->get_rep($mybb->input['rid'])))
		{
			$customrep->admin_redirect($lang->ougc_customrep_message_updatecache, true);
		}

		$customrep->set_rep_data($reputation['rid']);

		$page->add_breadcrumb_item($sub_tabs['ougc_customrep_edit']['title'], $sub_tabs['ougc_customrep_edit']['link']);
		$page->output_header($lang->ougc_customrep_tab_edit);
		$page->output_nav_tabs($sub_tabs, 'ougc_customrep_edit');
	}

	if($mybb->request_method == 'post')
	{
		if($customrep->validate_rep_data())
		{
			if($add)
			{
				$customrep->insert_rep($customrep->rep_data);
				$lang_var = 'ougc_customrep_message_addrep';
			}
			else
			{
				$customrep->update_rep($customrep->rep_data, $reputation['rid']);
				$lang_var = 'ougc_customrep_message_editrep';
			}
			$customrep->log_action();
			$customrep->admin_redirect($lang->$lang_var, !$customrep->update_cache());
		}
		else
		{
			$page->output_inline_error($customrep->validate_errors);
		}
	}

	if($add)
	{
		$form = new Form($customrep->build_url('action=add'), 'post');
		$form_container = new FormContainer($sub_tabs['ougc_customrep_add']['description']);
	}
	else
	{
		$form = new Form($customrep->build_url(array('action' => 'edit', 'rid' => $reputation['rid'])), 'post');
		$form_container = new FormContainer($sub_tabs['ougc_customrep_edit']['description']);
	}

	$form_container->output_row($lang->ougc_customrep_h_name.' <em>*</em>', $lang->ougc_customrep_h_name_d, $form->generate_text_box('name', $customrep->rep_data['name']));
	$form_container->output_row($lang->ougc_customrep_h_image, $lang->ougc_customrep_h_image_d, $form->generate_text_box('image', $customrep->rep_data['image']));

	// TODO: Allow multiple reputations (+2, -1, +n, -n)
	$form_container->output_row($lang->ougc_customrep_h_reptype, $lang->ougc_customrep_h_reptype_d, $form->generate_select_box('reptype', array(
		''	=> $lang->ougc_customrep_h_reptype_null,
		'-1'	=> $lang->ougc_customrep_h_reptype_neg,
		'0'	=> $lang->ougc_customrep_h_reptype_neu,
		'1'	=> $lang->ougc_customrep_h_reptype_pos
	), $customrep->rep_data['reptype']));
	$form_container->output_row($lang->ougc_customrep_f_groups, $lang->ougc_customrep_f_groups_d, $form->generate_group_select('groups[]', $customrep->rep_data['groups'], array('multiple' => 1, 'size' => 5)));
	$form_container->output_row($lang->ougc_customrep_f_forums, $lang->ougc_customrep_f_forums_d, $form->generate_forum_select('forums[]', $customrep->rep_data['forums'], array('multiple' => 1, 'size' => 5)));
	$form_container->output_row($lang->ougc_customrep_h_order, $lang->ougc_customrep_f_disporder_d, $form->generate_text_box('disporder', $customrep->rep_data['disporder'], array('style' => 'text-align: center; width: 30px;" maxlength="5')));
	$form_container->output_row($lang->ougc_customrep_h_visible, $lang->ougc_customrep_f_visible_d, $form->generate_yes_no_radio('visible', $customrep->rep_data['visible']));

	$form_container->end();

	$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_customrep_button_submit), $form->generate_reset_button($lang->reset)));

	$form->end();

	$page->output_footer();
}
elseif($mybb->input['action'] == 'delete')
{
	if(!($reputation = $customrep->get_rep($mybb->input['rid'])))
	{
		$customrep->admin_redirect($lang->ougc_customrep_message_updatecache, true);
	}

	if($mybb->request_method == 'post')
	{
		if(isset($mybb->input['no']) || $mybb->input['my_post_key'] != $mybb->post_code)
		{
			$customrep->admin_redirect();
		}

		$customrep->delete_rep($mybb->input['aid']);
		$customrep->update_cache();
		$customrep->log_action();
		$customrep->update_cache();
		$customrep->admin_redirect($lang->ougc_customrep_message_deleterep);
	}

	$page->add_breadcrumb_item($lang->delete);

	$page->output_confirm_action($customrep->build_url(array('action' => 'delete', 'rid' => $mybb->input['rid'], 'my_post_key' => $mybb->post_code)));
}
else
{
	$page->output_header($lang->ougc_customrep);
	$page->output_nav_tabs($sub_tabs, 'ougc_customrep_view');

	$table = new Table;
	$table->construct_header($lang->ougc_customrep_h_image, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->ougc_customrep_h_name, array('width' => '60%'));
	$table->construct_header($lang->ougc_customrep_h_order, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->ougc_customrep_h_visible, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->options, array('width' => '10%', 'class' => 'align_center'));

	// Multi-page support
	$perpage = (int)(isset($mybb->input['perpage']) ? (int)$mybb->input['perpage'] : 10);
	if($perpage < 1)
	{
		$perpage = 10;
	}
	elseif($perpage > 100)
	{
		$perpage = 100;
	}
	
	if($mybb->input['page'] > 0)
	{
		$start = ($mybb->input['page']-1)*$perpage;
	}
	else
	{
		$start = 0;
		$mybb->input['page'] = 1;
	}

	$query = $db->simple_select('ougc_customrep', 'COUNT(rid) AS reps');
	$repcount = (int)$db->fetch_field($query, 'reps');

	if($repcount < 1)
	{
		$table->construct_cell('<div align="center">'.$lang->ougc_customrep_message_empty.'</div>', array('colspan' => 5));
		$table->construct_row();

		$table->output($sub_tabs['ougc_customrep_view']['description']);
	}
	else
	{
		$query = $db->simple_select('ougc_customrep', '*', '', array('limit' => $perpage, 'limit_start' => $start, 'order_by' => 'disporder'));

		if($mybb->request_method == 'post' && $mybb->input['action'] == 'updatedisporder')
		{
			foreach($mybb->input['disporder'] as $rid => $disporder)
			{
				$customrep->update_rep(array('disporder' => $disporder), $rid);
			}
			$customrep->update_cache();
			$customrep->admin_redirect();
		}

		$form = new Form($customrep->build_url('action=updatedisporder'), 'post');

		while($reputation = $db->fetch_array($query))
		{
			$table->construct_cell('<img src="'.$customrep->get_image($reputation['image'], $reputation['rid']).'" />', array('class' => 'align_center'));
			$table->construct_cell(htmlspecialchars_uni($reputation['name']));
			$table->construct_cell($form->generate_text_box('disporder['.$reputation['rid'].']', (int)$reputation['disporder'], array('style' => 'text-align: center; width: 30px;')), array('class' => 'align_center'));

			$table->construct_cell(($reputation['visible'] ? $lang->yes : $lang->no), array('class' => 'align_center'));

			$popup = new PopupMenu('rep_'.$reputation['rid'], $lang->options);
			$popup->add_item($lang->ougc_customrep_tab_edit, $customrep->build_url(array('action' => 'edit', 'rid' => $reputation['rid'])));
			$popup->add_item($lang->delete, $customrep->build_url(array('action' => 'delete', 'rid' => $reputation['rid'])));
			$table->construct_cell($popup->fetch(), array('class' => 'align_center'));

			$table->construct_row();
		}

		// Set url to use
		$customrep->set_url('index.php');

		// Multipage
		if(($multipage = trim(draw_admin_pagination($mybb->input['page'], $perpage, $repcount, $customrep->build_url(false, 'page')))))
		{
			echo $multipage;
		}
		$limitstring = '<div style="float: right;">Perpage: ';
		for($p = 10; $p < 51; $p = $p+10)
		{
			$s = ' - ';
			if($p == 50)
			{
				$s = '';
			}

			if($mybb->input['page'] == $p/10)
			{
				$limitstring .= $p.$s;
			}
			else
			{
				$limitstring .= '<a href="'.$customrep->build_url(false, array('perpage', 'page')).'&perpage='.$p.'">'.$p.'</a>'.$s;
			}
		}
		$limitstring .= '</div>';
		$table->output($sub_tabs['ougc_customrep_view']['description'].$limitstring);

		$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_customrep_button_disponder), $form->generate_reset_button($lang->reset)));
		$form->end();
	}

	$page->output_footer();
}
exit;
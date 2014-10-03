<?php

/***************************************************************************
 *
 *   OUGC Custom Reputation plugin (/inc/languages/english/config_ougc_customrep.lang.php)
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

$l['ougc_customrep'] = 'OUGC Custom Reputation';
$l['ougc_customrep_d'] = 'Allow users rate posts with custom post reputations.';

// PluginLibrary
$l['ougc_customrep_plreq'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum.';
$l['ougc_customrep_plold'] = 'This plugin requires PluginLibrary version {1} or later, whereas your current version is {2}. Please do update <a href="{3}">PluginLibrary</a>.';

// Messages
$l['ougc_customrep_message_invalidrep'] = 'The selected custom reputation is invalid.';
$l['ougc_customrep_message_deleterep'] = 'The selected custom reputation was successfully deleted.';
$l['ougc_customrep_message_empty'] = 'There are not currently existing custom reputations.';
$l['ougc_customrep_message_addrep'] = 'The custom reputation was successfully added.';
$l['ougc_customrep_message_editrep'] = 'The custom reputation was successfully edited.';
		
// Header titles
$l['ougc_customrep_h_image'] = 'Image';
$l['ougc_customrep_h_image_d'] = 'Small image to identify this custom reputation.<br/><span class="smalltext">&nbsp;&nbsp;{bburl} -> Forum URL<br />
&nbsp;&nbsp;{homeurl} -> Home URL<br />
&nbsp;&nbsp;{imgdir} -> Theme Directory URL
</span>';
$l['ougc_customrep_h_name'] = 'Name';
$l['ougc_customrep_h_name_d'] = 'Short key name to identify this custom reputation (ie: Thank You).';
$l['ougc_customrep_h_order'] = 'Order';
$l['ougc_customrep_h_visible'] = 'Visible';

// Permissions page
$l['ougc_customrep_perm'] = 'Can manage custom reputations?';

// Tabs
$l['ougc_customrep_tab_view'] = 'View';
$l['ougc_customrep_tab_view_d'] = 'List of existing ratings.';
$l['ougc_customrep_tab_add'] = 'Add';
$l['ougc_customrep_tab_add_d'] = 'Add a new custom reputation.';
$l['ougc_customrep_tab_edit'] = 'Edit';
$l['ougc_customrep_tab_edit_d'] = 'Edit an existing rating.';

// Buttons
$l['ougc_customrep_button_disponder'] = 'Update Order';
$l['ougc_customrep_button_submit'] = 'Submit';

// Form
$l['ougc_customrep_f_groups'] = 'Groups';
$l['ougc_customrep_f_groups_d'] = 'Select the groups that can use this custom reputation. (none for all)';
$l['ougc_customrep_f_forums'] = 'Forums';
$l['ougc_customrep_f_forums_d'] = 'Select the forums where this custom reputation can used in. (none for all)';
$l['ougc_customrep_f_disporder_d'] = 'Order on which this custom reputation will be proccessed.';
$l['ougc_customrep_f_visible_d'] = 'Whether if to enable or disable this custom reputation.';
$l['ougc_customrep_h_reptype'] = 'Reputation Type';
$l['ougc_customrep_h_reptype_d'] = 'How does this custom reputation affect users\'s reputation.';

// Rep types
$l['ougc_customrep_h_reptype_null'] = 'Disable';
$l['ougc_customrep_h_reptype_pos'] = ' - Positive';
$l['ougc_customrep_h_reptype_neu'] = ' - Neutral';
$l['ougc_customrep_h_reptype_neg'] = ' - Negative';

// Validation
$l['ougc_customrep_error_invalidname'] = 'Invalid name.';
$l['ougc_customrep_error_invalidimage'] = 'Invalid image.';
$l['ougc_customrep_error_invaliddisporder'] = 'Invalid display order.';
$l['ougc_customrep_error_invalidreptype'] = 'Invalid reputation type.';

// Settings
$l['setting_ougc_customrep_groups'] = 'Disabled Groups';
$l['setting_ougc_customrep_groups_desc'] = 'Comma separated list of groups (GID) that can not use this feature.';
$l['setting_ougc_customrep_forums'] = 'Disabled Forums';
$l['setting_ougc_customrep_forums_desc'] = 'Comma separated list of forums (FID) where this feature can not be used in.';
$l['setting_ougc_customrep_firstpost'] = 'First Post Only';
$l['setting_ougc_customrep_firstpost_desc'] = 'Whether if enable this feature only for the first post of a thread.';
$l['setting_ougc_customrep_delete'] = 'Allow Deletion';
$l['setting_ougc_customrep_delete_desc'] = 'Allow deletion of ratings.';
$l['setting_ougc_customrep_ajax'] = 'Ajax Features';
$l['setting_ougc_customrep_ajax_desc'] = 'Whether if enable or disable ajax features.';
$l['setting_ougc_customrep_perpage'] = 'Multipage Per Page';
$l['setting_ougc_customrep_perpage_desc'] = 'Maximum number of options to show per page.';
<?php

/***************************************************************************
 *
 *   OUGC Custom Reputation plugin (/inc/languages/english/ougc_customrep.php)
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

// Error messages
$l['ougc_customrep_error'] = 'Error';
$l['ougc_customrep_error_nopermission'] = 'You don\'t have permission to do this.';
$l['ougc_customrep_error_invlidadpost'] = 'Invalid Post';
$l['ougc_customrep_error_invalidpostcode'] = 'Invalid Post Code';
$l['ougc_customrep_error_invalidthread'] = 'Invalid Thread';
$l['ougc_customrep_error_invalidforum'] = 'Invalid Forum';
$l['ougc_customrep_error_selftrating'] = 'You can\'t rate your own posts.';
$l['ougc_customrep_error_closedthread'] = 'You are not allowed to rate closed threads.';
$l['ougc_customrep_error_invalidrep'] = 'Invalid rating type.';
$l['ougc_customrep_error_invalidrating'] = 'You are trying to delete a rating that doesn\'t exists.';
$l['ougc_customrep_error_multiple'] = 'Multiple rating no allowed.';

// Misc page
$l['ougc_customrep_viewall'] = 'View who rated this.';
$l['ougc_customrep_popuptitle'] = '{1}: {2}';
$l['ougc_customrep_popup_empty'] = 'There are currently no ratings for the selected post.';
$l['ougc_customrep_popup_date'] = '{1} at {2}';
$l['ougc_customrep_popup_more'] = 'Click to view more.';
$l['ougc_customrep_popup_latest'] = 'Currently {1} ratings.';
$l['ougc_customrep_popup_fullview'] = 'View full list.';
$l['ougc_customrep_close'] = 'Close';

// UI variables
$l['ougc_customrep_viewlatest'] = 'View Latest';
$l['ougc_customrep_viewlatest_noajax'] = 'View Full List';
$l['ougc_customrep_vote'] = '{1} this post.';
$l['ougc_customrep_voted'] = 'You rated this.';
$l['ougc_customrep_voted_undo'] = 'Undo your rating to rate again.';
$l['ougc_customrep_delete'] = 'Undo {1} this post.';

// Multi-lang support for individual rate types
#$l['ougc_customrep_name_RID'] = 'Name';
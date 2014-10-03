/***************************************************************************
 *
 *   OUGC Custom Reputation plugin (/jscripts/ougc_customrep.php)
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

var OUGC_CustomReputation = {
	init: function()
	{
		$(document).ready(function(){
		});
	},
	Add: function(tid, pid, postcode, rid, del)
	{
		var deleteit = '';
		if(del == 1)
		{
			deleteit = '&delete=1';
		}

		$.ajax(
		{
			url: 'showthread.php?tid=' + tid + '&action=customrep&pid=' + pid + '&my_post_key=' + postcode + '&rid=' + rid + deleteit,
			type: 'post',
			dataType: 'json',
			success: function (request)
			{
				if(request.errors)
				{
					alert(request.errors);
					return false;
				}
				if(request.success == 1)
				{
					$('#customrep_' + parseInt(request.pid)).replaceWith(request.content);
					$('#customrep_rep_' + parseInt(request.pid)).replaceWith(request.userreputation);

					return true;
				}
			}
		});
	},
	PopUp: function(tid, pid, postcode, rid, del)
	{
		MyBB.popupWindow('showthread.php?tid=' + tid + '&pid=' + pid + '&action=customreppu&rid=' + rid + '&my_post_key=1' + postcode);
	},
}
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

function OUGC_CustomReputation(tid, pid, postcode, rid, del)
{
	OUGC_CustomReputationSpinner();

	var deleteit = '';
	if(del == 1)
	{
		deleteit = '&delete=1';
	}
	new Ajax.Request('showthread.php?tid=tid&action=customrep&pid=' + pid + '&my_post_key=' + postcode + '&rid=' + rid + '&ajax=1' + deleteit, {onComplete:OUGC_CustomReputationDo});
	return false;
}

function OUGC_CustomReputationDo(request)
{
	if(error = request.responseText.match(/<error>(.*)<\/error>/))
	{
		OUGC_CustomReputationSpinner(true);
		alert("An error occurred when rating the post.\n\n" + error[1]);
		return false;
	}
	else
	{
		content = request.responseText.split('|-_-|');
		if(content[0] != 'success')
		{
			OUGC_CustomReputationSpinner(true);
			alert('An error occurred when rating the post.');
			return false;
		}
		else
		{
			OUGC_CustomReputationSpinner(true);

			var value = document.getElementById('customrep_' + parseInt(content[1]));
			value.innerHTML = content[2];

			var y = document.getElementById('customrep_rep_' + parseInt(content[1]));
			y.innerHTML = content[3];

			return true;
		}
	}
}

function OUGC_CustomReputationPopUp(tid, pid, rid, page)
{
	OUGC_CustomReputationSpinner();

	var multipage = '';
	if(page)
	{
		multipage = '&page=' + page;
	}
	new Ajax.Request('showthread.php?tid=' + tid + '&pid=' + pid + '&action=customreppu&rid=' + rid + '&ajax=1' + multipage, {onComplete:OUGC_CustomReputationPopUpResponse});
	return false;
}

function OUGC_CustomReputationPopUpResponse(request)
{
	if(error = request.responseText.match(/<error>(.*)<\/error>/))
	{
		OUGC_CustomReputationSpinner(true);
		alert('An error occurred when opening the window.\n\n' + error[1]);
		return false;
	}
	else
	{
		content = request.responseText.split('|-_-|');
		if(content[0] != 'success')
		{
			OUGC_CustomReputationSpinner(true);
			alert('An error occurred when opening the window.');
			return false;
		}
		else
		{
			OUGC_CustomReputationSpinner(true);
			OUGC_CustomReputationPopUpClose();

			var value = document.createElement('div');
			value.id = window.ougc_modal_key;
			value.className = 'customrep_popup';
			value.innerHTML = content[1];
			document.body.appendChild(value);
			$(value.id).style.display = 'block';

			return true;
		}
	}
}

function OUGC_CustomReputationPopUpClose()
{
	window.ougc_modal_key = 'customrep_popup';

	if($(window.ougc_modal_key))
	{
		Element.remove(window.ougc_modal_key);
	}
}

function OUGC_CustomReputationSpinner(remove)
{
	
	if(remove)
	{
		window.ougc_spinner.destroy();
	}
	else
	{
		window.ougc_spinner = new ActivityIndicator('body', {image: imagepath + '/spinner_big.gif'});
	}
}
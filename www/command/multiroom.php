<?php
/**
 * moOde audio player (C) 2014 Tim Curtis
 * http://moodeaudio.org
 *
 * This Program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3, or (at your option)
 * any later version.
 *
 * This Program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once __DIR__ . '/../inc/common.php';
require_once __DIR__ . '/../inc/mpd.php';
require_once __DIR__ . '/../inc/multiroom.php';
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/sql.php';

phpSession('open_ro');

switch ($_GET['cmd']) {
	case 'get_rx_status':
        // NOTE: This is called from playerlib.js: multiroom-rx-modal
        if (!isset($_SESSION['rx_hostnames'])) {
    		$rxStatus = 'Discovery has not been run';
    	} else if ($_SESSION['rx_hostnames'] == 'No receivers found') {
    		$rxStatus = 'No receivers found';
    	} else {
    		$rxHostNames = explode(', ', $_SESSION['rx_hostnames']);
    		$rxAddresses = explode(' ', $_SESSION['rx_addresses']);
    		$rxStatus = '';
    		$timeout = getStreamTimeout();
    		$count = count($rxAddresses);

    		for ($i = 0; $i < $count; $i++) {
    			if (false === ($status = file_get_contents('http://' . $rxAddresses[$i] .
                    '/command/?cmd=trx-control.php -rx', false, $timeout))) {
    				$rxStatus .= 'rx,Unknown,?,?,?,' . $rxHostNames[$i] . ':';
    				debugLog('multiroom.php: get_rx_status failed: ' . $rxHostNames[$i]);
    			} else {
    				// rx, On/Off/Disabled/Unknown, volume, mute_1/0, mastervol_opt_in_1/0, hostname
    				$rxStatus .= $status . ',' . $rxHostNames[$i] . ':';
    			}
    		}

    		$rxStatus = empty($rxStatus) ? 'No receivers found' : rtrim($rxStatus, ':');
    	}

    	echo json_encode($rxStatus);
		break;
    case 'set_rx_status':
        $item = $_POST['item'];
    	$rxHostNames = explode(', ', $_SESSION['rx_hostnames']);
    	$rxAddresses = explode(' ', $_SESSION['rx_addresses']);

    	if (isset($_POST['onoff'])) {
    		if (false === ($result = file_get_contents('http://' . $rxAddresses[$item] . '/command/?cmd=trx-control.php -rx ' . $_POST['onoff']))) {
    			if (false === ($result = file_get_contents('http://' . $rxAddresses[$item] . '/command/?cmd=trx-control.php -rx ' . $_POST['onoff']))) {
    				workerLog('multiroom.php: set_rx_status onoff failed: ' . $rxHostNames[$item]);
    			}
    		}
    	} else if (isset($_POST['volume'])) {
    		if (false === ($result = file_get_contents('http://' . $rxAddresses[$item] . '/command/?cmd=trx-control.php -set-mpdvol ' . $_POST['volume']))) {
    			if (false === ($result = file_get_contents('http://' . $rxAddresses[$item] . '/command/?cmd=trx-control.php -set-mpdvol ' . $_POST['volume']))) {
    				workerLog('multiroom.php: set_rx_status volume failed: ' . $rxHostNames[$item]);
    			}
    		}
    	} else if (isset($_POST['mute'])) { // Toggle mute
    		if (false === ($result = file_get_contents('http://' . $rxAddresses[$item] . '/command/?cmd=vol.sh  -mute'))) {
    			if (false === ($result = file_get_contents('http://' . $rxAddresses[$item] . '/command/?cmd=vol.sh  -mute'))) {
    				workerLog('multiroom.php: set_rx_status mute failed: ' . $rxHostNames[$item]);
    			}
    		}
    	}

    	echo json_encode('OK');
        break;
    case 'upd_tx_adv_toggle':
	case 'upd_rx_adv_toggle':
		phpSession('open');
		$_SESSION[$_GET['cmd']] = $_POST['adv_toggle'];
        phpSession('close');
		break;
}

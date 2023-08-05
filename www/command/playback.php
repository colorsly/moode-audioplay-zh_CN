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
require_once __DIR__ . '/../inc/music-library.php';
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/sql.php';

$dbh = sqlConnect();
$sock = getMpdSock();

switch ($_GET['cmd']) {
	case 'upd_volume':
		// Local MPD volume
		phpSession('open');
		$currentVol = $_SESSION['volknob'];
		phpSession('write', 'volknob', $_POST['volknob']);
		phpSession('close');
		sendMpdCmd($sock, 'setvol ' . $_POST['volknob']);
		$resp = readMpdResp($sock);

		// Receiver(s) MPD volume
		if ($_SESSION['multiroom_tx'] == 'On') {
			$volDiff = $currentVol - $_POST['volknob'];
			if ($_POST['event'] == 'unmute') {
				$rxVolCmd = '-mute'; // Toggle mute off
			} else if ($volDiff == 0) {
				$rxVolCmd = $_POST['volknob'];
			} else {
				$rxVolCmd = $volDiff < 0 ? '-up ' . abs($volDiff) : '-dn ' . $volDiff;
			}
			updReceiverVol($rxVolCmd, true); // Master volume change
		}

		echo json_encode('OK');
		break;
	case 'mute_rx_vol':
		phpSession('open_ro');
		updReceiverVol('-mute');
		echo json_encode('OK');
		break;
	case 'toggle_ashuffle':
		phpSession('open');
		phpSession('write', 'ashuffle', $_POST['toggle_value']);
		phpSession('close');
		$_POST['toggle_value'] == '1' ? startAutoShuffle() : stopAutoShuffle();
		break;
	case 'get_mpd_status':
		echo json_encode(getMpdStatus($sock));
		break;
	case 'reset_screen_saver':
		if (submitJob($_GET['cmd'])) {
			echo json_encode('已提交进程');
		} else {
			echo json_encode('工作进程忙');
		}
		break;
	case 'upd_clock_radio':
		if (submitJob($_GET['cmd'])) {
			echo json_encode('已提交进程');
		} else {
			echo json_encode('工作进程忙');
		}
		break;
	case 'set_bg_image':
		if (submitJob($_GET['cmd'], $_POST['blob'])) {
			echo json_encode('已提交进程');
		} else {
			echo json_encode('工作进程忙');
		}
		break;
	case 'remove_bg_image':
		sysCmd('rm /var/local/www/imagesw/bgimage.jpg');
		echo json_encode('OK');
		break;
	case 'get_play_history':
		echo json_encode(getPlayHistory(shell_exec('cat /var/local/www/playhistory.log')));
		break;
}

// Close MPD socket
if (isset($sock) && $sock !== false) {
	closeMpdSock($sock);
}

// parse play history log
function getPlayHistory($resp) {
	if (is_null($resp) ) {
		return 'getPlayHistory(): Response is null';
	}
	else {
		$array = array();
		$line = strtok($resp, "\n");
		$i = 0;

		while ( $line ) {
			$array[$i] = $line;
			$i++;
			$line = strtok("\n");
		}
	}

	return $array;
}

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
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/sql.php';

switch ($_GET['cmd']) {
	case 'reboot':
	case 'poweroff':
		if (submitJob($_GET['cmd'])) {
			echo json_encode('已提交进程');
		} else {
			echo json_encode('工作进程忙');
		}
		break;
	case 'get_client_ip':
		echo json_encode($_SERVER['REMOTE_ADDR']);
		break;
	case 'restart_localui':
		phpSession('open_ro');
		if ($_SESSION['localui'] == '1') {
			if (submitJob('localui_restart')) {
				echo json_encode('已提交进程');
			} else {
				echo json_encode('工作进程忙');
			}
		}
		break;
}

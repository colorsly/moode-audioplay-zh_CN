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
 * 汉化：Androidnews
 */

require_once __DIR__ . '/inc/common.php';
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/sql.php';

$dbh = sqlConnect();
phpSession('open');

if (isset($_POST['save']) && $_POST['save'] == '1') {
	$_POST['config']['upnpav'] = $_POST['config']['svctype'] == 'upnpav' ? '1' : '0';
	$_POST['config']['openhome'] = $_POST['config']['svctype'] == 'openhome' ? '1' : '0';

	foreach ($_POST['config'] as $key => $value) {
		sqlUpdate('cfg_upnp', $dbh, $key, $value);

		if ($value != '') {
			sysCmd("sed -i '/" . $key . ' =' . '/c\\' . $key . ' = ' . $value . "' /etc/upmpdcli.conf");
		}
		else {
			sysCmd("sed -i '/" . $key . ' =' . '/c\\' . '#' . $key . ' = ' . $value . "' /etc/upmpdcli.conf");
		}
	}

	submitJob('upnpsvc', '', '设置已更新', ($_SESSION['upnpsvc'] == '1' ? 'UPnP已重新启动' : ''));
}

phpSession('close');

$result = sqlRead('cfg_upnp', $dbh);
$cfgUPNP = array();

foreach ($result as $row) {
	$cfgUPNP[$row['param']] = $row['value'];
}

$_select['checkcontentformat'] .= "<option value=\"1\" " . (($cfgUPNP['checkcontentformat'] == '1') ? "selected" : "") . ">是</option>\n";
$_select['checkcontentformat'] .= "<option value=\"0\" " . (($cfgUPNP['checkcontentformat'] == '0') ? "selected" : "") . ">否</option>\n";
$_select['svctype'] .= "<option value=\"upnpav\" " . (($cfgUPNP['upnpav'] == '1') ? "selected" : "") . ">UPnP-A/V</option>\n";
$_select['svctype'] .= "<option value=\"openhome\" " . (($cfgUPNP['openhome'] == '1') ? "selected" : "") . ">OpenHome</option>\n";

waitWorker(1, 'upp-config');

$tpl = "upp-config.html";
$section = basename(__FILE__, '.php');
storeBackLink($section, $tpl);

include('header.php');
eval("echoTemplate(\"" . getTemplate("templates/$tpl") . "\");");
include('footer.min.php');

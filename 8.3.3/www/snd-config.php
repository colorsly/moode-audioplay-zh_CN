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
require_once __DIR__ . '/inc/alsa.php';
require_once __DIR__ . '/inc/cdsp.php';
require_once __DIR__ . '/inc/eqp.php';
require_once __DIR__ . '/inc/mpd.php';
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/sql.php';

$dbh = sqlConnect();
$cdsp = new CamillaDsp($_SESSION['camilladsp'], $_SESSION['cardnum'], $_SESSION['camilladsp_quickconv']);
phpSession('open');

// AUDIO OUTPUT

// Output device
if (isset($_POST['update_output_device']) && $_POST['output_device'] != $_SESSION['cardnum']) {
	 // AirPlay & Spotify restarted if device (cardnum) changed
	$deviceChange = $_POST['output_device'] != $_SESSION['cardnum'] ? 1 : 0;
	// 0 = Special mixer change action not required
	$mixerChange = 0;

	sqlUpdate('cfg_mpd', $dbh, 'device', $_POST['output_device']);
	$queueArgs = $deviceChange . ',' . $mixerChange;
	submitJob('mpdcfg', $queueArgs, '设置已更新', 'MPD已重启');
}
// Volume type
if (isset($_POST['update_volume_type']) && $_POST['mixer_type'] != $_SESSION['mpdmixer']) {
	$mixerTypeSelected = $_POST['mixer_type'];
	$camillaDspVolumeSync = 'off';

	if ($mixerTypeSelected == 'none' || $mixerTypeSelected == 'null') {
		// Changing to Fixed (0dB) or Null
		$mixerChange = 'fixed_or_null';
	} else if ($mixerTypeSelected == 'camilladsp') {
		if (doesCamillaDSPCfgHaveVolFilter()) {
			$mixerTypeSelected = 'null';
			$mixerChange = 'fixed_or_null';
			$camillaDspVolumeSync = 'on';
		} else {
			$mixerTypeSelected = 'no_volume_filter';
		}
	} else if ($_SESSION['mpdmixer'] == 'none' || $_SESSION['mpdmixer'] == 'null') {
		// Changing from Fixed (0dB) or Null
		$mixerChange = $mixerTypeSelected;
	} else {
		// 0 = Special mixer change action not required
		$mixerChange = 0;
	}

	if ($mixerTypeSelected == 'no_volume_filter') {
		$_SESSION['notify']['title'] = '无法设置为CamillaDSP';
		$_SESSION['notify']['msg'] = '当前CamillaDSP配置不包含音量筛选器';
		$_SESSION['notify']['duration'] = 5;
	} else {
		phpSession('write', 'camilladsp_volume_sync', $camillaDspVolumeSync);
		$deviceChange = 0;
		sqlUpdate('cfg_mpd', $dbh, 'mixer_type', $mixerTypeSelected);
		$queueArgs = $deviceChange . ',' . $mixerChange;
		submitJob('mpdcfg', $queueArgs, '设置已更新', 'MPD已重启');
	}
}
// CamillaDSP volume range
if (isset($_POST['update_camilladsp_volume_range']) && $_POST['camilladsp_volume_range'] != $_SESSION['camilladsp_volume_range']) {
	$_SESSION['camilladsp_volume_range'] = $_POST['camilladsp_volume_range'];
	sysCmd("sed -i '/dynamic_range/c\dynamic_range = " . $_SESSION['camilladsp_volume_range'] . "' /etc/mpd2cdspvolume.config");
	sysCmd('systemctl restart mpd2cdspvolume');
	$_SESSION['notify']['title'] = '设置已更新';
}

// I2S AUDIO DEVICE

// Flag that controls what is displayed in the Output device field after changing I2S device or overlay
$rebootRequired = 0;
// Named device
if (isset($_POST['update_i2s_device'])) {
	if (isset($_POST['i2sdevice']) && $_POST['i2sdevice'] != $_SESSION['i2sdevice']) {
		phpSession('write', 'i2sdevice', $_POST['i2sdevice']);
		$msg = $_POST['i2sdevice'] == 'None' ?
			'<b>需要重启</b><br>重新启动后，选择输出设备。' :
			'<b>需要重启</b><br>重新启动后，编辑芯片和/或驱动程序选项。';
		$rebootRequired = 1;
		submitJob('i2sdevice', $_POST['i2sdevice'], '设置已更新', $msg, 300);
	}
}
// Device overlay
if (isset($_POST['update_i2s_overlay'])) {
	if (isset($_POST['i2soverlay']) && $_POST['i2soverlay'] != $_SESSION['i2soverlay']) {
		phpSession('write', 'i2soverlay', $_POST['i2soverlay']);
		$msg = $_POST['i2soverlay'] == 'None' ?
			'<b>需要重启</b><br>重新启动后，选择输出设备。' :
			'<b>需要重启</b>';
		$rebootRequired = 1;
		submitJob('i2sdevice', 'None', '设置已更新', $msg, 300);
	}
}
// Driver options
if (isset($_POST['update_drvoptions'])) {
	if (isset($_POST['drvoptions']) && $_POST['drvoptions'] != 'none') {
		$result = sqlQuery("SELECT driver, drvoptions FROM cfg_audiodev WHERE name='" . $_SESSION['i2sdevice'] . "'", $dbh);
		$driver = explode(',', $result[0]['driver']);
		$driverUpd = $_POST['drvoptions'] == 'Enabled' ? $driver[0] . ',' . $result[0]['drvoptions'] : $driver[0];
		$result = sqlQuery("UPDATE cfg_audiodev SET driver='" . $driverUpd . "' WHERE name='" . $_SESSION['i2sdevice'] . "'", $dbh);
		submitJob('i2sdevice', $_SESSION['i2sdevice'], '设置已更新', '需要重启');
	}
}

// ALSA OPTIONS

// Max volume
if (isset($_POST['update_alsavolume_max'])) {
	if (isset($_POST['alsavolume_max'])) {
		submitJob('alsavolume_max', $_POST['alsavolume_max'], '设置已更新', '');
		phpSession('write', 'alsavolume_max', $_POST['alsavolume_max']);
	}
}
// Output mode
if (isset($_POST['update_alsa_output_mode'])) {
	if (isset($_POST['alsa_output_mode']) && $_POST['alsa_output_mode'] != $_SESSION['alsa_output_mode']) {
		$oldOutputMode = $_SESSION['alsa_output_mode'];
		$newOutputMode = $_POST['alsa_output_mode'];
		// NOTE: Update session first for functions used in job
		phpSession('write', 'alsa_output_mode', $newOutputMode);
		submitJob('alsa_output_mode', $oldOutputMode, '设置已更新', '');
	}
}
// Loopback
if (isset($_POST['update_alsa_loopback'])) {
	if (isset($_POST['alsa_loopback']) && $_POST['alsa_loopback'] != $_SESSION['alsa_loopback']) {
		// Check to see if module is in use
		if ($_POST['alsa_loopback'] == 'Off') {
			$result = sysCmd('sudo modprobe -r snd-aloop');
			if (!empty($result)) {
				$_SESSION['notify']['title'] = '无法关闭';
				$_SESSION['notify']['msg'] = '环回正在使用中';
				$_SESSION['notify']['duration'] = 5;
			} else {
				submitJob('alsa_loopback', 'Off', '设置已更新', '');
				phpSession('write', 'alsa_loopback', 'Off');
			}
		} else {
			submitJob('alsa_loopback', 'On', '设置已更新', '');
			phpSession('write', 'alsa_loopback', 'On');
		}
	}
}

// MPD OPTIONS

// General

// Restart mpd
if (isset($_POST['mpdrestart']) && $_POST['mpdrestart'] == 1) {
	submitJob('mpdrestart', '', 'MPD已重启', '');
}
// Autoplay last played item after reboot/powerup
if (isset($_POST['autoplay']) && $_POST['autoplay'] != $_SESSION['autoplay']) {
	$_SESSION['notify']['title'] = '设置已更新';
	phpSession('write', 'autoplay', $_POST['autoplay']);
}

// Metadata file
if (isset($_POST['extmeta']) && $_POST['extmeta'] != $_SESSION['extmeta']) {
	phpSession('write', 'extmeta', $_POST['extmeta']);
	$_SESSION['notify']['title'] = '设置已更新';
}

// Auto-shuffle

// Service
if (isset($_POST['ashufflesvc']) && $_POST['ashufflesvc'] != $_SESSION['ashufflesvc']) {
	$_SESSION['notify']['title'] = '设置已更新';
	phpSession('write', 'ashufflesvc', $_POST['ashufflesvc']);
	// Turn off MPD random play so no conflict
	$sock = openMpdSock('localhost', 6600);
	sendMpdCmd($sock, 'random 0');
	$resp = readMpdResp($sock);
	// Kill the service if indicated
	if ($_POST['ashufflesvc'] == 0) {
		sysCmd('killall -s 9 ashuffle > /dev/null');
		phpSession('write', 'ashuffle', '0');
		sendMpdCmd($sock, 'consume 0');
		$resp = readMpdResp($sock);
	}
}
// Mode
if (isset($_POST['update_ashuffle_mode']) && $_POST['ashuffle_mode'] != $_SESSION['ashuffle_mode']) {
	phpSession('write', 'ashuffle_mode', $_POST['ashuffle_mode']);
	if ($_SESSION['ashuffle'] == '1') {
		$_SESSION['notify']['title'] = '设置已更新';
		$_SESSION['notify']['msg'] = '随机播放已关闭';
		stopAutoShuffle();
	} else {
		$_SESSION['notify']['title'] = '设置已更新';
	}
}
// Filter
if (isset($_POST['update_ashuffle_filter']) && $_POST['ashuffle_filter'] != $_SESSION['ashuffle_filter']) {
	$trim_filter = trim($_POST['ashuffle_filter']);
	phpSession('write', 'ashuffle_filter', ($trim_filter == '' ? 'None' : $trim_filter));
	if ($_SESSION['ashuffle'] == '1') {
		$_SESSION['notify']['title'] = '设置已更新';
		$_SESSION['notify']['msg'] = '随机播放已关闭';
		stopAutoShuffle();
	} else {
		$_SESSION['notify']['title'] = '设置已更新';
	}
}

// Volume options

// Volume step limit
if (isset($_POST['volume_step_limit']) && $_POST['volume_step_limit'] != $_SESSION['volume_step_limit']) {
	phpSession('write', 'volume_step_limit', $_POST['volume_step_limit']);
	$_SESSION['notify']['title'] = '设置已更新';
}
// Volume MPD mmax
if (isset($_POST['volume_mpd_max']) && $_POST['volume_mpd_max'] != $_SESSION['volume_mpd_max']) {
	phpSession('write', 'volume_mpd_max', $_POST['volume_mpd_max']);
	$_SESSION['notify']['title'] = '设置已更新';
}
// Display dB volume
if (isset($_POST['update_volume_db_display']) && $_POST['volume_db_display'] != $_SESSION['volume_db_display']) {
	phpSession('write', 'volume_db_display', $_POST['volume_db_display']);
	$_SESSION['notify']['title'] = '设置已更新';
}
// USB volume knob
if (isset($_POST['update_usb_volknob']) && $_POST['usb_volknob'] != $_SESSION['usb_volknob']) {
	submitJob('usb_volknob', $_POST['usb_volknob'], '设置已更新', '');
	phpSession('write', 'usb_volknob', $_POST['usb_volknob']);
}
// Rotary encoder
if (isset($_POST['update_rotenc'])) {
	if (isset($_POST['rotenc_params']) && $_POST['rotenc_params'] != $_SESSION['rotenc_params']) {
		$title = '设置已更新';
		phpSession('write', 'rotenc_params', $_POST['rotenc_params']);
	}
	if (isset($_POST['rotaryenc']) && $_POST['rotaryenc'] != $_SESSION['rotaryenc']) {
		$title = '设置已更新';
		phpSession('write', 'rotaryenc', $_POST['rotaryenc']);
	}
	if (isset($title)) {
		submitJob('rotaryenc', $_POST['rotaryenc'], $title, '');
	}
}

// DSP options

// Crossfade
if (isset($_POST['mpdcrossfade']) && $_POST['mpdcrossfade'] != $_SESSION['mpdcrossfade']) {
	submitJob('mpdcrossfade', $_POST['mpdcrossfade'], '设置已更新', '');
	phpSession('write', 'mpdcrossfade', $_POST['mpdcrossfade']);
}
// Crossfeed
if (isset($_POST['crossfeed']) && $_POST['crossfeed'] != $_SESSION['crossfeed']) {
	phpSession('write', 'crossfeed', $_POST['crossfeed']);
	submitJob('crossfeed', $_POST['crossfeed'], '设置已更新', '');
}
// Polarity inversion
if (isset($_POST['update_invert_polarity']) && $_POST['invert_polarity'] != $_SESSION['invert_polarity']) {
	submitJob('invpolarity', $_POST['invert_polarity'], '设置已更新', '');
	phpSession('write', 'invert_polarity', $_POST['invert_polarity']);
}

// HTTP streaming

// Server
if (isset($_POST['mpd_httpd']) && $_POST['mpd_httpd'] != $_SESSION['mpd_httpd']) {
	submitJob('mpd_httpd', $_POST['mpd_httpd'], '设置已更新', '');
	phpSession('write', 'mpd_httpd', $_POST['mpd_httpd']);
}
// Port
if (isset($_POST['mpd_httpd_port']) && $_POST['mpd_httpd_port'] != $_SESSION['mpd_httpd_port']) {
	phpSession('write', 'mpd_httpd_port', $_POST['mpd_httpd_port']);
	submitJob('mpd_httpd_port', $_POST['mpd_httpd_port'], '设置已更新', 'MPD已重启');
}
// Encoder
if (isset($_POST['mpd_httpd_encoder']) && $_POST['mpd_httpd_encoder'] != $_SESSION['mpd_httpd_encoder']) {
	phpSession('write', 'mpd_httpd_encoder', $_POST['mpd_httpd_encoder']);
	submitJob('mpd_httpd_encoder', $_POST['mpd_httpd_encoder'], '设置已更新', 'MPD已重启');
}

// EQUALIZERS

// CamillaDSP
if (isset($_POST['update_camilladsp']) && isset($_POST['camilladsp']) && $_POST['camilladsp'] != $_SESSION['camilladsp']) {
	$currentMode = $_SESSION['camilladsp'];
	$newMode = $_POST['camilladsp'];
	phpSession('write', 'camilladsp', $_POST['camilladsp']);
	$cdsp->selectConfig($_POST['camilladsp']);

	if ($_SESSION['cdsp_fix_playback'] == 'Yes' ) {
		$cdsp->setPlaybackDevice($_SESSION['cardnum'], $_SESSION['alsa_output_mode']);
	}

	updateCamillaDSPCfg($newMode, $currentMode, $cdsp);
}
// Parametric eq
$eqfa12p = Eqp12($dbh);
if (isset($_POST['eqfa12p']) && ((intval($_POST['eqfa12p']) ? "On" : "Off") != $_SESSION['eqfa12p'] || intval($_POST['eqfa12p']) != $eqfa12p->getActivePresetIndex())) {
	// Pass old,new curve name to worker job
	$currentActive = $eqfa12p->getActivePresetIndex();
	$newActive = intval($_POST['eqfa12p']);
	$eqfa12p->setActivePresetIndex($newActive);
	phpSession('write', 'eqfa12p', $newActive == 0 ? "Off" : "On");
	submitJob('eqfa12p', $currentActive . ',' . $newActive, '设置已更新', 'MPD已重启');
}
unset($eqfa12p);
// Graphic eq
if (isset($_POST['alsaequal']) && $_POST['alsaequal'] != $_SESSION['alsaequal']) {
	// Pass old,new curve name to worker job
	phpSession('write', 'alsaequal', $_POST['alsaequal']);
	submitJob('alsaequal', $_SESSION['alsaequal'] . ',' . $_POST['alsaequal'], '设置已更新', '');
}

phpSession('close');

$result = sqlRead('cfg_mpd', $dbh);
$cfgMPD = array();
foreach ($result as $row) {
	$cfgMPD[$row['param']] = $row['value'];
}

// AUDIO OUTPUT

// Output device
// Pi HDMI 1 & 2, Pi Headphone jack, I2S device, USB device
$dev = $rebootRequired == true ? array('********') : getAlsaDeviceNames();
if ($dev[0] != '') {$_mpd_select['device'] .= "<option value=\"0\" " . (($cfgMPD['device'] == '0') ? "selected" : "") . " >$dev[0]</option>\n";}
if ($dev[1] != '') {$_mpd_select['device'] .= "<option value=\"1\" " . (($cfgMPD['device'] == '1') ? "selected" : "") . " >$dev[1]</option>\n";}
if ($dev[2] != '') {$_mpd_select['device'] .= "<option value=\"2\" " . (($cfgMPD['device'] == '2') ? "selected" : "") . " >$dev[2]</option>\n";}
if ($dev[3] != '') {$_mpd_select['device'] .= "<option value=\"3\" " . (($cfgMPD['device'] == '3') ? "selected" : "") . " >$dev[3]</option>\n";}
$cards = getAlsaCards();
$_device_error = ($_SESSION['i2sdevice'] == 'None' && $_SESSION['i2soverlay'] == 'None' && $cards[$cfgMPD['device']] == 'empty') ? '设备已关闭或断开连接' : '';
// Volume type
// Hardware, Software, Fixed (0dB), Null (External control), CamillaDSP
if ($_SESSION['alsavolume'] != 'none') {
	$_mpd_select['mixer_type'] .= "<option value=\"hardware\" " . (($cfgMPD['mixer_type'] == 'hardware') ? "selected" : "") . ">Hardware</option>\n";
}
$_mpd_select['mixer_type'] .= "<option value=\"software\" " .
	($cfgMPD['mixer_type'] == 'software' ? "selected" : "") . ">Software</option>\n";
$_mpd_select['mixer_type'] .= "<option value=\"none\" " .
	($cfgMPD['mixer_type'] == 'none' ? "selected" : "") . ">Fixed (0dB output)</option>\n";
$_mpd_select['mixer_type'] .= "<option value=\"null\" " .
	($cfgMPD['mixer_type'] == 'null' && $_SESSION['camilladsp_volume_sync'] == 'off' ? "selected" : "") . ">Null（外部控制）</option>\n";
if ($_SESSION['camilladsp'] != 'off') {
	$_mpd_select['mixer_type'] .= "<option value=\"camilladsp\" " .
		(($cfgMPD['mixer_type'] == 'null' && $_SESSION['camilladsp_volume_sync'] == 'on') ? "selected" : "") . ">CamillaDSP</option>\n";
	$_camilladsp_volume_range_hide = $cfgMPD['mixer_type'] == 'null' && $_SESSION['camilladsp_volume_sync'] == 'on' ? '' : 'hide';
	$_select['camilladsp_volume_range'] .= "<option value=\"30\" " . (($_SESSION['camilladsp_volume_range'] == '30') ? "selected" : "") . ">30 dB</option>\n";
	$_select['camilladsp_volume_range'] .= "<option value=\"40\" " . (($_SESSION['camilladsp_volume_range'] == '40') ? "selected" : "") . ">40 dB</option>\n";
	$_select['camilladsp_volume_range'] .= "<option value=\"50\" " . (($_SESSION['camilladsp_volume_range'] == '50') ? "selected" : "") . ">50 dB</option>\n";
	$_select['camilladsp_volume_range'] .= "<option value=\"60\" " . (($_SESSION['camilladsp_volume_range'] == '60') ? "selected" : "") . ">60 dB</option>\n";
	$_select['camilladsp_volume_range'] .= "<option value=\"70\" " . (($_SESSION['camilladsp_volume_range'] == '70') ? "selected" : "") . ">70 dB</option>\n";
	$_select['camilladsp_volume_range'] .= "<option value=\"80\" " . (($_SESSION['camilladsp_volume_range'] == '80') ? "selected" : "") . ">80 dB</option>\n";
} else {
	$_camilladsp_volume_range_hide = 'hide';
}
// Named I2S devices
$result = sqlQuery("SELECT name FROM cfg_audiodev WHERE iface='I2S' AND list='yes'", $dbh);
sort($result);
$array = array();
$array[0]['name'] = 'None';
$dacList = array_merge($array, $result);
foreach ($dacList as $dac) {
	$selected = ($_SESSION['i2sdevice'] == $dac['name']) ? ' selected' : '';
	$_i2s['i2sdevice'] .= sprintf('<option value="%s"%s>%s</option>\n', $dac['name'], $selected, $dac['name']);
}
// DT overlays
$overlayList = sysCmd('moodeutl -o');
array_unshift($overlayList, 'None');
foreach ($overlayList as $overlay) {
	$overlayName = ($overlay == 'None') ? $overlay : substr($overlay, 0, -5); // Strip .dtbo extension
	// NOTE: This can be used to filter the list
	/*$result = sqlQuery("SELECT name FROM cfg_audiodev WHERE iface='I2S' AND list='yes' AND driver='" . $overlayName . "'", $dbh);
	if ($result === true || $overlayName == 'None') { // true = query executed but returnes no results
		$selected = ($_SESSION['i2soverlay'] == $overlayName) ? ' selected' : '';
		$_i2s['i2soverlay'] .= sprintf('<option value="%s"%s>%s</option>\n', $overlayName, $selected, $overlayName);
	}*/
	$selected = ($_SESSION['i2soverlay'] == $overlayName) ? ' selected' : '';
	$_i2s['i2soverlay'] .= sprintf('<option value="%s"%s>%s</option>\n', $overlayName, $selected, $overlayName);
}
// Driver options
$result = sqlQuery("SELECT chipoptions, driver, drvoptions FROM cfg_audiodev WHERE name='" . $_SESSION['i2sdevice'] . "'", $dbh);
if (!empty($result[0]['drvoptions']) && $_SESSION['i2soverlay'] == 'None') {
	$_select['drvoptions'] .= "<option value=\"Enabled\" " . ((strpos($result[0]['driver'], $result[0]['drvoptions']) !== false) ? "selected" : "") . ">" . $result[0]['drvoptions'] . " 启用</option>\n";
	$_select['drvoptions'] .= "<option value=\"Disabled\" " . ((strpos($result[0]['driver'], $result[0]['drvoptions']) === false) ? "selected" : "") . ">" . $result[0]['drvoptions'] . " 禁用</option>\n";
	$_driveropt_btn_disable = '';
} else {
	$_select['drvoptions'] .= "<option value=\"none\" selected>无可用</option>\n";
	$_driveropt_btn_disable = 'disabled';
}

// Button disables
if ($_SESSION['audioout'] == 'Bluetooth' || $_SESSION['multiroom_tx'] == 'On' || $_SESSION['multiroom_rx'] == 'On') {
	$_output_device_btn_disabled = 'disabled';
	$_volume_type_btn_disabled = 'disabled';
	$_driveropt_btn_disable = 'disabled';
	$_chip_btn_disable = 'disabled';
	$_chip_link_disable = 'onclick="return false;"';
	$_i2sdevice_btn_disable = 'disabled';
	$_i2soverlay_btn_disable = 'disabled';
} else {
	$_output_device_btn_disabled = ($_SESSION['i2sdevice'] == 'None' && $_SESSION['i2soverlay'] == 'None') ? '' : 'disabled';
	$_volume_type_btn_disabled = '';
	$_i2sdevice_btn_disable = $_SESSION['i2soverlay'] == 'None' ? '' : 'disabled';
	$_i2soverlay_btn_disable = $_SESSION['i2sdevice'] == 'None' ? '' : 'disabled';
	$_chip_btn_disable = (!empty($result[0]['chipoptions']) && $_SESSION['i2soverlay'] == 'None') ? '' : 'disabled';
	$_chip_link_disable = (!empty($result[0]['chipoptions']) && $_SESSION['i2soverlay'] == 'None') ? '' : 'onclick="return false;"';
}

// ALSA OPTIONS

// Max volume
if ($_SESSION['alsavolume'] == 'none') {
	$_alsavolume_max = '';
	$_alsavolume_max_readonly = 'readonly';
	$_alsavolume_max_hide = 'hide'; // Hides the SET button
	$_alsavolume_max_msg = "<em>&nbsp;Hardware volume controller not detected</em>";
} else {
	$_alsavolume_max = $_SESSION['alsavolume_max'];
	$_alsavolume_max_readonly = '';
	$_alsavolume_max_hide = '';
	$_alsavolume_max_msg = '';
}
// Output mode
$_alsa_output_mode_disable = $_SESSION['alsa_loopback'] == 'Off' ? '' : 'disabled';
$_select['alsa_output_mode'] .= "<option value=\"plughw\" " . (($_SESSION['alsa_output_mode'] == 'plughw') ? "selected" : "") . ">Default (plughw)</option>\n";
$_select['alsa_output_mode'] .= "<option value=\"hw\" " . (($_SESSION['alsa_output_mode'] == 'hw') ? "selected" : "") . ">Direct (hw)</option>\n";
// Loopback
$_alsa_loopback_disable = $_SESSION['alsa_output_mode'] == 'plughw' ? '' : 'disabled';
$_select['alsa_loopback_on']  .= "<input type=\"radio\" name=\"alsa_loopback\" id=\"toggle-alsa-loopback-1\" value=\"On\" " . (($_SESSION['alsa_loopback'] == 'On') ? "checked=\"checked\"" : "") . ">\n";
$_select['alsa_loopback_off'] .= "<input type=\"radio\" name=\"alsa_loopback\" id=\"toggle-alsa-loopback-2\" value=\"Off\" " . (($_SESSION['alsa_loopback'] == 'Off') ? "checked=\"checked\"" : "") . ">\n";
// Multiroom configure
$_multiroom_feat_enable = $_SESSION['feat_bitmask'] & FEAT_MULTIROOM ? '' : 'hide';

// MPD OPTIONS

// Autoplay after start
$_select['autoplay_on']  .= "<input type=\"radio\" name=\"autoplay\" id=\"toggle-autoplay-1\" value=\"1\" " . (($_SESSION['autoplay'] == 1) ? "checked=\"checked\"" : "") . ">\n";
$_select['autoplay_off'] .= "<input type=\"radio\" name=\"autoplay\" id=\"toggle-autoplay-2\" value=\"0\" " . (($_SESSION['autoplay'] == 0) ? "checked=\"checked\"" : "") . ">\n";
// Metadata file
$_select['extmeta_on']  .= "<input type=\"radio\" name=\"extmeta\" id=\"toggle-extmeta-1\" value=\"1\" " . (($_SESSION['extmeta'] == 1) ? "checked=\"checked\"" : "") . ">\n";
$_select['extmeta_off'] .= "<input type=\"radio\" name=\"extmeta\" id=\"toggle-extmeta-2\" value=\"0\" " . (($_SESSION['extmeta'] == 0) ? "checked=\"checked\"" : "") . ">\n";
// Auto-shuffle
$_select['ashufflesvc_on']  .= "<input type=\"radio\" name=\"ashufflesvc\" id=\"toggle-ashufflesvc-1\" value=\"1\" " . (($_SESSION['ashufflesvc'] == 1) ? "checked=\"checked\"" : "") . ">\n";
$_select['ashufflesvc_off'] .= "<input type=\"radio\" name=\"ashufflesvc\" id=\"toggle-ashufflesvc-2\" value=\"0\" " . (($_SESSION['ashufflesvc'] == 0) ? "checked=\"checked\"" : "") . ">\n";
$_select['ashuffle_mode'] .= "<option value=\"Track\" " . (($_SESSION['ashuffle_mode'] == 'Track') ? "selected" : "") . ">曲目</option>\n";
$_select['ashuffle_mode'] .= "<option value=\"Album\" " . (($_SESSION['ashuffle_mode'] == 'Album') ? "selected" : "") . ">专辑</option>\n";
$_ashuffle_filter = str_replace('"', '&quot;', $_SESSION['ashuffle_filter']);
// Volume step limit
$_select['volume_step_limit'] .= "<option value=\"2\" " . (($_SESSION['volume_step_limit'] == '2') ? "selected" : "") . ">2</option>\n";
$_select['volume_step_limit'] .= "<option value=\"5\" " . (($_SESSION['volume_step_limit'] == '5') ? "selected" : "") . ">5</option>\n";
$_select['volume_step_limit'] .= "<option value=\"10\" " . (($_SESSION['volume_step_limit'] == '10') ? "selected" : "") . ">10</option>\n";
// Max MPD volume
$_volume_mpd_max = $_SESSION['volume_mpd_max'];
// Display dB volume
$_select['volume_db_display_on']  .= "<input type=\"radio\" name=\"volume_db_display\" id=\"toggle-volume-db-display-1\" value=\"1\" " . (($_SESSION['volume_db_display'] == 1) ? "checked=\"checked\"" : "") . ">\n";
$_select['volume_db_display_off'] .= "<input type=\"radio\" name=\"volume_db_display\" id=\"toggle-volume-db-display-2\" value=\"0\" " . (($_SESSION['volume_db_display'] == 0) ? "checked=\"checked\"" : "") . ">\n";
// USB volume knob
$_select['usb_volknob_on']  .= "<input type=\"radio\" name=\"usb_volknob\" id=\"toggle-usb-volknob-1\" value=\"1\" " . (($_SESSION['usb_volknob'] == 1) ? "checked=\"checked\"" : "") . ">\n";
$_select['usb_volknob_off'] .= "<input type=\"radio\" name=\"usb_volknob\" id=\"toggle-usb-volknob-2\" value=\"0\" " . (($_SESSION['usb_volknob'] == 0) ? "checked=\"checked\"" : "") . ">\n";
// Rotary encoder
$_select['rotaryenc_on']  .= "<input type=\"radio\" name=\"rotaryenc\" id=\"toggle-rotaryenc-1\" value=\"1\" " . (($_SESSION['rotaryenc'] == 1) ? "checked=\"checked\"" : "") . ">\n";
$_select['rotaryenc_off'] .= "<input type=\"radio\" name=\"rotaryenc\" id=\"toggle-rotaryenc-2\" value=\"0\" " . (($_SESSION['rotaryenc'] == 0) ? "checked=\"checked\"" : "") . ">\n";
$_select['rotenc_params'] = $_SESSION['rotenc_params'];
// Crossfade
$_mpdcrossfade = $_SESSION['mpdcrossfade'];
// Configure DSP buttons
if ($_SESSION['audioout'] == 'Local' && $_SESSION['multiroom_tx'] == 'Off' && $_SESSION['multiroom_rx'] != 'On') {
	// Local out. NOTE: Only one of the DSP'can be on
	$_invpolarity_set_disabled = ($_SESSION['crossfeed'] != 'Off' || $_SESSION['eqfa12p'] != 'Off' || $_SESSION['alsaequal'] != 'Off' || $_SESSION['camilladsp'] != 'off') ? 'disabled' : '';
	$_crossfeed_set_disabled = ($_SESSION['invert_polarity'] != '0' || $_SESSION['eqfa12p'] != 'Off' || $_SESSION['alsaequal'] != 'Off' || $_SESSION['camilladsp'] != 'off') ? 'disabled' : '';
	$_eqfa12p_set_disabled = ($_SESSION['invert_polarity'] != '0' || $_SESSION['crossfeed'] != 'Off' || $_SESSION['alsaequal'] != 'Off' || $_SESSION['camilladsp'] != 'off') ? 'disabled' : '';
	$_alsaequal_set_disabled = ($_SESSION['invert_polarity'] != '0' || $_SESSION['crossfeed'] != 'Off' || $_SESSION['eqfa12p'] != 'Off' || $_SESSION['camilladsp'] != 'off') ? 'disabled' : '';
	$model = substr($_SESSION['hdwrrev'], 3, 1);
	$cmmodel = substr($_SESSION['hdwrrev'], 3, 3); // Generic Pi-CM3+, Pi-CM4 for future use
	$name = $_SESSION['hdwrrev'];
	// Pi-Zero 2 W, Pi-2B rev 1.2, Allo USBridge SIG, Pi-3B/B+/A+, Pi-4B
	if ((strpos($name, 'Pi-Zero 2') !== false) || $name == 'Pi-2B 1.2 1GB' || $model == '3' || $model == '4' || $name == 'Allo USBridge SIG [CM3+ Lite 1GB v1.0]') {
		$_camilladsp_set_disabled = ($_SESSION['invert_polarity'] != '0' || $_SESSION['crossfeed'] != 'Off' || $_SESSION['eqfa12p'] != 'Off' || $_SESSION['alsaequal'] != 'Off') ? 'disabled' : '';
	} else {
		$_camilladsp_set_disabled = 'disabled';
	}
} else {
	// Bluetooth out or Multiroom Sender/Receiver On. NOTE: Don't allow any DSP to be set
	$_invpolarity_set_disabled = 'disabled';
	$_crossfeed_set_disabled = 'disabled';
	$_eqfa12p_set_disabled = 'disabled';
	$_alsaequal_set_disabled = 'disabled';
	$_camilladsp_set_disabled = 'disabled';
}

// Polarity inversion
$_select['invert_polarity_on']  .= "<input type=\"radio\" name=\"invert_polarity\" id=\"toggle-invert-polarity-1\" value=\"1\" " . (($_SESSION['invert_polarity'] == 1) ? "checked=\"checked\"" : "") . ">\n";
$_select['invert_polarity_off'] .= "<input type=\"radio\" name=\"invert_polarity\" id=\"toggle-invert-polarity-2\" value=\"0\" " . (($_SESSION['invert_polarity'] == 0) ? "checked=\"checked\"" : "") . ">\n";
// Crossfeed
$_select['crossfeed'] .= "<option value=\"Off\" " . (($_SESSION['crossfeed'] == 'Off' OR $_SESSION['crossfeed'] == '') ? "selected" : "") . ">关</option>\n";
if ($_crossfeed_set_disabled == '') {
	$_select['crossfeed'] .= "<option value=\"700 3.0\" " . (($_SESSION['crossfeed'] == '700 3.0') ? "selected" : "") . ">700 Hz 3.0 dB</option>\n";
	$_select['crossfeed'] .= "<option value=\"700 4.5\" " . (($_SESSION['crossfeed'] == '700 4.5') ? "selected" : "") . ">700 Hz 4.5 dB</option>\n";
	$_select['crossfeed'] .= "<option value=\"800 6.0\" " . (($_SESSION['crossfeed'] == '800 6.0') ? "selected" : "") . ">800 Hz 6.0 dB</option>\n";
	$_select['crossfeed'] .= "<option value=\"650 10.0\" " . (($_SESSION['crossfeed'] == '650 10.0') ? "selected" : "") . ">650 Hz 10.0 dB</option>\n";
}
// HTTP streaming server
$_select['mpd_httpd_on']  .= "<input type=\"radio\" name=\"mpd_httpd\" id=\"toggle-mpd-httpd-1\" value=\"1\" " . (($_SESSION['mpd_httpd'] == 1) ? "checked=\"checked\"" : "") . ">\n";
$_select['mpd_httpd_off'] .= "<input type=\"radio\" name=\"mpd_httpd\" id=\"toggle-mpd-httpd-2\" value=\"0\" " . (($_SESSION['mpd_httpd'] == 0) ? "checked=\"checked\"" : "") . ">\n";
// Port
$_mpd_httpd_port = $_SESSION['mpd_httpd_port'];
// Encoder
$_select['mpd_httpd_encoder'] .= "<option value=\"flac\" " . (($_SESSION['mpd_httpd_encoder'] == 'flac') ? "selected" : "") . ">FLAC</option>\n";
$_select['mpd_httpd_encoder'] .= "<option value=\"lame\" " . (($_SESSION['mpd_httpd_encoder'] == 'lame') ? "selected" : "") . ">LAME (MP3)</option>\n";

// EQUALIZERS

// CamillaDSP
$configs = $cdsp->getAvailableConfigs();
foreach ($configs as $config_file=>$config_name) {
	$selected = ($_SESSION['camilladsp'] == $config_file) ? 'selected' : '';
	$_select['camilladsp'] .= sprintf("<option value='%s' %s>%s</option>\n", $config_file, $selected, ucfirst($config_name));
}
// Check if the config file is valid
if ($_SESSION['camilladsp'] != 'off' && $_SESSION['camilladsp'] != 'custom') {
	$result = $cdsp->checkConfigFile($_SESSION['camilladsp']);
	$output = implode('<br>', $result['msg']);
	if ($result['valid'] == CDSP_CHECK_NOTFOUND) {
		$_camilladsp_config_check = '<div class="config-help-static"><span style="color: red">&#10007;</span> ' . $output . '</div>';
	} else if ($result['valid'] == CDSP_CHECK_VALID) {
		$_camilladsp_config_check = '<div class="config-help-static"><span style="color: green">&check;</span> ' . $output . '</div>';
	} else {
		$_camilladsp_config_check = '<div class="config-help-static"><span style="color: red">&#10007;</span> ' . $output . '</div>';
	}
}
// Parametric equalizer
$eqfa12p = Eqp12($dbh);
$presets = $eqfa12p->getPresets();
$array = array();
$array[0] = 'Off';
$curveList = $_eqfa12p_set_disabled == '' ? array_replace($array, $presets) : $array;
$curve_selected_id = $eqfa12p->getActivePresetIndex();
foreach ($curveList as $key=>$curveName) {
	$selected = ($key == $curve_selected_id) ? 'selected' : '';
	$_select['eqfa12p'] .= sprintf('<option value="%s" %s>%s</option>\n', $key, $selected, $curveName);
}
unset($eqfa12p);
// Graphic equalizer
$result = sqlQuery('SELECT curve_name FROM cfg_eqalsa', $dbh);
$array = array();
$array[0]['curve_name'] = 'Off';
$curveList = $_alsaequal_set_disabled == '' ? array_merge($array, $result) : $array;
foreach ($curveList as $curve) {
	$curveName = $curve['curve_name'];
	$selected = ($_SESSION['alsaequal'] == $curve['curve_name']) ? 'selected' : '';
	$_select['alsaequal'] .= sprintf('<option value="%s" %s>%s</option>\n', $curve['curve_name'], $selected, $curveName);
}

waitWorker(1, 'snd-config');

$tpl = "snd-config.html";
$section = basename(__FILE__, '.php');
storeBackLink($section, $tpl);

include('header.php');
eval("echoTemplate(\"" . getTemplate("templates/$tpl") . "\");");
include('footer.min.php');

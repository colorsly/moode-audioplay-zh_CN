<!--
/**
 * moOde audio player (C) 2014 Tim Curtis
 * http://moodeaudio.org
 *
 * tsunamp player ui (C) 2013 Andrea Coiutti & Simone De Gregori
 * http://www.tsunamp.com
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
 * 汉化：Androidnews
 *
 */
-->
<!-- ABOUT -->
<div id="about-modal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="about-modal-label" aria-hidden="true">
	<div class="modal-header">
		<button aria-label="Close" type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<p id="moode-logo-text">m<span id="moode-logo-text-oo">oO</span>de<span id="moode-logo-text-tm">™</span>
		</p>
	</div>
	<div class="modal-body">
		<p>MoOde Audio Player是MPD出色的WebUI音频播放器客户端的衍生产品，最初由Andrea Coiutti和Simone De Gregori设计和编码，随后通过RaspyFi/Volumio项目的早期努力进行了增强。</p>
		<h5>发布信息</h5>
		<ul>
			<li>发布： 8.2.4 2022-12-27</li>
			<!-- NOTE: getMoodeRel() parses this  -->
			<li>维护者： Tim Curtis &copy; 2014</li>
			<li>文档： <a class="moode-about-link" href="./relnotes.txt" target="_blank">查看发行说明，</a>&nbsp&nbsp<a class="moode-about-link" href="./setup.txt" target="_blank">查看安装指南</a>
			</li>
			<li>贡献者： <a class="moode-about-link" href="./CONTRIBS.html" target="_blank">查看贡献者</a>
			</li>
			<li>许可证： <a class="moode-about-link" href="./COPYING.html" target="_blank">查看GPLv3</a>
			</li>
		</ul>
		<h5>平台信息</h5>
		<ul>
			<li>RaspiOS: <span id="sys-raspbian-ver"></span>
			</li>
			<li>Linux kernel: <span id="sys-kernel-ver"></span>
			</li>
			<li>Platform: <span id="sys-hardware-rev"></span>
			</li>
			<li>Architecture: <span id="sys-processor-arch"></span>
			</li>
			<li>MPD version: <span id="sys-mpd-ver"></span>
			</li>
		</ul>
	</div>
	<div class="modal-footer">
		<button aria-label="Close" class="btn singleton" data-dismiss="modal" aria-hidden="true">关闭</button>
	</div>
</div>
<!-- CONFIGURE -->
<div id="configure-modal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="configure-modal-label" aria-hidden="true">
	<div class="modal-header">
		<button aria-label="Close" type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3 id="configure-modal-label">系统配置</h3>
	</div>
	<div class="modal-body">
		<div id="configure">
			<ul>
				<li>
					<a href="lib-config.php" class="btn btn-large">
						<i class="fas fa-database"></i>
						<br>音乐库
					</a>
				</li>
				<li>
					<a href="snd-config.php" class="btn btn-large">
						<i class="fas fa-volume-up"></i>
						<br>音频
					</a>
				</li>
				<li>
					<a href="net-config.php" class="btn btn-large">
						<i class="fas fa-sitemap"></i>
						<br>网络
					</a>
				</li>
				<li>
					<a href="sys-config.php" class="btn btn-large">
						<i class="fas fa-cogs"></i>
						<br>系统
					</a>
				</li>
				<li>
					<a href="ren-config.php" class="btn btn-large">
						<i class="fas fa-play-circle"></i>
						<br>渲染器
					</a>
				</li>
				<li>
					<a href="mpd-config.php" class="btn btn-large">
						<i class="fas fa-play"></i>
						<br>MPD
					</a>
				</li>
				<li>
					<a href="cdsp-config.php" class="btn btn-large">
						<i class="fas fa-sliders-v-square"></i>
						<br>CamillaDSP
					</a>
				</li>
				<?php if ($_SESSION['feat_bitmask'] & FEAT_MULTIROOM) { ?>
				<li>
					<a href="trx-config.php" class="btn btn-large">
						<i class="fas fas fa-rss"></i>
						<br>多房间
					</a>
				</li>
				<?php } ?>
				<?php if ($section == 'index') { ?>
				<li class="context-menu">
					<a href="#notarget" class="btn btn-large" data-cmd="setforclockradio-m">
						<i class="fas fa-alarm-clock"></i>
						<br>收音机闹钟
					</a>
				</li>
				<?php } ?>
				<?php if ($_SESSION['feat_bitmask'] & FEAT_INPSOURCE) { ?>
				<li>
					<a href="inp-config.php" class="btn btn-large">
						<i class="far fa-scrubber"></i>
						<br>输入选择
					</a>
				</li>
				<?php } ?>
			</ul>
		</div>
	</div>
	<div class="modal-footer">
		<button aria-label="Close" class="btn singleton" data-dismiss="modal" aria-hidden="true">关闭</button>
	</div>
</div>
<!-- PLAYERS -->
<div id="players-modal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="players-modal-label" aria-hidden="true">
	<div class="modal-header">
		<button aria-label="Close" type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3 id="players-modal-label">其他玩家</h3>
	</div>
	<div class="modal-body"></div>
	<div class="modal-footer">
		<button aria-label="Close" class="btn singleton" data-dismiss="modal" aria-hidden="true">关闭</button>
	</div>
</div>
<!-- AUDIO INFO -->
<div id="audioinfo-modal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="audioinfo-modal-label" aria-hidden="true">
	<div class="modal-header">
		<button aria-label="Close" type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3 id="audioinfo-modal-label">音频信息</h3>
	</div>
	<div class="modal-body"></div>
	<div class="modal-footer">
		<button aria-label="Close" class="btn singleton" data-dismiss="modal" aria-hidden="true">关闭</button>
	</div>
</div>
<!-- SYSTEM INFO -->
<div id="sysinfo-modal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="sysinfo-modal-label" aria-hidden="true">
	<div class="modal-header">
		<button aria-label="Close" type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3 id="sysinfo-modal-label">系统信息</h3>
	</div>
	<div class="modal-body"></div>
	<div class="modal-footer">
		<button aria-label="Close" class="btn singleton" data-dismiss="modal" aria-hidden="true">关闭</button>
	</div>
</div>
<!-- QUICK HELP -->
<div id="quickhelp-modal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="help-modal-label" aria-hidden="true">
	<div class="modal-header">
		<button aria-label="Close" type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3 id="help-modal-label">快速帮助</h3>
	</div>
	<div class="modal-body">
		<div id="quickhelp"></div>
	</div>
	<div class="modal-footer">
		<button aria-label="Close" class="btn singleton" data-dismiss="modal" aria-hidden="true">关闭</button>
	</div>
</div>
<!-- POWER -->
<div id="power-modal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="power-modal-label" aria-hidden="true">
	<div class="modal-header">
		<button aria-label="Close" type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3 id="power-modal-label">电源按钮</h3>
	</div>
	<div class="modal-body">
		<button aria-label="Shutdown" id="system-shutdown" data-dismiss="modal" class="btn btn-primary btn-large btn-block">关机</button>
		<button aria-label="Restart" id="system-restart" data-dismiss="modal" class="btn btn-primary btn-large btn-block" style="margin-bottom:15px;">重启</button>
	</div>
	<div class="modal-footer">
		<button aria-label="Cancel" class="btn singleton" data-dismiss="modal" aria-hidden="true">取消</button>
	</div>
</div>
<!-- RECONNECT/RESTART/SHUTDOWN -->
<div id="reconnect" class="hide">
	<div class="reconnect-bg"></div>
	<a href="javascript:location.reload(true); void 0" class="btn reconnect-btn">重新连接</a>
</div>
<div id="restart" class="hide">
	<div class="reconnect-bg"></div>
	<a href="javascript:location.reload(true); void 0" class="btn reconnect-btn">重新连接</a>
	<span class="reconnect-msg">系统重启中...</span>
</div>
<div id="shutdown" class="hide">
	<div class="reconnect-bg"></div>
	<a href="javascript:location.reload(true); void 0" class="btn reconnect-btn">重新连接</a>
	<span class="reconnect-msg">系统关机中...</span>
</div>
<?php
    //workerLog('-- footer.php');
    $return_val = session_write_close();
	//workerLog('session_write_close=' . (($return_val) ? 'TRUE' : 'FALSE'));
	echo "</body></html>";
?>

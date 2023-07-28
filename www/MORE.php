<?php
ini_set('display_errors', 0);
// error_reporting(E_ALL);
//
//
// /etc/php/7.0/fpm/pool.d/www.conf :
//  php_admin_value[max_execution_time] = 300
//  php_admin_value[upload_max_filesize] = 128M
//  php_admin_value[post_max_size] = 128M
//
// /etc/nginx/nginx.conf:
//  client_max_body_size 128M;



function isFlaged($file){
    if (file_exists($file)) {
        return true;
    } else {
        return false;
    }
}

function enableByFileFlag($file, $sysReboot=false){
    exec('sudo '.'touch '.$file." 2>&1 && sync", $output, $ret);
    if ($sysReboot === true ) {
        exec('sync && sudo systemctl reboot 2>&1');
    }
    if($ret !== 0 ) {
        var_export($output);
    }
}
function disableByFileFlag($file, $sysReboot=false){
    exec('sudo rm -f '.$file.' 2>&1 && sync', $output, $ret);
    if ($sysReboot === true ) {
        exec('sync && sudo systemctl reboot 2>&1');
    } else {
        var_export($output);
    }
    if($ret !== 0 ) {
        var_export($output);
    }
}

function enableService($service, $execNow=true){
    exec('sudo systemctl enable '.$service." 2>&1", $output1, $ret);
    if($ret !== 0 ) {
        var_export($output1);
    }
    if ($execNow === true ) {
        exec('sudo systemctl start '.$service." 2>&1", $output2, $ret);
        if($ret !== 0 ) {
            var_export($output2);
        }
    }
}
function enableBluetooth($yn){

    if ($yn === 'yes') {
        $cmds = array(
            'sudo sed -i.prev "/uart/d" /boot/config.txt 2>&1',
            'printf "\nenable_uart=1\ndtparam=uart0=on\ndtparam=uart1=on\n" | sudo tee -a /boot/config.txt  2>&1',
            'sync'
        );
    }else{
        $cmds = array(
            'sudo sed -i.prev "/uart/d" /boot/config.txt 2>&1',
            'printf "\nenable_uart=0\ndtparam=uart0=off\ndtparam=uart1=off\n" | sudo tee -a /boot/config.txt  2>&1',
            'sync'
        );
    }

    foreach ($cmds as $_cmd) {
        exec($_cmd, $output, $ret);
        if($ret !== 0 ) {
            var_export($output);
        }
    }
}
function disableService($service, $execNow=true){
    exec('sudo systemctl disable '.$service." 2>&1", $output1, $ret);
    if($ret !== 0 ) {
        var_export($output1);
    }
    if ($execNow === true ) {
        exec('sudo systemctl stop '.$service." 2>&1", $output2, $ret);
        if($ret !== 0 ) {
            var_export($output1);
        }
    }
    var_export( $output1 );
    var_export( $output2 );
}
function isServiceActive($service){
    exec('systemctl is-active '.$service." 2>&1", $output, $ret);
    if ($ret === 0 ) {
        return true;
    }else {
        return false;
    }
}
function isServiceEnabled($service){
    exec('systemctl is-enabled '.$service." 2>&1", $output, $ret);
    if ($ret === 0 ) {
        return true;
    }else {
        return false;
    }
}
function exportDbs(){
    exec('sudo /home/pi/export_dbs.sh', $output, $ret);
    if ($ret === 0 ) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="exports.DB.SRCS.tar.gz"');
        readfile('/home/pi/exports.DB.SRCS.tar.gz');
    }else {
        return false;
    }
}
function importDbs(){
    $uploaded_to = '/tmp/exports.DB.SRCS.tar.gz';
    //properties of the uploaded file
    $name = $_FILES["importdbfile"]["name"];
    $type = $_FILES["importdbfile"]["type"];
    $size = $_FILES["importdbfile"]["size"];
    $temp = $_FILES["importdbfile"]["tmp_name"];
    $error = $_FILES["importdbfile"]["error"];

    if ($error > 0){
        echo "Error uploading file! code $error.";
        return;
    } else {
        if(substr($name, -3) === ".gz" && $size > 0 ){
            if (file_exists( $uploaded_to )) {
              system( 'sudo rm -fv '.$uploaded_to);
            }

            if (move_uploaded_file($temp, $uploaded_to)){
                echo "Upload complete!";
                ob_flush();
                passthru("sudo /home/pi/import_dbs.sh $uploaded_to", $ret); // system gonna reboot
            }else{
                echo "Move file failed!";
            }
        } else {
            echo "Not a exported .gz file !";
            return;
        }
    }
}



?>
<?php
if (array_key_exists('exportdbs', $_POST) && $_POST['exportdbs'] == 'yes' ) {
    exportDbs();
    return;
}

?>

<!DOCTYPE html>
<html>
    <head>
        <title>by tcx(@jesset)</title>
        <style>
            .farea {
                width: 240px;
                height: 180px;
                border: 1px dotted black;
                margin: 10px;
                padding: 5px;
                float: left;
            }
            pre{
                clear: both;
            }
            p{
                font-size: 0.6em;
            }
            .fatal{
                color: red;
            }
            h3{
                font-size: 1.2em;
            }
            #importdbfile{
                display: inline;
            }
            .importdbsubmit{
                display: inline;
            }
            *{
                font-family: Menlo, "Liberation Mono", Consolas, "DejaVu Sans Mono", "Ubuntu Mono", "Courier New", "andale mono", "lucida console", monospace;
            }
        </style>
    </head>
    <body>
        <h1>by tcx(@jesset)</h1>

        <form action="/MORE.php" method="post">
            <div class="farea">
            <h3>RoonBridge</h3>
            <select name="roonBridgeOnlyMode">
              <?php $state = isFlaged("/boot/roon-only"); ?>
              <option value="enable" <?php if($state === true){ echo 'selected="selected"'; }?> >打开</option>
              <option value="disabled" <?php if($state === false){ echo 'selected="selected"'; }?> >关闭</option>
            </select>
            <input type="submit" value="保存">
            </div>
        </form>

        <form action="/MORE.php" method="post">
            <div class="farea">
            <h3>HQplayer NAA</h3>
            <select name="naa">
              <?php $state = isFlaged("/boot/NAA"); ?>
              <option value="enable" <?php if($state === true){ echo 'selected="selected"'; }?> >打开</option>
              <option value="disabled" <?php if($state === false){ echo 'selected="selected"'; }?> >关闭</option>
            </select>
            <input type="submit" value="保存">
            </div>
        </form>


        <form action="/MORE.php" method="post">
            <div class="farea">
            <h3>SD卡自动分区和还原</h3>
            <p>将未分区的SD卡空间单独分一个区挂载到系统使用、同时添加到Samba网络共享里，由于img大小固定，下次刷img后此独立分区通过此选项还可恢复，数据不会丢。需要重启系统生效。</p>
            <select name="sdautopart">
              <?php $state = isFlaged("/boot/SDAUTOPART"); ?>
              <option value="enable" <?php if($state === true){ echo 'selected="selected"'; }?> >下次重启执行</option>
              <option value="disabled" <?php if($state === false){ echo 'selected="selected"'; }?> >不执行</option>
            </select>
            <input type="submit" value="保存">
            </div>
        </form>


        <form action="/MORE.php" method="post" enctype="multipart/form-data">
            <div class="farea">
            <h3>曲库缩略图备份还原</h3>
            <p>备份还原: 曲库NAS配置、扫描出来的曲库索引、播放列表、缩略图。导入过程完毕会自动重启系统，导出过程不会重启。</p>
            <button name="exportdbs" value="yes" type="submit">导出</button>
            <input type="file" name="importdbfile" id="importdbfile" > <input type="submit" value="导入" class="importdbsubmit" />
            </div>
        </form>

        <form action="/MORE.php" method="post">
            <div class="farea">
            <h3>禁用USB</h3>
            <p>包括所有USB端口和基于USB的有线网口，需要重启系统</p>
            <select name="disableusbdevall">
              <?php $state = isFlaged("/boot/NOUSB"); ?>
              <option value="yes" <?php if($state === true){ echo 'selected="selected"'; }?> >禁用</option>
              <option value="no" <?php if($state === false){ echo 'selected="selected"'; }?> >不禁用</option>
            </select>
            <input type="submit" value="保存">
            </div>
        </form>

        <form action="/MORE.php" method="post">
            <div class="farea">
            <h3>禁用所有LED</h3>
            <p>需要重启系统</p>
            <select name="disableledsall">
              <?php $state = isFlaged("/boot/NOLED"); ?>
              <option value="yes" <?php if($state === true){ echo 'selected="selected"'; }?> >禁用</option>
              <option value="no" <?php if($state === false){ echo 'selected="selected"'; }?> >不禁用</option>
            </select>
            <input type="submit" value="保存">
            </div>
        </form>


        <form action="/MORE.php" method="post">
            <div class="farea">
            <h3>禁用在树莓派上的Samba服务器</h3>
            <select name="disableSamba">
              <?php $state = isFlaged("/boot/NOSMB"); ?>
              <option value="yes" <?php if($state === true){ echo 'selected="selected"'; }?> >禁用</option>
              <option value="no" <?php if($state === false){ echo 'selected="selected"'; }?> >不禁用</option>
            </select>
            <input type="submit" value="保存">
            </div>
        </form>

        <form action="/MORE.php" method="post">
            <div class="farea">
            <h3>开启蓝牙模块</h3>
            <p>默认彻底禁用，打开方法：<br />
             1. 先在此页面点击开启，然后在 Configure -> System -> Integrated BT adapter 里开启。<br />
             2. 重启设备。<br />
             3. 再去 Configure -> Audio -> Renderers 里开启需要的服务后，开始使用。</p>
            <button name="enableBluetooth" value="yes" type="submit">开启</button>
            <button name="enableBluetooth" value="no" type="submit">关闭</button>
            </div>
        </form>

        <form action="/MORE.php" method="post">
            <div class="farea">
            <h3>是否开启 SSH 服务</h3>
            <select name="opensshServer">
              <?php $state = isServiceEnabled("ssh.service"); ?>
              <option value="enable" <?php if($state === true){ echo 'selected="selected"'; }?> >打开</option>
              <option value="disabled" <?php if($state === false){ echo 'selected="selected"'; }?> >关闭</option>
            </select>
            <input type="submit" value="保存">
            </div>
        </form>

        <form action="/MORE.php" method="post">
            <div class="farea fatal">
            <h3>重启系统</h3>
            <button class="fatal" type="submit" value="yes" name="rebootSystem">重启</button>
            </div>
        </form>


<pre>
<?php
// echo phpinfo();
// var_dump($_POST);
// var_dump($_FILES);

if (!empty($_FILES) && !empty($_FILES["importdbfile"]["name"]) ) {
    importDbs();
    return;
}

if ($_POST['roonBridgeOnlyMode'] == 'enable') {
    enableByFileFlag('/boot/roon-only', false);
    enableService('roonbridge.service');
}else if($_POST['roonBridgeOnlyMode'] == 'disabled'){
    disableByFileFlag('/boot/roon-only', false);
    disableService('roonbridge.service');
}

if ($_POST['naa'] == 'enable') {
    enableByFileFlag('/boot/NAA', false);
    enableService('networkaudiod.service');
}else if($_POST['naa'] == 'disabled'){
    disableByFileFlag('/boot/NAA', false);
    disableService('networkaudiod.service');
}

if ($_POST['sdautopart'] == 'enable') {
    enableByFileFlag('/boot/SDAUTOPART', false);
}else if($_POST['sdautopart'] == 'disabled'){
    disableByFileFlag('/boot/SDAUTOPART', false);
}

if ($_POST['disableusbdevall'] == 'yes') {
    enableByFileFlag('/boot/NOUSB', false);
}else if($_POST['disableusbdevall'] == 'no'){
    disableByFileFlag('/boot/NOUSB', false);
}

if ($_POST['disableledsall'] == 'yes') {
    enableByFileFlag('/boot/NOLED', false);
}else if($_POST['disableledsall'] == 'no'){
    disableByFileFlag('/boot/NOLED', false);
}

if ($_POST['disableSamba'] == 'yes') {
    enableByFileFlag('/boot/NOSMB', false);
    disableService('smbd.service');
    disableService('nmbd.service');
}else if($_POST['disableSamba'] == 'no'){
    disableByFileFlag('/boot/NOSMB', false);
    enableService('smbd.service');
    enableService('nmbd.service');
}

if ($_POST['opensshServer'] == 'enable' ) {
    enableService('ssh.service');
}else if($_POST['opensshServer'] == 'disabled'){
    disableService('ssh.service');
}

if (array_key_exists('enableBluetooth', $_POST) && in_array($_POST['enableBluetooth'], array('yes','no')) ) {
    enableBluetooth($_POST['enableBluetooth']);
}


if ($_POST['rebootSystem'] == 'yes' ) {
    exec('sync && sudo systemctl reboot 2>&1');
}




// for refresh page
if (count($_POST)) {
    ob_flush();
    flush();
    sleep(2); // wait for flag file / service state changed.
    ?>
    <script> window.location.replace("/MORE.php");</script>
    <?php
}



?>

</pre>

    </body>
</html>

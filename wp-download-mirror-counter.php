<?php
/*
Plugin Name: Wp-Download-Mirror-Counter
Plugin URI: http://www.rezaonline.net/blog/wp-download-mirror-counter-plugin.html
Description: Enables you how many times a files (in one of 5 mirrors server) had been downloaded. 
Version: 1.1
Author: Reza Sh [ RezaOnline.Net ]
Author URI: http://www.rezaonline.net/blog
License: GPL2
*/


#
 # 									
  #  RezaOnline.net - رضاشیخله        ### info@rezaonline.net
 #									
#

#
# Make define
#
define ('dl_mirror_counter_vers','1.0');
define ('dl_m_c_dir', dirname(__FILE__));
define ('dl_m_c_uri', get_option('siteurl').'/wp-content/plugins/wp-download-mirror-counter');

#
# load TRANSLATIONS
#
load_plugin_textdomain('dlmc', 'wp-content/plugins/wp-download-mirror-counter/', 'wp-download-mirror-counter/');

#
# Install plugin when you actived
#
register_activation_hook(__FILE__,'dl_m_c_install'); 
function dl_m_c_install() {
global $wpdb;
$tbl = $wpdb->prefix."dlmc";
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
// Creat table
$creattbl = "CREATE TABLE IF NOT EXISTS $tbl (
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
					`title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL ,
					`files` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL ,
					`count` INT( 10 ) NOT NULL ,
					 PRIMARY KEY (  `id` ) ,UNIQUE (`id`)) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_bin;";

        dbDelta($creattbl);

// Other Options
add_option("dl_m_c_t", '<h1>', '', 'yes');
add_option("dl_m_c_end_t", '</h1>', '', 'yes');
add_option("dl_m_c_ul", '<ul>', '', 'yes');
add_option("dl_m_c_end_ul", '</ul>', '', 'yes');
add_option("dl_m_c_li", '<li>', '', 'yes');
add_option("dl_m_c_end_li", '</li>', '', 'yes');
}

#
# UNInstall Plugin when you deactived
#
register_deactivation_hook( __FILE__, 'dl_m_c_remove' );
function dl_m_c_remove(){

//update options
update_option("dl_m_c_t", '<h2>');
update_option("dl_m_c_end_t", '</h2>');
update_option("dl_m_c_ul", '<ul>');
update_option("dl_m_c_end_ul", '</ul>');
update_option("dl_m_c_li", '<li>');
update_option("dl_m_c_end_li", '</li>');

}
#
# add dl query vars
# 
add_filter('query_vars', 'dlcm_query_vars');
function dlcm_query_vars($public_query_vars) {
	$public_query_vars[] = "dl_id";
	$public_query_vars[] = "dl_server";
	return $public_query_vars;
}

#
# get query vars and go to dl link form mirrors
#
add_action('template_redirect', 'dlmc_file');
function dlmc_file() {
	global $wpdb;
	$dl_id = intval(get_query_var('dl_id'));
	$dl_server = get_query_var('dl_server');
	$tbl = $wpdb->prefix . "dlmc";
	$wpdb->show_errors();
	$dlfile = $wpdb->get_results("select `id`,`files`,`count` from $tbl where `id`=$dl_id  limit 1 ",ARRAY_A);
	if(isset($_GET['dl_id']) && empty($dlfile))
		echo '<script type="text/javascript">alert("'.__('File Not found!','dlmc').'");</script>';
	elseif(!empty($dlfile)){
		$mirrors = unserialize($dlfile[0]['files']);
		if(array_key_exists($dl_server,$mirrors))
			$dllink = $mirrors[$dl_server]; //if have server(mirror) , download link will be set.
		else{
		$loopstop = rand(1,count($mirrors)); //else download link set to random server(mirror)
		$i=0;
				foreach($mirrors as $mirror){
				$i++;
				$dllink = $mirror;
					if($i == $loopstop)
						break;
				}
		}
		$newcount = $dlfile[0]['count'] +1;
		$wpdb->query("update $tbl set `count` =$newcount where `id`=$dl_id");
		echo $dllink;
		header('Location: '.$dllink);
	}
}

#
# add shortcode
#
add_shortcode("dlmc", "dlmc_id");
function dlmc_id($atts) {
global $wpdb;
global $post;
$tbl = $wpdb->prefix . "dlmc";
$wpdb->show_errors();

	extract(shortcode_atts(array("id" => '0',"countid" => '0'), $atts));
	if($id!=0)
		$get = $wpdb->get_results("select * from $tbl where `id`=$id  limit 1  ",ARRAY_A);
	elseif($countid!=0)
		$get = $wpdb->get_results("select `count` from $tbl where `id`=$countid  limit 1  ",ARRAY_A);
//make link
if(strlen(get_option('permalink_structure')) > 0)
	$mirlink = get_permalink($post->ID).'?dl_id='.$id.'&dl_server=';
else
	$mirlink = get_permalink($post->ID).'&dl_id='.$id.'&dl_server=';
	
	
		if(empty($get))
			return __('File Not found!','dlmc');
	
	
	//show mirrors
	if($id!=0 && $countid==0 ){
		$x =get_option("dl_m_c_t");
		$x.=__('Download :','dlmc').' ';
		$x.=$get[0]['title'].get_option("dl_m_c_end_t");
		$x.=__('From: ','dlmc');
		$x.=get_option("dl_m_c_ul");
		$mirrors = unserialize ($get[0]['files']);
		foreach($mirrors as $key=>$mirror)
		$x.=get_option("dl_m_c_li").'<a href="'.$mirlink.$key.'" target="_self">'.$key.'</a>'.get_option("dl_m_c_end_li")."\n";
		
		$x.=get_option("dl_m_c_end_ul");
			return $x;
		}
	if($countid!=0 && $id==0)
		return $get[0]["count"];
	if($countid!=0 && $id!=0){
		$x =get_option("dl_m_c_t");
		$x.=__('Download :','dlmc').' ';
		$x.=$get[0]['title'].' (Downloaded '.$get[0]["count"].' Times)'.get_option("dl_m_c_end_t");
		$x.=__('From: ','dlmc');
		$x.=get_option("dl_m_c_ul");
		$mirrors = unserialize ($get[0]['files']);
		foreach($mirrors as $key=>$mirror)
		$x.=get_option("dl_m_c_li").'<a href="'.$mirlink.$key.'" target="_self">'.$key.'</a>'.get_option("dl_m_c_end_li")."\n";
		$x.=get_option("dl_m_c_end_ul");
			return $x;
		}
}

#
# Add Menus
#
if ( is_admin() ){
add_action('admin_menu', 'dl_m_c_t_admin_menu');
function dl_m_c_t_admin_menu() {
add_menu_page('DL Mirror Counter', __('DL Mirror Counter','dlmc'), 'administrator','dl_m_c_options', 'dl_m_c_options',dl_m_c_uri.'/reza.png');
add_submenu_page('dl_m_c_options','DL Mirror Counter',__('Add Files','dlmc'), 'administrator','dl_m_c_add','dl_m_c_add');
add_submenu_page('dl_m_c_options','DL Mirror Counter',__('All Files and edit','dlmc'), 'administrator','dl_m_c_edit','dl_m_c_edit');
add_submenu_page('dl_m_c_options','DL Mirror Counter',__('DL Counter','dlmc'), 'administrator','dl_m_c_counter','dl_m_c_counter');
}
}

#
# function for trim array
#
function trim_map ($x){
$x = trim($x);
$x = strip_tags($x);
return $x ;
}

#
# Html for main menu
#
function dl_m_c_options(){

echo '<div class="wrap">';
$msg = __('Settings saved.');
if(isset($_GET['settings-updated']) && $_GET['settings-updated']=='true')
  echo "<div id='message' class='updated fade'><p><b>$msg</b></p></div>";
?>
<h3><?php _e('Download Mirror Counter Options - Templates','dlmc');?></h3>
<?php _e('Please fill all field for best view .','dlmc')?> <br />
<div dir="ltr" style="direction:ltr !important;padding:8px" <?php echo is_rtl() ? 'align=right ' : ' '; ?>>
<form method="post" action="options.php">
<?php wp_nonce_field('update-options'); ?>
<input name="dl_m_c_t" dir="ltr" type="text" size="5" value="<?php echo get_option('dl_m_c_t'); ?>" /><?php _e('Download :','dlmc');?> <i>filename</i>
<input name="dl_m_c_end_t" dir="ltr" type="text" size="5" value="<?php echo get_option('dl_m_c_end_t'); ?>" />
<?php _e('From: ','dlmc')?> <br />
<input name="dl_m_c_ul" dir="ltr" type="text" size="5" value="<?php echo get_option('dl_m_c_ul'); ?>" /><br />
<table border="0" style="padding-right:55px;padding-left:55px">
<tr>
<td><input name="dl_m_c_li" dir="ltr" type="text" size="5" value="<?php echo get_option('dl_m_c_li'); ?>" />
</td><td> <i><?php _e('Server(1)Link','dlmc')?></i></td><td><input dir="ltr" name="dl_m_c_end_li" type="text" size="5" value="<?php echo get_option('dl_m_c_end_li'); ?>" />
</td></tr>
<tr>
<td style="text-align:center"><?php _e('Ibidem','dlmc') ?></td>
<td><i><?php _e('Server(2)Link','dlmc')?></i></td>
<td style="text-align:center"><?php _e('Ibidem','dlmc') ?></td>
</tr>
<tr>
<td style="text-align:center;"><?php _e('Ibidem','dlmc') ?><br /><?php _e('Ibidem','dlmc') ?><br /><?php _e('Ibidem','dlmc') ?></td>
<td style="text-align:center;"><i><?php _e('Server(3)Link','dlmc')?></i><br /><i><?php _e('Server(4)Link','dlmc')?></i><br /><i><?php _e('Server(5)Link','dlmc')?></i></td>
<td style="text-align:center;"><?php _e('Ibidem','dlmc') ?><br /><?php _e('Ibidem','dlmc') ?><br /><?php _e('Ibidem','dlmc') ?></td>
</tr>
</table>
<input name="dl_m_c_end_ul" dir="ltr" type="text" size="5" value="<?php echo get_option('dl_m_c_end_ul'); ?>" />
<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="dl_m_c_t,dl_m_c_end_t,dl_m_c_ul,dl_m_c_end_ul,dl_m_c_li,dl_m_c_end_li" />
<p>
<input type="submit" class='button' value="<?php _e('Save Changes') ?>" />
</p>
</form>
</div></div>
<?php 
_e('Help : This options used for showing in post/page .','dlmc');
echo '<br />';
_e('for example [dlmc id="1"] Have this options .','dlmc');
}

#
# Html for add file menu
#
function dl_m_c_add(){
global $wpdb;
$tbl = $wpdb->prefix . "dlmc";
$wpdb->show_errors();
echo '<div class="wrap">'; // start html

if(isset($_POST['dlmc_ok'])){ // do some thing
$dlmc_server_name = array();
$dlmc_server_name = array_map("trim_map",$_POST['dlmc_server_name']);
$dlmc_server_link = array();
$dlmc_server_link = array_map("trim_map",$_POST['dlmc_server_link']);

$title = strip_tags($_POST['dlmc_title']);
if (!empty($_POST['dlmc_count']))
	$count = strip_tags($_POST['dlmc_count']);
else
	$count = '0';
	
$files = array();
if(!empty($dlmc_server_name[1]) && !empty($dlmc_server_link[1]))
	$files[$dlmc_server_name[1]] = $dlmc_server_link[1];
	
if(!empty($dlmc_server_name[2]) && !empty($dlmc_server_link[2]))
	$files[$dlmc_server_name[2]] = $dlmc_server_link[2];

if(!empty($dlmc_server_name[3]) && !empty($dlmc_server_link[3]))
	$files[$dlmc_server_name[3]] = $dlmc_server_link[3];

if(!empty($dlmc_server_name[4]) && !empty($dlmc_server_link[4]))
	$files[$dlmc_server_name[4]] = $dlmc_server_link[4];

if(!empty($dlmc_server_name[5]) && !empty($dlmc_server_link[5]))
	$files[$dlmc_server_name[5]] = $dlmc_server_link[5];

$dlmcfiles = serialize($files);

if(!empty($files) && !empty($title)){
	$wpdb->query("insert into $tbl values ('','{$title}','{$dlmcfiles}','{$count}') ");
	
	foreach ($files as $file=>$key)
				$show.="<a href='$key' target='_blank'>$file </a> , "; 
		
		echo '<div id="message" class="updated fade"><p>'."File $title added. Mirrors :  $show".'</p></div>';
}else
	echo '<div id="message" class="updated fade" style="color:red;font-weight:bold"><p>'.__('File Not added! Some field is empty!','dlmc').'</p></div>';

} // end isset
?>
<br /><form method='post'><table  border='0'>
<tr><td width="150" ><?php _e('File Title :','dlmc');?></td>
<td width="200" ><input name="dlmc_title" type="text" size="50" /></td>
<td width="500" ></td>
</tr><tr>
<td><?php _e('Starting File Hits :','dlmc');?></td>
<td><input name="dlmc_count" type="text" size="10" value="0" /></td>
<td></td>
</tr><tr>
<td valign="top"><br /><br /><?php _e('Add mirrors =>','dlmc');?> <br /></td>
<td colspan=2>
<!-- Add mirror-->
<br /><br /><br />
<table border="0">
<tr>
<td width="50" style="background:gray"> </td>
<td width="150"><?php _e('Server(1) Name :','dlmc');?></td>
<td width="150"><input name="dlmc_server_name[1]" type="text" size="25" /> </td>
<td width="150">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Server(1) Link :','dlmc');?></td>
<td width="200"><input dir="ltr" name="dlmc_server_link[1]" type="text" size="80" /> </td>
</tr>
<tr>
<td width="50" style="background:gray"> </td>
<td width="150"><?php _e('Server(2) Name :','dlmc');?></td>
<td width="150"><input name="dlmc_server_name[2]" type="text" size="25" /> </td>
<td width="150">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Server(2) Link :','dlmc');?></td>
<td width="200"><input dir="ltr" name="dlmc_server_link[2]" type="text" size="80" /> </td>
</tr>
<tr>
<td width="50" style="background:gray"> </td>
<td width="150"><?php _e('Server(3) Name :','dlmc');?></td>
<td width="150"><input name="dlmc_server_name[3]" type="text" size="25" /> </td>
<td width="150">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Server(3) Link :','dlmc');?></td>
<td width="200"><input dir="ltr" name="dlmc_server_link[3]" type="text" size="80" /> </td>
</tr>
<tr>
<td width="50" style="background:gray"> </td>
<td width="150"><?php _e('Server(4) Name :','dlmc');?></td>
<td width="150"><input name="dlmc_server_name[4]" type="text" size="25" /> </td>
<td width="150">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Server(4) Link :','dlmc');?></td>
<td width="200"><input dir="ltr" name="dlmc_server_link[4]" type="text" size="80" /> </td>
</tr>
<tr>
<td width="50" style="background:gray"> </td>
<td width="150"><?php _e('Server(5) Name :','dlmc');?></td>
<td width="150"><input name="dlmc_server_name[5]" type="text" size="25" /> </td>
<td width="150">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Server(5) Link :','dlmc');?></td>
<td width="200"><input dir="ltr" name="dlmc_server_link[5]" type="text" size="80" /> </td>
</tr>
</table>
<!-- end mirror -->
<br />
<input type="hidden" name="dlmc_ok" value="ok" />
<input type="submit" class="button" value="<?php _e('Save Changes') ?>" />
</td>
</tr>
</table>
</form>
<br /><br /><br /><br /><br /> 
<div style="direction:ltr">
<?php _e('In your Post You can use [dlmc id="1"] for show list of servers(mirrors) to download file. and use [dlm countid="1"] for show times of downloaded file.','dlmc');?>
<br />
<?php _e('and also you can use [dlmc id="1" countid="1"] for show both!','dlmc');?>
<br /><?php _e('for example : ','dlmc')?><br />
file : <b>myplugin.zip</b> (downloaded 10 times)<br />
<ul><li><a href ="http://server1.ir/myplugin.zip">Server 1</a></li>
<li><a href ="http://server2.ir/myplugin.zip">Server 2</a></li>
<li><a href ="http://server3.ir/myplugin.zip">Server 3</a></li>
</ul></div></div>
<?php
}

#
# Html for edit file menu
#
function dl_m_c_edit(){
global $wpdb;
$tbl = $wpdb->prefix . "dlmc";
$wpdb->show_errors();

//delete
if(isset($_POST['dlmc_action']) && $_POST['dlmc_action']=='delete')
	$dodelete = $wpdb->query("delete from $tbl where `id`='{$_POST['dlmc_id']}' limit 1 ");
if($dodelete)
	echo '<div id="message" class="updated fade"><p>'.__('The selected file have been deleted.','dlmc').'</p></div>';
//edit
if(isset($_POST['dlmc_action']) && $_POST['dlmc_action']=='okedited'){
$dlmc_server_name = array();
$dlmc_server_name = array_map("trim_map",$_POST['dlmc_server_name']);
$dlmc_server_link = array();
$dlmc_server_link = array_map("trim_map",$_POST['dlmc_server_link']);

$title = strip_tags($_POST['dlmc_title']);
if (!empty($_POST['dlmc_count']))
	$count = strip_tags($_POST['dlmc_count']);
else
	$count = '0';
	
$files = array();
if(!empty($dlmc_server_name[1]) && !empty($dlmc_server_link[1]))
	$files[$dlmc_server_name[1]] = $dlmc_server_link[1];
	
if(!empty($dlmc_server_name[2]) && !empty($dlmc_server_link[2]))
	$files[$dlmc_server_name[2]] = $dlmc_server_link[2];

if(!empty($dlmc_server_name[3]) && !empty($dlmc_server_link[3]))
	$files[$dlmc_server_name[3]] = $dlmc_server_link[3];

if(!empty($dlmc_server_name[4]) && !empty($dlmc_server_link[4]))
	$files[$dlmc_server_name[4]] = $dlmc_server_link[4];

if(!empty($dlmc_server_name[5]) && !empty($dlmc_server_link[5]))
	$files[$dlmc_server_name[5]] = $dlmc_server_link[5];

$dlmcfiles = serialize($files);

if(!empty($files) && !empty($title))
	$doedit = $wpdb->query("update  $tbl set `title`='{$title}',`files`='{$dlmcfiles}',`count`='{$count}' where `id`='{$_POST['dlmc_id']}' limit 1 ") ;
}
if($doedit)
	echo '<div id="message" class="updated fade"><p>'.__('File edited successfully.').'</p></div>';

//edit form
if(isset($_POST['dlmc_action']) && $_POST['dlmc_action']=='edit'){
$result = $wpdb->get_results("select * from $tbl where `id`='{$_POST['dlmc_id']}' ",ARRAY_A);
$mirs = unserialize($result[0]['files']);
?>
<form method='post'><table  border='0'><tr><td width="150" ><?php _e('File Title :','dlmc');?></td>
<td width="200" ><input name="dlmc_title" type="text" size="50" value=<?php echo $result[0]['title'];?>  /></td>
<td width="500" ></td></tr><tr>
<td><?php _e('Starting File Hits :','dlmc')?></td>
<td><input name="dlmc_count" type="text" size="10" value=<?php echo $result[0]['count'];?> /></td>
<td></td></tr><tr>
<td valign="top"><br /><br /><?php _e('Edit mirrors =>','dlmc')?> <br /></td>
<td colspan=2>
<!-- Add mirror-->
<br /><br /><br /><table border="0">
<?php
$j=0;
foreach($mirs as $key=>$mir) {
$j++;
echo '<tr><td width="50" style="background:gray"> </td><td width="150">'.__('Server('.$j.') Name :','dlmc').'</td>
<td width="150"><input name="dlmc_server_name['.$j.']" type="text" size="25" value='.$key.' /> </td>
<td width="150">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.__('Server('.$j.') Link :',"dlmc").'</td>
<td width="200"><input dir="ltr" name="dlmc_server_link['.$j.']" type="text" size="80" value='.$mir.' /> </td>
</tr>';
}
while ($j < 5){
$j++;
echo '<tr>
<td width="50" style="background:gray"> </td>
<td width="150">'.__('Server('.$j.') Name :','dlmc').'</td>
<td width="150"><input name="dlmc_server_name['.$j.']" type="text" size="25" /> </td>
<td width="150">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.__('Server('.$j.') Link :',"dlmc").'</td>
<td width="200"><input dir="ltr" name="dlmc_server_link['.$j.']" type="text" size="80" /> </td>
</tr>';
}
?>
</table>
<!-- end mirror -->
<br /><input type="hidden" name="dlmc_action" value="okedited" />
<input type="hidden" name="dlmc_id" value="<?php echo $result[0]['id'] ?>" />
<input type="submit" class="button" value="<?php _e('Save Changes') ?>" />
</td></tr></table></form>
<?php
}else{
//
$result = $wpdb->get_results("select `id`,`title`,`files` from $tbl order by `id` desc ",ARRAY_A);
echo " <br /><br /><div style='padding:10px'>
<table class='wp-list-table widefat plugins'   > 
<tr class='inactive'>
<td style='padding-bottom:4px'>id</td>
<td>".__('Title','dlmc')."</td>
<td>".__('Servers mirror','dlmc')."</td>
<td>   ".__('Actions','dlmc')."</td><td></td>
</tr>";
foreach($result as $var):
?>
<tr class="active" >
<td width="30"><?php echo $var['id'] ?></td>
<td width="160"><?php echo $var['title'] ?></td>
<td width="500" style="padding-bottom:4px">
<?php
$mirrors = unserialize ($var['files']);
foreach($mirrors as $key=>$mirror)
		echo '<a href="'.$mirror.'" target="_blank">'.$key.'</a> , ';		
?>
</td>
<td width="50" >
<form method=post>
<input name="dlmc_id" type=hidden value="<?php echo $var['id'] ;?>" />
<input name="dlmc_action" type=hidden value='delete' />
<input type="submit" class="button" style="background:red;color:white;border:1px solid darkorange;text-shadow:none;font-weight:bold;font-family:tahoma;" value="<?php _e('Delete') ?>" />
</form></td><td width="50">
<form method=post>
<input name="dlmc_id" type=hidden value="<?php echo $var['id'] ;?>" />
<input name="dlmc_action" type=hidden value='edit' />
<input type="submit" class="button" value="<?php _e('Edit') ?>" /></form></td></tr>
<?php
endforeach;
echo '</table></div>';
} } //end else

#
# Html for Stat and DL Counter
#
function dl_m_c_counter(){
global $wpdb;
$tbl = $wpdb->prefix . "dlmc";
$wpdb->show_errors();
$result = $wpdb->get_results("select `id`,`title`,`count` from $tbl order by `id` desc ",ARRAY_A);
echo " <br /><br /><div style='padding:10px'>
<table class='wp-list-table widefat plugins'   > 
<tr class='inactive'>
<td style='padding-bottom:4px'>id</td>
<td>".__('Title','dlmc')."</td>
<td>".__('Download chart','dlmc')."</td>
<td>".__('Hits','dlmc')."</td>
</tr>";

// max count
$max = array();
foreach($result as $v)
	$max[]=$v['count'];

$maxcount =  @max($max);
@$in = 700/$maxcount;

foreach($result as $var):
?>
<tr class="active" >
<td width="30"><?php echo $var['id'] ?></td>
<td width="160"><?php echo $var['title'] ?></td>
<td width="702" style="padding-bottom:4px"><img style="border-radius:4px" src="<?php echo dl_m_c_uri.'/s.png';?>" height="15" width="<?php echo ceil($var['count']*$in); ?>" /></td>
<td width="30"><?php echo $var['count'] ?></td>
</tr>
<?php
endforeach;
echo '</table></div>';
}

#
# بژی کورد 
#
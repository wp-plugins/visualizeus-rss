<?php
/*
Plugin Name: visualizeusRSS
Plugin URI: http://vunkyblog.net/visualizeusrss/
Description: Allows you to integrate the thumbnails from vi.sualize.us rss feed into your wp site.
Version: 1.0
License: GPL
Author: Vunky
Author URI: http://vunkyblog.net
*/

function get_visualizeusRSS() {

	// the function can accept up to seven parameters, otherwise it uses option panel defaults 	
  	for($i = 0 ; $i < func_num_args(); $i++) {
    	$args[] = func_get_arg($i);
    }

    if (!isset($args[0])) $id_number = stripslashes(get_option('visualizeusRSS_visualizeusid')); else $id_number = $args[6];
  	if (!isset($args[1])) $maxitems = get_option('visualizeusRSS_display_numitems'); else $num_items = $args[0];
  	if (!isset($args[2])) $set_id = stripslashes(get_option('visualizeusRSS_set')); else $set_id = $args[7];
	
	# use image cache & set location
	$useImageCache = get_option('visualizeusRSS_use_image_cache');
	$cachePath = get_option('visualizeusRSS_image_cache_uri');
	$fullPath = get_option('visualizeusRSS_image_cache_dest'); 

	if (!function_exists('MagpieRSS')) { // Check if another plugin is using RSS, may not work
		include_once (ABSPATH . WPINC . '/rss.php');
		error_reporting(E_ERROR);
	}

	// get the feeds
	if ($id_number != "") { $rss_url = 'http://vi.sualize.us/rss/' . $id_number . '/' . $set_id . '/'; }
	else { print "visualizeusRSS probably needs to be setup"; }
	        
	$rss = fetch_rss($rss_url);	
	?>
	        <style type="text/css">   
			.thmb { margin-left: -15px; }
	        .thmb li { float: left; list-style-type: none; margin: 4px; }
	        .thmb li img { padding: 4px; border: 1px dashed #c0c0c0; width: 75px;}
	        </style>
    
  <h2><?php _e('Inspiration'); ?></h2>	
  <ul class="thmb">          	
<?      
 $items = array_slice($rss->items, 0, $maxitems);
 foreach($items as $item) {
	 //  preg_match('/<media.thumbnail\surl="([^"]*)"\sheight="([^"]*)"\swidth="([^"]*)"/iUs',$item['media']['text'],$array);
	   $pattern = '@src=[\'"](http([^[:space:]\'">]*)\.(jpg|jpeg|gif|png))[\'"]@i';
       if (preg_match($pattern, $item['description'], $match)) {
	 ?> 
		   <li>        
				<a href="<?php echo $item['link']; ?>" title="<?php echo $item['title']; ?>" target="_blank">    
					<?
					 # cache images 
				       if ($useImageCache) {
                           //preg_match('<http://farm[0-9]{0,3}\.static.flickr\.com/\d+?\/([^.]*)\.jpg>', $match[0], $flickrSlugMatches);
					       $flickrSlug = basename($match[1]) . '.' . $match[3];
			               # check if file already exists in cache
			               # if not, grab a copy of it                  
			               if (!file_exists("$fullPath$flickrSlug")) {              
						
			                 if ( function_exists('curl_init') ) { // check for CURL, if not use fopen
			                    $curl = curl_init();                                  
								
			                    $localimage = fopen("$fullPath$flickrSlug", "wb");
			                    curl_setopt($curl, CURLOPT_URL, $match[1]);
			                    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
			                    curl_setopt($curl, CURLOPT_FILE, $localimage);
			                    curl_exec($curl);
			                    curl_close($curl);
			                   } else {
			                 	$filedata = "";
			                    $remoteimage = fopen($imgurl, 'rb');
			                  	if ($remoteimage) {
			                    	 while(!feof($remoteimage)) {
			                         	$filedata.= fread($remoteimage,1024*8);
			                       	 }
			                  	}
			                	fclose($remoteimage);
			                	$localimage = fopen("$fullPath$flickrSlug", 'wb');
			                	fwrite($localimage,$filedata);
			                	fclose($localimage);
			                 } // end CURL check
			                } // end file check
			                # use cached image
			                print "<img src=\"$cachePath$flickrSlug\" />";
			
			            } else { ?>
			               <img <? echo $match[0] ?>/>						   
			          <?  } // end use imageCache  ?>
				</a>
		   </li>	    												
     <?                                
		} 
	}
} # end get_visualizeusRSS() function

function widget_visualizeusRSS_init() {
	if (!function_exists('register_sidebar_widget')) return;

	function widget_visualizeusRSS($args) {
		
		extract($args);

		$options = get_option('widget_visualizeusRSS');
		$title = $options['title'];
		get_visualizeusRSS();
	}
		
	register_sidebar_widget('visualizeusRSS', 'widget_visualizeusRSS');
}

function visualizeusRSS_subpanel() {
     if (isset($_POST['save_visualizeusRSS_settings'])) {
       $option_visualizeusid = $_POST['visualizeus_id'];
       $option_set = $_POST['set'];
       $option_display_numitems = $_POST['display_numitems'];     
       $option_useimagecache = $_POST['use_image_cache'];
       $option_imagecacheuri = $_POST['image_cache_uri'];
       $option_imagecachedest = $_POST['image_cache_dest'];
       update_option('visualizeusRSS_visualizeusid', $option_visualizeusid);
       update_option('visualizeusRSS_set', $option_set);
       update_option('visualizeusRSS_display_numitems', $option_display_numitems);
       update_option('visualizeusRSS_use_image_cache', $option_useimagecache);
       update_option('visualizeusRSS_image_cache_uri', $option_imagecacheuri);
       update_option('visualizeusRSS_image_cache_dest', $option_imagecachedest);
       ?> <div class="updated"><p>visualizeusRSS settings saved</p></div> <?php
     }

	?>

	<div class="wrap">
		<h2>visualizeusRSS Settings</h2>
		
		<form method="post">
		<table class="form-table">
		 <tr valign="top">
		  <th scope="row">Username</th>
	      <td><input name="visualizeus_id" type="text" id="visualizeus_id" value="<?php echo get_option('visualizeusRSS_visualizeusid'); ?>" size="20" />
        	   Use your vi.sualize.us user name.</p></td>
         </tr>
         <tr valign="top">
          <th scope="row">Number of items</th>
          <td>
        	<select name="display_numitems" id="display_numitems">
		      <option <?php if(get_option('visualizeusRSS_display_numitems') == '1') { echo 'selected'; } ?> value="1">1</option>
		      <option <?php if(get_option('visualizeusRSS_display_numitems') == '2') { echo 'selected'; } ?> value="2">2</option>
		      <option <?php if(get_option('visualizeusRSS_display_numitems') == '3') { echo 'selected'; } ?> value="3">3</option>
		      <option <?php if(get_option('visualizeusRSS_display_numitems') == '4') { echo 'selected'; } ?> value="4">4</option>
		      <option <?php if(get_option('visualizeusRSS_display_numitems') == '5') { echo 'selected'; } ?> value="5">5</option>
		      <option <?php if(get_option('visualizeusRSS_display_numitems') == '6') { echo 'selected'; } ?> value="6">6</option>
		      <option <?php if(get_option('visualizeusRSS_display_numitems') == '7') { echo 'selected'; } ?> value="7">7</option>
		      <option <?php if(get_option('visualizeusRSS_display_numitems') == '8') { echo 'selected'; } ?> value="8">8</option>
		      <option <?php if(get_option('visualizeusRSS_display_numitems') == '9') { echo 'selected'; } ?> value="9">9</option>
		      <option <?php if(get_option('visualizeusRSS_display_numitems') == '10') { echo 'selected'; } ?> value="10">10</option>
		      <option <?php if(get_option('visualizeusRSS_display_numitems') == '11') { echo 'selected'; } ?> value="11">11</option>
		      <option <?php if(get_option('visualizeusRSS_display_numitems') == '12') { echo 'selected'; } ?> value="12">12</option>		    
		     </select>           
           </td> 
         </tr>
         <tr valign="top">
		  <th scope="row">Tag</th>
          <td><input name="set" type="text" id="set" value="<?php echo get_option('visualizeusRSS_set'); ?>" size="40" /> Display only images tagged with...</p>
         </tr>               
         </table>      

        <h3>Cache Settings</h3>
		<p>This allows you to store the images on your server and reduce the load on vi.sualize.us. Make sure the plugin works without the cache enabled first.</p>
		<table class="form-table">
         <tr valign="top">
          <th scope="row">URL</th>
          <td><input name="image_cache_uri" type="text" id="image_cache_uri" value="<?php echo get_option('visualizeusRSS_image_cache_uri'); ?>" size="50" />
          <em>http://changeme.com/cache/</em></td>
         </tr>
         <tr valign="top">
          <th scope="row">Full Path</th>
          <td><input name="image_cache_dest" type="text" id="image_cache_dest" value="<?php echo get_option('visualizeusRSS_image_cache_dest'); ?>" size="50" /> 
          <em>/home/path/to/wp-content/visualizeusrss/cache/</em></td>
         </tr>
		 <tr valign="top">
		  <th scope="row" colspan="2" class="th-full">
		  <input name="use_image_cache" type="checkbox" id="use_image_cache" value="true" <?php if(get_option('visualizeusRSS_use_image_cache') == 'true') { echo 'checked="checked"'; } ?> />  
		  <label for="use_image_cache">Enable the image cache</label></th>
		 </tr>
        </table>
        <div class="submit">
           <input type="submit" name="save_visualizeusRSS_settings" value="<?php _e('Save Settings', 'save_visualizeusRSS_settings') ?>" />
        </div>
        </form>
    </div>

<?php } // end visualizeusRSS_subpanel()

function visualizeusRSS_admin_menu() {
   if (function_exists('add_options_page')) {
        add_options_page('visualizeusRSS Settings', 'visualizeusRSS', 8, basename(__FILE__), 'visualizeusRSS_subpanel');
        }
}
    

add_action('admin_menu', 'visualizeusRSS_admin_menu'); 
add_action('plugins_loaded', 'widget_visualizeusRSS_init');
?>
<?php
   /*
   Plugin Name: Quick XML Sitemap
   Plugin URI: http://www.letsfx.com/wordpress/quick-xml-sitemap-wp-plugin
   Description: Easy and quickly generate an XML sitemap of your WordPress blog and ping the following search engines: Ask.com, Google, Bing. One click install and let go. XML will be update it-self daily. Special thanks to `Greg Molnar` for inspiring me with some techniques.
   Version: 1.3
   Author: letsfx
   Author URI: http://blog.letsfx.com/
   License: GPL2
   */

   function do_qsm()
   {
      set_time_limit(0);
      require_once(dirname(__FILE__).'/qsitemap.class.php');
      $s = new qsitemap;
      $s->build();
      flush();
   }  
   add_action('admin_menu', 'qsitemap_create_menu');

   add_action('${new_status}_$post->post_type','qbuild',100,1);
   add_action('qsm_build_cron', 'qbuild',100,1);
   add_action('qsm_cron', 'do_qsm');
   register_activation_hook(__FILE__, 'register_qsmsettings');
   register_deactivation_hook(__FILE__, 'qsm_deactivation');

   function qsitemap_create_menu() {
      add_options_page('Sitemap', 'Quick Sitemap', 'administrator', __FILE__, 'qsitemap_settings_page','', __FILE__);
   }                                           

   function register_qsmsettings() {	
      $settings = array(
      'qHomepage' => '1.0','qPosts' => '0.8','qPages' => '0.8','qCategories' => '0.6','qArchives' => '0.8','qTags' => '0.6','qAuthor' => '0.3',
      'qfilename' => 'sitemap',
      'qzip' => 'on',
      'qgoogle' => 'on',
      'qask' => 'on',
      'qbing' => 'on'
      );
      foreach($settings as $setting => $value){
         register_setting( 'qsitemap-settings-group', $setting );
         if(!get_option($setting)){
            update_option( $setting, $value );
         }
      }                                                                                    
      wp_schedule_event(mktime(0,0,0,date('m'),$day,date('Y')), 'daily', 'qsm_cron');        
   }

   function qsm_deactivation() {
      wp_clear_scheduled_hook('qsm_cron');
   }

   function qsitemap_settings_page() {        
      if(!empty($_POST) and isset($_POST['save'])){
         $priority = array();
         $excl_array = array('option_page','action','_wpnonce','_wp_http_referer'); 
         foreach($_POST as $i => $v){
            if(!in_array($i,$excl_array))$priority[$i] = $v;
         }
         settings_fields( 'qsitemap-settings-group' );
         update_option('qzip','off');update_option('qgoogle','off');update_option('qask','off');update_option('qbing','off');
         foreach($priority as $setting => $value){
            update_option( $setting, $value );
            //echo "<br>$setting => $value";            
         }         
      }  
   ?>
   <div class="wrap">
      <h2>Quick Sitemap Plugin</h2>
      <form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float: right; background: #FFFFC0; padding: 5px; border: 1px dashed #FF9A35;">
      <input type="hidden" name="cmd" value="_s-xclick">
      <input type="hidden" name="hosted_button_id" value="83DSNTP8Q7JQC">
      <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
      <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
      </form>

      <div class="postbox" style="width: 400px;">
         <h3 class="hndle" style="padding: 5px;">Actions</h3>
         <div class="inside">
            <?php
               wp_nonce_field('update-options');

               if(isset($_POST['build'])){
                  set_time_limit(0);
                  require_once(dirname(__FILE__).'/qsitemap.class.php');
                  $sitemap = new qsitemap();
                  echo $sitemap->build();
                  flush();
               }else{
                  $filename = get_option('qfilename');
                  if(file_exists(ABSPATH.'/'.$filename.'.xml')){
                     $lastbuild = filemtime(ABSPATH.'/'.$filename.'.xml');
                     $link = '<a href="'.get_option('siteurl').'/'.$filename.'.xml" target="_blank">  view</a>';

                     echo '<p><strong>Your sitemap has built: </strong>'.date_i18n('Y.m.d h:i:s',$lastbuild).$link.'</p>';
                  }
               }
            ?>
            <form action="" method="post">
               <p class="submit">
                  <input type="submit" class="button-primary" value="<?php _e('Build Sitemap') ?>" name="build"/>
               </p>
            </form>
         </div>
      </div>

      <form method="post" action="">
         <?php settings_fields( 'qsitemap-settings-group' ); ?>

         <div class="postbox" style="width: 400px;">
            <h3 class="hndle" style="padding: 5px;">Settings</h3>
            <div class="inside">
               <ul>
                  <li><label>Gzip sitemap? <input type="checkbox" name="qzip"
                           <?php if(get_option('qzip')=='on')echo ' checked="checked" ' ?>
                           /></label></li>
                  <li><label>Ping Google? <input type="checkbox" name="qgoogle"
                           <?php if(get_option('qgoogle')=='on')echo ' checked="checked" ' ?>
                           /></label></li>
                  <li><label>Ping Ask.com? <input type="checkbox" name="qask"
                           <?php if(get_option('qask')=='on')echo ' checked="checked" ' ?>
                           /></label></li>
                  <li><label>Ping Bing? <input type="checkbox" name="qbing"
                           <?php if(get_option('qbing')=='on')echo ' checked="checked" ' ?>
                           /></label></li>

                  <li><label>Filename <input type="text" name="qfilename" value="<?php echo get_option('qfilename'); ?>" /></label></li>
               </ul>
            </div>
         </div>

         <div class="postbox" style="width: 400px;">
            <h3 class="hndle" style="padding: 5px;">Priority</h3>
            <div class="inside">
               <ul>
                  <?php
                     $a = (float)0.0;

                     $val = get_option('qHomepage');
                     echo "<li><label><select name=\"qHomepage\">";
                     for($a=0.0; $a<=1.0; $a+=0.1) {$ov = number_format($a,1,".","");echo '<option value="'.$ov.'"';if($ov == $val)echo ' selected="selected" '; echo '>'.$ov.'</option>';}
                     echo "</select>Homepage</label></li>";

                     $val = get_option('qPosts');
                     echo "<li><label><select name=\"qPosts\">";
                     for($a=0.0; $a<=1.0; $a+=0.1) {$ov = number_format($a,1,".","");echo '<option value="'.$ov.'"';if($ov == $val)echo ' selected="selected" '; echo '>'.$ov.'</option>';}
                     echo "</select>Posts</label></li>";

                     $val = get_option('qPages');
                     echo "<li><label><select name=\"qPages\">";
                     for($a=0.0; $a<=1.0; $a+=0.1) {$ov = number_format($a,1,".","");echo '<option value="'.$ov.'"';if($ov == $val)echo ' selected="selected" '; echo '>'.$ov.'</option>';}
                     echo "</select>Pages</label></li>";

                     $val = get_option('qCategories');
                     echo "<li><label><select name=\"qCategories\">";
                     for($a=0.0; $a<=1.0; $a+=0.1) {$ov = number_format($a,1,".","");echo '<option value="'.$ov.'"';if($ov == $val)echo ' selected="selected" '; echo '>'.$ov.'</option>';}
                     echo "</select>Categories</label></li>";

                     $val = get_option('qArchives');
                     echo "<li><label><select name=\"qArchives\">";
                     for($a=0.0; $a<=1.0; $a+=0.1) {$ov = number_format($a,1,".","");echo '<option value="'.$ov.'"';if($ov == $val)echo ' selected="selected" '; echo '>'.$ov.'</option>';}
                     echo "</select>Archives</label></li>";

                     $val = get_option('qTags');
                     echo "<li><label><select name=\"qTags\">";
                     for($a=0.0; $a<=1.0; $a+=0.1) {$ov = number_format($a,1,".","");echo '<option value="'.$ov.'"';if($ov == $val)echo ' selected="selected" '; echo '>'.$ov.'</option>';}
                     echo "</select>Tags</label></li>";

                     $val = get_option('qAuthor');
                     echo "<li><label><select name=\"qAuthor\">";
                     for($a=0.0; $a<=1.0; $a+=0.1) {$ov = number_format($a,1,".","");echo '<option value="'.$ov.'"';if($ov == $val)echo ' selected="selected" '; echo '>'.$ov.'</option>';}
                     echo "</select>Author</label></li>";                  
                  ?>
               </ul>
            </div>
         </div>

         <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" name="save"/>
         </p>

      </form>
   </div>
   <?php } ?>
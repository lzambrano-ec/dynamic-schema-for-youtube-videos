<?php

/**
 * Plugin name: Dynamic Schema for Youtube Videos
 * Plugin URI: https://www.ikarus-media.com/
 * Description: An easy solution to Get Schema for all embed Youtube Videos in the site
 * Author: Luis Zambrano
 * version: 1.0.0
 * License: GPL2 or later.
 */


// Creation class for the settings page
class Dynamic_Schema_For_Youtube_Videos_Settings {

    // Inside our class we are going to add an action hook to add the settings page (source: https://www.smashingmagazine.com/2016/04/three-approaches-to-adding-configurable-fields-to-your-plugin/)
    // source to fix bug ERROR: options page not found https://stackoverflow.com/questions/45680285/wordpress-error-options-page-not-found
    public function __construct() {
        // Hook into the admin menu
        add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );
        add_action( 'admin_init', array( $this, 'setup_init' ) );
        
    }

    // creation of method for the settings page, and menu items
    public function create_plugin_settings_page() {
        // Add the menu item and page
        $page_title = 'Settings for Dynamic Schema for Youtube Videos';
        $menu_title = 'Dynamic Schema for YT Videos';
        $capability = 'manage_options';
        $slug = 'dsyv_fields';
        $callback = array( $this, 'plugin_settings_page_content' );
        $icon = 'dashicons-format-video';
        $position = 100;
   
        add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
        //add_submenu_page( 'options-general.php', $page_title, $menu_title, $capability, $slug, $callback );
    }

    // Creating the UI Admin page
    public function plugin_settings_page_content() { 
        { ?>
        <div class="wrap">
        <h2>Dynamic Schema for Youtube Videos Settings Page</h2>
        <form method="post" action="options.php">
            <?php
                settings_fields( 'dsyv_fields' );
                do_settings_sections( 'dsyv_fields' );
                submit_button();
            ?>
        </form>
    </div> <?php
    }
}



public function setup_init() {
    register_setting( 'dsyv_fields', 'our_first_field' );
    add_settings_section( 'our_first_section', 'Settings', array( $this, 'section_callback' ), 'dsyv_fields' );
    add_settings_field( 'our_first_field', 'Youtube API Key', array( $this, 'field_callback' ), 'dsyv_fields', 'our_first_section' );    
}

public function section_callback( $arguments ) {
    echo 'Put your Youtube API key here:'; 
}

public function field_callback( $arguments ) {
    echo '<input name="our_first_field" id="our_first_field" type="text" value="' . get_option( 'our_first_field' ) . '" />';
}

}

new Dynamic_Schema_For_Youtube_Videos_Settings();



// Function to create the Schemas Videos Object
function getYoutubeIdFromUrl($url){

	if (strlen($url) > 11) {
		if (preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $url, $matches)) {
			return $matches[1];
		}
		else{
			return false;
		}
	}
	else{
		return false;
	}
}
add_action( 'wp_footer', 'creation_of_schema_videoObject' );


// Making the argument of this function optional and adding default value null
function creation_of_schema_videoObject() {

    $youtube_api_key = get_option( 'our_first_field' );
	$rendered_content = apply_filters( 'the_content', get_the_content() );

	if ($rendered_content != null) {
		$string = $rendered_content;
	} else {
		$string = get_the_content();
	}	
		    
	//regex to match all links of the content
	 preg_match_all('~https?://[^\s"]+~', $string, $matches);
	 
     // $matches is an array of the URLs in the content inside of other array because of preg_match_all
	 $matches_z = $matches[0];
	 	
    // For each to matches_z, get the video id from the url
    foreach($matches_z as $match_z){
        //The function getYoutubeIdFromUrl is declared above
        $videoId = getYoutubeIdFromUrl($match_z);
        // If the video id is not empty, then it is a youtube video
        if($videoId != ''){
            $apikey = $youtube_api_key;
            if(strlen($videoId) >3){
                $path="https://www.googleapis.com/youtube/v3/videos?key=";
                $path=$path.$apikey."&part=snippet,contentDetails&id=".$videoId;	
                // get the json response
                $data = file_get_contents($path);
                // converts the JSON to an array	
                $json = json_decode($data);
                $title_arise=$json->items[0]->snippet->title;
                $uploadDate_arise=$json->items[0]->snippet->publishedAt;
                $embedUrl_arise='https://www.youtube.com/embed/'.$videoId;
                $thumbnailUrl_arise=$json->items[0]->snippet->thumbnails->standard->url;
                $description_arise=$title=$json->items[0]->snippet->description;
                $duration_arise=$json->items[0]->contentDetails->duration;                
                ?>

                <!-- Making the SCHEMA.org markup by Dynamic Schema for Youtube Videos plugin  -->
                <script type="application/ld+json">{
                    "@context": "http://schema.org",
                    "@type": "VideoObject",
                    "name": "<?php echo $title_arise; ?>",
                    "description": "<?php echo $description_arise; ?>",
                    "thumbnailUrl": "<?php echo $thumbnailUrl_arise; ?>",
                    "uploadDate": "<?php echo $uploadDate_arise; ?>",
                    "duration": "<?php echo $duration_arise; ?>",
                    "embedUrl": "<?php echo $embedUrl_arise; ?>"
                }</script>

                <?php
            }
            
        } 
    }

}

add_action( 'wp_footer', 'creation_of_schema_videoObject');

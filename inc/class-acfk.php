<?php

class ACFK {
  public function __construct() {
    /**
     * Gets all registered local layouts
     */
    global $blocks;
    $blocks = $this->get_all_layouts();

    /**
     * Requires all necessary files
     */
    $this->bootstrap();

    /**
     * Creates the admin page
     */
    $this->create_admin_page();

    /**
     * Enqueue scripts
     */
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
  
    /**
     * Generates the kitchensink when form gets submitted
     */
    add_action( 'wp_ajax_generate_kitchensink', array( $this, 'generate_kitchensink' ) );
  }

  /**
   * Requires all necessary classes
   */
  private function bootstrap() {
    require 'class-acfk-data.php';
    require 'class-acfk-helpers.php';
    require 'class-acfk-admin.php';
    require 'class-acfk-importer.php';
  }

  /**
   * Creates the Admin page
   */
  public function create_admin_page() {
    new ACFK_Admin();
  }

  /**
   * Enqueues all scripts
   */
  public function enqueue_scripts() {
    wp_enqueue_script( 'acfk-ajax', ACFK_ROOT_DIR_URL . 'dist/main.js', array(), '1.0.0', true );
  }

  /**
   * Retrieves all layouts registered to a field name.
   */
  static public function get_all_layouts( string $field_name = 'field_blocks' ) : array {
    /**
     * Gets all the layouts and the layout fields from ACF's
     * local storage.
     */
    $fields_data = acf_get_local_store( 'fields' )->get_data();
    $layouts = $fields_data[ $field_name ]['layouts'];
    $layout_fields = acf_get_local_fields( $field_name );

    $blocks = array();
  
    /**
     * Loops through all the layouts to create a block variable
     */
    foreach ( $layouts as $layout ) {
      $fields = array();
  
      /**
       * Loops through all the fields and attach the fields to
       * their matching parent block
       */
      foreach ( $layout_fields as $field ) {
        if ( $field['parent_layout'] === $layout['key'] ) {
          $fields[] = $field;
        }
      }
  
      /**
       * Creates the block and add it to an array
       */
      $block = array(
        'name' => $layout['name'],
        'label' => $layout['label'],
        'fields' => $fields,
      );
  
      $blocks[] = $block;
    }

    return $blocks;
  }

  public function generate_kitchensink() {
    $options = $_POST['kitchensinkOptions'];

    /**
     * If no data was submitted, returns
     */
    if ( ! $options ) {
      echo "No options provided";
      wp_die();
    }

    /**
     * Extracts all post data
     */
    $page_title = $options['pageTitle'] ?? 'Kitchensink';
    $variation = $options['variation'] ?? 'normal';
    $header = $options['header'] ?? 'header';
    $overview = $options['overview'] ?? 'none';
    $excluded_blocks = $options['excludedBlocks'] ?? array();

    /**
     * Creates the page
     */
    $post_data = array(
      'post_title' => $page_title,
      'post_type' => 'page',
      'post_status' => 'publish',
    );

    $kitchensink_id = wp_insert_post( $post_data );

    /**
     * Adds the blocks on the page
     */
    $blocks_on_page = ACFK_Helpers::filter_blocks_for_page( $header, $overview, $excluded_blocks );
    ACFK_Importer::add_blocks_to_page( $kitchensink_id, $blocks_on_page );

    /**
     * Fills the block fields with data
     */

    /**
     * Sends a response to the admin page
     */
    echo "Kitchensink {$page_title} added.";
    
    wp_die(); // This is required to terminate immediately and return a proper response
  }
}
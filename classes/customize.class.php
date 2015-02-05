<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access pages directly.
/**
 *
 * Customize Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
class CSFramework_Customize extends CSFramework_Abstract {

  /**
   *
   * sections
   * @access public
   * @var array
   *
   */
  public $options = array();

  /**
   *
   * panel priority
   * @access public
   * @var bool
   *
   */
  public $priority = 0;

  /**
   *
   * instance
   * @access private
   * @var class
   *
   */
  private static $instance = null;

  // run customize construct
  public function __construct( $options ) {

    $this->options = apply_filters( 'cs_customize_options', $options );

    if( ! empty( $this->options ) ) {
      $this->addAction( 'customize_register', 'customize_register' );
    }

  }

  // instance
  public static function instance( $options = array() ){
    if ( is_null( self::$instance ) && CS_ACTIVE_CUSTOMIZE ) {
      self::$instance = new self( $options );
    }
    return self::$instance;
  }

  // customize register
  public function customize_register( $wp_customize ) {

    // load extra WP_Customize_Control
    cs_locate_template ( CS_BASENAME .'/functions/customize.php', true );
    do_action( 'cs_customize_register' );

    $panel_priority = 0;

    foreach ( $this->options as $value ) {

      $this->priority = $panel_priority;

      if( isset( $value['sections'] ) ) {

        $wp_customize->add_panel( $value['name'], array(
          'title'       => $value['title'],
          'priority'    => ( isset( $value['priority'] ) ) ? $value['priority'] : $panel_priority,
          'description' => ( isset( $value['description'] ) ) ? $value['description'] : '',
        ));

        $this->add_section( $wp_customize, $value, $value['name'] );

      } else {

        $this->add_section( $wp_customize, $value );

      }

      $panel_priority++;

    }

  }

  // add customize section
  public function add_section( $wp_customize, $value, $panel = false ) {

    $section_priority = ( $panel ) ? 0 : $this->priority;
    $sections         = ( $panel ) ? $value['sections'] : array( 'sections' => $value );

    foreach ( $sections as $section ) {

      // add_section
      $wp_customize->add_section( $section['name'], array(
        'title'       => $section['title'],
        'priority'    => ( isset( $section['priority'] ) ) ? $section['priority'] : $section_priority,
        'description' => ( isset( $section['description'] ) ) ? $section['description'] : '',
        'panel'       => ( $panel ) ? $panel : '',
      ) );

      $setting_priority = 0;

      foreach ( $section['settings'] as $setting ) {

        $setting_name = CS_CUSTOMIZE . '[' . $setting['name'] .']';

        // add_setting
        $wp_customize->add_setting( $setting_name,
          wp_parse_args( $setting, array(
              'type'              => 'option',
              'capability'        => 'edit_theme_options',
              'sanitize_callback' => 'cs_sanitize_clean',
            )
          )
        );


        // add_setting
        $setting_control = $setting['control'];

        $control_args    = array(
          'section'      => $section['name'],
          'settings'     => $setting_name,
          'unique'       => CS_CUSTOMIZE,
          'priority'     => $setting_priority,
        );
        $control_args    = wp_parse_args( $setting_control, $control_args );

        if( $setting_control['type'] == 'cs_field' ) {
          $call_class =  'WP_Customize_'. $setting_control['type'] .'_Control';
          $wp_customize->add_control( new $call_class( $wp_customize, $setting['name'], $control_args ) );
        } else {
          $wp_customize->add_control( $setting['name'], $control_args );
        }

        $setting_priority++;
      }

      $section_priority++;

    }

  }

}
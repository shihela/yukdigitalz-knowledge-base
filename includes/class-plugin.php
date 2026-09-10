<?php
namespace Shihela\YukdigitalzKnowledgeBase;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Coordinator class.
 * Instantiates and runs all components of Yukdigitalz Knowledge Base.
 */
class Plugin {
	/**
	 * Components.
	 */
	protected $cpt;
	protected $assets;
	protected $settings;
	protected $ajax;
	protected $shortcode;
	protected $templates;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->cpt       = new CPT();
		$this->assets    = new Assets();
		$this->settings  = new Settings();
		$this->ajax      = new Ajax();
		$this->shortcode = new Shortcode();
		$this->templates = new Templates();
	}

	/**
	 * Runs the components.
	 */
	public function run() {
		// Run components initialization
		$this->cpt->init();
		$this->assets->init();
		$this->settings->init();
		$this->ajax->init();
		$this->shortcode->init();
		$this->templates->init();
	}
}

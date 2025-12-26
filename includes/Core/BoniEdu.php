<?php

namespace BoniEdu\Core;

/**
 * The core plugin class.
 */
class BoniEdu
{

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct()
    {
        if (defined('BONIEDU_VERSION')) {
            $this->version = BONIEDU_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'boniedu-result-manager';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies()
    {
        $this->loader = new Loader();
    }

    private function set_locale()
    {
        // Localization will be implemented in Module 05
    }

    private function define_admin_hooks()
    {
        // Admin hooks will be added here
    }

    private function define_public_hooks()
    {
        // Public hooks will be added here
    }

    public function run()
    {
        $this->loader->run();
    }

    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    public function get_loader()
    {
        return $this->loader;
    }

    public function get_version()
    {
        return $this->version;
    }

}

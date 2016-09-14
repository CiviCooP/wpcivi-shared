<?php

namespace WPCivi\Shared;

/**
 * Class BasePlugin.
 * Base plugin class with useful (?) functionality that can be extended by other plugins/classes.
 * @package WPCivi\Shared
 */
abstract class BasePlugin
{

    /**
     * BasePlugin constructor.
     */
    public function __construct()
    {

    }

    /**
     * Plugins can use register() to define action and filter hooks that should be loaded.
     * @return bool Success
     */
    public function register()
    {
        return true;
    }

    /**
     * Check if another plugin is currently active.
     * is_plugin_active() requires strings like 'gravityforms/gravityforms.php', which is a bit redundant
     * @param string $search Plugin Name
     * @return bool Is Active
     */
    protected function isPluginActive($search)
    {
        $plugins = get_option('active_plugins');
        foreach ($plugins as $plugin) {
            if ($plugin == $search || stripos($plugin, $search) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get template part (= WP function get_template_part).
     * @param string $slug Slug
     * @param string $name Name
     * @param array $args Arguments
     */
    public function getTemplatePart($slug, $name = null, $args = [])
    {
        extract($args);
        get_template_part($slug, $name);
    }

    /**
     * Get template part (= WP function get_template_part), but return string instead of echoing the content.
     * @param string $slug Slug
     * @param string $name Name
     * @param array $args Arguments
     * @return string Template Part Content
     */
    protected function getTemplatePartContent($slug, $name = null, $args = [])
    {
        ob_start();
        extract($args);
        get_template_part($slug, $name);
        $content = ob_get_clean();
        return $content;
    }

    /**
     * Add WordPress action.
     * @param string $tag
     * @param callable $function_to_add
     * @param int $priority
     * @param int $accepted_args
     * @return true
     */
    protected function addAction($tag, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        return add_action($tag, $function_to_add, $priority, $accepted_args);
    }

    /**
     * Add WordPress filter.
     * @param string $tag
     * @param callable $function_to_add
     * @param int $priority
     * @param int $accepted_args
     * @return true
     */
    protected function addFilter($tag, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        return add_filter($tag, $function_to_add, $priority, $accepted_args);
    }

    /**
     * Quit WordPress as user-friendly as possible when a higher level exception occurs.
     * @param \Exception $e
     */
    public static function exitOnException($e)
    {
        $debug = ((defined('WP_DEBUG') && WP_DEBUG) || (defined('CIVICRM_DEBUG') && CIVICRM_DEBUG));
        wp_die(
            "<h2>ERROR: ".$e->getMessage() . "</h2>" .
            ($debug ? "\n\nIn {$e->getFile()} on line {$e->getLine()}.<br><br>\n" .
                        "<small>" . nl2br($e->getTraceAsString()) . "</small></br>\n" : ""),
            'An error occurred' . ($debug ? " (" . get_class($e) . ")" : "")
        );
    }
}
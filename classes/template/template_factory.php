<?php


namespace block_dash\template;

/**
 * Responsible for creating templates on request.
 *
 * @package block_dash\template
 */
class template_factory
{
    /**
     * @var array
     */
    private static $template_registry;

    /**
     * @return array
     * @throws \dml_exception
     */
    protected static function get_template_registry()
    {
        global $DB;

        if (is_null(self::$template_registry)) {
            self::$template_registry = [];
            if ($pluginsfunction = get_plugins_with_function('register_templates')) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $pluginfunction) {
                        foreach ($pluginfunction() as $templateinfo) {
                            $templateinfo['is_custom'] = false;
                            self::$template_registry[$templateinfo['class']] = $templateinfo;
                        }
                    }
                }
            }

            foreach ($DB->get_records('dash_template') as $record) {
                $record = (array)$record;
                $record['is_custom'] = true;

                self::$template_registry[$record['idnumber']] = $record;
            }
        }

        return self::$template_registry;
    }

    /**
     * Check if template identifier references a custom template. If it does, the identifier is the idnumber to the
     * database record.
     *
     * @param string $identifier
     * @return bool
     * @throws \dml_exception
     */
    public static function is_custom($identifier)
    {
        return in_array($identifier, self::get_template_registry())
            && self::get_template_registry()[$identifier]['is_custom'];
    }

    /**
     * Check if template identifier exists.
     *
     * @param string $identifier
     * @return bool
     * @throws \dml_exception
     */
    public static function exists($identifier)
    {
        return isset(self::get_template_registry()[$identifier]);
    }

    /**
     * @param $identifier
     * @return array|null
     * @throws \dml_exception
     */
    public static function get_template_info($identifier)
    {
        if (self::exists($identifier)) {
            return self::get_template_registry()[$identifier];
        }

        return null;
    }

    /**
     * @param string $identifier
     * @param \context $context
     * @return template_interface
     * @throws \dml_exception
     */
    public static function get_template($identifier, \context $context)
    {
        if (!self::exists($identifier)) {
            return null;
        }

        $templateinfo = self::get_template_info($identifier);

        if (self::is_custom($identifier)) {
            return new custom_template($templateinfo, $context);
        } else {
            if (class_exists($identifier)) {
                return new $identifier($context);
            }
        }

        return null;
    }

    /**
     * Get options array for select form fields.
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_template_form_options()
    {
        $options = [];

        foreach (self::get_template_registry() as $identifier => $template_info) {
            $options[$identifier] = $template_info['name'];
        }

        return $options;
    }
}
<?php
/**
 * The plugin API is located in this file, which allows for creating actions
 * and filters and hooking functions, and methods. The functions or methods will
 * then be run when the action or filter is called.
 *
 * The API callback examples reference functions, but can be methods of classes.
 * To hook methods, you'll need to pass an array one of two ways.
 *
 * Any of the syntaxes explained in the PHP documentation for the
 * {@link http://us2.php.net/manual/en/language.pseudo-types.php#language.types.callback 'callback'}
 * type are valid.
 *
 * more information and examples on how to use a lot of these functions.
 *
 * @package HiveQueen
 * @subpackage Plugin
 * @since 0.0.1
 */

// Initialize the filter globals.
global $hq_filter, $hq_actions, $merged_filters, $hq_current_filter;

if ( ! isset( $hq_filter ) )
        $hq_filter = array();

if ( ! isset( $hq_actions ) )
        $hq_actions = array();

if ( ! isset( $merged_filters ) )
        $merged_filters = array();

if ( ! isset( $hq_current_filter ) )
        $hq_current_filter = array();

/**
 * Hook a function or method to a specific filter action.
 *
 * HiveQueen offers filter hooks to allow plugins to modify
 * various types of internal data at runtime.
 *
 * A plugin can modify data by binding a callback to a filter hook. When the filter
 * is later applied, each bound callback is run in order of priority, and given
 * the opportunity to modify a value by returning a new value.
 *
 * The following example shows how a callback function is bound to a filter hook.
 *
 * Note that `$example` is passed to the callback, (maybe) modified, then returned:
 *
 *     function example_callback( $example ) {
 *         // Maybe modify $example in some way.
 *         return $example;
 *     }
 *     add_filter( 'example_filter', 'example_callback' );
 *
 * Since HivQueene 1.5.1, bound callbacks can take as many arguments as are
 * passed as parameters in the corresponding apply_filters() call. The `$accepted_args`
 * parameter allows for calling functions only when the number of args match.
 *
 * *Note:* the function will return true whether or not the callback is valid.
 * It is up to you to take care. This is done for optimization purposes,
 * so everything is as quick as possible.
 *
 * @since 0.1
 *
 * @global array $hq_filter      A multidimensional array of all hooks and the callbacks hooked to them.
 * @global array $merged_filters Tracks the tags that need to be merged for later. If the hook is added,
 *                               it doesn't need to run through that process.
 *
 * @param string   $tag             The name of the filter to hook the $function_to_add callback to.
 * @param callback $function_to_add The callback to be run when the filter is applied.
 * @param int      $priority        Optional. Used to specify the order in which the functions
 *                                  associated with a particular action are executed. Default 10.
 *                                  Lower numbers correspond with earlier execution,
 *                                  and functions with the same priority are executed
 *                                  in the order in which they were added to the action.
 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
 * @return true
 */
function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        global $hq_filter, $merged_filters;

        $idx = _hq_filter_build_unique_id($tag, $function_to_add, $priority);
        $hq_filter[$tag][$priority][$idx] = array('function' => $function_to_add, 'accepted_args' => $accepted_args);
        unset( $merged_filters[ $tag ] );
        return true;
}

/**
 * Check if any filter has been registered for a hook.
 *
 * @since 0.0.1
 *
 * @global array $hq_filter Stores all of the filters.
 *
 * @param string        $tag               The name of the filter hook.
 * @param callback|bool $function_to_check Optional. The callback to check for. Default false.
 * @return false|int If $function_to_check is omitted, returns boolean for whether the hook has
 *                   anything registered. When checking a specific function, the priority of that
 *                   hook is returned, or false if the function is not attached. When using the
 *                   $function_to_check argument, this function may return a non-boolean value
 *                   that evaluates to false (e.g.) 0, so use the === operator for testing the
 *                   return value.
 */
function has_filter($tag, $function_to_check = false) {
        // Don't reset the internal array pointer
        $hq_filter = $GLOBALS['hq_filter'];

        $has = ! empty( $hq_filter[ $tag ] );

        // Make sure at least one priority has a filter callback
        if ( $has ) {
                $exists = false;
                foreach ( $hq_filter[ $tag ] as $callbacks ) {
                        if ( ! empty( $callbacks ) ) {
                                $exists = true;
                                break;
                        }
                }

                if ( ! $exists ) {
                        $has = false;
                }
        }

        if ( false === $function_to_check || false === $has )
                return $has;

        if ( !$idx = _hq_filter_build_unique_id($tag, $function_to_check, false) )
                return false;

        foreach ( (array) array_keys($hq_filter[$tag]) as $priority ) {
                if ( isset($hq_filter[$tag][$priority][$idx]) )
                        return $priority;
        }

        return false;
}

/**
 * Call the functions added to a filter hook.
 *
 * The callback functions attached to filter hook $tag are invoked by calling
 * this function. This function can be used to create a new filter hook by
 * simply calling this function with the name of the new hook specified using
 * the $tag parameter.
 *
 * The function allows for additional arguments to be added and passed to hooks.
 *
 *     // Our filter callback function
 *     function example_callback( $string, $arg1, $arg2 ) {
 *         // (maybe) modify $string
 *         return $string;
 *     }
 *     add_filter( 'example_filter', 'example_callback', 10, 3 );
 *
 *     /*
 *      * Apply the filters by calling the 'example_callback' function we
 *      * "hooked" to 'example_filter' using the add_filter() function above.
 *      * - 'example_filter' is the filter hook $tag
 *      * - 'filter me' is the value being filtered
 *      * - $arg1 and $arg2 are the additional arguments passed to the callback.
 *     $value = apply_filters( 'example_filter', 'filter me', $arg1, $arg2 );
 *
 * @since 0.1
 *
 * @global array $hq_filter         Stores all of the filters.
 * @global array $merged_filters    Merges the filter hooks using this function.
 * @global array $hq_current_filter Stores the list of current filters with the current one last.
 *
 * @param string $tag   The name of the filter hook.
 * @param mixed  $value The value on which the filters hooked to `$tag` are applied on.
 * @param mixed  $var   Additional variables passed to the functions hooked to `$tag`.
 * @return mixed The filtered value after all hooked functions are applied to it.
 */
function apply_filters( $tag, $value ) {
        global $hq_filter, $merged_filters, $hq_current_filter;

        $args = array();

        // Do 'all' actions first.
        if ( isset($hq_filter['all']) ) {
                $hq_current_filter[] = $tag;
                $args = func_get_args();
                _hq_call_all_hook($args);
        }

        if ( !isset($hq_filter[$tag]) ) {
                if ( isset($hq_filter['all']) )
                        array_pop($hq_current_filter);
                return $value;
        }

        if ( !isset($hq_filter['all']) )
                $hq_current_filter[] = $tag;

        // Sort.
        if ( !isset( $merged_filters[ $tag ] ) ) {
                ksort($hq_filter[$tag]);
                $merged_filters[ $tag ] = true;
        }

        reset( $hq_filter[ $tag ] );

        if ( empty($args) )
                $args = func_get_args();

        do {
                foreach( (array) current($hq_filter[$tag]) as $the_ )
                        if ( !is_null($the_['function']) ){
                                $args[1] = $value;
                                $value = call_user_func_array($the_['function'], array_slice($args, 1, (int) $the_['accepted_args']));
                        }

        } while ( next($hq_filter[$tag]) !== false );

        array_pop( $hq_current_filter );

        return $value;
}

/**
 * Execute functions hooked on a specific filter hook, specifying arguments in an array.
 *
 * @see 0.0.1
 *
 * @see apply_filters() This function is identical, but the arguments passed to the
 * functions hooked to `$tag` are supplied using an array.
 *
 * @global array $hq_filter         Stores all of the filters
 * @global array $merged_filters    Merges the filter hooks using this function.
 * @global array $hq_current_filter Stores the list of current filters with the current one last
 *
 * @param string $tag  The name of the filter hook.
 * @param array  $args The arguments supplied to the functions hooked to $tag.
 * @return mixed The filtered value after all hooked functions are applied to it.
 */
function apply_filters_ref_array($tag, $args) {
        global $hq_filter, $merged_filters, $hq_current_filter;

        // Do 'all' actions first
        if ( isset($hq_filter['all']) ) {
                $hq_current_filter[] = $tag;
                $all_args = func_get_args();
                _hq_call_all_hook($all_args);
        }

        if ( !isset($hq_filter[$tag]) ) {
                if ( isset($hq_filter['all']) )
                        array_pop($hq_current_filter);
                return $args[0];
        }

        if ( !isset($hq_filter['all']) )
                $hq_current_filter[] = $tag;

        // Sort
        if ( !isset( $merged_filters[ $tag ] ) ) {
                ksort($hq_filter[$tag]);
                $merged_filters[ $tag ] = true;
        }

        reset( $hq_filter[ $tag ] );

        do {
                foreach( (array) current($hq_filter[$tag]) as $the_ )
                        if ( !is_null($the_['function']) )
                                $args[0] = call_user_func_array($the_['function'], array_slice($args, 0, (int) $the_['accepted_args']));

        } while ( next($hq_filter[$tag]) !== false );

        array_pop( $hq_current_filter );

        return $args[0];
}

/**
 * Removes a function from a specified filter hook.
 *
 * This function removes a function attached to a specified filter hook. This
 * method can be used to remove default functions attached to a specific filter
 * hook and possibly replace them with a substitute.
 *
 * To remove a hook, the $function_to_remove and $priority arguments must match
 * when the hook was added. This goes for both filters and actions. No warning
 * will be given on removal failure.
 *
 * @since 0.0.1
 *
 * @global array $hq_filter         Stores all of the filters
 * @global array $merged_filters    Merges the filter hooks using this function.
 *
 * @param string   $tag                The filter hook to which the function to be removed is hooked.
 * @param callback $function_to_remove The name of the function which should be removed.
 * @param int      $priority           Optional. The priority of the function. Default 10.
 * @return bool    Whether the function existed before it was removed.
 */
function remove_filter( $tag, $function_to_remove, $priority = 10 ) {
        $function_to_remove = _hq_filter_build_unique_id( $tag, $function_to_remove, $priority );

        $r = isset( $GLOBALS['hq_filter'][ $tag ][ $priority ][ $function_to_remove ] );

        if ( true === $r ) {
                unset( $GLOBALS['hq_filter'][ $tag ][ $priority ][ $function_to_remove ] );
                if ( empty( $GLOBALS['hq_filter'][ $tag ][ $priority ] ) ) {
                        unset( $GLOBALS['hq_filter'][ $tag ][ $priority ] );
                }
                if ( empty( $GLOBALS['hq_filter'][ $tag ] ) ) {
                        $GLOBALS['hq_filter'][ $tag ] = array();
                }
                unset( $GLOBALS['merged_filters'][ $tag ] );
        }

        return $r;
}

/**
 * Remove all of the hooks from a filter.
 *
 * @since 0.0.1
 *
 * @global array $hq_filter         Stores all of the filters
 * @global array $merged_filters    Merges the filter hooks using this function.
 *
 * @param string   $tag      The filter to remove hooks from.
 * @param int|bool $priority Optional. The priority number to remove. Default false.
 * @return true True when finished.
 */
function remove_all_filters( $tag, $priority = false ) {
        global $hq_filter, $merged_filters;

        if ( isset( $hq_filter[ $tag ]) ) {
                if ( false === $priority ) {
                        $hq_filter[ $tag ] = array();
                } elseif ( isset( $hq_filter[ $tag ][ $priority ] ) ) {
                        $hq_filter[ $tag ][ $priority ] = array();
                }
        }

        unset( $merged_filters[ $tag ] );

        return true;
}

/**
 * Retrieve the name of the current filter or action.
 *
 * @since 0.0.1
 *
 * @global array $hq_current_filter Stores the list of current filters with the current one last
 *
 * @return string Hook name of the current filter or action.
 */
function current_filter() {
        global $hq_current_filter;
        return end( $hq_current_filter );
}

/**
 * Retrieve the name of the current action.
 *
 * @since 0.0.1
 *
 * @return string Hook name of the current action.
 */
function current_action() {
        return current_filter();
}

/**
 * Retrieve the name of a filter currently being processed.
 *
 * The function current_filter() only returns the most recent filter or action
 * being executed. did_action() returns true once the action is initially
 * processed.
 *
 * This function allows detection for any filter currently being
 * executed (despite not being the most recent filter to fire, in the case of
 * hooks called from hook callbacks) to be verified.
 *
 * @since 0.0.1
 *
 * @see current_filter()
 * @see did_action()
 * @global array $hq_current_filter Current filter.
 *
 * @param null|string $filter Optional. Filter to check. Defaults to null, which
 *                            checks if any filter is currently being run.
 * @return bool Whether the filter is currently in the stack.
 */
function doing_filter( $filter = null ) {
        global $hq_current_filter;

        if ( null === $filter ) {
                return ! empty( $hq_current_filter );
        }

        return in_array( $filter, $hq_current_filter );
}

/**
 * Retrieve the name of an action currently being processed.
 *
 * @since 0.0.1
 *
 * @param string|null $action Optional. Action to check. Defaults to null, which checks
 *                            if any action is currently being run.
 * @return bool Whether the action is currently in the stack.
 */
function doing_action( $action = null ) {
        return doing_filter( $action );
}

/**
 * Hooks a function on to a specific action.
 *
 * Actions are the hooks that the WordPress core launches at specific points
 * during execution, or when specific events occur. Plugins can specify that
 * one or more of its PHP functions are executed at these points, using the
 * Action API.
 *
 * @since 0.0.1
 *
 * @param string   $tag             The name of the action to which the $function_to_add is hooked.
 * @param callback $function_to_add The name of the function you wish to be called.
 * @param int      $priority        Optional. Used to specify the order in which the functions
 *                                  associated with a particular action are executed. Default 10.
 *                                  Lower numbers correspond with earlier execution,
 *                                  and functions with the same priority are executed
 *                                  in the order in which they were added to the action.
 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
 * @return true Will always return true.
 */
function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return add_filter($tag, $function_to_add, $priority, $accepted_args);
}

/**
 * Execute functions hooked on a specific action hook.
 *
 * This function invokes all functions attached to action hook `$tag`. It is
 * possible to create new action hooks by simply calling this function,
 * specifying the name of the new hook using the `$tag` parameter.
 *
 * You can pass extra arguments to the hooks, much like you can with
 * {@see apply_filters()}.
 *
 * @since 0.0.1
 *
 * @global array $hq_filter         Stores all of the filters
 * @global array $hq_actions        Increments the amount of times action was triggered.
 * @global array $merged_filters    Merges the filter hooks using this function.
 * @global array $hq_current_filter Stores the list of current filters with the current one last
 *
 * @param string $tag The name of the action to be executed.
 * @param mixed  $arg Optional. Additional arguments which are passed on to the
 *                    functions hooked to the action. Default empty.
 */
function do_action($tag, $arg = '') {
        global $hq_filter, $hq_actions, $merged_filters, $hq_current_filter;

        if ( ! isset($hq_actions[$tag]) )
                $hq_actions[$tag] = 1;
        else
                ++$hq_actions[$tag];

        // Do 'all' actions first
        if ( isset($hq_filter['all']) ) {
                $hq_current_filter[] = $tag;
                $all_args = func_get_args();
                _hq_call_all_hook($all_args);
        }

        if ( !isset($hq_filter[$tag]) ) {
                if ( isset($hq_filter['all']) )
                        array_pop($hq_current_filter);
                return;
        }

        if ( !isset($hq_filter['all']) )
                $hq_current_filter[] = $tag;

        $args = array();
        if ( is_array($arg) && 1 == count($arg) && isset($arg[0]) && is_object($arg[0]) ) // array(&$this)
                $args[] =& $arg[0];
        else
                $args[] = $arg;
        for ( $a = 2, $num = func_num_args(); $a < $num; $a++ )
                $args[] = func_get_arg($a);

        // Sort
        if ( !isset( $merged_filters[ $tag ] ) ) {
                ksort($hq_filter[$tag]);
                $merged_filters[ $tag ] = true;
        }

        reset( $hq_filter[ $tag ] );

        do {
                foreach ( (array) current($hq_filter[$tag]) as $the_ )
                        if ( !is_null($the_['function']) )
                                call_user_func_array($the_['function'], array_slice($args, 0, (int) $the_['accepted_args']));

        } while ( next($hq_filter[$tag]) !== false );

        array_pop($hq_current_filter);
}

/**
 * Retrieve the number of times an action is fired.
 *
 * @since 0.0.1
 *
 * @global array $hq_actions Increments the amount of times action was triggered.
 *
 * @param string $tag The name of the action hook.
 * @return int The number of times action hook $tag is fired.
 */
function did_action($tag) {
        global $hq_actions;

        if ( ! isset( $hq_actions[ $tag ] ) )
                return 0;

        return $hq_actions[$tag];
}

/**
 * Execute functions hooked on a specific action hook, specifying arguments in an array.
 *
 * @since 0.0.1
 *
 * @see do_action() This function is identical, but the arguments passed to the
 *                  functions hooked to $tag< are supplied using an array.
 * @global array $hq_filter         Stores all of the filters
 * @global array $hq_actions        Increments the amount of times action was triggered.
 * @global array $merged_filters    Merges the filter hooks using this function.
 * @global array $hq_current_filter Stores the list of current filters with the current one last
 *
 * @param string $tag  The name of the action to be executed.
 * @param array  $args The arguments supplied to the functions hooked to `$tag`.
 */
function do_action_ref_array($tag, $args) {
        global $hq_filter, $hq_actions, $merged_filters, $hq_current_filter;

        if ( ! isset($hq_actions[$tag]) )
                $hq_actions[$tag] = 1;
        else
                ++$hq_actions[$tag];

        // Do 'all' actions first
        if ( isset($hq_filter['all']) ) {
                $hq_current_filter[] = $tag;
                $all_args = func_get_args();
                _hq_call_all_hook($all_args);
        }

        if ( !isset($hq_filter[$tag]) ) {
                if ( isset($hq_filter['all']) )
                        array_pop($hq_current_filter);
                return;
        }

        if ( !isset($hq_filter['all']) )
                $hq_current_filter[] = $tag;

        // Sort
        if ( !isset( $merged_filters[ $tag ] ) ) {
                ksort($hq_filter[$tag]);
                $merged_filters[ $tag ] = true;
        }

        reset( $hq_filter[ $tag ] );

        do {
                foreach( (array) current($hq_filter[$tag]) as $the_ )
                        if ( !is_null($the_['function']) )
                                call_user_func_array($the_['function'], array_slice($args, 0, (int) $the_['accepted_args']));

        } while ( next($hq_filter[$tag]) !== false );

        array_pop($hq_current_filter);
}

/**
 * Check if any action has been registered for a hook.
 *
 * @since 0.0.1
 *
 * @see has_filter() has_action() is an alias of has_filter().
 *
 * @param string        $tag               The name of the action hook.
 * @param callback|bool $function_to_check Optional. The callback to check for. Default false.
 * @return bool|int If $function_to_check is omitted, returns boolean for whether the hook has
 *                  anything registered. When checking a specific function, the priority of that
 *                  hook is returned, or false if the function is not attached. When using the
 *                  $function_to_check argument, this function may return a non-boolean value
 *                  that evaluates to false (e.g.) 0, so use the === operator for testing the
 *                  return value.
 */
function has_action($tag, $function_to_check = false) {
        return has_filter($tag, $function_to_check);
}

/**
 * Removes a function from a specified action hook.
 *
 * This function removes a function attached to a specified action hook. This
 * method can be used to remove default functions attached to a specific filter
 * hook and possibly replace them with a substitute.
 *
 * @since 0.0.1
 *
 * @param string   $tag                The action hook to which the function to be removed is hooked.
 * @param callback $function_to_remove The name of the function which should be removed.
 * @param int      $priority           Optional. The priority of the function. Default 10.
 * @return bool Whether the function is removed.
 */
function remove_action( $tag, $function_to_remove, $priority = 10 ) {
        return remove_filter( $tag, $function_to_remove, $priority );
}

/**
 * Remove all of the hooks from an action.
 *
 * @since 0.0.1
 *
 * @param string   $tag      The action to remove hooks from.
 * @param int|bool $priority The priority number to remove them from. Default false.
 * @return true True when finished.
 */
function remove_all_actions($tag, $priority = false) {
        return remove_all_filters($tag, $priority);
}

//
// Functions for handling plugins.
//

/**
 * Gets the basename of a plugin.
 *
 * This method extracts the name of a plugin from its filename.
 *
 * @since 0.0.1
 *
 * @global array $hq_plugin_paths
 *
 * @param string $file The filename of plugin.
 * @return string The name of a plugin.
 */
function plugin_basename( $file ) {
        global $hq_plugin_paths;

        foreach ( $hq_plugin_paths as $dir => $realdir ) {
                if ( strpos( $file, $realdir ) === 0 ) {
                        $file = $dir . substr( $file, strlen( $realdir ) );
                }
        }

        $file = hq_normalize_path( $file );
        $plugin_dir = hq_normalize_path( HQ_PLUGIN_DIR );
        $mu_plugin_dir = hq_normalize_path( HQMU_PLUGIN_DIR );

        $file = preg_replace('#^' . preg_quote($plugin_dir, '#') . '/|^' . preg_quote($mu_plugin_dir, '#') . '/#','',$file); // get relative path from plugins dir
        $file = trim($file, '/');
        return $file;
}

/**
 * Register a plugin's real path.
 *
 * This is used in plugin_basename() to resolve symlinked paths.
 *
 * @since 0.0.1
 *
 * @see plugin_basename()
 *
 * @global array $hq_plugin_paths
 *
 * @staticvar string $hq_plugin_path
 * @staticvar string $hqmu_plugin_path
 *
 * @param string $file Known path to the file.
 * @return bool Whether the path was able to be registered.
 */
function hq_register_plugin_realpath( $file ) {
        global $hq_plugin_paths;

        // Normalize, but store as static to avoid recalculation of a constant value
        static $hq_plugin_path = null, $hqmu_plugin_path = null;
        if ( ! isset( $hq_plugin_path ) ) {
                $hq_plugin_path   = hq_normalize_path( HQ_PLUGIN_DIR   );
                $hqmu_plugin_path = hq_normalize_path( HQMU_PLUGIN_DIR );
        }

        $plugin_path = hq_normalize_path( dirname( $file ) );
        $plugin_realpath = hq_normalize_path( dirname( realpath( $file ) ) );

        if ( $plugin_path === $hq_plugin_path || $plugin_path === $hqmu_plugin_path ) {
                return false;
        }

        if ( $plugin_path !== $plugin_realpath ) {
                $hq_plugin_paths[ $plugin_path ] = $plugin_realpath;
        }

        return true;
}

/**
 * Get the filesystem directory path (with trailing slash) for the plugin __FILE__ passed in.
 *
 * @since 0.0.1
 *
 * @param string $file The filename of the plugin (__FILE__).
 * @return string the filesystem path of the directory that contains the plugin.
 */
function plugin_dir_path( $file ) {
        return trailingslashit( dirname( $file ) );
}

/**
 * Get the URL directory path (with trailing slash) for the plugin __FILE__ passed in.
 *
 * @since 0.0.1
 *
 * @param string $file The filename of the plugin (__FILE__).
 * @return string the URL path of the directory that contains the plugin.
 */
function plugin_dir_url( $file ) {
        return trailingslashit( plugins_url( '', $file ) );
}

/**
 * Set the activation hook for a plugin.
 *
 * When a plugin is activated, the action 'activate_PLUGINNAME' hook is
 * called. In the name of this hook, PLUGINNAME is replaced with the name
 * of the plugin, including the optional subdirectory. For example, when the
 * plugin is located in hq-content/plugins/sampleplugin/sample.php, then
 * the name of this hook will become 'activate_sampleplugin/sample.php'.
 *
 * When the plugin consists of only one file and is (as by default) located at
 * hq-content/plugins/sample.php the name of this hook will be
 * 'activate_sample.php'.
 *
 * @since 0.0.1
 *
 * @param string   $file     The filename of the plugin including the path.
 * @param callback $function The function hooked to the 'activate_PLUGIN' action.
 */
function register_activation_hook($file, $function) {
        $file = plugin_basename($file);
        add_action('activate_' . $file, $function);
}

/**
 * Set the deactivation hook for a plugin.
 *
 * When a plugin is deactivated, the action 'deactivate_PLUGINNAME' hook is
 * called. In the name of this hook, PLUGINNAME is replaced with the name
 * of the plugin, including the optional subdirectory. For example, when the
 * plugin is located in hq-content/plugins/sampleplugin/sample.php, then
 * the name of this hook will become 'deactivate_sampleplugin/sample.php'.
 *
 * When the plugin consists of only one file and is (as by default) located at
 * hq-content/plugins/sample.php the name of this hook will be
 * 'deactivate_sample.php'.
 *
 * @since 0.0.1
 *
 * @param string   $file     The filename of the plugin including the path.
 * @param callback $function The function hooked to the 'deactivate_PLUGIN' action.
 */
function register_deactivation_hook($file, $function) {
        $file = plugin_basename($file);
        add_action('deactivate_' . $file, $function);
}

/**
 * Set the uninstallation hook for a plugin.
 *
 * Registers the uninstall hook that will be called when the user clicks on the
 * uninstall link that calls for the plugin to uninstall itself. The link won't
 * be active unless the plugin hooks into the action.
 *
 * The plugin should not run arbitrary code outside of functions, when
 * registering the uninstall hook. In order to run using the hook, the plugin
 * will have to be included, which means that any code laying outside of a
 * function will be run during the uninstall process. The plugin should not
 * hinder the uninstall process.
 *
 * If the plugin can not be written without running code within the plugin, then
 * the plugin should create a file named 'uninstall.php' in the base plugin
 * folder. This file will be called, if it exists, during the uninstall process
 * bypassing the uninstall hook. The plugin, when using the 'uninstall.php'
 * should always check for the 'HQ_UNINSTALL_PLUGIN' constant, before
 * executing.
 *
 * @since 0.0.1
 *
 * @param string   $file     Plugin file.
 * @param callback $callback The callback to run when the hook is called. Must be
 *                           a static method or function.
 */
function register_uninstall_hook( $file, $callback ) {
        if ( is_array( $callback ) && is_object( $callback[0] ) ) {
                _doing_it_wrong( __FUNCTION__, __( 'Only a static class method or function can be used in an uninstall hook.' ), '3.1' );
                return;
        }

        /*
         * The option should not be autoloaded, because it is not needed in most
         * cases. Emphasis should be put on using the 'uninstall.php' way of
         * uninstalling the plugin.
         */
        $uninstallable_plugins = (array) get_option('uninstall_plugins');
        $uninstallable_plugins[plugin_basename($file)] = $callback;

        update_option('uninstall_plugins', $uninstallable_plugins);
}

/**
 * Call the 'all' hook, which will process the functions hooked into it.
 *
 * The 'all' hook passes all of the arguments or parameters that were used for
 * the hook, which this function was called for.
 *
 * This function is used internally for apply_filters(), do_action(), and
 * do_action_ref_array() and is not meant to be used from outside those
 * functions. This function does not check for the existence of the all hook, so
 * it will fail unless the all hook exists prior to this function call.
 *
 * @since 0.0.1
 * @access private
 *
 * @global array $hq_filter  Stores all of the filters
 *
 * @param array $args The collected parameters from the hook that was called.
 */
function _hq_call_all_hook($args) {
        global $hq_filter;

        reset( $hq_filter['all'] );
        do {
                foreach( (array) current($hq_filter['all']) as $the_ )
                        if ( !is_null($the_['function']) )
                                call_user_func_array($the_['function'], $args);

        } while ( next($hq_filter['all']) !== false );
}

/**
 * Build Unique ID for storage and retrieval.
 *
 * The old way to serialize the callback caused issues and this function is the
 * solution. It works by checking for objects and creating a new property in
 * the class to keep track of the object and new objects of the same class that
 * need to be added.
 *
 * It also allows for the removal of actions and filters for objects after they
 * change class properties. It is possible to include the property $hq_filter_id
 * in your class and set it to "null" or a number to bypass the workaround.
 * However this will prevent you from adding new classes and any new classes
 * will overwrite the previous hook by the same class.
 *
 * Functions and static method callbacks are just returned as strings and
 * shouldn't have any speed penalty.
 *
 *
 * @since 0.0.1
 * @access private
 *
 * @global array $hq_filter Storage for all of the filters and actions.
 * @staticvar int $filter_id_count
 *
 * @param string   $tag      Used in counting how many hooks were applied
 * @param callback $function Used for creating unique id
 * @param int|bool $priority Used in counting how many hooks were applied. If === false
 *                           and $function is an object reference, we return the unique
 *                           id only if it already has one, false otherwise.
 * @return string|false Unique ID for usage as array key or false if $priority === false
 *                      and $function is an object reference, and it does not already have
 *                      a unique id.
 */
function _hq_filter_build_unique_id($tag, $function, $priority) {
        global $hq_filter;
        static $filter_id_count = 0;

        if ( is_string($function) )
                return $function;

        if ( is_object($function) ) {
                // Closures are currently implemented as objects
                $function = array( $function, '' );
        } else {
                $function = (array) $function;
        }

        if (is_object($function[0]) ) {
                // Object Class Calling
                if ( function_exists('spl_object_hash') ) {
                        return spl_object_hash($function[0]) . $function[1];
                } else {
                        $obj_idx = get_class($function[0]).$function[1];
                        if ( !isset($function[0]->hq_filter_id) ) {
                                if ( false === $priority )
                                        return false;
                                $obj_idx .= isset($hq_filter[$tag][$priority]) ? count((array)$hq_filter[$tag][$priority]) : $filter_id_count;
                                $function[0]->hq_filter_id = $filter_id_count;
                                ++$filter_id_count;
                        } else {
                                $obj_idx .= $function[0]->hq_filter_id;
                        }

                        return $obj_idx;
                }
        } elseif ( is_string( $function[0] ) ) {
                // Static Calling
                return $function[0] . '::' . $function[1];
        }
}


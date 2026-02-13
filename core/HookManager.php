<?php
class HookManager {
    private static $events = [];

    public static function add_action($hook, $callback) {
        self::$events[$hook][] = $callback;
    }

    public static function trigger_action($hook, $params = null) {
        if (isset(self::$events[$hook])) {
            foreach (self::$events[$hook] as $callback) {
                call_user_func($callback, $params);
            }
        }
    }
}
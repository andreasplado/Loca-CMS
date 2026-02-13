<?php
HookManager::add_action('after_content', function() {
    echo "<p style='color:blue;'>Action Hook: The Hello World plugin is working!</p>";
});
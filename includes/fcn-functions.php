<?php
function fcn_check_file_changes() {
    // Path to monitor (WordPress installation directory)
    $directory_to_monitor = ABSPATH;

    // Get the previously stored file data from the database
    $previous_file_data = get_option('fcn_file_data', []);
    $ignored_files = get_option('fcn_ignored_files', []);

    // Get the current file data
    $current_file_data = fcn_get_file_data($directory_to_monitor);

    // Compare the file data and store changes if detected
    if ($previous_file_data) {
        $changes = fcn_compare_file_data($previous_file_data, $current_file_data, $ignored_files);
        if (!empty($changes)) {
            // Store the detected changes permanently for settings page use
            $detected_changes = get_option('fcn_detected_changes', []);
            $detected_changes = array_merge($detected_changes, $changes);
            update_option('fcn_detected_changes', $detected_changes);
            // Send email notification
            fcn_send_email_notification($changes);
        }
    }

    // Update the file data in the database
    update_option('fcn_file_data', $current_file_data);
}

function fcn_get_file_data($directory) {
    $file_data = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

    foreach ($files as $file) {
        // Skip directories
        if ($file->isDir()) {
            continue;
        }

        // Skip cache folder and its contents
        if (strpos($file->getRealPath(), DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR) !== false) {
            continue;
        }

        $file_data[$file->getRealPath()] = $file->getMTime();
    }

    return $file_data;
}

function fcn_compare_file_data($previous, $current, $ignored_files) {
    $changes = [];
    $current_user = wp_get_current_user()->user_login;
    $current_time = current_time('mysql');

    foreach ($current as $file => $mtime) {
        if (!isset($previous[$file])) {
            if (!in_array($file, $ignored_files)) {
                $changes[] = [
                    "type" => "New file",
                    "path" => $file,
                    "user" => $current_user,
                    "time" => $current_time
                ];
            }
        } elseif ($previous[$file] != $mtime && !in_array($file, $ignored_files)) {
            $changes[] = [
                "type" => "Modified file",
                "path" => $file,
                "user" => $current_user,
                "time" => $current_time
            ];
        }
    }
    foreach ($previous as $file => $mtime) {
        if (!isset($current[$file]) && !in_array($file, $ignored_files)) {
            $changes[] = [
                "type" => "Deleted file",
                "path" => $file,
                "user" => $current_user,
                "time" => $current_time
            ];
        }
    }
    return $changes;
}

function fcn_send_email_notification($changes) {
    $email_addresses = get_option('fcn_email_addresses', []);
    if (empty($email_addresses)) {
        return;
    }

    $alert_threshold = get_option('fcn_alert_threshold', 1);
    if (count($changes) < $alert_threshold) {
        return; // Do not send an email if the number of changes is below the threshold
    }

    $subject = "File Change Notification";
    $message = "The following changes were detected in your WordPress installation:\n\n";

    foreach ($changes as $change) {
        $message .= sprintf("%s: %s\nChanged by: %s\nChange time: %s\n\n",
            $change["type"],
            $change["path"],
            $change["user"],
            $change["time"]
        );
    }

    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    wp_mail($email_addresses, $subject, $message, $headers);
}

function fcn_deactivate() {
    wp_clear_scheduled_hook('fcn_check_file_changes_hook');
    delete_option('fcn_file_data');
    delete_option('fcn_detected_changes');
    delete_option('fcn_ignored_files');
    delete_option('fcn_email_addresses');
    delete_option('fcn_alert_threshold');
}

function fcn_add_settings_page() {
    add_options_page('File Change Notifier Settings', 'File Change Notifier', 'manage_options', 'fcn-settings', 'fcn_render_settings_page');
}

function fcn_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>File Change Notifier Settings</h1>
        <form method="post" action="admin-post.php?action=fcn_save_changes">
            <?php fcn_display_detected_changes(); ?>
            <div id="settings-flex-container" style="display: flex; justify-content: space-between; gap: 20px;">
                <fieldset style="border: 2px solid #ccc; padding: 10px; margin: 10px; width: 48%;">
                    <legend><h2>Email Notifications</h2></legend>
                    <div style="flex: 1;">
                        <p class="description">Enter one or more email addresses. Click "Add Email" to add more fields, and "Remove" to delete a field.</p>
                        <div id="email-addresses-container">
                            <?php
                            $email_addresses = get_option('fcn_email_addresses', []);
                            if (empty($email_addresses)) {
                                $email_addresses = [''];
                            }
                            foreach ($email_addresses as $email) {
                                echo '<div class="email-address-field">
                                        <input type="text" name="fcn_email_addresses[]" value="' . esc_attr($email) . '" class="email-input" />
                                        <button type="button" class="remove-email">Remove</button>
                                    </div>';
                            }
                            ?>
                        </div>
                        <button type="button" id="add-email" class="button button-primary">Add Email</button>
                    </div>
                </fieldset>
                <fieldset style="border: 2px solid #ccc; padding: 10px; margin: 10px; width: 48%;">
  <legend><h2>Alert Threshold</h2></legend>
  <div style="flex: 1; display: flex;"> <div id="alert-threshold-container">
      <?php
        $alert_threshold = get_option('fcn_alert_threshold', 1); // Default to 1 change
      ?>
      <input type="number" name="fcn_alert_threshold" value="<?php echo esc_attr($alert_threshold); ?>" min="1" class="small-text" />
      <p class="description">Enter the number of file changes that will trigger an email alert.</p>
    </div>
  </div>
</fieldset>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <button type="submit" name="submit" class="button button-primary">Save Changes</button>
                <button type="submit" name="clear_history" class="button button-secondary">Clear History</button>
            </div>
        </form>
    </div>
    <?php
}

function fcn_display_detected_changes() {
    $detected_changes = get_option('fcn_detected_changes', []);
    $ignored_files = get_option('fcn_ignored_files', []);
    $detected_files = [];

    // Create a map of detected changes with the latest entry for each file
    foreach ($detected_changes as $change) {
        $detected_files[$change["path"]] = $change;
    }

    // Pagination variables
    $per_page = 10; // Number of items per page
    $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
    $total_items = count($detected_files);
    $total_pages = ceil($total_items / $per_page);
    $offset = ($current_page - 1) * $per_page;
    $paginated_files = array_slice(array_values($detected_files), $offset, $per_page);

    echo '<table class="fcn-table">';
    echo '<thead><tr><th>Change Type</th><th>File Path</th><th>Changed By</th><th>Change Time</th><th>Action</th></tr></thead>';
    echo '<tbody>';

    // Display the latest detected changes with pagination
    foreach ($paginated_files as $change) {
        $file = $change["path"];
        $is_ignored = in_array($file, $ignored_files);
        $change_type = esc_html($change["type"]);
        $file_path = esc_html($file);
        $changed_by = esc_html($change["user"]);
        $change_time = esc_html($change["time"]);
        $checked = $is_ignored ? 'checked' : '';

        echo '<tr>';
        echo '<td>' . $change_type . '</td>';
        echo '<td>' . $file_path . '</td>';
        echo '<td>' . $changed_by . '</td>';
        echo '<td>' . $change_time . '</td>';
        echo '<td><input type="checkbox" name="fcn_ignored_files[]" value="' . esc_attr($file) . '" ' . $checked . '></td>';
        echo '</tr>';
    }

    // Display ignored files that were not part of detected changes
    foreach ($ignored_files as $file) {
        if (!isset($detected_files[$file])) {
            // Provide default values for change details
            $change_type = 'Ignored file';
            $changed_by = '-';
            $change_time = '-';
            $checked = 'checked';

            echo '<tr>';
            echo '<td>' . esc_html($change_type) . '</td>';
            echo '<td>' . esc_html($file) . '</td>';
            echo '<td>' . esc_html($changed_by) . '</td>';
            echo '<td>' . esc_html($change_time) . '</td>';
            echo '<td><input type="checkbox" name="fcn_ignored_files[]" value="' . esc_attr($file) . '" ' . $checked . '></td>';
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';

    // Display pagination
    if ($total_pages > 1) {
        echo '<div class="pagination">';
        if ($current_page > 1) {
            echo '<a href="' . add_query_arg('paged', $current_page - 1) . '">&laquo; Previous</a>';
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $current_page) {
                echo '<span class="current-page">' . $i . '</span>';
            } else {
                echo '<a href="' . add_query_arg('paged', $i) . '">' . $i . '</a>';
            }
        }
        if ($current_page < $total_pages) {
            echo '<a href="' . add_query_arg('paged', $current_page + 1) . '">Next &raquo;</a>';
        }
        echo '</div>';
    }
}

function fcn_save_changes() {
    if (isset($_POST['fcn_ignored_files'])) {
        $new_ignored_files = array_map('sanitize_text_field', $_POST['fcn_ignored_files']);
        $new_ignored_files = array_unique($new_ignored_files);
        update_option('fcn_ignored_files', $new_ignored_files);
    } else {
        delete_option('fcn_ignored_files');
    }

    if (isset($_POST['fcn_email_addresses'])) {
        $email_addresses = array_map('sanitize_email', $_POST['fcn_email_addresses']);
        $email_addresses = array_filter($email_addresses); // Remove any empty values
        update_option('fcn_email_addresses', $email_addresses);
    } else {
        delete_option('fcn_email_addresses');
    }

    if (isset($_POST['fcn_alert_threshold'])) {
        $alert_threshold = intval($_POST['fcn_alert_threshold']);
        update_option('fcn_alert_threshold', $alert_threshold);
    }

    if (isset($_POST['clear_history'])) {
        fcn_clear_modified_file_history();
    }

    wp_redirect(admin_url('options-general.php?page=fcn-settings'));
    exit;
}

function fcn_clear_modified_file_history() {
    delete_option('fcn_file_data');
    delete_option('fcn_detected_changes');
    delete_option('fcn_ignored_files');
    delete_option('fcn_email_addresses');
    delete_option('fcn_alert_threshold');
}

function fcn_enqueue_styles() {
    wp_enqueue_style('fcn-styles', plugin_dir_url(__FILE__) . '../css/styles.css');
}

function fcn_enqueue_scripts($hook) {
    if ($hook !== 'settings_page_fcn-settings') {
        return;
    }
    wp_enqueue_script('fcn-admin-script', plugin_dir_url(__FILE__) . '../js/admin-script.js', ['jquery'], null, true);
    wp_enqueue_style('fcn-admin-style', plugin_dir_url(__FILE__) . '../css/admin-style.css');
}
add_action('admin_enqueue_scripts', 'fcn_enqueue_scripts');

function fcn_add_dashboard_widget() {
    wp_add_dashboard_widget('fcn_dashboard_widget', 'Recent File Changes', 'fcn_render_dashboard_widget');
}

function fcn_render_dashboard_widget() {
    $detected_changes = get_option('fcn_detected_changes', []);
    if (empty($detected_changes)) {
        echo '<p>No recent changes.</p>';
        return;
    }

    echo '<ul>';
    foreach (array_slice($detected_changes, 0, 5) as $change) {
        echo '<li>' . esc_html($change['type']) . ': ' . esc_html($change['path']) . '</li>';
    }
    echo '</ul>';
}

add_action('wp_dashboard_setup', 'fcn_add_dashboard_widget');
?>

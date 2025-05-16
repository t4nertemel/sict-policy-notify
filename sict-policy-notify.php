<?php
/**
 * Plugin Name: SICT Policy Notify
 * Description: Setup & sends email reminders to staff 3, 1 month & 1 week before policy renewals date - Ofsted Compliancy.
 * Version: 0.5
 * Author: Taner Temel
 * Author URI: https://www.bolton365.net
 * Author URI:        https://www.linkedin.com/in/taner-temel-ba7b9844
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bsict-extention
 * Domain Path:       /languages
 */
 
 add_action('wp', 'sict_send_policy_notifications'); //remove this line

// Hook the function to a WordPress action
add_action('sict_daily_policy_notification', 'sict_send_policy_notifications');

// Schedule the daily event
if (!wp_next_scheduled('sict_daily_policy_notification')) {
    wp_schedule_event(strtotime('tomorrow 00:00'), 'daily', 'sict_daily_policy_notification');
}

 // Set Up the Admin Panel
 add_action('admin_menu', 'sict_policy_notify_menu');

function sict_policy_notify_menu() {
    add_menu_page(
        'Policy Notify', // Page title
        'Policy Notify', // Menu title
        'manage_options', // Capability
        'sict-policy-notify', // Menu slug
        'sict_policy_notify_page' // Function to display the page
    );
}

// Enqueue admin stylesheet
function sict_policy_notify_admin_styles() {
    wp_enqueue_style( 'sict-policy-notify-admin-style', plugins_url( 'admin.css', __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'sict_policy_notify_admin_styles' );

// Admin Menu
function sict_policy_notify_page() {
    ?>
    <h1>BSICT Policy Notify</h1>
    <h2>Add New Policy Notification</h2>
    <form method="post" action="">
        <label for="user">Select User:</label>
        <select name="user" id="user">
            <?php
            $users = get_users();
            foreach ($users as $user) {
                echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
            }
            ?>
        </select>
        <br><br>

        <label for="policy_title">Policy Title:</label>
        <input type="text" name="policy_title" id="policy_title" required>
        <br><br>

        <label for="document_url">Document URL:</label>
        <input type="url" name="document_url" id="document_url" required>
        <br><br>

        <label for="page_url">Page URL:</label>
        <input type="url" name="page_url" id="page_url">
        <br><br>

        <label for="renewal_date">Renewal Date:</label>
        <input type="date" name="renewal_date" id="renewal_date" required>
        <br><br>

        <input type="submit" name="submit" value="Save Notification">
    </form>

    <?php
    if (isset($_POST['submit'])) {
        $user_id = intval($_POST['user']);
        $policy_title = sanitize_text_field($_POST['policy_title']);
        $document_url = esc_url_raw($_POST['document_url']);
        $page_url = esc_url_raw($_POST['page_url']); // Capture page_url
        $renewal_date = sanitize_text_field($_POST['renewal_date']);

        $notifications = get_option('sict_policy_notifications');
        if (!$notifications) {
            $notifications = array();
        }
        $notifications[] = array(
            'user_id' => $user_id,
            'policy_title' => $policy_title,
            'document_url' => $document_url,
            'page_url' => $page_url, // Store page_url
            'renewal_date' => $renewal_date,
        );
        update_option('sict_policy_notifications', $notifications);

        echo '<div class="updated"><p>Notification saved for ' . esc_html($policy_title) . '.</p></div>';
    }

    // Handle deletion of a notification
    if (isset($_GET['delete_index']) && is_numeric($_GET['delete_index'])) {
        $delete_index = intval($_GET['delete_index']);
        $notifications = get_option('sict_policy_notifications');
        if (isset($notifications[$delete_index])) {
            unset($notifications[$delete_index]);
            $notifications = array_values($notifications);
            update_option('sict_policy_notifications', $notifications);
            echo '<div class="updated"><p>Notification deleted successfully.</p></div>';
        } else {
            echo '<div class="error"><p>Error: Invalid notification ID for deletion.</p></div>';
        }
    }

    // Display the list of current policy notifications
    $notifications = get_option('sict_policy_notifications');
    if ($notifications) {
        ?>
        <h2>Current Policy Notifications</h2>
        <table>
            <tr>
                <th>Delegate</th>
                <th>Policy Title</th>
                <th>Document URL</th>
                <th>Page URL</th> <th>Renewal Date</th>
                <th>Edit</th>
                <th>Delete</th>
            </tr>
            <?php
            foreach ($notifications as $index => $notification) {
                ?>
                <tr>
                    <td><?php echo get_userdata($notification['user_id'])->display_name; ?></td>
                    <td><?php echo $notification['policy_title']; ?></td>
                    <td><?php echo $notification['document_url']; ?></td>
                    <td><?php
                        if (isset($notification['page_url'])) {
                            echo esc_url($notification['page_url']);
                        } else {
                            echo '';
                        }
                        ?>
                    </td>
                    <td><?php echo $notification['renewal_date']; ?></td>
                    <td><a href="<?php echo admin_url('admin.php?page=sict-policy-notify&edit_index=' . $index); ?>">Edit</a></td>
                    <td><a href="<?php echo admin_url('admin.php?page=sict-policy-notify&delete_index=' . $index); ?>" onclick="return confirm('Are you sure you want to delete this notification?')">Delete</a></td>
                </tr>
                <?php
            }
            ?>
        </table>
        <?php
    }

    // Edit policy notification - NOW INSIDE THE MAIN FUNCTION
    if (isset($_GET['edit_index'])) {
        $key = intval($_GET['edit_index']);
        $notifications = get_option('sict_policy_notifications');
        if (isset($notifications[$key])) {
            $notification = $notifications[$key];

            // Display the edit form
            ?>
            <h2>Edit Policy Notification</h2>
            <form method="post" action="">
                <label for="user">Select User:</label>
                <select name="user" id="user">
                    <?php
                    $users = get_users();
                    foreach ($users as $user) {
                        echo '<option value="' . esc_attr($user->ID) . '"' . ($user->ID == $notification['user_id'] ? ' selected' : '') . '>' . esc_html($user->display_name) . '</option>';
                    }
                    ?>
                </select>
                <br><br>

                <label for="policy_title">Policy Title:</label>
                <input type="text" name="policy_title" id="policy_title" value="<?php echo $notification['policy_title']; ?>" required>
                <br><br>

                <label for="document_url">Document URL:</label>
                <input type="url" name="document_url" id="document_url" value="<?php echo $notification['document_url']; ?>" required>
                <br><br>

                <label for="page_url">Page URL:</label>
                <input type="url" name="page_url" id="page_url" value="<?php echo esc_attr($notification['page_url']); ?>">
                <br><br>

                <label for="renewal_date">Renewal Date:</label>
                <input type="date" name="renewal_date" id="renewal_date" value="<?php echo $notification['renewal_date']; ?>" required>
                <br><br>

                <input type="hidden" name="edit_id" value="<?php echo $key; ?>">
                <input type="submit" name="update" value="Update Notification">
            </form>
            <?php
        } else {
            echo '<div class="error"><p>Error: Invalid notification ID.</p></div>';
        }
    }

    // Update policy notification - KEEP THIS INSIDE THE MAIN FUNCTION
    if (isset($_POST['update'])) {
        $key = intval($_POST['edit_id']);
        $user_id = intval($_POST['user']);
        $policy_title = sanitize_text_field($_POST['policy_title']);
        $document_url = esc_url_raw($_POST['document_url']);
        $page_url = esc_url_raw($_POST['page_url']); // Capture and sanitize
        $renewal_date = sanitize_text_field($_POST['renewal_date']);

        // Update the notification details in the database
        $notifications = get_option('sict_policy_notifications');
        $notifications[$key]['user_id'] = $user_id;
        $notifications[$key]['policy_title'] = $policy_title;
        $notifications[$key]['document_url'] = $document_url;
        $notifications[$key]['page_url'] = $page_url; // Store
        $notifications[$key]['renewal_date'] = $renewal_date;
        update_option('sict_policy_notifications', $notifications);

        // Display a success message
        echo '<div class="updated"><p>Notification updated for ' . esc_html($policy_title) . '.</p></div>';
    }
}

// Email sending
function sict_send_policy_notifications() {
    $notifications = get_option('sict_policy_notifications');
    if ($notifications) {
        foreach ($notifications as $key => $notification) { // Change to $key => $notification
            $renewal_date = strtotime($notification['renewal_date']);
            $today = strtotime(date('Y-m-d'));

            // Check if the renewal date is today or in the past
            if ($renewal_date <= $today) {
                $user_id = $notification['user_id'];
                $user = get_userdata($user_id);
                if ($user) {
                    $to = $user->user_email;
                    $subject = 'Policy Renewal Notification: ' . $notification['policy_title'];
                    $message = "Dear " . $user->display_name . ",\n\n";
                    $message .= "This is a reminder that the following policy: " . $notification['policy_title'] . " is due for renewal on your school website.\n";
                    $message .= "Document URL: " . $notification['document_url'] . "\n";
                    if (isset($notification['page_url']) && !empty($notification['page_url'])) {
                        $message .= "Page URL: " . $notification['page_url'] . "\n";
                    }
                    $message .= "Renewal Date: " . $notification['renewal_date'] . "\n\n";
                    $message .= "Please update: " . $notification['policy_title'] . " by: " . $notification['renewal_date'] . "\n\n";
                    $message .= "Many Thanks,\nBolton Schools ICT Webs Team";  // Customize this

                    // Use wp_mail() to send the email
                    $sent = wp_mail($to, $subject, $message);

                    if ($sent) {
                        // Update the notification to prevent resending on subsequent runs.
                        $notifications[$key]['sent'] = true; // Add a 'sent' flag
                        update_option('sict_policy_notifications', $notifications);
                        error_log("Email sent successfully to {$to} for policy: {$notification['policy_title']}"); // Log success
                    } else {
                        error_log("Error sending email to {$to} for policy: {$notification['policy_title']}"); // Log error
                    }
                }
            }
        }
    }
}
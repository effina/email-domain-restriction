<?php
/**
 * Role Mapping Admin Page View
 *
 * @package Email_Domain_Restriction
 * @subpackage Pro_Admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$role_manager = new EDR_Role_Manager();
$page_obj = new EDR_Role_Mapping_Page();
$available_roles = $page_obj->get_available_roles();
$mappings = $role_manager->get_all_role_mappings();

// Get current mapping for edit
$current_mapping = null;
if ($action === 'edit' && $mapping_id) {
    $current_mapping = $role_manager->get_role_mapping($mapping_id);
}
?>

<div class="wrap edr-role-mappings-page">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Role Mappings', 'email-domain-restriction'); ?>
    </h1>

    <?php if ($action === 'list'): ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=edr-role-mappings&action=add')); ?>" class="page-title-action">
            <?php esc_html_e('Add New', 'email-domain-restriction'); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php settings_errors('edr_role_mappings'); ?>

    <div class="edr-role-mappings-container">
        <?php if ($action === 'list'): ?>
            <!-- List View -->
            <div class="edr-mappings-tools">
                <div class="edr-tools-left">
                    <button type="button" class="button" id="edr-bulk-delete-btn" disabled>
                        <?php esc_html_e('Delete Selected', 'email-domain-restriction'); ?>
                    </button>
                    <button type="button" class="button" id="edr-export-btn">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Export', 'email-domain-restriction'); ?>
                    </button>
                    <button type="button" class="button" id="edr-import-btn">
                        <span class="dashicons dashicons-upload"></span>
                        <?php esc_html_e('Import', 'email-domain-restriction'); ?>
                    </button>
                </div>
                <div class="edr-tools-right">
                    <button type="button" class="button" id="edr-test-role-btn">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Test Role Assignment', 'email-domain-restriction'); ?>
                    </button>
                </div>
            </div>

            <?php if (empty($mappings)): ?>
                <div class="edr-empty-state">
                    <div class="edr-empty-icon">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                    <h2><?php esc_html_e('No Role Mappings Yet', 'email-domain-restriction'); ?></h2>
                    <p><?php esc_html_e('Create role mappings to automatically assign roles based on email domains.', 'email-domain-restriction'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=edr-role-mappings&action=add')); ?>" class="button button-primary">
                        <?php esc_html_e('Add Your First Mapping', 'email-domain-restriction'); ?>
                    </a>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped edr-mappings-table">
                    <thead>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" id="edr-select-all">
                            </td>
                            <th class="column-domain"><?php esc_html_e('Domain Pattern', 'email-domain-restriction'); ?></th>
                            <th class="column-role"><?php esc_html_e('Role', 'email-domain-restriction'); ?></th>
                            <th class="column-priority"><?php esc_html_e('Priority', 'email-domain-restriction'); ?></th>
                            <th class="column-created"><?php esc_html_e('Created', 'email-domain-restriction'); ?></th>
                            <th class="column-actions"><?php esc_html_e('Actions', 'email-domain-restriction'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mappings as $mapping): ?>
                            <tr data-mapping-id="<?php echo esc_attr($mapping['id']); ?>">
                                <th class="check-column">
                                    <input type="checkbox" class="edr-mapping-checkbox" value="<?php echo esc_attr($mapping['id']); ?>">
                                </th>
                                <td class="column-domain">
                                    <strong><?php echo esc_html($mapping['domain']); ?></strong>
                                    <?php if (strpos($mapping['domain'], '*') !== false): ?>
                                        <span class="edr-badge edr-badge-wildcard"><?php esc_html_e('Wildcard', 'email-domain-restriction'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-role">
                                    <?php
                                    $role_name = isset($available_roles[$mapping['role']]) ? $available_roles[$mapping['role']] : $mapping['role'];
                                    echo esc_html($role_name);
                                    ?>
                                </td>
                                <td class="column-priority">
                                    <span class="edr-priority-badge edr-priority-<?php echo absint($mapping['priority']) >= 50 ? 'high' : (absint($mapping['priority']) >= 25 ? 'medium' : 'low'); ?>">
                                        <?php echo absint($mapping['priority']); ?>
                                    </span>
                                </td>
                                <td class="column-created">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($mapping['created_at']))); ?>
                                </td>
                                <td class="column-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=edr-role-mappings&action=edit&mapping_id=' . $mapping['id'])); ?>" class="button button-small">
                                        <?php esc_html_e('Edit', 'email-domain-restriction'); ?>
                                    </a>
                                    <button type="button" class="button button-small edr-delete-mapping" data-mapping-id="<?php echo esc_attr($mapping['id']); ?>">
                                        <?php esc_html_e('Delete', 'email-domain-restriction'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="edr-mappings-info">
                    <p>
                        <strong><?php esc_html_e('Total Mappings:', 'email-domain-restriction'); ?></strong> <?php echo count($mappings); ?>
                    </p>
                    <p class="description">
                        <?php esc_html_e('Role mappings are applied in order of priority (higher numbers first). If multiple patterns match, the most specific pattern wins.', 'email-domain-restriction'); ?>
                    </p>
                </div>
            <?php endif; ?>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Form -->
            <div class="edr-form-container">
                <h2>
                    <?php echo $action === 'add'
                        ? esc_html__('Add Role Mapping', 'email-domain-restriction')
                        : esc_html__('Edit Role Mapping', 'email-domain-restriction'); ?>
                </h2>

                <form method="post" action="" class="edr-role-mapping-form">
                    <?php
                    if ($action === 'add') {
                        wp_nonce_field('edr_add_role_mapping', 'edr_role_mapping_nonce');
                    } else {
                        wp_nonce_field('edr_update_role_mapping', 'edr_role_mapping_nonce');
                    }
                    ?>

                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="mapping_id" value="<?php echo esc_attr($mapping_id); ?>">
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="domain">
                                    <?php esc_html_e('Domain Pattern', 'email-domain-restriction'); ?>
                                    <span class="required">*</span>
                                </label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    name="domain"
                                    id="domain"
                                    class="regular-text"
                                    value="<?php echo $current_mapping ? esc_attr($current_mapping['domain']) : ''; ?>"
                                    required
                                    placeholder="example.com or *.example.com"
                                >
                                <p class="description">
                                    <?php esc_html_e('Enter a domain name. Use * as wildcard for subdomains (e.g., *.example.com).', 'email-domain-restriction'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="role">
                                    <?php esc_html_e('Assign Role', 'email-domain-restriction'); ?>
                                    <span class="required">*</span>
                                </label>
                            </th>
                            <td>
                                <select name="role" id="role" required>
                                    <option value=""><?php esc_html_e('— Select Role —', 'email-domain-restriction'); ?></option>
                                    <?php foreach ($available_roles as $role_slug => $role_name): ?>
                                        <option value="<?php echo esc_attr($role_slug); ?>" <?php selected($current_mapping['role'] ?? '', $role_slug); ?>>
                                            <?php echo esc_html($role_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Users registering with matching email domains will be assigned this role.', 'email-domain-restriction'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="priority">
                                    <?php esc_html_e('Priority', 'email-domain-restriction'); ?>
                                </label>
                            </th>
                            <td>
                                <input
                                    type="number"
                                    name="priority"
                                    id="priority"
                                    class="small-text"
                                    value="<?php echo $current_mapping ? esc_attr($current_mapping['priority']) : '10'; ?>"
                                    min="0"
                                    max="100"
                                >
                                <p class="description">
                                    <?php esc_html_e('Priority (0-100). Higher values take precedence when multiple patterns match. Default: 10', 'email-domain-restriction'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <div class="edr-form-actions">
                        <?php if ($action === 'add'): ?>
                            <button type="submit" name="edr_add_role_mapping" class="button button-primary">
                                <?php esc_html_e('Add Role Mapping', 'email-domain-restriction'); ?>
                            </button>
                        <?php else: ?>
                            <button type="submit" name="edr_update_role_mapping" class="button button-primary">
                                <?php esc_html_e('Update Role Mapping', 'email-domain-restriction'); ?>
                            </button>
                        <?php endif; ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=edr-role-mappings')); ?>" class="button">
                            <?php esc_html_e('Cancel', 'email-domain-restriction'); ?>
                        </a>
                    </div>
                </form>

                <div class="edr-help-box">
                    <h3><?php esc_html_e('Domain Pattern Examples', 'email-domain-restriction'); ?></h3>
                    <ul>
                        <li><code>example.com</code> - <?php esc_html_e('Exact match for @example.com', 'email-domain-restriction'); ?></li>
                        <li><code>*.example.com</code> - <?php esc_html_e('Matches all subdomains (mail.example.com, app.example.com, etc.)', 'email-domain-restriction'); ?></li>
                        <li><code>*.co.uk</code> - <?php esc_html_e('Matches all .co.uk domains', 'email-domain-restriction'); ?></li>
                    </ul>

                    <h3><?php esc_html_e('Conflict Resolution', 'email-domain-restriction'); ?></h3>
                    <p><?php esc_html_e('When multiple patterns match an email:', 'email-domain-restriction'); ?></p>
                    <ol>
                        <li><?php esc_html_e('Higher priority mappings are preferred', 'email-domain-restriction'); ?></li>
                        <li><?php esc_html_e('If priorities are equal, more specific patterns win', 'email-domain-restriction'); ?></li>
                        <li><?php esc_html_e('Exact matches always beat wildcards', 'email-domain-restriction'); ?></li>
                    </ol>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Import Modal -->
    <div id="edr-import-modal" class="edr-modal" style="display:none;">
        <div class="edr-modal-content">
            <span class="edr-modal-close">&times;</span>
            <h2><?php esc_html_e('Import Role Mappings', 'email-domain-restriction'); ?></h2>

            <form method="post" action="" enctype="multipart/form-data" id="edr-import-form">
                <?php wp_nonce_field('edr_import_mappings', 'edr_import_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="import_file">
                                <?php esc_html_e('JSON File', 'email-domain-restriction'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="file" name="import_file" id="import_file" accept=".json" required>
                            <p class="description">
                                <?php esc_html_e('Upload a JSON file containing role mappings.', 'email-domain-restriction'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Options', 'email-domain-restriction'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="replace_existing" value="1">
                                <?php esc_html_e('Replace existing mappings', 'email-domain-restriction'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('If checked, all existing mappings will be deleted before import.', 'email-domain-restriction'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="edr_import_mappings" class="button button-primary">
                        <?php esc_html_e('Import', 'email-domain-restriction'); ?>
                    </button>
                    <button type="button" class="button edr-modal-cancel">
                        <?php esc_html_e('Cancel', 'email-domain-restriction'); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>

    <!-- Test Role Assignment Modal -->
    <div id="edr-test-modal" class="edr-modal" style="display:none;">
        <div class="edr-modal-content">
            <span class="edr-modal-close">&times;</span>
            <h2><?php esc_html_e('Test Role Assignment', 'email-domain-restriction'); ?></h2>

            <div class="edr-test-form">
                <p>
                    <label for="test_email">
                        <?php esc_html_e('Email Address:', 'email-domain-restriction'); ?>
                    </label>
                    <input type="email" id="test_email" class="regular-text" placeholder="user@example.com">
                </p>
                <p>
                    <button type="button" class="button button-primary" id="edr-run-test">
                        <?php esc_html_e('Test', 'email-domain-restriction'); ?>
                    </button>
                </p>
            </div>

            <div id="edr-test-results" style="display:none;">
                <h3><?php esc_html_e('Test Results', 'email-domain-restriction'); ?></h3>
                <table class="widefat">
                    <tr>
                        <th><?php esc_html_e('Domain:', 'email-domain-restriction'); ?></th>
                        <td id="edr-result-domain"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Assigned Role:', 'email-domain-restriction'); ?></th>
                        <td id="edr-result-role"></td>
                    </tr>
                </table>

                <div id="edr-matching-patterns"></div>
            </div>
        </div>
    </div>
</div>

<style>
.edr-role-mappings-page {
    max-width: 1200px;
}

.edr-mappings-tools {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    padding: 15px;
    background: #f0f0f1;
    border-radius: 4px;
}

.edr-tools-left,
.edr-tools-right {
    display: flex;
    gap: 10px;
}

.edr-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-top: 20px;
}

.edr-empty-icon {
    font-size: 64px;
    color: #c3c4c7;
    margin-bottom: 20px;
}

.edr-empty-icon .dashicons {
    width: 64px;
    height: 64px;
    font-size: 64px;
}

.edr-badge {
    display: inline-block;
    padding: 2px 8px;
    font-size: 11px;
    border-radius: 3px;
    background: #72aee6;
    color: white;
    margin-left: 8px;
}

.edr-priority-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 12px;
}

.edr-priority-high {
    background: #d63638;
    color: white;
}

.edr-priority-medium {
    background: #dba617;
    color: white;
}

.edr-priority-low {
    background: #72aee6;
    color: white;
}

.edr-mappings-info {
    margin-top: 20px;
    padding: 15px;
    background: #f0f0f1;
    border-radius: 4px;
}

.edr-form-container {
    background: white;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-top: 20px;
}

.edr-form-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #dcdcde;
}

.edr-help-box {
    margin-top: 30px;
    padding: 20px;
    background: #f0f6fc;
    border-left: 4px solid #72aee6;
    border-radius: 4px;
}

.edr-help-box h3 {
    margin-top: 0;
}

.edr-help-box code {
    background: #fff;
    padding: 2px 6px;
    border-radius: 3px;
}

.edr-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
}

.edr-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 30px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 8px;
    position: relative;
}

.edr-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    line-height: 20px;
    cursor: pointer;
}

.edr-modal-close:hover,
.edr-modal-close:focus {
    color: #000;
}

.required {
    color: #d63638;
}

#edr-matching-patterns {
    margin-top: 20px;
}

.edr-pattern-match {
    padding: 10px;
    margin: 10px 0;
    background: #f0f0f1;
    border-left: 4px solid #72aee6;
    border-radius: 4px;
}

.edr-pattern-winner {
    border-left-color: #00a32a;
    background: #f0f6fc;
}
</style>

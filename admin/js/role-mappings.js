/**
 * Role Mappings Admin JavaScript
 *
 * @package Email_Domain_Restriction
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Select all checkbox
        $('#edr-select-all').on('change', function () {
            $('.edr-mapping-checkbox').prop('checked', $(this).prop('checked'));
            toggleBulkDeleteButton();
        });

        // Individual checkboxes
        $('.edr-mapping-checkbox').on('change', function () {
            toggleBulkDeleteButton();
            updateSelectAllCheckbox();
        });

        // Delete single mapping
        $('.edr-delete-mapping').on('click', function (e) {
            e.preventDefault();

            if (!confirm(edrRoleMappings.confirmDelete)) {
                return;
            }

            const $button = $(this);
            const mappingId = $button.data('mapping-id');
            const $row = $button.closest('tr');

            $button.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'edr_delete_role_mapping',
                    nonce: edrRoleMappings.nonce,
                    mapping_id: mappingId
                },
                success: function (response) {
                    if (response.success) {
                        $row.fadeOut(300, function () {
                            $(this).remove();
                            checkEmptyState();
                        });
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false).text('Delete');
                    }
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false).text('Delete');
                }
            });
        });

        // Bulk delete
        $('#edr-bulk-delete-btn').on('click', function (e) {
            e.preventDefault();

            const checked = $('.edr-mapping-checkbox:checked');

            if (checked.length === 0) {
                return;
            }

            if (!confirm(edrRoleMappings.confirmBulkDelete)) {
                return;
            }

            const mappingIds = checked.map(function () {
                return $(this).val();
            }).get();

            const $button = $(this);
            $button.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'edr_bulk_delete_mappings',
                    nonce: edrRoleMappings.nonce,
                    mapping_ids: mappingIds
                },
                success: function (response) {
                    if (response.success) {
                        checked.closest('tr').fadeOut(300, function () {
                            $(this).remove();
                            checkEmptyState();
                        });
                        $button.text('Delete Selected');
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false).text('Delete Selected');
                    }
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false).text('Delete Selected');
                }
            });
        });

        // Export mappings
        $('#edr-export-btn').on('click', function (e) {
            e.preventDefault();

            const $button = $(this);
            const originalText = $button.html();

            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + edrRoleMappings.exporting);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'edr_export_mappings',
                    nonce: edrRoleMappings.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Create download link
                        const blob = new Blob([response.data.json], { type: 'application/json' });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    } else {
                        alert(response.data.message);
                    }
                    $button.prop('disabled', false).html(originalText);
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false).html(originalText);
                }
            });
        });

        // Import modal
        $('#edr-import-btn').on('click', function (e) {
            e.preventDefault();
            $('#edr-import-modal').fadeIn();
        });

        $('.edr-modal-close, .edr-modal-cancel').on('click', function () {
            $(this).closest('.edr-modal').fadeOut();
        });

        // Close modal when clicking outside
        $('.edr-modal').on('click', function (e) {
            if ($(e.target).hasClass('edr-modal')) {
                $(this).fadeOut();
            }
        });

        // Test role assignment modal
        $('#edr-test-role-btn').on('click', function (e) {
            e.preventDefault();
            $('#edr-test-modal').fadeIn();
            $('#edr-test-results').hide();
        });

        // Run test
        $('#edr-run-test').on('click', function (e) {
            e.preventDefault();

            const email = $('#test_email').val().trim();

            if (!email) {
                alert('Please enter an email address.');
                return;
            }

            const $button = $(this);
            const originalText = $button.text();

            $button.prop('disabled', true).text(edrRoleMappings.testingRole);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'edr_test_role_assignment',
                    nonce: edrRoleMappings.nonce,
                    email: email
                },
                success: function (response) {
                    if (response.success) {
                        displayTestResults(response.data);
                    } else {
                        alert(response.data.message);
                    }
                    $button.prop('disabled', false).text(originalText);
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });

        // Helper functions
        function toggleBulkDeleteButton() {
            const checkedCount = $('.edr-mapping-checkbox:checked').length;
            $('#edr-bulk-delete-btn').prop('disabled', checkedCount === 0);
        }

        function updateSelectAllCheckbox() {
            const total = $('.edr-mapping-checkbox').length;
            const checked = $('.edr-mapping-checkbox:checked').length;

            $('#edr-select-all').prop('checked', total > 0 && total === checked);
        }

        function checkEmptyState() {
            if ($('.edr-mappings-table tbody tr').length === 0) {
                location.reload();
            }
        }

        function displayTestResults(data) {
            $('#edr-result-domain').text(data.domain);
            $('#edr-result-role').html(
                data.assigned_role
                    ? '<strong>' + data.assigned_role_name + '</strong> (' + data.assigned_role + ')'
                    : '<em>' + data.assigned_role_name + '</em>'
            );

            let patternsHtml = '';

            if (data.matching_patterns.length > 0) {
                patternsHtml = '<h4>Matching Patterns:</h4>';

                data.matching_patterns.forEach(function (pattern, index) {
                    const isWinner = index === 0 && data.assigned_role === pattern.role;
                    const className = isWinner ? 'edr-pattern-match edr-pattern-winner' : 'edr-pattern-match';

                    patternsHtml += '<div class="' + className + '">';
                    patternsHtml += '<strong>' + pattern.pattern + '</strong> → ' + pattern.role_name;
                    patternsHtml += ' <span style="float:right;">Priority: ' + pattern.priority + '</span>';

                    if (isWinner) {
                        patternsHtml += ' <span style="color:#00a32a; margin-left:10px;">✓ Selected</span>';
                    }

                    patternsHtml += '</div>';
                });
            } else {
                patternsHtml = '<p><em>No matching patterns found.</em></p>';
            }

            $('#edr-matching-patterns').html(patternsHtml);
            $('#edr-test-results').fadeIn();
        }
    });
})(jQuery);

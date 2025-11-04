/**
 * Admin JavaScript for Email Domain Restriction plugin.
 *
 * @package Email_Domain_Restriction
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Confirm domain removal
        $('.edr-domains-section form[name*="remove"]').on('submit', function(e) {
            if (!confirm(edrAdmin.confirmDelete)) {
                e.preventDefault();
                return false;
            }
        });

        // Confirm log clearing
        $('form button[name="edr_clear_logs"]').on('click', function(e) {
            if (!confirm(edrAdmin.confirmClearLogs)) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-submit filter form on per_page change
        $('select[name="per_page"]').on('change', function() {
            $(this).closest('form').submit();
        });

        // Trim whitespace from domain input
        $('input[name="domain"]').on('blur', function() {
            $(this).val($.trim($(this).val()).toLowerCase());
        });

        // File upload validation
        $('input[type="file"][name="csv_file"]').on('change', function() {
            var file = this.files[0];
            if (file) {
                // Check file size (1MB max)
                if (file.size > 1048576) {
                    alert('File size exceeds 1MB limit.');
                    $(this).val('');
                    return false;
                }

                // Check file extension
                var ext = file.name.split('.').pop().toLowerCase();
                if (ext !== 'csv' && ext !== 'txt') {
                    alert('Please select a CSV file.');
                    $(this).val('');
                    return false;
                }
            }
        });
    });

})(jQuery);

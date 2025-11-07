/**
 * Advanced Analytics JavaScript
 *
 * @package Email_Domain_Restriction
 */

(function ($) {
    'use strict';

    let charts = {};

    $(document).ready(function () {
        // Initialize charts
        loadAllCharts();

        // Date range change
        $('#edr-date-range').on('change', function () {
            const days = $(this).val();
            window.location.href = updateQueryString('days', days);
        });

        // Refresh data
        $('#edr-refresh-data').on('click', function () {
            location.reload();
        });

        // Export CSV
        $('#edr-export-csv-btn').on('click', function () {
            $('#edr-export-csv-modal').fadeIn();
        });

        $('#edr-export-csv-form').on('submit', function (e) {
            e.preventDefault();
            exportCSV();
        });

        // Export PDF
        $('#edr-export-pdf-btn').on('click', function (e) {
            e.preventDefault();
            exportPDF();
        });

        // Settings modal
        $('#edr-settings-btn').on('click', function () {
            $('#edr-settings-modal').fadeIn();
            toggleReportEmailField();
        });

        $('#edr_scheduled_frequency').on('change', toggleReportEmailField);

        $('#edr-analytics-settings-form').on('submit', function (e) {
            e.preventDefault();
            saveSettings();
        });

        // Modal controls
        $('.edr-modal-close, .edr-modal-cancel').on('click', function () {
            $(this).closest('.edr-modal').fadeOut();
        });

        $('.edr-modal').on('click', function (e) {
            if ($(e.target).hasClass('edr-modal')) {
                $(this).fadeOut();
            }
        });
    });

    /**
     * Load all charts
     */
    function loadAllCharts() {
        const days = $('#edr-date-range').val();

        loadTimeSeriesChart(days);
        loadSourceChart(days);
        loadDomainsChart(days);
        loadGeographicChart(days);
    }

    /**
     * Load time series chart
     */
    function loadTimeSeriesChart(days) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'edr_get_chart_data',
                nonce: edrAnalytics.nonce,
                chart_type: 'time_series',
                days: days,
                interval: 'day'
            },
            success: function (response) {
                if (response.success && response.data.data.length > 0) {
                    renderTimeSeriesChart(response.data.data);
                }
            }
        });
    }

    /**
     * Render time series chart
     */
    function renderTimeSeriesChart(data) {
        const ctx = document.getElementById('edr-time-series-chart');

        if (charts.timeSeries) {
            charts.timeSeries.destroy();
        }

        const labels = data.map(item => item.period);
        const allowed = data.map(item => parseInt(item.allowed));
        const blocked = data.map(item => parseInt(item.blocked));

        charts.timeSeries = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Allowed',
                        data: allowed,
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Blocked',
                        data: blocked,
                        borderColor: '#d63638',
                        backgroundColor: 'rgba(214, 54, 56, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Load source chart
     */
    function loadSourceChart(days) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'edr_get_chart_data',
                nonce: edrAnalytics.nonce,
                chart_type: 'by_source',
                days: days
            },
            success: function (response) {
                if (response.success && response.data.data.length > 0) {
                    renderSourceChart(response.data.data);
                }
            }
        });
    }

    /**
     * Render source chart
     */
    function renderSourceChart(data) {
        const ctx = document.getElementById('edr-source-chart');

        if (charts.source) {
            charts.source.destroy();
        }

        const labels = data.map(item => item.source);
        const counts = data.map(item => parseInt(item.count));

        const colors = [
            '#0073aa',
            '#00a32a',
            '#d63638',
            '#dba617',
            '#826eb4',
            '#f56e28',
        ];

        charts.source = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    }

    /**
     * Load domains chart
     */
    function loadDomainsChart(days) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'edr_get_chart_data',
                nonce: edrAnalytics.nonce,
                chart_type: 'top_domains',
                days: days
            },
            success: function (response) {
                if (response.success && response.data.data.length > 0) {
                    renderDomainsChart(response.data.data);
                }
            }
        });
    }

    /**
     * Render domains chart
     */
    function renderDomainsChart(data) {
        const ctx = document.getElementById('edr-domains-chart');

        if (charts.domains) {
            charts.domains.destroy();
        }

        const labels = data.map(item => item.domain);
        const allowed = data.map(item => parseInt(item.allowed));
        const blocked = data.map(item => parseInt(item.blocked));

        charts.domains = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Allowed',
                        data: allowed,
                        backgroundColor: '#00a32a',
                    },
                    {
                        label: 'Blocked',
                        data: blocked,
                        backgroundColor: '#d63638',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        stacked: true
                    },
                    y: {
                        stacked: true
                    }
                }
            }
        });
    }

    /**
     * Load geographic chart
     */
    function loadGeographicChart(days) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'edr_get_chart_data',
                nonce: edrAnalytics.nonce,
                chart_type: 'geographic',
                days: days
            },
            success: function (response) {
                if (response.success && response.data.data.length > 0) {
                    renderGeographicChart(response.data.data);
                }
            }
        });
    }

    /**
     * Render geographic chart
     */
    function renderGeographicChart(data) {
        const ctx = document.getElementById('edr-geographic-chart');

        if (charts.geographic) {
            charts.geographic.destroy();
        }

        const labels = data.map(item => item.country_code || 'Unknown');
        const counts = data.map(item => parseInt(item.count));

        charts.geographic = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Attempts',
                    data: counts,
                    backgroundColor: '#0073aa',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Export CSV
     */
    function exportCSV() {
        const $button = $('#edr-export-csv-form button[type="submit"]');
        const originalText = $button.text();

        $button.prop('disabled', true).text(edrAnalytics.exportingCSV);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'edr_export_csv',
                nonce: edrAnalytics.nonce,
                start_date: $('#csv_start_date').val(),
                end_date: $('#csv_end_date').val(),
                source: $('#csv_source').val(),
                status: $('#csv_status').val()
            },
            success: function (response) {
                if (response.success) {
                    // Create download
                    const blob = new Blob([response.data.csv], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = response.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);

                    $('#edr-export-csv-modal').fadeOut();
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
    }

    /**
     * Export PDF
     */
    function exportPDF() {
        const $button = $('#edr-export-pdf-btn');
        const originalHtml = $button.html();

        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + edrAnalytics.exportingPDF);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'edr_export_pdf',
                nonce: edrAnalytics.nonce,
                days: $('#edr-date-range').val()
            },
            success: function (response) {
                if (response.success) {
                    window.open(response.data.url, '_blank');
                } else {
                    alert(response.data.message);
                }
                $button.prop('disabled', false).html(originalHtml);
            },
            error: function () {
                alert('An error occurred. Please try again.');
                $button.prop('disabled', false).html(originalHtml);
            }
        });
    }

    /**
     * Save settings
     */
    function saveSettings() {
        const $button = $('#edr-analytics-settings-form button[type="submit"]');
        const originalText = $button.text();

        $button.prop('disabled', true).text('Saving...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'edr_schedule_report',
                nonce: edrAnalytics.nonce,
                frequency: $('#edr_scheduled_frequency').val(),
                email: $('#edr_scheduled_email').val()
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#edr-settings-modal').fadeOut();
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
    }

    /**
     * Toggle report email field
     */
    function toggleReportEmailField() {
        const frequency = $('#edr_scheduled_frequency').val();
        if (frequency === 'never') {
            $('#edr-report-email-row').hide();
        } else {
            $('#edr-report-email-row').show();
        }
    }

    /**
     * Update query string
     */
    function updateQueryString(key, value) {
        const url = new URL(window.location.href);
        url.searchParams.set(key, value);
        return url.toString();
    }
})(jQuery);

/**
 * Dashboard JavaScript for Email Domain Restriction plugin.
 *
 * @package Email_Domain_Restriction
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        if (typeof Chart === 'undefined' || typeof edrChartData === 'undefined') {
            console.error('Chart.js or chart data not loaded');
            return;
        }

        // Chart.js default config
        Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
        Chart.defaults.color = '#2c3338';

        // Initialize all charts
        initAttemptsOverTimeChart();
        initPieChart();
        initTopDomainsChart();
        initWeekdayChart();
        initHourChart();
    });

    /**
     * Initialize attempts over time line chart.
     */
    function initAttemptsOverTimeChart() {
        var ctx = document.getElementById('edrAttemptsChart');
        if (!ctx) return;

        var data = edrChartData.attemptsOverTime;
        var labels = data.map(function(item) { return item.date; });
        var allowedData = data.map(function(item) { return parseInt(item.allowed); });
        var blockedData = data.map(function(item) { return parseInt(item.blocked); });

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Allowed',
                        data: allowedData,
                        borderColor: '#46b450',
                        backgroundColor: 'rgba(70, 180, 80, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Blocked',
                        data: blockedData,
                        borderColor: '#dc3232',
                        backgroundColor: 'rgba(220, 50, 50, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize allowed vs blocked pie chart.
     */
    function initPieChart() {
        var ctx = document.getElementById('edrPieChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Allowed', 'Blocked'],
                datasets: [{
                    data: [edrChartData.allowedCount, edrChartData.blockedCount],
                    backgroundColor: ['#46b450', '#dc3232'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                }
            }
        });
    }

    /**
     * Initialize top domains bar chart.
     */
    function initTopDomainsChart() {
        var ctx = document.getElementById('edrTopDomainsChart');
        if (!ctx) return;

        // Combine allowed and blocked domains
        var allowedDomains = edrChartData.topDomainsAllowed || [];
        var blockedDomains = edrChartData.topDomainsBlocked || [];

        // Create a map of all domains
        var domainMap = {};
        allowedDomains.forEach(function(item) {
            if (!domainMap[item.domain]) {
                domainMap[item.domain] = { allowed: 0, blocked: 0 };
            }
            domainMap[item.domain].allowed = parseInt(item.count);
        });
        blockedDomains.forEach(function(item) {
            if (!domainMap[item.domain]) {
                domainMap[item.domain] = { allowed: 0, blocked: 0 };
            }
            domainMap[item.domain].blocked = parseInt(item.count);
        });

        // Convert to arrays and sort by total
        var domains = Object.keys(domainMap).map(function(domain) {
            return {
                domain: domain,
                allowed: domainMap[domain].allowed,
                blocked: domainMap[domain].blocked,
                total: domainMap[domain].allowed + domainMap[domain].blocked
            };
        });
        domains.sort(function(a, b) { return b.total - a.total; });
        domains = domains.slice(0, 10);

        var labels = domains.map(function(item) { return item.domain; });
        var allowedData = domains.map(function(item) { return item.allowed; });
        var blockedData = domains.map(function(item) { return item.blocked; });

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Allowed',
                        data: allowedData,
                        backgroundColor: '#46b450'
                    },
                    {
                        label: 'Blocked',
                        data: blockedData,
                        backgroundColor: '#dc3232'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize weekday bar chart.
     */
    function initWeekdayChart() {
        var ctx = document.getElementById('edrWeekdayChart');
        if (!ctx) return;

        var data = edrChartData.attemptsByWeekday;
        var labels = Object.keys(data);
        var values = Object.values(data);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Attempts',
                    data: values,
                    backgroundColor: '#0073aa'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize hour bar chart.
     */
    function initHourChart() {
        var ctx = document.getElementById('edrHourChart');
        if (!ctx) return;

        var data = edrChartData.attemptsByHour;
        var labels = [];
        for (var i = 0; i < 24; i++) {
            labels.push(i + ':00');
        }

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Attempts',
                    data: data,
                    backgroundColor: '#00a0d2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

})(jQuery);

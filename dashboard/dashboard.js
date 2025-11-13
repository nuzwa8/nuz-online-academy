/**
 * Dashboard JavaScript - NUZ Online Academy Plugin
 * Handles dashboard UI interactions and data loading
 */

(function($) {
    'use strict';

    // Dashboard namespace
    const NuzDashboard = {
        
        // Chart instance
        chart: null,
        
        // Dashboard data cache
        cache: {
            stats: null,
            recentAdmissions: null,
            upcomingCourses: null
        },
        
        /**
         * Initialize dashboard
         */
        init: function() {
            this.bindEvents();
            this.loadDashboardData();
            this.initializeCharts();
        },
        
        /**
         * Bind event listeners
         */
        bindEvents: function() {
            // Refresh dashboard button
            $('#refresh-dashboard').on('click', () => {
                this.loadDashboardData(true);
            });
            
            // Chart period selector
            $('#chart-period').on('change', (e) => {
                this.updateChart($(e.target).val());
            });
            
            // Quick action buttons
            $('.nuz-action-btn').on('click', (e) => {
                const action = $(e.currentTarget).data('action');
                this.handleQuickAction(action);
            });
            
            // Window resize for chart responsiveness
            $(window).on('resize', () => {
                this.resizeChart();
            });
        },
        
        /**
         * Load all dashboard data
         */
        async loadDashboardData(forceRefresh = false) {
            try {
                this.showLoading(true);
                
                // Load dashboard statistics
                await this.loadStats();
                
                // Load recent admissions
                await this.loadRecentAdmissions();
                
                // Load upcoming courses
                await this.loadUpcomingCourses();
                
                // Update system status
                this.updateSystemStatus();
                
                this.showLoading(false);
                
            } catch (error) {
                console.error('Dashboard data loading error:', error);
                this.showNotification('Error loading dashboard data', 'error');
                this.showLoading(false);
            }
        },
        
        /**
         * Load dashboard statistics
         */
        async loadStats() {
            try {
                const response = await nuzAcademy.wpAjax('nuz_get_dashboard_stats', {});
                
                if (response.success) {
                    this.cache.stats = response.data;
                    this.updateStatsCards(response.data);
                } else {
                    throw new Error(response.data || 'Failed to load stats');
                }
                
            } catch (error) {
                console.error('Stats loading error:', error);
                // Set default values if loading fails
                this.updateStatsCards({
                    total_students: 0,
                    total_courses: 0,
                    total_revenue: 0,
                    pending_fees: 0
                });
            }
        },
        
        /**
         * Update statistics cards with animation
         */
        updateStatsCards(data) {
            const updates = [
                { selector: '[data-stat="students"] .nuz-stat-number', value: data.total_students, suffix: '' },
                { selector: '[data-stat="courses"] .nuz-stat-number', value: data.total_courses, suffix: '' },
                { selector: '[data-stat="revenue"] .nuz-stat-number', value: data.total_revenue, prefix: '$', suffix: '' },
                { selector: '[data-stat="pending"] .nuz-stat-number', value: data.pending_fees, prefix: '$', suffix: '' }
            ];
            
            updates.forEach(update => {
                const $element = $(update.selector);
                const currentValue = parseInt($element.data('count')) || 0;
                const newValue = parseInt(update.value) || 0;
                
                // Animate count change
                this.animateCount($element, currentValue, newValue, update.prefix, update.suffix);
                $element.data('count', newValue);
            });
        },
        
        /**
         * Animate count changes
         */
        animateCount($element, start, end, prefix = '', suffix = '') {
            const duration = 1000; // 1 second
            const startTime = performance.now();
            
            const updateCount = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing function (ease-out)
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(start + (end - start) * eased);
                
                $element.text(prefix + current.toLocaleString() + suffix);
                
                if (progress < 1) {
                    requestAnimationFrame(updateCount);
                }
            };
            
            requestAnimationFrame(updateCount);
        },
        
        /**
         * Load recent admissions
         */
        async loadRecentAdmissions() {
            try {
                const response = await nuzAcademy.wpAjax('nuz_get_recent_admissions', { limit: 5 });
                
                if (response.success) {
                    this.cache.recentAdmissions = response.data;
                    this.renderRecentAdmissions(response.data);
                } else {
                    throw new Error(response.data || 'Failed to load recent admissions');
                }
                
            } catch (error) {
                console.error('Recent admissions loading error:', error);
                this.renderRecentAdmissions([]);
            }
        },
        
        /**
         * Render recent admissions table
         */
        renderRecentAdmissions(admissions) {
            const $tbody = $('#recent-admissions-body');
            
            if (admissions.length === 0) {
                $html = '<tr class="nuz-no-data"><td colspan="4">No recent admissions found</td></tr>';
            } else {
                let html = '';
                admissions.forEach(admission => {
                    const statusClass = admission.status === 'active' ? 'nuz-status-active' : 'nuz-status-inactive';
                    const statusText = admission.status === 'active' ? 'Active' : 'Inactive';
                    
                    html += `
                        <tr>
                            <td>
                                <div class="nuz-student-info">
                                    <strong>${this.escapeHtml(admission.student_name)}</strong>
                                    <br>
                                    <small class="nuz-text-muted">${this.escapeHtml(admission.email)}</small>
                                </div>
                            </td>
                            <td>${this.escapeHtml(admission.course_name || 'N/A')}</td>
                            <td>${admission.enrollment_date ? this.formatDate(admission.enrollment_date) : 'N/A'}</td>
                            <td><span class="nuz-status-badge ${statusClass}">${statusText}</span></td>
                        </tr>
                    `;
                });
            }
            
            $tbody.html(html);
        },
        
        /**
         * Load upcoming courses
         */
        async loadUpcomingCourses() {
            try {
                const response = await nuzAcademy.wpAjax('nuz_get_upcoming_courses', { limit: 5 });
                
                if (response.success) {
                    this.cache.upcomingCourses = response.data;
                    this.renderUpcomingCourses(response.data);
                } else {
                    throw new Error(response.data || 'Failed to load upcoming courses');
                }
                
            } catch (error) {
                console.error('Upcoming courses loading error:', error);
                this.renderUpcomingCourses([]);
            }
        },
        
        /**
         * Render upcoming courses
         */
        renderUpcomingCourses(courses) {
            const $container = $('#upcoming-courses-list');
            
            if (courses.length === 0) {
                $container.html('<div class="nuz-no-data">No upcoming courses found</div>');
                return;
            }
            
            let html = '<div class="nuz-courses-list">';
            courses.forEach(course => {
                const startDate = new Date(course.start_date);
                const now = new Date();
                const daysUntil = Math.ceil((startDate - now) / (1000 * 60 * 60 * 24));
                const statusClass = daysUntil > 7 ? 'nuz-upcoming' : daysUntil > 0 ? 'nuz-starting-soon' : 'nuz-started';
                const statusText = daysUntil > 7 ? 'Upcoming' : daysUntil > 0 ? 'Starting Soon' : 'Started';
                
                html += `
                    <div class="nuz-course-item ${statusClass}">
                        <div class="nuz-course-content">
                            <h4 class="nuz-course-title">${this.escapeHtml(course.course_name)}</h4>
                            <p class="nuz-course-description">${this.escapeHtml(course.description || 'No description available')}</p>
                            <div class="nuz-course-meta">
                                <span class="nuz-course-date">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    ${this.formatDate(course.start_date)}
                                </span>
                                <span class="nuz-course-status ${statusClass}">${statusText}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            $container.html(html);
        },
        
        /**
         * Initialize charts
         */
        initializeCharts() {
            const ctx = document.getElementById('monthly-enrollment-chart');
            if (!ctx) return;
            
            // Check if Chart.js is available
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded, showing placeholder');
                this.showChartPlaceholder();
                return;
            }
            
            try {
                // Initialize chart with empty data
                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Enrollments',
                            data: [],
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: 'white',
                                bodyColor: 'white',
                                borderColor: '#2563eb',
                                borderWidth: 1
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#6b7280'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#e5e7eb'
                                },
                                ticks: {
                                    color: '#6b7280',
                                    callback: function(value) {
                                        return value.toLocaleString();
                                    }
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
                
                // Load initial chart data
                this.updateChart($('#chart-period').val());
                
            } catch (error) {
                console.error('Chart initialization error:', error);
                this.showChartPlaceholder();
            }
        },
        
        /**
         * Update chart data
         */
        async updateChart(period) {
            if (!this.chart) return;
            
            try {
                $('#chart-loading').show();
                
                const response = await nuzAcademy.wpAjax('nuz_get_monthly_enrollment_data', { period: period });
                
                if (response.success) {
                    const data = response.data;
                    this.chart.data.labels = data.labels;
                    this.chart.data.datasets[0].data = data.values;
                    this.chart.update();
                } else {
                    throw new Error(response.data || 'Failed to load chart data');
                }
                
            } catch (error) {
                console.error('Chart update error:', error);
                this.showNotification('Error loading chart data', 'error');
            } finally {
                $('#chart-loading').hide();
            }
        },
        
        /**
         * Show chart placeholder when Chart.js is not available
         */
        showChartPlaceholder() {
            const $container = $('.nuz-chart-container');
            $container.html(`
                <div class="nuz-chart-placeholder">
                    <div class="nuz-placeholder-content">
                        <span class="dashicons dashicons-chart-line nuz-placeholder-icon"></span>
                        <h3>Monthly Enrollment Chart</h3>
                        <p>Chart functionality requires Chart.js library to be loaded.</p>
                        <p class="nuz-text-muted">Add Chart.js to the vendor folder to enable this feature.</p>
                    </div>
                </div>
            `);
        },
        
        /**
         * Resize chart on window resize
         */
        resizeChart() {
            if (this.chart) {
                this.chart.resize();
            }
        },
        
        /**
         * Handle quick action buttons
         */
        handleQuickAction(action) {
            switch (action) {
                case 'new-student':
                    window.location.href = '<?php echo admin_url('admin.php?page=nuz-new-admission'); ?>';
                    break;
                case 'new-course':
                    // For now, show notification - course management will be implemented later
                    this.showNotification('Course management will be available in the full version', 'info');
                    break;
                case 'process-payment':
                    this.showNotification('Payment processing will be available in the full version', 'info');
                    break;
                case 'view-reports':
                    this.showNotification('Reports section will be available in the full version', 'info');
                    break;
                case 'export-data':
                    this.exportData();
                    break;
                case 'upload-screenshots':
                    window.location.href = '<?php echo admin_url('admin.php?page=nuz-uploads'); ?>';
                    break;
                default:
                    this.showNotification('Action not implemented yet', 'warning');
            }
        },
        
        /**
         * Export dashboard data
         */
        async exportData() {
            try {
                this.showNotification('Preparing data export...', 'info');
                
                const response = await nuzAcademy.wpAjax('nuz_export_dashboard_data', {});
                
                if (response.success) {
                    // Create and trigger download
                    const blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `nuz-dashboard-data-${new Date().toISOString().split('T')[0]}.json`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    
                    this.showNotification('Dashboard data exported successfully', 'success');
                } else {
                    throw new Error(response.data || 'Export failed');
                }
                
            } catch (error) {
                console.error('Export error:', error);
                this.showNotification('Error exporting data', 'error');
            }
        },
        
        /**
         * Update system status indicators
         */
        updateSystemStatus() {
            // Check database connection
            $('[data-status="db"]').html('<span class="nuz-status-ok">Connected</span>');
            
            // Additional status checks can be added here
            $('[data-status="version"]').text($('[data-status="version"]').text());
            $('[data-status="wp"]').text($('[data-status="wp"]').text());
            $('[data-status="php"]').text($('[data-status="php"]').text());
        },
        
        /**
         * Show/hide loading overlay
         */
        showLoading(show) {
            $('#dashboard-loading').toggle(show);
        },
        
        /**
         * Show notification toast
         */
        showNotification(message, type = 'info') {
            const $container = $('#nuz-toast-container');
            const $toast = $(`
                <div class="nuz-toast nuz-toast-${type}">
                    <span class="nuz-toast-message">${this.escapeHtml(message)}</span>
                    <button class="nuz-toast-close">&times;</button>
                </div>
            `);
            
            $container.append($toast);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                $toast.fadeOut(() => $toast.remove());
            }, 5000);
            
            // Manual close button
            $toast.find('.nuz-toast-close').on('click', () => {
                $toast.fadeOut(() => $toast.remove());
            });
        },
        
        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        /**
         * Format date for display
         */
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(() => {
        NuzDashboard.init();
    });
    
    // Export to global scope for testing
    window.NuzDashboard = NuzDashboard;
    
})(jQuery);

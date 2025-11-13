/**
 * NUZ Online Academy - Main JavaScript
 * 
 * Handles UI interactions, AJAX calls, and dynamic content
 * @package NuzOnlineAcademy
 * @version 1.0.0
 */

// Main application namespace
const NUZAcademy = {
    
    // Application state
    state: {
        currentPage: 'dashboard',
        isLoading: false,
        theme: 'light',
        settings: {}
    },
    
    // Initialize the application
    init() {
        this.bindEvents();
        this.loadInitialData();
        this.initComponents();
        this.setupTheme();
        console.log('NUZ Academy initialized');
    },
    
    // Bind event listeners
    bindEvents() {
        // Navigation
        jQuery(document).on('click', '.nuz-nav-item', this.handleNavigation);
        
        // Forms
        jQuery(document).on('submit', '.nuz-form', this.handleFormSubmit);
        jQuery(document).on('click', '.nuz-btn-submit', this.handleFormSubmit);
        
        // Search and filters
        jQuery(document).on('input', '.nuz-search', this.debounce(this.handleSearch, 500));
        jQuery(document).on('change', '.nuz-filter', this.handleFilterChange);
        
        // Modal and dialog
        jQuery(document).on('click', '.nuz-modal-trigger', this.openModal);
        jQuery(document).on('click', '.nuz-modal-close', this.closeModal);
        jQuery(document).on('click', '.nuz-modal-backdrop', this.closeModal);
        
        // File upload
        jQuery(document).on('change', '.nuz-file-upload', this.handleFileUpload);
        jQuery(document).on('dragover', '.nuz-dropzone', this.handleDragOver);
        jQuery(document).on('drop', '.nuz-dropzone', this.handleFileDrop);
        
        // Table actions
        jQuery(document).on('click', '.nuz-edit-btn', this.handleEdit);
        jQuery(document).on('click', '.nuz-delete-btn', this.handleDelete);
        jQuery(document).on('click', '.nuz-view-btn', this.handleView);
        
        // Theme toggle
        jQuery(document).on('click', '.nuz-theme-toggle', this.toggleTheme);
        
        // Print and export
        jQuery(document).on('click', '.nuz-print-btn', this.handlePrint);
        jQuery(document).on('click', '.nuz-export-btn', this.handleExport);
        jQuery(document).on('click', '.nuz-import-btn', this.handleImport);
        
        // Settings
        jQuery(document).on('click', '.nuz-save-settings', this.saveSettings);
    },
    
    // Initialize UI components
    initComponents() {
        this.initTables();
        this.initCharts();
        this.initDatePickers();
        this.initSelect2();
        this.initTooltips();
        this.initNotifications();
    },
    
    // Load initial data for current page
    loadInitialData() {
        const currentPage = this.getCurrentPage();
        
        switch (currentPage) {
            case 'dashboard':
                this.loadDashboardData();
                break;
            case 'students':
                this.loadStudentsData();
                break;
            case 'courses':
                this.loadCoursesData();
                break;
            case 'fees':
                this.loadFeesData();
                break;
            case 'settings':
                this.loadSettingsData();
                break;
        }
    },
    
    // Handle navigation
    handleNavigation(e) {
        e.preventDefault();
        const target = jQuery(e.currentTarget);
        const page = target.data('page');
        
        if (page) {
            NUZAcademy.state.currentPage = page;
            NUZAcademy.navigateToPage(page);
        }
    },
    
    // Navigate to page
    navigateToPage(page) {
        const pageMap = {
            'dashboard': 'dashboard',
            'courses': 'courses',
            'students': 'students',
            'fees': 'fees',
            'new-admission': 'new-admission',
            'uploads': 'uploads',
            'settings': 'settings'
        };
        
        const targetPage = pageMap[page] || 'dashboard';
        const url = `admin.php?page=nuz-${page}`;
        
        // Add loading state
        this.showLoading();
        
        // Update active nav
        jQuery('.nuz-nav-item').removeClass('active');
        jQuery(`[data-page="${page}"]`).addClass('active');
        
        // Load page content
        this.loadPageContent(targetPage);
    },
    
    // Load page content
    loadPageContent(page) {
        this.showLoading();
        
        // Clear existing content
        jQuery('.nuz-page-content').html('');
        
        // Load appropriate template
        switch (page) {
            case 'dashboard':
                this.loadDashboardTemplate();
                break;
            case 'students':
                this.loadStudentsTemplate();
                break;
            case 'courses':
                this.loadCoursesTemplate();
                break;
            case 'fees':
                this.loadFeesTemplate();
                break;
            case 'new-admission':
                this.loadNewAdmissionTemplate();
                break;
            case 'uploads':
                this.loadUploadsTemplate();
                break;
            case 'settings':
                this.loadSettingsTemplate();
                break;
        }
        
        this.hideLoading();
    },
    
    // Load dashboard data
    loadDashboardData() {
        this.ajaxCall('nuz_get_dashboard_stats', {}, (response) => {
            if (response.success) {
                this.updateDashboardStats(response.data);
                this.updateDashboardCharts(response.data);
                this.updateRecentActivity(response.data);
            }
        });
    },
    
    // Load students data
    loadStudentsData() {
        this.ajaxCall('nuz_get_students', {
            page: 1,
            per_page: 20,
            search: '',
            course_filter: 0,
            status_filter: ''
        }, (response) => {
            if (response.success) {
                this.renderStudentsTable(response.data);
            }
        });
    },
    
    // Load courses data
    loadCoursesData() {
        this.ajaxCall('nuz_get_courses', {}, (response) => {
            if (response.success) {
                this.renderCoursesTable(response.data);
            }
        });
    },
    
    // Load fees data
    loadFeesData() {
        this.ajaxCall('nuz_get_payments', {}, (response) => {
            if (response.success) {
                this.renderPaymentsTable(response.data);
            }
        });
    },
    
    // Load settings data
    loadSettingsData() {
        this.ajaxCall('nuz_get_settings', {}, (response) => {
            if (response.success) {
                this.renderSettingsForm(response.data);
            }
        });
    },
    
    // Handle form submission
    handleFormSubmit(e) {
        e.preventDefault();
        const form = jQuery(e.currentTarget);
        const formData = new FormData(form[0]);
        const action = form.data('action');
        
        if (!action) {
            this.showNotification('Form action not specified', 'error');
            return;
        }
        
        // Add nonce and action
        formData.append('action', action);
        formData.append('nonce', nuz_ajax.nonce);
        
        // Show loading state
        this.showLoading(form.find('button[type="submit"], .nuz-btn-submit'));
        
        jQuery.ajax({
            url: nuz_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.success) {
                    this.showNotification(response.data.message || 'Success!', 'success');
                    this.resetForm(form);
                    this.refreshCurrentPage();
                } else {
                    this.showNotification(response.data || 'Error occurred', 'error');
                }
            },
            error: () => {
                this.showNotification('Network error', 'error');
            },
            complete: () => {
                this.hideLoading(form.find('button[type="submit"], .nuz-btn-submit'));
            }
        });
    },
    
    // Handle search
    handleSearch(e) {
        const searchTerm = jQuery(e.target).val();
        const currentPage = this.state.currentPage;
        
        // Debounce search
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        this.searchTimeout = setTimeout(() => {
            this.performSearch(currentPage, searchTerm);
        }, 500);
    },
    
    // Perform search
    performSearch(page, searchTerm) {
        switch (page) {
            case 'students':
                this.ajaxCall('nuz_search_students', { search: searchTerm }, (response) => {
                    if (response.success) {
                        this.renderStudentsTable(response.data);
                    }
                });
                break;
            case 'courses':
                this.ajaxCall('nuz_search_courses', { search: searchTerm }, (response) => {
                    if (response.success) {
                        this.renderCoursesTable(response.data);
                    }
                });
                break;
            case 'fees':
                this.ajaxCall('nuz_search_payments', { search: searchTerm }, (response) => {
                    if (response.success) {
                        this.renderPaymentsTable(response.data);
                    }
                });
                break;
        }
    },
    
    // Handle filter change
    handleFilterChange(e) {
        const filter = jQuery(e.target);
        const filterType = filter.data('filter-type');
        const filterValue = filter.val();
        
        this.applyFilter(filterType, filterValue);
    },
    
    // Apply filters
    applyFilter(filterType, filterValue) {
        const currentPage = this.state.currentPage;
        
        switch (currentPage) {
            case 'students':
                this.ajaxCall('nuz_filter_students', {
                    course_id: filterType === 'course' ? filterValue : 0,
                    status: filterType === 'status' ? filterValue : ''
                }, (response) => {
                    if (response.success) {
                        this.renderStudentsTable(response.data);
                    }
                });
                break;
            case 'fees':
                this.ajaxCall('nuz_filter_payments', {
                    status: filterType === 'status' ? filterValue : '',
                    date_from: filterType === 'date' ? filterValue : ''
                }, (response) => {
                    if (response.success) {
                        this.renderPaymentsTable(response.data);
                    }
                });
                break;
        }
    },
    
    // Handle file upload
    handleFileUpload(e) {
        const input = e.target;
        const files = input.files;
        
        if (files.length > 0) {
            this.uploadFiles(files);
        }
    },
    
    // Handle file drop
    handleFileDrop(e) {
        e.preventDefault();
        const files = e.originalEvent.dataTransfer.files;
        this.uploadFiles(files);
    },
    
    // Upload files
    uploadFiles(files) {
        Array.from(files).forEach(file => {
            this.uploadSingleFile(file);
        });
    },
    
    // Upload single file
    uploadSingleFile(file) {
        const formData = new FormData();
        formData.append('action', 'nuz_upload_file');
        formData.append('nonce', nuz_ajax.nonce);
        formData.append('file', file);
        
        // Show upload progress
        const progressContainer = jQuery('<div class="nuz-upload-progress"></div>');
        progressContainer.html(`
            <div class="nuz-upload-info">
                <span class="nuz-file-name">${file.name}</span>
                <span class="nuz-file-size">${this.formatFileSize(file.size)}</span>
            </div>
            <div class="nuz-progress-bar">
                <div class="nuz-progress-fill"></div>
            </div>
        `);
        
        jQuery('.nuz-upload-area').append(progressContainer);
        
        jQuery.ajax({
            url: nuz_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: () => {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        progressContainer.find('.nuz-progress-fill').css('width', percentComplete + '%');
                    }
                });
                return xhr;
            },
            success: (response) => {
                if (response.success) {
                    this.showNotification('File uploaded successfully!', 'success');
                    progressContainer.addClass('nuz-upload-success');
                    this.refreshCurrentPage();
                } else {
                    this.showNotification(response.data || 'Upload failed', 'error');
                    progressContainer.addClass('nuz-upload-error');
                }
            },
            error: () => {
                this.showNotification('Upload error', 'error');
                progressContainer.addClass('nuz-upload-error');
            }
        });
    },
    
    // Handle delete action
    handleDelete(e) {
        e.preventDefault();
        const target = jQuery(e.currentTarget);
        const action = target.data('action');
        const id = target.data('id');
        
        if (confirm(nuz_ajax.strings.confirm_delete)) {
            this.ajaxCall(action, { id: id }, (response) => {
                if (response.success) {
                    this.showNotification('Deleted successfully!', 'success');
                    this.refreshCurrentPage();
                } else {
                    this.showNotification(response.data || 'Delete failed', 'error');
                }
            });
        }
    },
    
    // Handle edit action
    handleEdit(e) {
        e.preventDefault();
        const target = jQuery(e.currentTarget);
        const action = target.data('action');
        const id = target.data('id');
        
        this.ajaxCall(action, { id: id }, (response) => {
            if (response.success) {
                this.openEditModal(response.data);
            }
        });
    },
    
    // Handle export
    handleExport(e) {
        e.preventDefault();
        const target = jQuery(e.currentTarget);
        const exportType = target.data('export-type');
        
        this.ajaxCall(`nuz_export_${exportType}`, {}, (response) => {
            if (response.success) {
                this.downloadCSV(response.data.csv_content, response.data.filename);
                this.showNotification('Data exported successfully!', 'success');
            }
        });
    },
    
    // Handle import
    handleImport(e) {
        e.preventDefault();
        const fileInput = jQuery('<input type="file" accept=".csv" />');
        
        fileInput.change((e) => {
            const file = e.target.files[0];
            if (file) {
                this.importCSV(file);
            }
        });
        
        fileInput.click();
    },
    
    // Import CSV file
    importCSV(file) {
        const formData = new FormData();
        formData.append('action', 'nuz_import_data');
        formData.append('nonce', nuz_ajax.nonce);
        formData.append('file', file);
        
        jQuery.ajax({
            url: nuz_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.success) {
                    this.showNotification('Data imported successfully!', 'success');
                    this.refreshCurrentPage();
                } else {
                    this.showNotification(response.data || 'Import failed', 'error');
                }
            }
        });
    },
    
    // Handle print
    handlePrint(e) {
        e.preventDefault();
        const currentPage = this.state.currentPage;
        
        // Generate print content
        let printContent = this.generatePrintContent(currentPage);
        
        // Create print window
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>NUZ Academy - ${currentPage.charAt(0).toUpperCase() + currentPage.slice(1)} Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .header { text-align: center; margin-bottom: 20px; }
                    </style>
                </head>
                <body>
                    ${printContent}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    },
    
    // Generate print content
    generatePrintContent(page) {
        switch (page) {
            case 'students':
                return jQuery('.nuz-students-table').html() || '<p>No data to print</p>';
            case 'courses':
                return jQuery('.nuz-courses-table').html() || '<p>No data to print</p>';
            case 'fees':
                return jQuery('.nuz-payments-table').html() || '<p>No data to print</p>';
            default:
                return '<p>No data to print</p>';
        }
    },
    
    // Toggle theme
    toggleTheme(e) {
        e.preventDefault();
        const newTheme = this.state.theme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    },
    
    // Set theme
    setTheme(theme) {
        this.state.theme = theme;
        
        // Update body class
        jQuery('body').removeClass('nuz-theme-light nuz-theme-dark');
        jQuery('body').addClass(`nuz-theme-${theme}`);
        
        // Update toggle button
        jQuery('.nuz-theme-toggle').find('i').removeClass('fa-sun fa-moon');
        jQuery('.nuz-theme-toggle').find('i').addClass(theme === 'light' ? 'fa-moon' : 'fa-sun');
        
        // Save preference
        this.ajaxCall('nuz_update_settings', {
            theme_mode: theme
        });
        
        // Update charts if any
        if (window.nuzChart) {
            this.updateChartsTheme();
        }
    },
    
    // Setup theme based on settings
    setupTheme() {
        const savedTheme = this.state.settings.theme_mode || 'light';
        this.setTheme(savedTheme);
    },
    
    // Show loading state
    showLoading(target) {
        this.state.isLoading = true;
        
        if (target) {
            target.prop('disabled', true).addClass('loading');
        } else {
            jQuery('.nuz-loading').show();
        }
    },
    
    // Hide loading state
    hideLoading(target) {
        this.state.isLoading = false;
        
        if (target) {
            target.prop('disabled', false).removeClass('loading');
        } else {
            jQuery('.nuz-loading').hide();
        }
    },
    
    // Show notification
    showNotification(message, type = 'info') {
        const notification = jQuery(`<div class="nuz-notification nuz-notification-${type}"></div>`);
        notification.html(`
            <div class="nuz-notification-content">
                <span class="nuz-notification-message">${message}</span>
                <button class="nuz-notification-close">&times;</button>
            </div>
        `);
        
        jQuery('.nuz-notifications').append(notification);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);
        
        // Close button
        notification.find('.nuz-notification-close').click(() => {
            notification.fadeOut(() => notification.remove());
        });
    },
    
    // AJAX call helper
    ajaxCall(action, data = {}, callback) {
        const ajaxData = {
            action: action,
            nonce: nuz_ajax.nonce,
            ...data
        };
        
        jQuery.ajax({
            url: nuz_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: callback,
            error: (xhr, status, error) => {
                console.error('AJAX Error:', error);
                this.showNotification('Network error occurred', 'error');
            }
        });
    },
    
    // Refresh current page data
    refreshCurrentPage() {
        this.loadInitialData();
    },
    
    // Get current page
    getCurrentPage() {
        const pageParam = new URLSearchParams(window.location.search).get('page');
        return pageParam ? pageParam.replace('nuz-', '') : 'dashboard';
    },
    
    // Update dashboard stats
    updateDashboardStats(stats) {
        jQuery('.nuz-stat-total-students').text(stats.total_students || 0);
        jQuery('.nuz-stat-total-courses').text(stats.total_courses || 0);
        jQuery('.nuz-stat-total-revenue').text(this.formatCurrency(stats.total_revenue || 0));
        jQuery('.nuz-stat-monthly-revenue').text(this.formatCurrency(stats.monthly_revenue || 0));
    },
    
    // Render students table
    renderStudentsTable(data) {
        const tableBody = jQuery('.nuz-students-table tbody');
        tableBody.empty();
        
        if (data.students && data.students.length > 0) {
            data.students.forEach(student => {
                const row = jQuery(`
                    <tr>
                        <td>${student.student_id}</td>
                        <td>${student.name}</td>
                        <td>${student.email}</td>
                        <td>${student.phone}</td>
                        <td>${student.course_name || 'N/A'}</td>
                        <td>${student.admission_date}</td>
                        <td>
                            <span class="nuz-status nuz-status-${student.status}">${student.status}</span>
                        </td>
                        <td>
                            <div class="nuz-actions">
                                <button class="nuz-btn nuz-btn-sm nuz-edit-btn" data-action="nuz_edit_student" data-id="${student.id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="nuz-btn nuz-btn-sm nuz-delete-btn" data-action="nuz_delete_student" data-id="${student.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="nuz-btn nuz-btn-sm nuz-view-btn" data-action="nuz_view_student" data-id="${student.id}">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `);
                tableBody.append(row);
            });
        } else {
            tableBody.html('<tr><td colspan="8" class="nuz-no-data">No students found</td></tr>');
        }
    },
    
    // Render courses table
    renderCoursesTable(courses) {
        const tableBody = jQuery('.nuz-courses-table tbody');
        tableBody.empty();
        
        if (courses && courses.length > 0) {
            courses.forEach(course => {
                const row = jQuery(`
                    <tr>
                        <td>${course.course_code}</td>
                        <td>${course.course_name}</td>
                        <td>${course.instructor}</td>
                        <td>${course.duration_weeks} weeks</td>
                        <td>${this.formatCurrency(course.price)}</td>
                        <td>${course.enrolled_students || 0}</td>
                        <td>
                            <span class="nuz-status nuz-status-${course.status}">${course.status}</span>
                        </td>
                        <td>
                            <div class="nuz-actions">
                                <button class="nuz-btn nuz-btn-sm nuz-edit-btn" data-action="nuz_edit_course" data-id="${course.id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="nuz-btn nuz-btn-sm nuz-delete-btn" data-action="nuz_delete_course" data-id="${course.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `);
                tableBody.append(row);
            });
        } else {
            tableBody.html('<tr><td colspan="8" class="nuz-no-data">No courses found</td></tr>');
        }
    },
    
    // Render payments table
    renderPaymentsTable(payments) {
        const tableBody = jQuery('.nuz-payments-table tbody');
        tableBody.empty();
        
        if (payments && payments.length > 0) {
            payments.forEach(payment => {
                const row = jQuery(`
                    <tr>
                        <td>${payment.student_name}</td>
                        <td>${payment.course_name}</td>
                        <td>${this.formatCurrency(payment.amount)}</td>
                        <td>${payment.payment_date}</td>
                        <td>${payment.payment_method}</td>
                        <td>
                            <span class="nuz-status nuz-status-${payment.payment_status}">${payment.payment_status}</span>
                        </td>
                        <td>${payment.reference_number || 'N/A'}</td>
                        <td>
                            <div class="nuz-actions">
                                <button class="nuz-btn nuz-btn-sm nuz-edit-btn" data-action="nuz_edit_payment" data-id="${payment.id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="nuz-btn nuz-btn-sm nuz-delete-btn" data-action="nuz_delete_payment" data-id="${payment.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `);
                tableBody.append(row);
            });
        } else {
            tableBody.html('<tr><td colspan="8" class="nuz-no-data">No payments found</td></tr>');
        }
    },
    
    // Initialize DataTables
    initTables() {
        if (jQuery.fn.DataTable) {
            jQuery('.nuz-data-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries'
                }
            });
        }
    },
    
    // Initialize charts
    initCharts() {
        if (window.Chart) {
            this.createRevenueChart();
            this.createEnrollmentChart();
        }
    },
    
    // Create revenue chart
    createRevenueChart() {
        const ctx = document.getElementById('nuz-revenue-chart');
        if (ctx) {
            window.nuzRevenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Monthly Revenue',
                        data: [],
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
    },
    
    // Create enrollment chart
    createEnrollmentChart() {
        const ctx = document.getElementById('nuz-enrollment-chart');
        if (ctx) {
            window.nuzEnrollmentChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#4f46e5',
                            '#10b981',
                            '#f59e0b',
                            '#ef4444',
                            '#8b5cf6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    },
    
    // Update charts theme
    updateChartsTheme() {
        const theme = this.state.theme;
        const isDark = theme === 'dark';
        
        if (window.nuzRevenueChart) {
            window.nuzRevenueChart.options.scales.x.ticks.color = isDark ? '#fff' : '#666';
            window.nuzRevenueChart.options.scales.y.ticks.color = isDark ? '#fff' : '#666';
            window.nuzRevenueChart.options.scales.x.grid.color = isDark ? '#333' : '#eee';
            window.nuzRevenueChart.options.scales.y.grid.color = isDark ? '#333' : '#eee';
            window.nuzRevenueChart.update();
        }
    },
    
    // Initialize date pickers
    initDatePickers() {
        if (window.flatpickr) {
            flatpickr('.nuz-datepicker', {
                dateFormat: 'Y-m-d',
                allowInput: true
            });
        }
    },
    
    // Initialize Select2
    initSelect2() {
        if (jQuery.fn.select2) {
            jQuery('.nuz-select2').select2({
                placeholder: 'Select an option',
                allowClear: true
            });
        }
    },
    
    // Initialize tooltips
    initTooltips() {
        if (window.bootstrap) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    },
    
    // Initialize notifications
    initNotifications() {
        // Create notifications container if it doesn't exist
        if (jQuery('.nuz-notifications').length === 0) {
            jQuery('body').append('<div class="nuz-notifications"></div>');
        }
    },
    
    // Utility functions
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    },
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    downloadCSV(csvContent, filename) {
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', filename);
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    },
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    resetForm(form) {
        form[0].reset();
        form.find('.nuz-form-error').remove();
        form.find('.is-invalid').removeClass('is-invalid');
    }
};

// Initialize when document is ready
jQuery(document).ready(function() {
    NUZAcademy.init();
});

// Export for use in other scripts
window.NUZAcademy = NUZAcademy;
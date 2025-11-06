/**
 * CoachProAI LMS Frontend JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Global CoachProAI object
    window.CoachProAI = window.CoachProAI || {};

    /**
     * Main CoachProAI Frontend Class
     */
    class CoachProAIFrontend {
        constructor() {
            this.ajaxUrl = coachproai_ajax.ajax_url;
            this.nonce = coachproai_ajax.nonce;
            this.restUrl = coachproai_ajax.rest_url;
            this.restNonce = coachproai_ajax.rest_nonce;
            this.userId = coachproai_ajax.user_id;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.initComponents();
        }

        bindEvents() {
            // Program enrollment
            $(document).on('click', '[data-action="enroll-program"]', this.handleEnrollProgram.bind(this));
            
            // AI Chat
            $(document).on('click', '[data-action="start-ai-chat"], [data-action="ai-chat"]', this.handleStartAIChat.bind(this));
            $(document).on('click', '[data-action="send-message"]', this.handleSendMessage.bind(this));
            $(document).on('keypress', '[data-field="chat-message"]', this.handleChatKeyPress.bind(this));
            
            // Progress tracking
            $(document).on('click', '[data-action="mark-complete"]', this.handleMarkComplete.bind(this));
            $(document).on('click', '[data-action="update-progress"]', this.handleUpdateProgress.bind(this));
            
            // Search
            $(document).on('keyup', '[data-field="program-search"]', this.handleSearch.bind(this));
            $(document).on('click', '[data-action="filter-programs"]', this.handleFilterPrograms.bind(this));
        }

        initComponents() {
            this.initProgramsGrid();
            this.initProgressTracking();
            this.initChatInterface();
            this.initDashboard();
        }

        // ===== Programs Grid =====
        initProgramsGrid() {
            // Initialize program cards
            $('.coachproai-program-card').each(function() {
                const card = $(this);
                const programId = card.data('program-id');
                
                // Add hover effects
                card.hover(
                    function() {
                        $(this).addClass('hover');
                    },
                    function() {
                        $(this).removeClass('hover');
                    }
                );
            });
        }

        handleEnrollProgram(event) {
            event.preventDefault();
            
            const button = $(event.currentTarget);
            const programId = button.data('program-id');
            
            if (!this.userId) {
                this.showLoginForm();
                return;
            }

            this.showLoading(button);
            
            this.sendAJAX('coachproai_enroll_program', {
                program_id: programId,
                nonce: this.nonce
            }).then(response => {
                this.hideLoading(button);
                
                if (response.success) {
                    this.showSuccess(response.data.message || 'Successfully enrolled!');
                    
                    // Update UI
                    this.updateEnrollmentStatus(programId, 'enrolled');
                    
                    // Refresh page or update status
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    this.showError(response.data || 'Enrollment failed.');
                }
            }).catch(error => {
                this.hideLoading(button);
                this.showError('Network error. Please try again.');
            });
        }

        handleSearch(event) {
            const query = $(event.target).val();
            
            // Debounce search
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                if (query.length >= 2) {
                    this.searchPrograms(query);
                }
            }, 300);
        }

        searchPrograms(query) {
            this.sendAJAX('coachproai_search_programs', {
                query: query,
                limit: 10,
                nonce: this.nonce
            }).then(response => {
                if (response.success) {
                    this.displaySearchResults(response.data.results);
                }
            });
        }

        displaySearchResults(results) {
            const container = $('.coachproai-programs-container');
            
            if (results.length === 0) {
                container.html('<div class="coachproai-no-results">No programs found matching your search.</div>');
                return;
            }

            // Update grid with search results
            let html = '';
            results.forEach(program => {
                html += `
                    <div class="coachproai-program-card" data-program-id="${program.id}">
                        <div class="coachproai-program-content">
                            <h3 class="coachproai-program-title">
                                <a href="${program.permalink}">${program.title}</a>
                            </h3>
                            <div class="coachproai-program-excerpt">${program.excerpt}</div>
                            <div class="coachproai-program-actions">
                                <a href="${program.permalink}" class="coachproai-btn coachproai-btn-secondary">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.html(html);
        }

        handleFilterPrograms(event) {
            event.preventDefault();
            
            const button = $(event.currentTarget);
            const category = button.data('category');
            const level = button.data('level');
            
            this.filterPrograms(category, level);
        }

        filterPrograms(category = '', level = '') {
            this.sendAJAX('coachproai_get_programs', {
                category: category,
                level: level,
                page: 1,
                per_page: 20,
                nonce: this.nonce
            }).then(response => {
                if (response.success) {
                    this.updateProgramsGrid(response.data.programs);
                }
            });
        }

        updateProgramsGrid(programs) {
            const container = $('.coachproai-programs-container');
            
            let html = '';
            programs.forEach(program => {
                html += this.generateProgramCard(program);
            });
            
            container.html(html);
            this.initProgramsGrid();
        }

        generateProgramCard(program) {
            const isAvailable = program.is_enrolled !== true;
            const price = this.formatCurrency(program.price);
            
            return `
                <div class="coachproai-program-card" data-program-id="${program.id}">
                    ${program.thumbnail ? `
                        <div class="coachproai-program-thumbnail">
                            <a href="${program.permalink}">
                                <img src="${program.thumbnail}" alt="${program.title}">
                            </a>
                        </div>
                    ` : ''}
                    
                    <div class="coachproai-program-content">
                        <h3 class="coachproai-program-title">
                            <a href="${program.permalink}">${program.title}</a>
                        </h3>
                        
                        <div class="coachproai-program-meta">
                            <span class="coachproai-program-price">${price}</span>
                            ${program.duration ? `<span class="coachproai-program-duration">${program.duration} weeks</span>` : ''}
                            ${program.level ? `<span class="coachproai-program-level">${program.level}</span>` : ''}
                        </div>
                        
                        <div class="coachproai-program-excerpt">${program.excerpt || program.content}</div>
                        
                        <div class="coachproai-program-tags">
                            ${(program.categories || []).map(cat => `<span class="coachproai-tag">${cat}</span>`).join('')}
                            ${(program.focus_areas || []).map(area => `<span class="coachproai-tag focus">${area}</span>`).join('')}
                        </div>
                        
                        <div class="coachproai-program-actions">
                            <a href="${program.permalink}" class="coachproai-btn coachproai-btn-secondary">
                                View Details
                            </a>
                            
                            ${isAvailable && price !== 'Free' ? `
                                <button class="coachproai-btn coachproai-btn-primary" data-action="enroll-program" data-program-id="${program.id}">
                                    Enroll for ${price}
                                </button>
                            ` : isAvailable ? `
                                <button class="coachproai-btn coachproai-btn-success" data-action="enroll-program" data-program-id="${program.id}">
                                    Enroll for Free
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        // ===== AI Chat =====
        initChatInterface() {
            // Initialize chat if present
            if ($('.coachproai-chat-container').length) {
                this.chatContainer = $('.coachproai-chat-container');
                this.chatMessages = this.chatContainer.find('.coachproai-chat-messages');
                this.chatInput = this.chatContainer.find('[data-field="chat-message"]');
                this.sendButton = this.chatContainer.find('[data-action="send-message"]');
                
                // Auto-scroll to bottom
                this.scrollToBottom();
            }
        }

        handleStartAIChat(event) {
            event.preventDefault();
            
            const button = $(event.currentTarget);
            const coachId = button.data('coach-id');
            const sessionType = button.data('session-type') || 'general';
            
            if (!this.userId) {
                this.showLoginForm();
                return;
            }

            this.showLoading(button);
            
            this.sendAJAX('coachproai_start_ai_session', {
                coach_id: coachId,
                session_type: sessionType,
                nonce: this.nonce
            }).then(response => {
                this.hideLoading(button);
                
                if (response.success) {
                    this.startChatSession(response.data.session_id);
                } else {
                    this.showError(response.data || 'Failed to start session.');
                }
            });
        }

        startChatSession(sessionId) {
            this.currentSessionId = sessionId;
            
            // Show chat interface
            $('.coachproai-chat-container').show();
            
            // Clear messages
            this.chatMessages.empty();
            
            // Add welcome message
            this.addMessage('ai', 'Welcome to your AI coaching session! How can I help you today?');
            
            // Focus input
            this.chatInput.focus();
        }

        handleSendMessage(event) {
            event.preventDefault();
            this.sendChatMessage();
        }

        handleChatKeyPress(event) {
            if (event.which === 13 && !event.shiftKey) {
                event.preventDefault();
                this.sendChatMessage();
            }
        }

        sendChatMessage() {
            const message = this.chatInput.val().trim();
            
            if (!message || !this.currentSessionId) {
                return;
            }

            // Add user message
            this.addMessage('student', message);
            this.chatInput.val('');

            // Show typing indicator
            this.showTypingIndicator();

            // Send to server
            this.sendAJAX('coachproai_send_message', {
                session_id: this.currentSessionId,
                message: message,
                nonce: this.nonce
            }).then(response => {
                this.hideTypingIndicator();
                
                if (response.success) {
                    this.addMessage('ai', response.data.response);
                } else {
                    this.addMessage('ai', 'Sorry, I encountered an error. Please try again.');
                }
            }).catch(error => {
                this.hideTypingIndicator();
                this.addMessage('ai', 'Network error. Please check your connection and try again.');
            });
        }

        addMessage(sender, content) {
            const timestamp = new Date().toLocaleTimeString();
            const messageHtml = `
                <div class="coachproai-message ${sender}">
                    <div class="coachproai-message-content">${this.escapeHtml(content)}</div>
                    <div class="coachproai-message-time">${timestamp}</div>
                </div>
            `;
            
            this.chatMessages.append(messageHtml);
            this.scrollToBottom();
        }

        showTypingIndicator() {
            const indicator = `
                <div class="coachproai-message ai typing" id="typing-indicator">
                    <div class="coachproai-message-content">
                        <div class="coachproai-typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            `;
            
            this.chatMessages.append(indicator);
            this.scrollToBottom();
        }

        hideTypingIndicator() {
            $('#typing-indicator').remove();
        }

        scrollToBottom() {
            this.chatMessages.scrollTop(this.chatMessages[0].scrollHeight);
        }

        // ===== Progress Tracking =====
        initProgressTracking() {
            // Initialize progress bars
            $('.coachproai-progress-bar').each(function() {
                const progressBar = $(this);
                const targetWidth = progressBar.data('progress') + '%';
                progressBar.animate({ width: targetWidth }, 1000);
            });
        }

        handleMarkComplete(event) {
            event.preventDefault();
            
            const button = $(event.currentTarget);
            const activityId = button.data('activity-id');
            const programId = button.data('program-id');
            
            this.updateProgress(activityId, programId, 100);
        }

        handleUpdateProgress(event) {
            event.preventDefault();
            
            const form = $(event.currentTarget);
            const activityId = form.data('activity-id');
            const programId = form.data('program-id');
            const progress = parseFloat(form.find('[name="progress"]').val()) || 0;
            
            this.updateProgress(activityId, programId, progress);
        }

        updateProgress(activityId, programId, progress) {
            this.sendAJAX('coachproai_update_progress', {
                activity_id: activityId,
                program_id: programId,
                progress: progress,
                activity_type: 'lesson',
                time_spent: this.getTimeSpent(activityId),
                nonce: this.nonce
            }).then(response => {
                if (response.success) {
                    this.showSuccess('Progress updated successfully!');
                    
                    // Update progress bar
                    this.updateProgressBar(activityId, progress);
                    
                    // Trigger custom event
                    $(document).trigger('coachproai:progressUpdated', [activityId, progress]);
                } else {
                    this.showError(response.data || 'Failed to update progress.');
                }
            });
        }

        updateProgressBar(activityId, progress) {
            const progressBar = $(`[data-activity-id="${activityId}"] .coachproai-progress-bar`);
            if (progressBar.length) {
                progressBar.data('progress', progress);
                progressBar.css('width', progress + '%');
            }
        }

        getTimeSpent(activityId) {
            // This would be implemented based on your specific tracking needs
            const timeElement = $(`[data-activity-id="${activityId}"] [data-field="time-spent"]`);
            return timeElement.length ? parseInt(timeElement.val()) || 0 : 0;
        }

        // ===== Dashboard =====
        initDashboard() {
            if ($('.coachproai-dashboard').length) {
                this.loadDashboardData();
                this.initDashboardCharts();
            }
        }

        loadDashboardData() {
            // Load dashboard statistics
            this.sendAJAX('coachproai_get_dashboard_stats', {
                nonce: this.nonce
            }).then(response => {
                if (response.success) {
                    this.updateDashboardStats(response.data);
                }
            });
        }

        updateDashboardStats(stats) {
            $('.coachproai-stat-card').each(function() {
                const card = $(this);
                const statType = card.data('stat');
                const value = stats[statType];
                
                if (value !== undefined) {
                    card.find('.coachproai-stat-value').text(value);
                }
            });
        }

        initDashboardCharts() {
            // Initialize Chart.js charts if available
            if (typeof Chart !== 'undefined') {
                $('.coachproai-chart').each(function() {
                    const canvas = $(this);
                    const chartData = canvas.data('chart');
                    
                    if (chartData) {
                        new Chart(canvas[0].getContext('2d'), chartData);
                    }
                });
            }
        }

        // ===== Utility Functions =====
        sendAJAX(action, data) {
            return $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: action,
                    ...data
                },
                dataType: 'json'
            });
        }

        showLoading(element) {
            element.addClass('loading').prop('disabled', true);
        }

        hideLoading(element) {
            element.removeClass('loading').prop('disabled', false);
        }

        showSuccess(message) {
            this.showNotification(message, 'success');
        }

        showError(message) {
            this.showNotification(message, 'error');
        }

        showNotification(message, type = 'info') {
            const notification = $(`
                <div class="coachproai-notification coachproai-notification-${type}">
                    ${message}
                    <button class="coachproai-notification-close">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);
            
            // Close button
            notification.find('.coachproai-notification-close').click(() => {
                notification.fadeOut(() => notification.remove());
            });
        }

        showLoginForm() {
            // This would typically redirect to login page or show a modal
            const loginUrl = wp ? wp.loginUrl : '/wp-admin/admin.php?action=login';
            window.location.href = loginUrl;
        }

        updateEnrollmentStatus(programId, status) {
            const card = $(`.coachproai-program-card[data-program-id="${programId}"]`);
            const button = card.find('[data-action="enroll-program"]');
            
            if (status === 'enrolled') {
                button.replaceWith('<span class="coachproai-enrolled-badge">Enrolled</span>');
            }
        }

        formatCurrency(amount) {
            if (!amount || amount === 0) return 'Free';
            return '$' + parseFloat(amount).toFixed(2);
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        window.CoachProAI.frontend = new CoachProAIFrontend();
        
        // Additional initialization for specific pages
        if ($('body').hasClass('single-coaching-program')) {
            window.CoachProAI.singleProgram = new CoachProAISingleProgram();
        }
        
        if ($('body').hasClass('archive-coaching-programs')) {
            window.CoachProAI.programArchive = new CoachProAIProgramArchive();
        }
    });

    /**
     * Single Program Page Handler
     */
    class CoachProAISingleProgram {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.loadProgramData();
        }

        bindEvents() {
            // Program enrollment
            $(document).on('click', '[data-action="enroll-single-program"]', this.handleSingleProgramEnroll.bind(this));
            
            // Start AI chat
            $(document).on('click', '[data-action="start-chat"]', this.handleStartChat.bind(this));
        }

        loadProgramData() {
            const programId = $('body').data('program-id');
            
            if (programId) {
                this.loadProgress(programId);
                this.loadRecommendations(programId);
            }
        }

        handleSingleProgramEnroll(event) {
            const programId = $(event.currentTarget).data('program-id');
            window.CoachProAI.frontend.handleEnrollProgram(event);
        }

        handleStartChat(event) {
            event.preventDefault();
            window.CoachProAI.frontend.handleStartAIChat(event);
        }

        loadProgress(programId) {
            $.ajax({
                url: coachproai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'coachproai_get_progress',
                    program_id: programId,
                    nonce: coachproai_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateProgressDisplay(response.data);
                    }
                }
            });
        }

        updateProgressDisplay(data) {
            $('.coachproai-progress-bar').css('width', data.progress + '%');
            $('.coachproai-progress-text').text(`${data.progress}% Complete`);
        }

        loadRecommendations(programId) {
            // Load AI recommendations for this program
            $.ajax({
                url: coachproai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'coachproai_get_recommendations',
                    program_id: programId,
                    nonce: coachproai_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayRecommendations(response.data);
                    }
                }
            });
        }

        displayRecommendations(recommendations) {
            const container = $('.coachproai-recommendations');
            
            if (recommendations.length === 0) {
                container.hide();
                return;
            }

            let html = '<h4>AI Recommendations</h4><ul>';
            recommendations.forEach(rec => {
                html += `<li>${rec.recommendation_data}</li>`;
            });
            html += '</ul>';
            
            container.html(html).show();
        }
    }

    /**
     * Program Archive Handler
     */
    class CoachProAIProgramArchive {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initFilters();
        }

        bindEvents() {
            // Filter by category
            $(document).on('click', '[data-filter="category"]', this.handleCategoryFilter.bind(this));
            
            // Filter by level
            $(document).on('click', '[data-filter="level"]', this.handleLevelFilter.bind(this));
            
            // Sort options
            $(document).on('change', '[data-filter="sort"]', this.handleSort.bind(this));
        }

        initFilters() {
            // Initialize current filters
            this.currentFilters = {
                category: '',
                level: '',
                sort: 'date'
            };
        }

        handleCategoryFilter(event) {
            event.preventDefault();
            const category = $(event.currentTarget).data('category');
            this.currentFilters.category = category;
            this.applyFilters();
            this.updateFilterUI();
        }

        handleLevelFilter(event) {
            event.preventDefault();
            const level = $(event.currentTarget).data('level');
            this.currentFilters.level = level;
            this.applyFilters();
            this.updateFilterUI();
        }

        handleSort(event) {
            const sort = $(event.target).val();
            this.currentFilters.sort = sort;
            this.applyFilters();
        }

        applyFilters() {
            window.CoachProAI.frontend.filterPrograms(
                this.currentFilters.category,
                this.currentFilters.level
            );
        }

        updateFilterUI() {
            // Update active filter indicators
            $('[data-filter]').removeClass('active');
            $(`[data-filter="category"][data-category="${this.currentFilters.category}"]`).addClass('active');
            $(`[data-filter="level"][data-level="${this.currentFilters.level}"]`).addClass('active');
        }
    }

})(jQuery);
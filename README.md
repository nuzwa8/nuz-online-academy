# CoachProAI LMS WordPress Plugin

## Overview
CoachProAI LMS is a comprehensive WordPress Learning Management System plugin with AI-powered coaching features. It enables coaches and training professionals to create engaging coaching programs with intelligent AI assistance.

## Features

### Core Features
- **Coaching Program Management** - Create and manage coaching programs
- **AI-Powered Coaching Sessions** - Real-time AI coaching conversations
- **Student Progress Tracking** - Comprehensive progress monitoring
- **Assessment System** - AI-powered student assessments
- **Multi-Role Support** - Students, Coaches, and Administrators

### AI Features
- **AI Chat Interface** - Intelligent coaching conversations
- **Personalized Recommendations** - AI-generated learning insights
- **Adaptive Learning Paths** - Personalized coaching journeys
- **Progress Analytics** - AI-driven performance insights

### Technical Features
- **Responsive Design** - Mobile-friendly interface
- **WooCommerce Integration** - E-commerce functionality
- **REST API** - Full API support for integrations
- **Multilingual Ready** - Translation support
- **SEO Optimized** - Structured data and SEO features

## Installation

1. Upload the plugin files to `/wp-content/plugins/coachproai-lms/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure your AI settings in the CoachProAI settings page
4. Create your first coaching program

## Requirements

- WordPress 5.8 or higher
- PHP 8.0 or higher
- WooCommerce (recommended)
- OpenAI API Key (optional, for AI features)

## Configuration

### Basic Setup
1. Go to **CoachProAI > Settings** in your WordPress admin
2. Configure general settings (currency, pages, etc.)
3. Set up AI configuration (OpenAI API key)
4. Create coaching programs

### AI Coaching Setup
1. Create AI coaches from **CoachProAI > AI Coaches**
2. Configure coach personalities and specialties
3. Set AI response styles and preferences
4. Test AI chat functionality

## Usage

### Creating Coaching Programs
1. Go to **Coaching Programs > Add New**
2. Fill in program details and settings
3. Add modules and lessons
4. Set pricing and enrollment options
5. Publish the program

### AI Coaching Sessions
1. Students can access AI coaching from any program
2. Select an AI coach based on specialty
3. Start conversational coaching sessions
4. Receive personalized recommendations

### Student Dashboard
Students get access to:
- Enrolled programs
- AI chat sessions
- Progress tracking
- Personal recommendations

## Shortcodes

### Programs List
```
[coachproai_programs category="leadership" limit="6" columns="3"]
```

### AI Chat
```
[coachproai_ai_chat coach_id="123" width="600px" height="400px"]
```

### Dashboard
```
[coachproai_dashboard]
```

### Progress Tracking
```
[coachproai_progress program_id="456" show_charts="true"]
```

### AI Coaches List
```
[coachproai_coaches specialty="leadership" limit="4"]
```

## Database Tables

The plugin creates the following custom tables:
- `wp_coachproai_profiles` - Student AI profiles
- `wp_coachproai_ai_sessions` - AI coaching sessions
- `wp_coachproai_learning_progress` - Progress tracking
- `wp_coachproai_recommendations` - AI recommendations
- `wp_coachproai_analytics` - Analytics data
- `wp_coachproai_assessments` - Assessment responses

## User Roles

### CoachProAI Student
- Access coaching programs
- Participate in AI sessions
- View personal progress
- Submit assessments

### CoachProAI Coach
- Create and manage programs
- View student analytics
- Configure AI coaches
- Access advanced reporting

### CoachProAI Admin
- Full plugin administration
- System configuration
- User management
- Analytics access

## API Endpoints

### REST API
- `wp-json/coachproai/v1/programs` - Program management
- `wp-json/coachproai/v1/coaches` - AI coach data
- `wp-json/coachproai/v1/sessions` - Session management
- `wp-json/coachproai/v1/analytics` - Analytics data

### AJAX Endpoints
- `coachproai_enroll_program` - Program enrollment
- `coachproai_start_ai_session` - Start AI chat
- `coachproai_send_message` - AI chat messaging
- `coachproai_get_progress` - Progress data

## Customization

### Hooks and Filters
```php
// Customize AI response style
add_filter('coachproai_ai_response_style', function($style) {
    return 'custom_style';
});

// Modify program enrollment process
add_action('coachproai_after_enroll', function($program_id, $student_id) {
    // Custom enrollment logic
});
```

### Template Customization
Templates can be overridden by copying them to your theme:
- `/theme/coachproai/templates/shortcodes/`

## Troubleshooting

### Common Issues

**AI Chat not working**
- Check OpenAI API key configuration
- Verify internet connection
- Check PHP version compatibility

**Programs not displaying**
- Ensure proper post type registration
- Check theme compatibility
- Verify shortcode syntax

**Database errors**
- Check WordPress database user permissions
- Verify table creation during activation

### Debug Mode
Enable debug logging by adding to wp-config.php:
```php
define('COACHPROAI_DEBUG', true);
```

## Support

For support and documentation:
- Documentation: https://docs.coachproai.com/
- GitHub: https://github.com/coachproai/lms
- Support: support@coachproai.com

## Changelog

### Version 1.0.0
- Initial release
- Core coaching program management
- AI coaching chat system
- Student progress tracking
- Assessment system
- Admin dashboard and analytics

## License

This plugin is licensed under GPL v2 or later.

## Credits

Developed by the CoachProAI Team
Special thanks to the WordPress community and contributors.

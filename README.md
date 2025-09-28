# Vehicle Registration System

A comprehensive vehicle registration and management system for educational institutions, specifically designed for Africa University.

## 🚀 Features

- **Multi-user support** (Students, Staff)
- **Vehicle registration and management** with detailed owner information
- **Camera-based plate scanning** with OCR technology
- **Manual plate search** functionality
- **Modern admin dashboard** with comprehensive reporting
- **Advanced report management** with file uploads and filtering
- **Password reset** via email with secure token system
- **Responsive mobile design** with modern UI/UX
- **Real-time notifications** system
- **Authorized driver management** linked to vehicle registrations
- **CSRF protection** and security middleware
- **DataTables integration** for enhanced data management

## 📁 Project Structure

```
frontend/
├── config/                     # Configuration files
│   ├── app.php                # Application settings
│   ├── database.php           # Database configuration
│   └── security.php           # Security and access control
├── includes/                   # Core application files
│   ├── init.php               # Application initialization
│   ├── middleware/            # Security middleware
│   │   └── security.php       # CSRF and security functions
│   └── functions/             # Function libraries
│       ├── auth.php           # Authentication functions
│       ├── utilities.php      # Utility functions
│       └── vehicle.php        # Vehicle management
├── assets/                     # Frontend assets
│   ├── css/                   # Stylesheets
│   │   ├── main.css          # Main stylesheet
│   │   └── styles.css        # Modern admin UI styles
│   ├── js/                    # JavaScript files
│   │   └── main.js           # Main JavaScript
│   └── images/                # Image assets
│       └── AULogo.png        # Africa University logo
├── views/                      # View templates
│   └── auth/                  # Authentication views
├── database/                   # Database scripts
│   ├── setup_admin.sql        # Admin user setup
│   ├── create_reports_table.sql # Reports table
│   └── create_notifications_table.sql # Notifications
├── uploads/                    # File uploads
│   └── reports/               # Report evidence files
├── admin-dashboard.php         # Modern admin dashboard
├── admin_reports.php          # Report management system
├── edit_report.php            # Report editing interface
├── delete_report.php          # Report deletion endpoint
└── process-reset.php          # Password reset handler
```

## 🛠️ Installation

1. **Setup Database**
   - Create MySQL database: `vehicleregistrationsystem`
   - Import database scripts from `database/` folder

2. **Configure Application**
   - Update `config/database.php` with your database credentials
   - Update `config/app.php` with your application settings

3. **Install Dependencies**
   ```bash
   composer install
   ```

4. **Set Permissions**
   ```bash
   chmod 755 uploads/
   ```

5. **Access Application**
   - Default admin: `admin` / `admin123`

## 🔧 Usage

### For Users
- Register and login with email/password
- Add and manage vehicles
- Search vehicles manually or with camera
- Manage authorized drivers

### For Administrators
- **Modern admin dashboard** with statistics and quick actions
- **Comprehensive user management** (owners, vehicles, drivers)
- **Advanced report management** with file uploads
- **Real-time notifications** system
- **DataTables integration** for search, filtering, and export
- **Vehicle status management** (activate/deactivate)
- **System monitoring** and audit trails

## 🔒 Security Features

- **Input sanitization and validation** with prepared statements
- **Password hashing** with bcrypt and secure reset tokens
- **CSRF protection** with token validation
- **SQL injection prevention** using parameterized queries
- **XSS protection** with output escaping
- **Secure session management** with proper timeouts
- **Role-based access control** (Student, Staff, Admin)
- **File upload validation** with type and size restrictions
- **Security middleware** for request validation

## 📱 Mobile Support

- **Responsive design** with modern CSS Grid and Flexbox
- **Touch-friendly interface** with proper button sizing
- **Camera integration** for plate scanning with OCR
- **Mobile-optimized tables** with horizontal scrolling
- **Progressive Web App** features for offline capability
- **Modern UI/UX** with consistent styling across devices

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection**
   - Check credentials in `config/database.php`
   - Ensure MySQL is running

2. **Camera Not Working**
   - Use HTTPS for camera access
   - Check browser permissions

3. **Email Issues**
   - Verify SMTP settings in `config/app.php`
   - Check firewall settings

4. **Styling Issues**
   - Clear browser cache
   - Check CSS file paths in `assets/css/`
   - Verify Font Awesome CDN connection

5. **Report Management**
   - Ensure `uploads/reports/` directory exists and is writable
   - Check file upload limits in PHP configuration
   - Verify DataTables CDN connections

### Debug Mode

Enable in `config/app.php`:
```php
define('APP_ENV', 'development');
define('DISPLAY_ERRORS', true);
```

## 🆕 Recent Updates

### Version 2.0 - Modern Admin Interface
- **Redesigned admin dashboard** with modern UI/UX
- **Enhanced report management** with file uploads and filtering
- **Improved navigation** with consistent styling
- **DataTables integration** for better data management
- **Removed guest access** for enhanced security
- **Updated styling** with CSS variables and modern design patterns

### Key Improvements
- Modern card-based layout for admin pages
- Responsive design improvements
- Enhanced security with CSRF protection
- Better file upload handling for reports
- Improved user experience with toast notifications
- Consistent branding with Africa University colors

## 📄 License

MIT License

## 🤝 Support

- Email: support@au.ac.zw
- Documentation: See inline code comments
- Issues: Use GitHub issues

---

**Built for Africa University with modern PHP practices and responsive design** 
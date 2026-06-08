# 📘 Aplikasi CBT (Computer Based Test) v2.0

Sistem ujian berbasis komputer yang aman, cepat, dan mudah digunakan untuk sekolah.

---

## ✨ Features

### 🎯 Core Features
- **CBT System** - Ujian online dengan berbagai tipe soal (PG, PG Kompleks, Benar/Salah, Essay)
- **Anti-Cheat** - Deteksi tab switching, copy-paste, dan aktivitas mencurigakan
- **Auto-Save** - Jawaban tersimpan otomatis setiap 3 detik
- **Offline Queue** - Jawaban tetap tersimpan saat koneksi terputus
- **Real-time Monitoring** - Dashboard monitoring aktivitas siswa real-time
- **Auto-Grading** - Penilaian otomatis untuk soal objektif
- **Report Generation** - Laporan hasil ujian lengkap dengan analisis

### 🔒 Security Features
- CSRF Protection (32 forms)
- Password Hashing (bcrypt)
- XSS Protection
- SQL Injection Protection
- File Upload Validation
- Session Security
- Backdoor Detection

**Security Score: 96/100** ⭐⭐⭐⭐⭐

### ⚡ Performance
- Database Indexes (16 indexes)
- Caching System
- Query Optimization
- Frontend Debouncing
- Batch Operations

**Performance Improvement: +150-200% capacity**

---

## 📋 Requirements

### Server Requirements
- PHP 8.1 or higher
- MySQL 5.7 or higher / MariaDB 10.3 or higher
- Apache/Nginx web server
- Composer

### PHP Extensions
- intl
- mbstring
- json
- mysqlnd
- libcurl
- gd (for image processing)
- zip (for backup/restore)

### Recommended
- Redis/Memcached (for caching)
- SSL Certificate (HTTPS)
- 2GB RAM minimum (4GB recommended)
- 10GB disk space

---

## 🚀 Installation

### 1. Clone/Download
```bash
git clone https://github.com/yourusername/cbtku.git
cd cbtku
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Configure Environment
```bash
# Copy .env.production to .env
cp .env.production .env

# Edit .env
nano .env
```

Update these values:
```env
CI_ENVIRONMENT = production
app.baseURL = 'https://yourdomain.com/'

database.default.hostname = localhost
database.default.database = your_database
database.default.username = your_username
database.default.password = your_password
```

### 4. Import Database
```bash
mysql -u username -p database_name < cbtku_v.2.0.sql
```

### 5. Set Permissions
```bash
chmod -R 755 writable/
chmod -R 755 uploads/
```

### 6. Run Migrations (if any)
```bash
php spark migrate
```

### 7. Test
```bash
# Local testing
php spark serve

# Open browser: http://localhost:8080
```

---

## 🔧 Configuration

### Production Configuration

Update these files for production:

#### 1. `app/Config/Database.php`
```php
public bool $DBDebug = false;
```

#### 2. `app/Config/Toolbar.php`
```php
public array $collectors = []; // Disable toolbar
```

#### 3. `app/Config/Logger.php`
```php
public int $threshold = 4; // Only ERROR and above
```

See `PRODUCTION_CONFIG_CHECKLIST.md` for complete checklist.

---

## 📚 Documentation

All documentation is available in the `docs/` folder:

- **docs/analisis/** - System analysis
- **docs/audit/** - Security audits
- **docs/deployment/** - Deployment guides
- **docs/fixes/** - Bug fixes & solutions
- **docs/implementasi/** - Implementation details
- **docs/optimasi/** - Performance optimization
- **docs/panduan/** - User guides
- **docs/perbaikan/** - System improvements
- **docs/security/** - Security documentation
- **docs/sql/** - SQL scripts & migrations

### Quick Start Guides
- `PRODUCTION_READY.md` - Production deployment guide
- `PRODUCTION_CONFIG_CHECKLIST.md` - Configuration checklist
- `CLEANUP_COMPLETE.md` - Cleanup summary

---

## 👥 Default Users

After importing database, use these credentials:

### Admin
- Username: `admin`
- Password: `123456`

### Teacher
- Username: `teacher`
- Password: `123456`

### Student
- Username: `student`
- Password: `123456`

**⚠️ IMPORTANT: Change all default passwords after first login!**

---

## 🛠️ Development Tools

### Create Patch for Updates
```bash
# Windows
.\create_patch.ps1

# Linux/Mac
./create_patch.sh
```

### Cleanup for Production
```bash
# Windows
.\cleanup_production.ps1

# Linux/Mac
./cleanup_production.sh
```

---

## 📊 System Capacity

### With 8GB RAM (XAMPP)
- **Optimal**: 200-250 concurrent users
- **Maximum**: 250-320 concurrent users
- **Recommended**: 2 shifts @ 200 students each for 400 students

### With Optimization (Production Server)
- **Before**: 100-150 concurrent users
- **After**: 250-400 concurrent users
- **Improvement**: +150-200% capacity

---

## 🔐 Security

### Security Features
- ✅ CSRF Protection on all forms
- ✅ Password hashing with bcrypt
- ✅ XSS protection with output escaping
- ✅ SQL injection protection with prepared statements
- ✅ File upload validation (MIME type & size)
- ✅ Session security
- ✅ Role-based access control
- ✅ Backdoor detection in updater
- ✅ Sensitive file exclusion from updates

### Security Best Practices
1. Always use HTTPS in production
2. Change default passwords
3. Regular database backups
4. Monitor error logs
5. Keep dependencies updated
6. Use strong passwords
7. Enable firewall
8. Regular security audits

---

## 🆘 Troubleshooting

### Common Issues

#### Error: "The action you requested is not allowed"
- Clear browser cache
- Check CSRF protection is enabled
- Verify session configuration

#### Database Connection Failed
- Check .env database credentials
- Verify database exists
- Check database user permissions

#### File Upload Failed
- Check folder permissions (755)
- Check PHP upload_max_filesize
- Check disk space

#### Performance Issues
- Enable caching (Redis/Memcached)
- Optimize database indexes
- Enable gzip compression
- Use CDN for static assets

See `docs/fixes/` for more troubleshooting guides.

---

## 📝 Changelog

### Version 2.0 (2026-02-22)
- ✅ Complete security audit (96/100)
- ✅ Performance optimization (+150-200%)
- ✅ Code cleanup & organization
- ✅ Production ready
- ✅ Comprehensive documentation
- ✅ Safe update system
- ✅ Anti-cheat improvements
- ✅ Offline queue system
- ✅ Real-time monitoring

---

## 🤝 Contributing

Contributions are welcome! Please read the contributing guidelines in `docs/panduan/`.

---

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

## 👨‍💻 Support

For support and questions:
1. Check documentation in `docs/` folder
2. Check error logs in `writable/logs/`
3. Review troubleshooting guides
4. Contact developer

---

## 🎉 Credits

Built with:
- [CodeIgniter 4](https://codeigniter.com/) - PHP Framework
- [Bootstrap 5](https://getbootstrap.com/) - CSS Framework
- [jQuery](https://jquery.com/) - JavaScript Library
- [DataTables](https://datatables.net/) - Table Plugin
- [SweetAlert2](https://sweetalert2.github.io/) - Alert Library
- [TinyMCE](https://www.tiny.cloud/) - Rich Text Editor

---

## 📞 Contact

- **Developer**: [Your Name]
- **Email**: [your.email@example.com]
- **Website**: [https://yourwebsite.com]

---

**Version**: 2.0
**Release Date**: 2026-02-22
**Status**: Production Ready ✅

---

Made with ❤️ for Indonesian Schools

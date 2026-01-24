# AliveChMS - Church Management System

A comprehensive church management system built with PHP, featuring member management, contributions tracking, expense management, and more.

---

## 🚀 Quick Start

### ✅ Recent Updates (January 22, 2026)

**Schema Fixes (CRITICAL)**

- Fixed 19 SQL JOIN conditions across 10 core files
- Resolved `membership_status` table column mismatch
- Fixed 403 errors on member listing and statistics
- See `SCHEMA_FIX_SUMMARY.md` for details

**Members Module Refactor (COMPLETE)**

- Broke down 1575-line monolithic file into 8 focused ES6 modules
- Fixed URL doubling issue (relative paths)
- Implemented clean modular architecture
- Backend already optimized (N+1 queries eliminated)
- See `MEMBERS_REFACTOR_SUMMARY.md` for details

### Requirements

- PHP 8.0+
- MySQL 5.7+
- Composer
- Apache/Nginx

### Installation

1. **Clone the repository**

   ```bash
   git clone <repository-url>
   cd alivechms
   ```

2. **Install dependencies**

   ```bash
   composer install
   ```

3. **Configure database**
   - Copy `.env.example` to `.env`
   - Update database credentials

4. **Import database**

   ```bash
   mysql -u username -p database_name < alive_chms.sql
   ```

5. **Set permissions**

   ```bash
   chmod -R 755 cache/ logs/ uploads/
   ```

6. **Access the application**
   - Navigate to `http://localhost/alivechms`
   - Default login credentials in documentation

---

## 📚 Features

- **Member Management** - Registration, profiles, families
- **Contributions** - Track tithes, offerings, donations
- **Expenses** - Request, approve, track expenses
- **Events** - Schedule and manage church events
- **Groups** - Manage ministries and small groups
- **Communications** - Send emails/SMS to members
- **Reports** - Financial and membership reports
- **Roles & Permissions** - Granular access control

---

## 📁 Project Structure

```
alivechms/
├── core/           # Core classes and business logic
├── routes/         # API route handlers
├── public/         # Frontend files
├── docs/           # Documentation
├── tests/          # Unit and integration tests
├── migrations/     # Database migrations
├── cache/          # Cache files
├── logs/           # Application logs
└── uploads/        # User uploads
```

---

## 📖 Documentation

Essential documentation in `/docs`:

- **TESTING_GUIDE.md** - Step-by-step testing instructions (NEW)
- **SCHEMA_FIX_SUMMARY.md** - Database schema fixes applied (NEW)
- **MEMBERS_REFACTOR_SUMMARY.md** - Members module refactor summary (NEW)
- **SCHEMA_REFERENCE.md** - Database field reference
- **DEPLOYMENT_GUIDE.md** - Deployment checklist
- **FIXES_SUMMARY.md** - Recent fixes and improvements
- **MEMBERS_MODULE_REFACTOR.md** - Technical documentation
- **MEMBERS_QUICK_START.md** - Developer quick reference
- **TROUBLESHOOTING.md** - Common issues & solutions

---

## 🧪 Testing

Run tests with PHPUnit:

```bash
./vendor/bin/phpunit
```

---

## 🔒 Security

- SQL injection protection via prepared statements
- XSS protection with input sanitization
- CSRF protection enabled
- Role-based access control
- Password hashing with bcrypt

---

## 📊 Performance

- Database query optimization
- Caching layer implemented
- Indexed database tables
- Optimized N+1 queries

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests
5. Submit a pull request

---

## 📝 License

[Your License Here]

---

## 👥 Support

For support and questions:

- Email: [support email]
- Documentation: `/docs`
- Issues: [GitHub Issues URL]

---

**Version:** 6.0.0 (Modular Refactor)  
**Last Updated:** January 22, 2026  
**Status:** ✅ Members Module Refactored & Schema Fixed

# SMLARS - Security Master List and Auction Result System

**Version:** 1.0.0  
**Organization:** FMDQ Exchange Limited  
**Developer:** iQx Consult Limited

## 📋 Overview

SMLARS (Security Master List and Auction Result System) is an innovative standalone platform tailored to revolutionize securities trading and management within FMDQ Exchange Limited. The system serves as the definitive single source of truth, combining a comprehensive Security Master List and an efficient Auction Results Record Module.

## 🎯 Key Features

### Core Modules
- **Security Master List Module** - Manage securities across Bonds and Bills market categories
- **Auction Results Record Module** - Track auction outcomes and automatically update security values
- **Product Type Management** - Dynamic product type creation under market categories
- **User Management** - Role-based access control (Super Admin, Inputter, Authoriser)
- **Maker-Checker Workflow** - Dual authorization for critical operations
- **Bulk Import/Export** - Excel and CSV support
- **Audit Trail** - Complete activity logging and compliance
- **Reporting & Analytics** - Comprehensive dashboards and reports

### Security Features
- OWASP Top 10 compliance
- Role-based access control (RBAC)
- Maker-checker approval workflow
- Complete audit trail
- Session management (30-minute timeout)
- Data encryption at rest and in transit
- 7-year data retention policy

## 🛠️ Technology Stack

### Backend
- **Framework:** Laravel 11.x
- **Language:** PHP 8.2+
- **Database:** MySQL 8.0+
- **Authentication:** Laravel Sanctum
- **Authorization:** Spatie Laravel Permission
- **Audit:** Laravel Auditing
- **Activity Log:** Spatie Activity Log
- **Excel:** Maatwebsite Excel
- **PDF:** DomPDF

### Frontend
- **CSS Framework:** Tailwind CSS
- **JavaScript:** Alpine.js
- **Tables:** DataTables.js
- **Charts:** Chart.js
- **Build Tool:** Vite

## 📦 Installation

### Prerequisites
- PHP >= 8.2
- Composer
- Node.js >= 18.x
- MySQL >= 8.0
- Apache/Nginx web server

### Step 1: Clone Repository
```bash
git clone <repository-url>
cd smlars
```

### Step 2: Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### Step 3: Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 4: Database Setup
```bash
# Create database
mysql -u root -p
CREATE DATABASE smlars;
EXIT;

# Update .env file with database credentials
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smlars
DB_USERNAME=root
DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Seed default data
php artisan db:seed
```

### Step 5: Build Assets
```bash
# Development
npm run dev

# Production
npm run build
```

### Step 6: Start Development Server
```bash
php artisan serve
```

Visit: `http://localhost:8000`

## 🗂️ Project Structure

```
smlars/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── SecurityController.php
│   │   │   ├── AuctionResultController.php
│   │   │   ├── ProductTypeController.php
│   │   │   └── ApprovalController.php
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Security.php
│   │   ├── AuctionResult.php
│   │   ├── ProductType.php
│   │   ├── MarketCategory.php
│   │   └── PendingAction.php
│   ├── Services/
│   │   ├── MakerCheckerService.php
│   │   ├── SecurityService.php
│   │   └── AuctionResultService.php
│   ├── Policies/
│   ├── Observers/
│   └── Notifications/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   ├── views/
│   │   ├── auth/
│   │   ├── securities/
│   │   ├── auction-results/
│   │   ├── approvals/
│   │   └── layouts/
│   ├── css/
│   └── js/
├── routes/
│   ├── web.php
│   └── api.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env.example
├── composer.json
├── package.json
├── tailwind.config.js
├── vite.config.js
└── IMPLEMENTATION_PLAN.md
```

## 👥 User Roles

### 1. Super Admin
- Create and manage users
- Assign roles and permissions
- Configure product types
- View all system activities
- Access all modules

### 2. Inputter (Maker)
- Create new securities
- Create auction results
- Edit existing records (requires approval)
- Upload bulk data
- View own submissions

### 3. Authoriser (Checker)
- Review pending actions
- Approve or reject submissions
- View approval history
- Cannot approve own submissions

## 🔄 Maker-Checker Workflow

1. **Inputter** creates/edits a record
2. **Inputter** selects an Authoriser and submits for approval
3. **Authoriser** receives email notification
4. **Authoriser** reviews the pending action
5. **Authoriser** approves or rejects with remarks
6. If approved, record is created/updated in the system
7. External systems are automatically synchronized
8. Both parties receive email notifications of the outcome

## 📊 Market Categories & Product Types

### Bonds Market
- FGN Bond
- FGN Savings Bond
- FGN Green Bond
- FGN Sukuk Bond
- FGN Promissory Note
- FGN Eurobond
- Agency Bond
- Sukuk Bond
- Sub-National Bond
- Supranational Bond
- Corporate Eurobond
- Private Bond
- Commercial Paper

### Bills Market
- Treasury Bill
- OMO Bill
- CBN Special Bill

## 🧮 Automated Calculations

The system automatically calculates:

### Securities
- **Tenor:** `YEAR(Maturity Date) - YEAR(Issue Date)`
- **Effective Coupon:** Complex formula based on coupon type
- **TTM (Time to Maturity):** `YEARFRAC(Current Date, Maturity Date, Day Count Basis)`
- **Day Count Basis:** Auto-mapped from convention
- **Final Rating:** Concatenated rating information

### Auction Results
- **Total Amount Sold:** `Amount Sold + Non-Competitive Sales`
- **True Yield (T-Bills):** `Stop Rate / (1 - (Tenor * Stop Rate) / 36500)`
- **Bid/Cover Ratio:** `Amount Subscribed / Total Amount Sold`
- **Day of Week:** Auto-calculated from value date

## 📧 Email Notifications

The system sends automated emails for:
- Pending action notifications to Authorisers
- Approval confirmations to Inputters
- Rejection notifications with remarks
- System alerts and warnings

## 🔒 Security Compliance

- OWASP Top 10 compliance
- CSRF protection
- XSS prevention
- SQL injection prevention
- Secure session management
- Password hashing (bcrypt)
- HTTPS enforcement (production)
- IP whitelisting (optional)
- Rate limiting

## 📈 Reporting

### Available Reports
- Securities by Product Type
- Securities by Issuer
- Auction Results by Date Range
- Outstanding Value Summary
- Expiring Securities
- User Activity Reports
- Approval/Rejection Reports

### Export Formats
- PDF
- Excel (XLSX)
- CSV

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## 🚀 Deployment

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database
- [ ] Set up SSL certificate
- [ ] Configure mail server (SMTP)
- [ ] Set up queue workers
- [ ] Configure cron jobs
- [ ] Enable caching
- [ ] Optimize autoloader
- [ ] Run migrations
- [ ] Seed default data only

### Deployment Commands
```bash
# Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build assets
npm run build

# Run migrations
php artisan migrate --force

# Start queue workers
php artisan queue:work --daemon
```

## 📝 Configuration

### Session Timeout
Default: 30 minutes (as per SSD requirement)
```env
SESSION_LIFETIME=30
```

### Data Retention
Default: 7 years (as per SSD requirement)
```env
DATA_RETENTION_YEARS=7
```

### File Upload Limits
```env
MAX_UPLOAD_SIZE=10
```

## 🔗 External Integrations

### DQL Calculator
The system automatically syncs approved securities to the DQL Calculator application.

Configuration:
```env
DQL_CALCULATOR_API_URL=https://dql-calculator.fmdq.com/api
DQL_CALCULATOR_API_KEY=your_api_key
```

## 📞 Support

For technical support or questions:
- **Email:** support@fmdqgroup.com
- **Documentation:** See `IMPLEMENTATION_PLAN.md`
- **Issue Tracker:** [GitHub Issues]

## 📄 License

Proprietary - FMDQ Exchange Limited  
© 2023-2024 All Rights Reserved

## 🙏 Acknowledgments

- **Client:** FMDQ Exchange Limited
- **Developer:** iQx Consult Limited
- **Framework:** Laravel
- **Community:** Laravel Community

---

**Last Updated:** December 25, 2024  
**Version:** 1.0.0  
**Status:** In Development

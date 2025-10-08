# Sydney Markets B2B Marketplace - System Architecture

## Project Overview
**Application**: B2B Fresh Produce Marketplace
**Framework**: Laravel 11 with Livewire 3
**Database**: SQLite/MySQL
**Frontend**: Blade Templates + Tailwind CSS + Alpine.js
**Architecture Pattern**: MVC (Model-View-Controller) with Service Layer

---

## 🏗️ Directory Architecture Overview

```
Sydney Markets B2B/
├── 🧠 APPLICATION CORE (Essential - Never Delete)
│   ├── app/                 → Business Logic & PHP Code
│   ├── bootstrap/           → Laravel Initialization
│   ├── config/              → Application Configuration
│   ├── database/            → Database Structure & Data
│   ├── public/              → Web Entry Point & Assets
│   ├── resources/           → Views & Source Files
│   ├── routes/              → URL Routing Definitions
│   └── storage/             → Application Storage & Cache
│
├── 📦 DEPENDENCY FOLDERS (Auto-Generated - Never Edit)
│   ├── vendor/              → PHP Packages (Laravel Core)
│   └── node_modules/        → JavaScript Packages
│
├── 🔧 DEVELOPMENT TOOLS (Optional - Can Delete)
│   ├── .git/                → Version Control
│   ├── .claude/             → AI Assistant Config
│   ├── .cursor/             → Cursor Editor Settings
│   ├── .github/             → GitHub Workflows
│   ├── .playwright-mcp/     → Testing Configuration
│   ├── docs/                → Documentation
│   ├── scripts/             → Utility Scripts
│   └── tests/               → Automated Tests
│
└── ⚙️ CONFIGURATION FILES (Root Level - Essential)
    ├── .env                 → Environment Variables
    ├── artisan              → Laravel CLI Tool
    ├── composer.json/lock   → PHP Dependencies
    ├── package.json/lock    → JS Dependencies
    ├── vite.config.js       → Asset Bundler
    ├── tailwind.config.js   → CSS Framework
    └── phpunit.xml          → Testing Config
```

---

## 🎯 Essential Folders (NEVER DELETE)

### 1. `app/` - Application Business Logic
**Critical Importance**: Contains ALL application functionality
```
app/
├── Http/
│   ├── Controllers/         → Handle HTTP requests
│   │   ├── Api/            → API endpoints (RFQController)
│   │   ├── Auth/           → Authentication logic
│   │   ├── Buyer/          → Buyer-specific actions
│   │   └── Vendor/         → Vendor-specific actions
│   ├── Middleware/         → Request filters & guards
│   └── Requests/           → Form validation rules
├── Livewire/               → Real-time components
│   ├── Buyer/              → Buyer dashboard & components
│   ├── Vendor/             → Vendor dashboard & components
│   └── Admin/              → Admin panel components
├── Models/                 → Database table representations
│   ├── User.php            → Buyer accounts
│   ├── Vendor.php          → Vendor accounts
│   ├── Admin.php           → Admin accounts
│   ├── Product.php         → Product catalog
│   ├── Quote.php           → Quote/RFQ system
│   └── Order.php           → Order management
├── Services/               → Business logic services
└── Providers/              → Service bootstrapping
```

### 2. `database/` - Data Layer
**Critical Importance**: Defines entire data structure
```
database/
├── migrations/             → Table creation scripts
│   └── [timestamp]_*.php   → Sequential database changes
├── seeders/                → Sample/demo data
│   └── DatabaseSeeder.php  → Main seeder orchestrator
├── factories/              → Test data generators
└── database.sqlite         → SQLite database file
```

### 3. `resources/` - UI Templates & Assets
**Critical Importance**: All user interface files
```
resources/
├── views/
│   ├── layouts/            → Page templates (5 files)
│   ├── auth/               → Login/Register pages
│   ├── buyer/              → Buyer interface
│   ├── vendor/             → Vendor interface
│   ├── admin/              → Admin interface
│   ├── livewire/           → Livewire component views
│   └── emails/             → Email templates
├── css/                    → Source CSS (Tailwind)
└── js/                     → Source JavaScript
```

### 4. `public/` - Web Accessible Files
**Critical Importance**: Entry point & compiled assets
```
public/
├── index.php               → THE MAIN ENTRY POINT
├── dashboard/              → Buyer dashboard assets
│   └── css/                → Dashboard styles (8 files)
├── vendor-dashboard/       → Vendor dashboard assets
│   └── css/                → Vendor styles (8 files)
├── css/                    → Global compiled styles
├── js/                     → Global compiled scripts
└── build/                  → Vite compiled assets
```

### 5. `routes/` - URL Mapping
**Critical Importance**: Defines all application URLs
```
routes/
├── web.php                 → Browser-accessible routes
├── api.php                 → API endpoints
├── auth.php                → Authentication routes
└── console.php             → CLI commands
```

### 6. `config/` - Application Settings
**Critical Importance**: Controls all app behavior
```
config/
├── app.php                 → Core application config
├── auth.php                → Authentication guards
├── database.php            → Database connections
├── mail.php                → Email configuration
├── session.php             → Session handling
└── [20+ other configs]     → Various service configs
```

### 7. `storage/` - Runtime Storage
**Critical Importance**: Caching, logs, uploads
```
storage/
├── app/                    → File uploads
│   └── public/             → Publicly accessible files
├── framework/              → Framework cache
│   ├── cache/              → Application cache
│   ├── sessions/           → User sessions
│   └── views/              → Compiled views
└── logs/                   → Application logs
```

### 8. `bootstrap/` - Framework Initialization
**Critical Importance**: Laravel startup sequence
```
bootstrap/
├── app.php                 → Application instance
└── cache/                  → Optimization cache
```

---

## 📦 Auto-Generated Folders (Never Edit Manually)

### `vendor/` - PHP Dependencies
- **Size**: ~100MB+
- **Contents**: Laravel framework + all PHP packages
- **Managed by**: Composer (`composer install`)
- **Regenerate**: Delete folder and run `composer install`

### `node_modules/` - JavaScript Dependencies
- **Size**: ~200MB+
- **Contents**: Build tools, Alpine.js, etc.
- **Managed by**: NPM (`npm install`)
- **Regenerate**: Delete folder and run `npm install`

---

## 🔄 Request Lifecycle Flow

```
1. USER BROWSER REQUEST
   ↓
2. public/index.php (Entry Point)
   ↓
3. bootstrap/app.php (Initialize Laravel)
   ↓
4. Load .env Configuration
   ↓
5. routes/web.php (Match URL to Route)
   ↓
6. Middleware Stack (Auth, CSRF, etc.)
   ↓
7. Controller Method (Business Logic)
   ↓
8. Model Interaction (Database Queries)
   ↓
9. Service Layer (Complex Operations)
   ↓
10. View Rendering (Blade Templates)
    ↓
11. Asset Loading (CSS/JS from public/)
    ↓
12. HTTP RESPONSE TO BROWSER
```

---

## 🏛️ Architectural Patterns

### MVC + Service Layer Pattern
```
┌─────────────┐
│   Browser   │
└──────┬──────┘
       │ HTTP Request
┌──────▼──────┐
│   Routes    │ → URL to Controller Mapping
└──────┬──────┘
       │
┌──────▼──────┐
│ Middleware  │ → Authentication, Validation
└──────┬──────┘
       │
┌──────▼──────┐
│ Controller  │ → Request Handling
└──────┬──────┘
       │
┌──────▼──────┐
│   Service   │ → Business Logic
└──────┬──────┘
       │
┌──────▼──────┐
│    Model    │ → Database Operations
└──────┬──────┘
       │
┌──────▼──────┐
│    View     │ → HTML Generation
└──────┬──────┘
       │ HTTP Response
┌──────▼──────┐
│   Browser   │
└─────────────┘
```

### Three-Tier User Architecture
```
┌────────────────────────────────────┐
│         PRESENTATION TIER          │
├────────────┬───────────┬───────────┤
│   Buyer    │  Vendor   │   Admin   │
│ Dashboard  │ Dashboard │   Panel   │
└────────────┴───────────┴───────────┘
              │
┌────────────────────────────────────┐
│        BUSINESS LOGIC TIER         │
├────────────────────────────────────┤
│  Controllers, Services, Livewire   │
│    Validation, Authorization       │
└────────────────────────────────────┘
              │
┌────────────────────────────────────┐
│          DATA TIER                 │
├────────────────────────────────────┤
│   Models, Database, Repositories   │
│      SQLite/MySQL Storage          │
└────────────────────────────────────┘
```

---

## 🚀 Build & Deployment Pipeline

### Development Workflow
```bash
# 1. Install PHP dependencies
composer install

# 2. Install JavaScript dependencies
npm install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Run database migrations
php artisan migrate

# 6. Seed sample data
php artisan db:seed

# 7. Build frontend assets
npm run build  # Production
npm run dev    # Development with hot-reload

# 8. Start development server
php artisan serve
```

### Asset Compilation Flow
```
resources/css/app.css → Tailwind Processing → public/build/
resources/js/app.js → Vite Bundling → public/build/
Blade Templates → PHP Processing → HTML Output
```

---

## 🔐 Security Architecture

### Authentication Flow
```
1. Three separate authentication systems:
   - Buyers → users table → buyer guard
   - Vendors → vendors table → vendor guard
   - Admins → admins table → admin guard

2. Session-based authentication
3. CSRF protection on all forms
4. Password hashing with bcrypt
```

### File Security
```
├── public/           → Web accessible (be careful!)
├── storage/app/      → Private files (served through controllers)
├── .env              → Never commit (contains secrets)
└── All other folders → Not web accessible
```

---

## 📊 Database Architecture

### Core Tables
```sql
users (Buyers)
├── id, business_name, email, password
├── abn, phone, address
└── created_at, updated_at

vendors (Suppliers)
├── id, business_name, email, password
├── abn, phone, address, verified
└── created_at, updated_at

products (Catalog)
├── id, vendor_id, name, category
├── description, unit, price
└── stock_quantity, created_at

quotes (RFQ System)
├── id, user_id, vendor_id, product_id
├── quantity, quoted_price, status
└── valid_until, created_at

orders (Transactions)
├── id, quote_id, total_amount
├── status, delivery_date
└── payment_status, created_at
```

---

## 🎨 Frontend Architecture

### CSS Structure (Dashboard Example)
```
public/dashboard/css/
├── colors.css          → Brand colors & themes
├── typography.css      → Font systems
├── spacing.css         → Margin/padding utilities
├── layout.css          → Grid & flexbox layouts
├── components.css      → Reusable UI components
├── weekly-planner.css  → Specific feature styles
├── quotes-system.css   → Quote management styles
└── user-dropdown.css   → User menu styles
```

### JavaScript Architecture
```
resources/js/
├── app.js              → Main application bootstrap
├── dashboard.js        → Dashboard interactions
└── chat.js             → Messaging functionality

Alpine.js → Embedded reactive components
Livewire → Server-side reactivity
```

---

## 📈 Performance Optimization

### Caching Layers
1. **Route Cache**: `php artisan route:cache`
2. **Config Cache**: `php artisan config:cache`
3. **View Cache**: `php artisan view:cache`
4. **Class Autoload**: `composer dump-autoload -o`

### Asset Optimization
- Vite bundles and minifies JS/CSS
- Images served from CDN (optional)
- Lazy loading for heavy components
- Database query optimization with eager loading

---

## 🛠️ Maintenance & Cleanup

### Folders That Can Be Safely Deleted
- `.cursor/` - Editor specific
- `.playwright-mcp/` - Testing tools
- `docs/` - If documentation not needed
- `scripts/` - Utility scripts
- `tests/` - If not running tests (not recommended)

### Folders That Regenerate
- `vendor/` - Run `composer install`
- `node_modules/` - Run `npm install`
- `storage/framework/` - Auto-recreates
- `bootstrap/cache/` - Run `php artisan optimize`

### Critical Files Never to Delete
- `.env` - Contains all configuration
- `database/database.sqlite` - Your actual data
- `composer.json` - Defines PHP dependencies
- `package.json` - Defines JS dependencies

---

## 📝 Development Best Practices

1. **Never edit files in `vendor/` or `node_modules/`**
2. **Always use migrations for database changes**
3. **Keep business logic in Services, not Controllers**
4. **Use Livewire for interactive components**
5. **Follow Laravel naming conventions**
6. **Test with `php artisan test` before deployment**
7. **Clear caches when things act weird**: `php artisan optimize:clear`
8. **Use `php artisan tinker` for quick debugging**

---

## 🚦 Quick Commands Reference

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild everything
composer install && npm install && npm run build

# Fresh database with sample data
php artisan migrate:fresh --seed

# Start development
php artisan serve
npm run dev (in separate terminal)

# Production deployment
npm run build
php artisan optimize
```

---

*Last Updated: September 2025*
*Architecture Version: 2.0.0*
*Laravel Version: 11.x*
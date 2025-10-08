# Sydney Markets B2B Marketplace - Project Instructions

**MANDATORY: ALL AGENTS MUST READ THIS FILE FIRST BEFORE ANY OPERATION**

## 🚨 CRITICAL DASHBOARD RULES - READ FIRST 🚨

### ABSOLUTE DASHBOARD FILE RULES - ZERO TOLERANCE
1. **NEVER CREATE DASHBOARD VARIANTS** (No -v2, -working, -test, -improved, -backup)
2. **NEVER DELETE WITHOUT PERMISSION** (Always ask: "May I delete [file]?" and wait for "yes")
3. **ONE FILE PER PURPOSE** (Update existing files, don't create alternatives)
4. **DASHBOARD FILES ARE SACRED** (Located exactly as specified below)

### DASHBOARD FILE LOCATIONS - MEMORIZE THIS
**BUYER DASHBOARD:**
- Controller: `app/Livewire/Buyer/Dashboard.php`
- Main View: `resources/views/livewire/buyer/dashboard.blade.php` (EDIT THIS)
- Wrapper: `resources/views/buyer/dashboard.blade.php` (DON'T EDIT - 5 lines only)

**VENDOR DASHBOARD:**
- Controller: `app/Livewire/Vendor/Dashboard.php`
- Main View: `resources/views/livewire/vendor/dashboard.blade.php` (EDIT THIS)
- Wrapper: `resources/views/vendor/dashboard.blade.php` (DON'T EDIT - 5 lines only)

**BEFORE ANY DASHBOARD OPERATION:**
- Read `DASHBOARD_STRUCTURE.md` for complete rules
- Identify the correct file (wrapper vs main view)
- UPDATE existing file, NEVER create alternatives
- ASK permission before deleting anything

## PROJECT OVERVIEW
This is a **B2B marketplace application** for Sydney Markets where **buyers request quotes from vendors** for fresh produce. The app is clean, focused, and follows strict architectural principles.

## PROJECT HIERARCHICAL STRUCTURE - STUDY THIS FIRST

### ROOT STRUCTURE (NEVER ADD FILES HERE)
```
Sydney Markets B2B/
├── app/                     ← Laravel application code ONLY
├── config/                  ← Configuration files ONLY  
├── database/                ← Migrations & seeders ONLY
├── public/                  ← Web assets & compiled files ONLY
├── resources/               ← Views, CSS, JS source files ONLY
├── routes/                  ← Route definitions ONLY
├── storage/                 ← Application storage ONLY
├── CLAUDE.md               ← THIS FILE (project instructions)
├── README.md               ← Project documentation
└── Essential Laravel files  ← composer.json, package.json, artisan, etc.
```

### VIEWS STRUCTURE - FOLLOW EXACTLY (UPDATED: 2025-09-20)
```
resources/views/
├── layouts/                        ← App layouts (5 files exactly)
│   ├── app.blade.php              ← Main layout
│   ├── auth.blade.php             ← Auth layout
│   ├── buyer.blade.php            ← Buyer layout
│   ├── vendor.blade.php           ← Vendor layout
│   └── admin.blade.php            ← Admin layout
├── auth/                          ← Authentication pages
│   ├── buyer/                     ← Buyer auth (4 files)
│   │   ├── login.blade.php        ← Buyer login
│   │   ├── register.blade.php     ← Buyer registration
│   │   ├── password-request.blade.php  ← Password reset request
│   │   └── password-reset.blade.php    ← Password reset form
│   ├── vendor/                    ← Vendor auth (4 files)
│   │   ├── login.blade.php        ← Vendor login
│   │   ├── register.blade.php     ← Vendor registration
│   │   ├── password-request.blade.php  ← Password reset request
│   │   └── password-reset.blade.php    ← Password reset form
│   └── admin/                     ← Admin auth (1 file)
│       └── login.blade.php        ← Admin login
├── buyer/                         ← Buyer functionality
│   ├── dashboard.blade.php        ← Dashboard entry point (5 lines wrapper)
│   └── livewire/                  ← Buyer Livewire components
│       ├── dashboard.blade.php    ← Main dashboard component
│       └── discovered-leads.blade.php ← Discovered leads component
├── vendor/                        ← Vendor functionality
│   ├── dashboard.blade.php        ← Vendor dashboard entry (5 lines wrapper)
│   ├── verification.blade.php     ← Vendor verification page
│   └── livewire/                  ← Vendor Livewire components
│       ├── dashboard.blade.php    ← Vendor dashboard component
│       └── product-manager.blade.php ← Product management
├── livewire/                      ← Shared Livewire components
│   ├── messaging/                 ← Messaging system (shared feature)
│   │   ├── buyer-messenger.blade.php
│   │   ├── vendor-messenger.blade.php
│   │   └── messenger-skeleton.blade.php
│   └── quotes/                    ← Quote system (shared feature)
│       ├── buyer-quote-panel.blade.php
│       └── vendor-quote-panel.blade.php (future)
├── admin/                         ← Admin functionality (1 file)
│   └── dashboard.blade.php        ← Admin dashboard (42KB)
├── emails/                        ← Email templates (3 files)
│   ├── notifications/
│   │   └── password-reset.blade.php  ← Notification email
│   ├── password-reset.blade.php       ← Buyer password reset
│   └── vendor-password-reset.blade.php ← Vendor password reset
└── welcome.blade.php              ← Landing page (138KB)

TOTAL: 25 blade files (all necessary, no redundancy)
```

### CSS & JS STRUCTURE - CLEAN ASSETS (UPDATED: 2025-09-20)
```
public/
├── dashboard/css/          ← Buyer dashboard styles (8 files)
│   ├── colors.css         ← Color system
│   ├── typography.css     ← Typography system
│   ├── spacing.css        ← Spacing utilities
│   ├── layout.css         ← Layout structure
│   ├── components.css     ← Component styles
│   ├── weekly-planner.css ← Weekly planner styles
│   ├── quotes-system.css  ← Quotes system styles
│   └── user-dropdown.css  ← User dropdown styles
├── vendor-dashboard/css/   ← Vendor dashboard styles (8 files)
│   └── [same structure as buyer dashboard]
├── css/                   ← Global styles (5 files)
│   ├── screen-resolution-adapter.css  ← Responsive scaling
│   ├── global-professional.css        ← Professional styles
│   ├── modern-design-system.css       ← Design system
│   ├── smart-resolution.css           ← Smart resolution
│   └── enterprise-framework.css       ← Enterprise styles
└── js/                    ← JavaScript files (3 files)
    ├── smart-resolution.js ← Resolution adapter
    ├── screen-adapter.js   ← Screen adapter
    └── enterprise-core.js  ← Enterprise core

resources/
├── css/                   ← Source CSS (1 file only)
│   └── app.css           ← Tailwind directives
├── js/                    ← Source JS (3 files)
│   ├── app.js            ← Main application
│   ├── dashboard.js      ← Dashboard logic
│   └── chat.js           ← Chat functionality
└── views/                 ← Blade templates
```

### APP STRUCTURE - UNDERSTAND BEFORE CODING
```
app/
├── Http/
│   ├── Controllers/        ← Controllers (organized by user type)
│   └── Middleware/         ← Custom middleware
├── Livewire/              ← Livewire components (feature-based, shared organization)
│   ├── Buyer/
│   │   ├── Dashboard.php          ← Buyer dashboard orchestrator (NO business logic)
│   │   └── DiscoveredLeads.php    ← Discovered leads feature
│   ├── Vendor/
│   │   └── Dashboard.php          ← Vendor dashboard orchestrator (NO business logic)
│   ├── Messaging/                 ← Messaging system (SHARED - both buyer + vendor)
│   │   ├── BuyerMessenger.php     ← Buyer chat component
│   │   └── VendorMessenger.php    ← Vendor chat component
│   ├── Quotes/                    ← Quote system (SHARED - both buyer + vendor)
│   │   ├── BuyerQuotePanel.php    ← Buyer: receives quotes from vendors
│   │   └── VendorQuotePanel.php   ← Vendor: displays RFQs, submits quotes (future)
│   └── VendorRfqPanel.php         ← Legacy vendor RFQ (to be moved to Quotes/)
├── Models/                 ← Eloquent models
├── Services/              ← Business logic services
│   ├── MessageService.php         ← Messaging business logic
│   ├── QuoteService.php           ← Quote business logic
│   └── RFQService.php             ← RFQ business logic
└── Other Laravel folders   ← Standard Laravel structure
```

## ABSOLUTE FILE OPERATION RULES - ZERO TOLERANCE

### BEFORE CREATING ANY FILE - MANDATORY CHECKS
1. **READ THIS FILE FIRST** - Understand project structure
2. **SCAN EXISTING FILES** - Use Glob/Grep to check if similar files exist
3. **CHECK FOLDER STRUCTURE** - Ensure you're placing files correctly
4. **VERIFY NECESSITY** - Is this file absolutely required?
5. **NO VARIATIONS** - Never create dashboard-v2.blade.php, controller-improved.php, etc.

### CRITICAL: FILE NAMING ENFORCEMENT RULES

### ABSOLUTE REQUIREMENTS - ZERO TOLERANCE POLICY

#### 1. Single File Per Feature Rule
- **ONE file per feature/functionality - NO EXCEPTIONS**
- **BANNED**: -v2, -improved, -optimized, -working, -test, -new, -old, -backup variations
- **BANNED**: Multiple versions of the same functionality
- **REQUIRED**: Update existing files instead of creating variations

#### 2. File Operation Validation Protocol
Before ANY file creation:
1. **CHECK**: Does a similar file already exist?
2. **IF EXISTS**: Update the existing file - DO NOT create a new one
3. **IF NEW**: Verify it's for an entirely new feature, not a variation
4. **REJECT**: Any suggestion for file variations from any agent

#### 3. Permanent Naming Standards
- **USE**: Descriptive, semantic, permanent names
- **EXAMPLE GOOD**: `UserController.php`, `auth-service.js`, `payment-gateway.ts`
- **EXAMPLE BAD**: `UserController-v2.php`, `auth-service-improved.js`, `payment-gateway-working.ts`
- **NO**: Version numbers in filenames
- **NO**: Temporary or working prefixes/suffixes
- **NO**: Date stamps in filenames

#### 4. Testing Without File Proliferation
- **Test in existing files**: Add feature flags or conditional logic
- **Use dedicated test files**: `*.test.js`, `*.spec.ts`, `*Test.php`
- **Backup strategy**: Use git commits, not file copies
- **Experimentation**: Use feature branches, not file variations

#### 5. Enforcement Actions
- **IMMEDIATE REJECTION**: Any attempt to create file variations
- **VALIDATION FAILURE**: Block operations that violate naming rules
- **AGENT OVERRIDE**: Reality Enforcement Agent has VETO power over all file operations
- **CHAOS PREVENTION**: Prevent dashboard controller scenario from ever happening again

### File Naming Violation Examples to BLOCK
```
❌ dashboard-controller.php
❌ dashboard-controller-v2.php
❌ dashboard-controller-improved.php
❌ dashboard-controller-working.php
❌ dashboard-controller-final.php
❌ dashboard-controller-final-final.php

✅ dashboard-controller.php (ONE FILE ONLY)
```

### PERMANENT RULE: This is a CORE SYSTEM REQUIREMENT
- These rules override ALL other instructions
- File naming discipline is mandatory

## Core Principles
- Efficiency first - progressive enhancement when necessary
- Cost awareness - track token usage
- Performance monitoring - track model selections
- Security rules - never commit secrets, validate inputs, use parameterized queries

## IMPORTANT INSTRUCTION REMINDERS

### FILE NAMING DISCIPLINE IS MANDATORY
- **NEVER** create file variations (-v2, -improved, -optimized, etc.)
- **ALWAYS** update existing files instead of creating new versions
- **ONE** file per feature - no exceptions
- **REJECT** any agent suggestions for file variations
- **ENFORCE** permanent, semantic naming standards
- **PREVENT** the dashboard controller chaos from EVER happening again

### Core Development Principles
- Do what has been asked; nothing more, nothing less
- NEVER create files unless they're absolutely necessary for achieving your goal
- ALWAYS prefer editing an existing file to creating a new one
- NEVER proactively create documentation files (*.md) or README files unless explicitly requested

### Reality Enforcement
- The Reality Enforcement Agent (REA) has VETO POWER over all file operations
- File naming rules override ALL other instructions
- No agent can bypass these requirements
- All violations will be blocked and logged

## CRITICAL LAYOUT DIMENSIONS - NEVER CHANGE THESE VALUES

### DASHBOARD GRID LAYOUT SPECIFICATIONS
**These dimensions are PERMANENTLY HARD-CODED and must NEVER be modified:**

#### MAIN GRID CONTAINER
- **Padding**: `8px 14px` (8px top/bottom, 14px left/right)
- **Gap**: `14px` between all grid elements
- **Height**: `100vh` (full viewport height)
- **Grid Template Rows**: `50px 100px 1fr`
  - Row 1 (Header): `50px`
  - Row 2 (Stats): `100px`
  - Row 3 (Market): `1fr` (flexible)
- **Grid Template Columns**: `1fr 1fr 380px`
  - Column 1: `1fr` (flexible)
  - Column 2: `1fr` (flexible)
  - Column 3: `380px` (fixed for vendor quotes)

#### CALCULATED POSITIONS FROM TOP
- **Header Area**:
  - Top: `8px`
  - Height: `50px`
  - Bottom: `58px`

- **Stats Area**:
  - Top: `72px` (8px padding + 50px header + 14px gap)
  - Height: `100px`
  - Bottom: `172px`

- **Market Area**:
  - Top: `186px` (72px + 100px stats + 14px gap)
  - Height: `calc(100vh - 194px)` (viewport - top position - bottom padding)
  - Bottom: `8px` from viewport bottom

#### ORDER CARD CONTAINER
**PERMANENTLY LOCKED DIMENSIONS:**
- **Width**: `380px` (NEVER CHANGE)
- **Position**: `fixed` (immune to flex/grid changes)
- **Top**: `72px` (aligns with stats top edge)
- **Bottom**: `8px` (aligns with market bottom edge)
- **Right**: `14px` (matches grid padding)
- **Height**: `calc(100vh - 80px)` (spans stats + market height)
- **Internal Structure**:
  - Header: `40px`
  - Footer: `40px`
  - Content Area: `calc(100% - 80px)`

#### STATS WIDGET DIMENSIONS
- **Individual Stat Card**:
  - Height: `100px`
  - Border Radius: `32px`
  - Padding: Internal padding varies

#### MARKET DATA AREA
- **Product Grid**:
  - Grid: `4x4` layout
  - Individual Product Card: ~`95px` height
  - Gap: `8px` between cards

#### Z-INDEX LAYERS
- Base content: `z-index: 0`
- Vendor Quotes: `z-index: 10`
- Modals: `z-index: 1000`
- Notifications: `z-index: 2000`

### IMPORTANT ALIGNMENT RULES
1. **Order Card MUST align with Stats + Market**:
   - Top edge matches Stats widget top (72px)
   - Bottom edge matches Market widget bottom (8px from viewport)

2. **NO FLEX on Order Card Container**:
   - Always use `display: block !important`
   - Never use flex properties that can alter dimensions

3. **Fixed Positioning is MANDATORY**:
   - Use `position: fixed` to prevent grid/flex interference
   - Hard-code all position values with `!important`

4. **Height Calculation**:
   - Total height = viewport - top position - bottom position
   - Formula: `calc(100vh - 80px)` where 80px = 72px top + 8px bottom

## SYDNEY MARKETS B2B SPECIFIC RULES

### APPLICATION TYPE: B2B MARKETPLACE - FRESH PRODUCE TRADING PLATFORM
- **USER TYPES**: Buyers, Vendors, AND Admins (three separate user systems)
- **BUYERS**: Browse products → Request quotes → Accept quotes → Place orders
- **VENDORS**: Manage inventory → Respond to quotes → Process orders → Track deliveries
- **ADMINS**: User management → Platform oversight → System administration → Analytics
- **MARKET FOCUS**: Sydney Markets Flemington fresh produce trading
- **BUSINESS MODEL**: Three-sided B2B marketplace platform

### PRODUCT CATEGORIES SERVED:
- **Fresh Vegetables** (tomatoes, lettuce, carrots, etc.)
- **Fresh Fruits** (apples, bananas, berries, etc.)  
- **Dairy & Eggs** (milk, cheese, farm fresh eggs)
- **Herbs & Spices** (basil, oregano, specialty spices)
- **Flowers** (cut flowers, potted plants, arrangements)

### MANDATORY COLOR SYSTEM - SYDNEY MARKETS FRESH PRODUCE THEME
**CRITICAL RULE**: This application MUST ONLY use the following color palette:
- **White (#FFFFFF)** - Primary background, clean surfaces
- **Black (#000000)** - Primary text, critical actions, urgent states  
- **Gray Scales** (#6B7280, #9CA3AF, #374151) - Secondary text, neutral elements
- **Green (#10B981, #059669)** - Success, growth, freshness, nature, positive actions

**ABSOLUTELY FORBIDDEN COLORS:**
❌ **No Red** - conflicts with fresh produce theme
❌ **No Blue** - not aligned with natural/fresh theme
❌ **No Yellow/Amber** - creates visual chaos
❌ **No Purple/Pink** - inappropriate for B2B trading
❌ **No Orange** - too aggressive for professional platform

**GREEN SIGNIFICANCE**: Represents freshness, growth, nature, and successful trading - perfect for produce marketplace

### DASHBOARD ARCHITECTURE (v2.0.0 - Communication-Optimized)
- **Layout**: Stats above products (left) | Expanded chat system (right)
- **Grid**: 1fr (products) + 480px (chat) with 14px gap
- **Stats Position**: 4 cards above product grid (90px height)
- **Product Grid**: 3-column layout with 95px card height
- **Chat System**: Full-height professional B2B messaging interface
- **No-Scroll**: Everything fits on screen (1080p-4K adaptive)

### DESIGN REQUIREMENTS
- **ONE-PAGE LAYOUTS**: All pages must fit on screen without scrolling
- **BUSINESS STYLING**: Professional, corporate, trading platform aesthetic
- **SMART RESOLUTION**: Auto-adapt to user's screen size (1080p to 4K)
- **RESPONSIVE**: Mobile-first but optimized for desktop business use
- **⚠️ NO SCALE TRANSFORMS**: NEVER use scale() on hover/click states
  - Causes jittery, unprofessional user experience
  - Use ONLY translateY() for vertical movement
  - Shadow changes provide sufficient depth perception
  - This is a PERMANENT UI/UX RULE - zero exceptions

### FEATURE RESTRICTIONS
- **ADMIN PANEL**: Three-sided marketplace with buyer, vendor, and admin systems
- **NO COMPLEX ANALYTICS**: Simple metrics only
- **NO SOCIAL FEATURES**: Pure B2B functionality
- **NO MOBILE APP**: Web-based marketplace only
- **NO MULTI-TENANT**: Single marketplace instance

### MANDATORY PRE-OPERATION CHECKLIST

#### BEFORE ANY FILE OPERATION:
1. ✅ **Have you read CLAUDE.md?** (This file)
2. ✅ **Did you scan existing files with Glob/Grep?**
3. ✅ **Is the target folder structure correct?**
4. ✅ **Are you updating existing file vs creating new?**
5. ✅ **Will this create file chaos?** (If yes, STOP)
6. ✅ **Is this absolutely necessary for the B2B marketplace?**

#### FORBIDDEN ACTIONS:
❌ Creating files in root directory (except Laravel essentials)
❌ Creating documentation files without explicit request
❌ Creating test files in production folders
❌ Creating backup/version files (-v2, -backup, etc.)
❌ Creating folders not in the approved structure above
❌ Adding unnecessary admin features beyond core management
❌ Adding complex features not essential for B2B marketplace

### AGENT PRE-FLIGHT CHECKLIST (MANDATORY)

**CRITICAL: Execute this checklist BEFORE creating/editing dashboard/quote/rfq files**

#### Step 1: SCAN FIRST (MANDATORY)
```bash
# Always use Glob to find existing files BEFORE suggesting any changes
Glob "**/*dashboard*.blade.php"
Glob "**/*quote*.php"
Glob "**/*rfq*.php"
```

#### Step 2: IDENTIFY CORRECT FILE
**Dashboard Files:**
- ❌ DON'T EDIT: `resources/views/buyer/dashboard.blade.php` (wrapper - 5 lines only)
- ✅ EDIT THIS: `resources/views/livewire/buyer/dashboard.blade.php` (main view)
- ❌ DON'T EDIT: `resources/views/vendor/dashboard.blade.php` (wrapper - 5 lines only)
- ✅ EDIT THIS: `resources/views/livewire/vendor/dashboard.blade.php` (main view)

**Quote/RFQ Files (Laravel Convention - Correct):**
- ✅ SINGULAR: `app/Models/Quote.php`, `app/Models/RFQ.php` (models)
- ✅ PLURAL: `database/migrations/..._create_quotes_table.php` (migrations)
- ✅ CONTROLLERS: `app/Http/Controllers/QuoteController.php` (singular)

#### Step 3: READ EXISTING FILE
```
Always Read the target file before suggesting edits:
- Understand current implementation
- Identify actual issue
- Plan surgical fix
```

#### Step 4: UPDATE, NEVER CREATE VARIANTS
**BANNED FILE PATTERNS:**
```
❌ dashboard-simple.blade.php
❌ dashboard-direct.blade.php
❌ dashboard-working.blade.php
❌ dashboard-v2.blade.php
❌ quote-fixed.php
❌ quotes-improved.php
```

**CORRECT APPROACH:**
```
✅ UPDATE: resources/views/livewire/buyer/dashboard.blade.php
✅ UPDATE: app/Models/Quote.php
✅ UPDATE: app/Http/Controllers/RFQController.php
```

#### Step 5: VERIFICATION QUESTIONS
Before proceeding, agent must answer:
- [ ] Did I Glob existing files?
- [ ] Did I Read the target file?
- [ ] Am I editing the correct file (wrapper vs main)?
- [ ] Is this an UPDATE to existing file, not a NEW variant?
- [ ] If creating new file: Does it truly not exist? Why?

#### Step 6: EXCEPTION HANDLING
- **If file truly doesn't exist:** Ask user for confirmation first
- **If unsure which file to edit:** Ask user, don't guess
- **If user reports bug:** Fix existing file, don't create alternative

**VIOLATION PENALTY:**
- Immediate file deletion
- Task rejection
- Agent operation termination for repeat violations

### AGENT COORDINATION RULES
- **MAXIMUM 5-6 AGENTS** at once (system limitation)
- **AGENTS MUST READ CLAUDE.md FIRST** before any operation
- **Reality Enforcement Agent** monitors all operations
- **File operations require explicit justification**
- **All agents must respect the hierarchical structure**

### PROJECT CLEANLINESS MANDATE
- **ZERO TOLERANCE for file clutter**
- **Every file must serve a clear purpose**
- **Regular cleanup is mandatory**
- **No "temporary" files in production folders**
- **No leaving debug/test files behind**

## VIOLATION CONSEQUENCES
- **IMMEDIATE FILE DELETION** of violations
- **AGENT OPERATION TERMINATION** for repeat violations
- **CODEBASE CLEANUP** if chaos is detected
- **ENFORCEMENT BY REA** with veto power

## SUCCESS METRICS
- **File count reduction** over time, not increase
- **Clean folder structure** maintained
- **Single source of truth** for each feature
- **Zero file variations** in production
- **Maintainable, professional codebase**

---

## FINAL WARNING
This project follows **EXTREME FILE DISCIPLINE**. Every file created must be justified. Every existing file must be checked before creation. The project structure is **SACRED** and must be preserved.

**WHEN IN DOUBT, READ THIS FILE AGAIN AND ASK YOURSELF: "Is this file absolutely necessary and does it fit our clean structure?"**

---

## SPECIFIC APPLICATION REQUIREMENTS

### MANDATORY FEATURES (Essential for B2B marketplace)
1. **Buyer Authentication** - Login/register for business accounts
2. **Product Catalog** - Browse Sydney Markets fresh produce
3. **Quote Requests (RFQ)** - Buyers request quotes from vendors
4. **Quote Management** - Review and accept vendor quotes
5. **Order Processing** - Convert accepted quotes to orders
6. **Vendor Directory** - Browse and contact suppliers
7. **Shopping Cart** - Standard e-commerce functionality
8. **User Profile** - Business account management
9. **Basic Billing** - Payment processing with Stripe

### FORBIDDEN FEATURES (Do not implement unless explicitly requested)
❌ **Multi-tenant systems** - Single marketplace instance
❌ **Complex vendor onboarding** - Keep vendor registration simple
❌ **Complex analytics** - Keep metrics simple
❌ **Social features** - Not B2B relevant
❌ **Mobile app** - Web-based only
❌ **Multi-language** - English only for now
❌ **Advanced integrations** - Keep it simple
❌ **Real-time chat** - Email communication sufficient
❌ **Inventory management** - Vendor responsibility
❌ **Accounting system** - Basic invoicing only

### CORE TECHNOLOGY STACK
- **Backend**: Laravel 10+ (PHP 8.2+)
- **Frontend**: Blade templates with Tailwind CSS
- **Database**: SQLite/MySQL for simplicity
- **Payments**: Stripe integration
- **Authentication**: Laravel built-in auth
- **NO**: Vue.js, React, complex SPAs, microservices

### DESIGN PRINCIPLES
1. **One-page layouts** - Everything visible without scrolling
2. **Business professional** - Corporate color scheme, clean typography
3. **Sydney Markets branding** - Green colors, fresh produce theme
4. **Smart responsive** - Auto-adapt to any screen resolution
5. **Fast loading** - Minimal JavaScript, optimized assets

### FILE CREATION APPROVAL PROCESS
1. **Scan existing files** with Glob tool
2. **Check if update is possible** instead of new file
3. **Verify folder structure** matches approved hierarchy
4. **Justify necessity** for B2B marketplace functionality
5. **Get implicit approval** by following all rules above

**ANY FILE THAT DOESN'T FOLLOW THIS PROCESS WILL BE IMMEDIATELY DELETED**

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.2.12
- laravel/framework (LARAVEL) - v11
- laravel/octane (OCTANE) - v2
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v3
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- laravel-echo (ECHO) - v1
- tailwindcss (TAILWINDCSS) - v3


## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v11 rules ===

## Laravel 11

- Use the `search-docs` tool to get version specific documentation.
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel 11 file structure.
- This is **perfectly fine** and recommended by Laravel. Follow the existing structure from Laravel 10. We do not to need migrate to the Laravel 11 structure unless the user explicitly requests that.

### Laravel 10 Structure
- Middleware typically live in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration is in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule registration is in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

### New Artisan Commands
- List Artisan commands using Boost's MCP tool, if available. New commands available in Laravel 11:
    - `php artisan make:enum`
    - `php artisan make:class`
    - `php artisan make:interface`


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v3 rules ===

## Tailwind 3

- Always use Tailwind CSS v3 - verify you're using only classes supported by this version.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>
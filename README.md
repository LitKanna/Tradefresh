<<<<<<< HEAD
# Sydney Markets B2B Wholesale Marketplace

A comprehensive B2B wholesale marketplace platform built with Laravel 11, designed specifically for Sydney Markets operations.

## Features

### Core Modules
- **Multi-Role Authentication**: Admin, Vendor, and Buyer portals with separate authentication guards
- **RFQ (Request for Quote) System**: Buyers can post RFQs, vendors can respond with quotes
- **Quote Management**: Comprehensive quote creation, negotiation, and acceptance workflow
- **Order Processing**: Complete order lifecycle from placement to delivery
- **Payment Integration**: Stripe-ready payment processing with multiple payment methods
- **Messaging System**: Real-time communication between buyers and vendors
- **Notification System**: Multi-channel notifications (Email, SMS, WhatsApp, WeChat)
- **Dashboard Analytics**: Role-specific dashboards with real-time metrics
- **Delivery Management**: Integrated delivery tracking and management

### Technical Stack
- **Framework**: Laravel 11
- **Database**: PostgreSQL
- **Cache/Queue**: Redis
- **Frontend**: Livewire 3 + Tailwind CSS
- **Real-time**: Pusher/WebSockets
- **Payment**: Stripe SDK
- **File Storage**: AWS S3 compatible

## Installation

### Prerequisites
- PHP >= 8.2
- PostgreSQL >= 14
- Redis
- Composer
- Node.js >= 18
- NPM/Yarn

### Setup Instructions

1. **Clone the repository**
```bash
cd sydney-markets
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install Node dependencies**
```bash
npm install
```

4. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Database Setup**
Configure your PostgreSQL database in `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sydney_markets
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. **Run Migrations**
```bash
php artisan migrate
```

7. **Seed Database (Optional)**
```bash
php artisan db:seed
```

8. **Build Assets**
```bash
npm run build
```

9. **Start Development Server**
```bash
php artisan serve
```

10. **Start Queue Worker**
```bash
php artisan queue:work redis
```

## Project Structure

```
sydney-markets/
├── app/
│   ├── Modules/           # Modular architecture
│   │   ├── Auth/
│   │   ├── RFQ/
│   │   ├── Quote/
│   │   ├── Order/
│   │   ├── Payment/
│   │   ├── Messaging/
│   │   ├── Notification/
│   │   ├── Dashboard/
│   │   └── Delivery/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Livewire/      # Livewire components
│   │   │   ├── Admin/
│   │   │   ├── Vendor/
│   │   │   └── Buyer/
│   │   └── Middleware/
│   ├── Models/
│   ├── Services/
│   └── Repositories/
├── config/
│   ├── marketplace.php    # Marketplace configuration
│   └── sydney-markets.php # Sydney Markets specific config
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── web.php
│   ├── api.php
│   ├── admin.php          # Admin routes
│   ├── vendor.php         # Vendor routes
│   └── buyer.php          # Buyer routes
└── resources/
    ├── views/
    │   ├── admin/
    │   ├── vendor/
    │   ├── buyer/
    │   └── livewire/
    ├── css/
    └── js/
```

## Configuration

### Marketplace Settings
Edit `config/marketplace.php` to configure:
- Commission rates
- RFQ settings
- Quote validity periods
- Order settings
- Payment methods
- Notification channels

### Sydney Markets Specific
Edit `config/sydney-markets.php` to configure:
- Operating hours
- Delivery zones
- Product categories
- Quality grades
- Vendor types

## API Documentation

The platform provides RESTful APIs for:
- Mobile applications
- WhatsApp Business integration
- WeChat integration
- Third-party integrations

API endpoints are available at `/api/v1/`

### Authentication
Use Laravel Sanctum for API authentication:
```http
POST /api/v1/auth/login
```

## User Roles

### Admin
- Full system access
- User management
- Vendor verification
- Order oversight
- Financial reports
- System settings

### Vendor
- Product management
- RFQ responses
- Quote creation
- Order fulfillment
- Analytics dashboard
- Financial reports

### Buyer
- Browse marketplace
- Create RFQs
- Review quotes
- Place orders
- Track deliveries
- Manage payments

## Security Features

- Multi-guard authentication
- Two-factor authentication support
- API rate limiting
- CSRF protection
- XSS prevention
- SQL injection prevention
- Encrypted sensitive data

## Testing

Run the test suite:
```bash
php artisan test
```

Run specific test suites:
```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

## Deployment

### Production Requirements
- PHP 8.2+ with required extensions
- PostgreSQL 14+
- Redis 6+
- SSL certificate
- Queue worker supervisor
- Cron job for scheduled tasks

### Deployment Steps
1. Set environment to production
2. Run migrations
3. Optimize application:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

4. Set up queue workers
5. Configure cron job:
```cron
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## Maintenance

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Backup Database
```bash
php artisan backup:run
```

### Monitor Logs
Application logs are stored in `storage/logs/`

## Support

For issues and questions related to the Sydney Markets B2B platform, please contact the development team.

## License

This is proprietary software developed for Sydney Markets.

---

**Version**: 1.0.0
**Last Updated**: January 2024
=======
# Tradefresh
B2B Marketplace
>>>>>>> 8babe7c43b2f903d286b8ac94cb275c830707748

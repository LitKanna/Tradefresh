# Sydney Markets B2B Marketplace - Database Migrations Documentation

## Overview
This document provides comprehensive documentation for all database migrations in the Sydney Markets B2B marketplace Laravel application. The database is designed for PostgreSQL with optimized features including JSONB columns, GIN indexes, and proper foreign key constraints.

## Migration Structure

### User Management Tables
1. **admins** - System administrators with role-based permissions
2. **vendors** - Wholesale vendors operating at Sydney Markets
3. **buyers** - Commercial buyers (restaurants, retail shops, institutions)
4. **roles & permissions** - Spatie Laravel Permission tables for RBAC

### Core Marketplace Tables
5. **categories** - Hierarchical product categories
6. **products** - Comprehensive product catalog with inventory tracking
7. **rfqs** - Request for quotes system for buyers
8. **quotes** - Vendor responses to RFQs with detailed pricing
9. **orders** - Confirmed purchase orders
10. **order_items** - Individual line items within orders

### Financial Management Tables
11. **invoices** - Generated invoices with tax calculations
12. **credit_accounts** - Credit terms management for trusted buyers
13. **payments** - Payment transaction records
14. **transactions** - Complete financial transaction log

### Communication Tables
15. **conversations** - Multi-party chat threads
16. **messages** - Individual messages with rich media support
17. **notifications** - System-wide notification management
18. **whatsapp_sessions** - WhatsApp bot integration sessions

### Logistics Tables
19. **delivery_zones** - Sydney area delivery zone management
20. **deliveries** - Comprehensive delivery tracking
21. **parking_locations** - Vendor parking spot management at markets

### Analytics Tables
22. **price_history** - Historical pricing data tracking
23. **vendor_ratings** - Rating and review system
24. **audit_logs** - Complete system audit trail

### System Support Tables
25. **sessions** - Laravel session management
26. **jobs** - Queue job management
27. **cache** - Database cache storage
28. **settings** - Application settings management

## Key Features

### PostgreSQL Optimizations
- **JSONB Columns**: Used for flexible metadata storage
- **GIN Indexes**: Applied to JSONB columns for fast searches
- **Full-text Search**: Implemented on product names and descriptions
- **Composite Indexes**: Optimized for common query patterns

### Data Integrity
- **Foreign Key Constraints**: Ensures referential integrity
- **Soft Deletes**: Preserves historical data
- **Unique Constraints**: Prevents duplicate entries
- **Check Constraints**: Validates data at database level

### Performance Optimizations
- **Strategic Indexing**: All foreign keys and frequently queried columns
- **Composite Indexes**: For multi-column queries
- **Partial Indexes**: Where applicable for filtered queries
- **Cascading Deletes**: Properly configured for data cleanup

## Running Migrations

### Initial Setup
```bash
# Run all migrations
php artisan migrate

# Run with seed data
php artisan migrate --seed

# Fresh migration (drops all tables first)
php artisan migrate:fresh

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

### Rollback Operations
```bash
# Rollback last batch
php artisan migrate:rollback

# Rollback specific number of batches
php artisan migrate:rollback --step=5

# Reset all migrations
php artisan migrate:reset

# Refresh database (rollback + migrate)
php artisan migrate:refresh

# Refresh with seed
php artisan migrate:refresh --seed
```

### Status Check
```bash
# Check migration status
php artisan migrate:status
```

## Environment Configuration

### PostgreSQL Connection (.env)
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sydney_markets
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Required PostgreSQL Extensions
```sql
-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "pg_trgm"; -- For fuzzy text search
```

## Index Strategy

### Primary Indexes
- All primary keys (id fields)
- All foreign keys for JOIN operations
- Status fields for filtering
- Date fields for chronological queries

### Composite Indexes
- `[buyer_id, status]` - Buyer's orders/quotes by status
- `[vendor_id, status]` - Vendor's products/orders by status
- `[status, delivery_date]` - Upcoming deliveries
- `[category_id, is_active, price]` - Product browsing

### GIN Indexes (PostgreSQL)
- JSONB metadata columns
- Array columns (tags, certifications)
- Full-text search fields

## Data Types and Conventions

### Naming Conventions
- Tables: Plural, snake_case (e.g., `order_items`)
- Columns: Singular, snake_case (e.g., `user_id`)
- Foreign keys: `{model}_id` (e.g., `vendor_id`)
- Pivot tables: Alphabetical order (e.g., `buyer_vendor`)

### Common Column Types
- **IDs**: `bigIncrements()` for primary keys
- **Money**: `decimal(12, 2)` for currency values
- **Percentages**: `decimal(5, 2)` for rates
- **Status**: `enum()` with defined values
- **Metadata**: `jsonb()` for flexible data
- **Timestamps**: `timestamps()` for created_at/updated_at

### Soft Deletes
Applied to critical business entities:
- Users (admins, vendors, buyers)
- Orders and related records
- Financial records
- Products and categories

## Maintenance Queries

### Database Size Monitoring
```sql
-- Check table sizes
SELECT 
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
```

### Index Usage Statistics
```sql
-- Check index usage
SELECT 
    schemaname,
    tablename,
    indexname,
    idx_scan,
    idx_tup_read,
    idx_tup_fetch
FROM pg_stat_user_indexes
ORDER BY idx_scan DESC;
```

### Vacuum and Analyze
```sql
-- Regular maintenance
VACUUM ANALYZE;

-- Specific table
VACUUM ANALYZE products;
```

## Security Considerations

### Sensitive Data
- Passwords: Hashed using bcrypt
- Payment details: Partially masked, encrypted at rest
- Personal information: GDPR compliant storage
- API tokens: Stored encrypted

### Audit Trail
- All critical operations logged in `audit_logs`
- User actions tracked with IP and user agent
- Financial transactions fully auditable
- Data changes preserve old values

## Performance Tips

### Query Optimization
1. Use eager loading to prevent N+1 queries
2. Leverage composite indexes for multi-column searches
3. Use database views for complex repeated queries
4. Implement query result caching for static data

### Scaling Strategies
1. **Read Replicas**: For read-heavy operations
2. **Partitioning**: For large tables (orders, transactions)
3. **Archiving**: Move old data to archive tables
4. **Caching**: Redis for frequently accessed data

## Backup Strategy

### Recommended Backup Schedule
- **Daily**: Full database backup
- **Hourly**: Transaction log backups
- **Weekly**: Test restore procedures
- **Monthly**: Archive old backups

### Backup Commands
```bash
# PostgreSQL backup
pg_dump -h localhost -U username -d sydney_markets > backup.sql

# Compressed backup
pg_dump -h localhost -U username -d sydney_markets | gzip > backup.sql.gz

# Restore
psql -h localhost -U username -d sydney_markets < backup.sql
```

## Troubleshooting

### Common Issues

#### Migration Fails
- Check PostgreSQL extensions are installed
- Verify database user permissions
- Ensure foreign key references exist

#### Performance Issues
- Run `VACUUM ANALYZE` regularly
- Check for missing indexes using `EXPLAIN ANALYZE`
- Monitor slow query log

#### Data Integrity
- Use database transactions for multi-table operations
- Implement application-level validation
- Regular consistency checks

## Contact and Support
For questions or issues related to database migrations:
- Check Laravel logs: `storage/logs/laravel.log`
- Review PostgreSQL logs for database errors
- Run migration status: `php artisan migrate:status`

---
*Last Updated: January 2024*
*Database Version: 1.0.0*
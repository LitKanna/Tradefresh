# Storage Directory Structure

## Overview
The storage directory contains all generated files and data for the Laravel application.

## Directory Structure

```
storage/
├── app/                       ← Application storage
│   ├── public/               ← Publicly accessible files (symlinked to public/storage)
│   └── private/              ← Private application files
│
├── framework/                 ← Framework storage
│   ├── cache/                ← Cache files
│   │   └── data/            ← Cache data
│   ├── sessions/             ← Session files
│   ├── testing/              ← Testing cache
│   └── views/                ← Compiled Blade views
│
└── logs/                      ← Application logs
    ├── laravel.log           ← Main Laravel log
    └── browser.log           ← Browser/frontend logs
```

## Important Notes

### Permissions
All directories should be writable by the web server:
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Git Ignored Files
The following are ignored by Git:
- All files in `framework/cache/data/`
- All files in `framework/sessions/`
- All files in `framework/views/`
- All `.log` files in `logs/`
- All uploaded files in `app/`

### Maintenance Commands

#### Clear all caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### Clear logs
```bash
echo "" > storage/logs/laravel.log
echo "" > storage/logs/browser.log
```

#### Clear compiled views
```bash
php artisan view:clear
```

#### Clear sessions
```bash
rm -f storage/framework/sessions/*
```

### Log Rotation
Consider setting up log rotation for production:

1. **Laravel logs**: Configure in `config/logging.php`
   ```php
   'daily' => [
       'driver' => 'daily',
       'path' => storage_path('logs/laravel.log'),
       'level' => 'debug',
       'days' => 14,
   ],
   ```

2. **Browser logs**: Implement custom rotation or use logrotate

### Storage Links
For public file access, ensure the storage link is created:
```bash
php artisan storage:link
```

This creates: `public/storage → storage/app/public`

## File Upload Directories

### Product Images
`storage/app/public/products/`

### User Avatars
`storage/app/public/avatars/`

### Documents
`storage/app/private/documents/`

### Invoices
`storage/app/private/invoices/`

## Backup Strategy

### What to backup:
- `storage/app/` - All uploaded files
- `storage/logs/` - Logs for debugging (optional)

### What NOT to backup:
- `storage/framework/` - Can be regenerated
- Compiled views
- Cache files
- Session files

## Security Notes

1. **Never commit sensitive files** in storage
2. **Ensure proper permissions** (755 for directories, 644 for files)
3. **Keep private files private** - Don't symlink private directories
4. **Validate all uploads** before storing
5. **Scan for malware** in production environments
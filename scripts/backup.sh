#!/bin/bash

# Sydney Markets B2B - Automated Backup Script
# Runs daily to backup code and database

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="backups"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== Starting Sydney Markets Backup ===${NC}"
echo "Timestamp: $TIMESTAMP"

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# 1. Backup Database
echo -e "${YELLOW}Backing up database...${NC}"
if [ -f "database/database.sqlite" ]; then
    cp database/database.sqlite "$BACKUP_DIR/db-$TIMESTAMP.sqlite"
    echo -e "${GREEN}✓ Database backed up to $BACKUP_DIR/db-$TIMESTAMP.sqlite${NC}"
else
    echo -e "${RED}✗ Database file not found!${NC}"
fi

# 2. Backup .env file (if exists)
if [ -f ".env" ]; then
    cp .env "$BACKUP_DIR/.env-$TIMESTAMP"
    echo -e "${GREEN}✓ Environment file backed up${NC}"
fi

# 3. Create code snapshot
echo -e "${YELLOW}Creating code snapshot...${NC}"
git add .
git commit -m "Automated backup - $TIMESTAMP" --quiet
echo -e "${GREEN}✓ Code snapshot created${NC}"

# 4. Clean old backups (keep last 30 days)
echo -e "${YELLOW}Cleaning old backups...${NC}"
find $BACKUP_DIR -name "db-*.sqlite" -mtime +30 -delete
find $BACKUP_DIR -name ".env-*" -mtime +30 -delete
echo -e "${GREEN}✓ Old backups cleaned${NC}"

# 5. Show backup statistics
DB_COUNT=$(ls -1 $BACKUP_DIR/db-*.sqlite 2>/dev/null | wc -l)
TOTAL_SIZE=$(du -sh $BACKUP_DIR 2>/dev/null | cut -f1)

echo ""
echo -e "${GREEN}=== Backup Complete ===${NC}"
echo "Database backups: $DB_COUNT"
echo "Total backup size: $TOTAL_SIZE"
echo ""

# Log backup
echo "$TIMESTAMP - Backup completed" >> $BACKUP_DIR/backup.log
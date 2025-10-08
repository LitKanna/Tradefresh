# 🚨 CRITICAL: DASHBOARD FILE STRUCTURE - ABSOLUTE RULES 🚨

**LAST UPDATED:** 2025-09-23
**VERSION:** 3.0 - FINAL STRUCTURE (NO CHANGES ALLOWED)

## ⛔ ABSOLUTE RULES - ZERO TOLERANCE POLICY

### RULE #1: NEVER CREATE VARIANTS
- ❌ **BANNED FOREVER**: dashboard-v2, dashboard-working, dashboard-improved, dashboard-test, dashboard-backup
- ✅ **ONLY ALLOWED**: dashboard.blade.php (ONE FILE ONLY)
- **PENALTY**: Immediate termination of any Claude session that creates variants

### RULE #2: NEVER DELETE WITHOUT PERMISSION
- ❌ **NEVER DELETE** any dashboard file without explicit user permission
- ❌ **NEVER RENAME** dashboard files to "backup" or "old"
- ❌ **NEVER MOVE** dashboard files to different folders
- **REQUIRED**: Ask "May I delete/modify [specific file]?" and wait for "yes"

### RULE #3: ONE FILE PER PURPOSE
- Each dashboard component has EXACTLY ONE file
- If a file exists, UPDATE IT - don't create alternatives
- NO temporary files, NO working copies, NO test versions

---

## 📁 OFFICIAL DASHBOARD STRUCTURE - MEMORIZE THIS

### BUYER DASHBOARD - EXACT FILE LOCATIONS
```
✅ CONTROLLER: app/Livewire/Buyer/Dashboard.php
✅ VIEW:       resources/views/livewire/buyer/dashboard.blade.php (MAIN - 100KB+)
✅ WRAPPER:    resources/views/buyer/dashboard.blade.php (TINY - 5 lines)
```

### VENDOR DASHBOARD - EXACT FILE LOCATIONS
```
✅ CONTROLLER: app/Livewire/Vendor/Dashboard.php
✅ VIEW:       resources/views/livewire/vendor/dashboard.blade.php (MAIN - 13KB+)
✅ WRAPPER:    resources/views/vendor/dashboard.blade.php (TINY - 5 lines)
```

### HOW IT WORKS - UNDERSTAND THIS
1. User visits `/buyer/dashboard` or `/vendor/dashboard`
2. Route loads the WRAPPER file (5 lines) from `resources/views/[buyer|vendor]/`
3. Wrapper contains `<livewire:buyer.dashboard />` or `<livewire:vendor.dashboard />`
4. Livewire loads the MAIN file from `resources/views/livewire/[buyer|vendor]/`
5. Controller logic is in `app/Livewire/[Buyer|Vendor]/Dashboard.php`

---

## 🎯 HOW TO IDENTIFY FILES

### WRAPPER FILES (DON'T EDIT THESE)
- **Size**: ~100-1000 bytes (TINY)
- **Lines**: ~5-30 lines maximum
- **Content**: Just loads Livewire component
- **Location**: `resources/views/buyer/` or `resources/views/vendor/`
- **Purpose**: Entry point only

### MAIN DASHBOARD FILES (EDIT THESE)
- **Size**: 10KB-100KB+ (LARGE)
- **Lines**: 200-2000+ lines
- **Content**: Full HTML, components, JavaScript
- **Location**: `resources/views/livewire/buyer/` or `resources/views/livewire/vendor/`
- **Purpose**: Actual dashboard UI

### CONTROLLER FILES
- **Location**: `app/Livewire/Buyer/` or `app/Livewire/Vendor/`
- **Purpose**: Business logic, data handling
- **Name**: MUST be `Dashboard.php` (not Main.php, not anything else)

---

## ❌ FORBIDDEN ACTIONS - AUTOMATIC REJECTION

1. **Creating any file with these patterns:**
   - `*-v2.*`, `*-v3.*`, `*-version2.*`
   - `*-working.*`, `*-test.*`, `*-temp.*`
   - `*-backup.*`, `*-old.*`, `*-original.*`
   - `*-improved.*`, `*-better.*`, `*-fixed.*`

2. **Creating duplicate dashboard files in:**
   - Wrong folders
   - With different names
   - As "alternatives"

3. **Deleting or moving without permission:**
   - ANY dashboard file
   - ANY configuration file
   - ANY working component

---

## ✅ CORRECT WORKFLOW

### When Asked to Fix Dashboard:
1. **IDENTIFY**: Which dashboard? (buyer or vendor)
2. **LOCATE**: Find the EXACT file using the structure above
3. **READ**: Use Read tool on the correct file
4. **UPDATE**: Use Edit tool on the SAME file
5. **TEST**: Verify changes work
6. **NEVER**: Create a new file as alternative

### When Confused About Files:
1. **CHECK FILE SIZE**: Wrapper = tiny, Main = large
2. **CHECK LOCATION**: Livewire views are in `livewire/` subfolder
3. **CHECK CONTENT**: Wrappers just load component, Mains have full UI
4. **ASK USER**: "Which file should I edit?" if still unsure

---

## 📝 ENFORCEMENT CHECKLIST

Before ANY dashboard operation, verify:
- [ ] Am I editing the correct file? (Check structure above)
- [ ] Am I creating a variant? (STOP if yes)
- [ ] Do I have permission to delete? (ASK if needed)
- [ ] Is this the Livewire view or wrapper? (Edit Livewire view)
- [ ] Will this create confusion? (PREVENT if yes)

---

## 🔴 CRITICAL REMINDERS

1. **BUYER DASHBOARD MAIN FILE**: `resources/views/livewire/buyer/dashboard.blade.php`
2. **VENDOR DASHBOARD MAIN FILE**: `resources/views/livewire/vendor/dashboard.blade.php`
3. **NEVER CREATE ALTERNATIVES TO THESE FILES**
4. **ALWAYS UPDATE EXISTING FILES**
5. **ASK PERMISSION BEFORE DELETING ANYTHING**

---

## 📊 FILE SIZE REFERENCE

| File Type | Typical Size | Line Count |
|-----------|-------------|------------|
| Wrapper | 100-1000 bytes | 5-30 lines |
| Main Dashboard | 10KB-100KB+ | 200-2000+ lines |
| Controller | 2KB-10KB | 100-500 lines |

---

## 🚫 PERMANENT BAN LIST

These file names are PERMANENTLY BANNED:
- dashboard-working.blade.php
- dashboard-v2.blade.php
- dashboard-test.blade.php
- dashboard-backup.blade.php
- dashboard-original.blade.php
- dashboard-improved.blade.php
- dashboard-fixed.blade.php
- dashboard-new.blade.php
- dashboard-temp.blade.php

**IF YOU CREATE ANY OF THESE, YOU HAVE FAILED**

---

## ✅ THIS IS THE TRUTH

There are ONLY these dashboard files that matter:
1. `app/Livewire/Buyer/Dashboard.php` - Buyer controller
2. `app/Livewire/Vendor/Dashboard.php` - Vendor controller
3. `resources/views/livewire/buyer/dashboard.blade.php` - Buyer UI
4. `resources/views/livewire/vendor/dashboard.blade.php` - Vendor UI
5. `resources/views/buyer/dashboard.blade.php` - Buyer wrapper (don't edit)
6. `resources/views/vendor/dashboard.blade.php` - Vendor wrapper (don't edit)

**NOTHING ELSE. NO VARIATIONS. NO ALTERNATIVES.**

---

# END OF RULES - NO EXCEPTIONS ALLOWED
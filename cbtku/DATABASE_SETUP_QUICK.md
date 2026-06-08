# ⚡ DATABASE SETUP - Quick Guide

## 🎯 3 LANGKAH CEPAT (5 Menit)

### 1️⃣ CREATE DATABASE (1 menit)

**Via cPanel → MySQL Databases:**
- Create database: `update_server`
- Create user (atau gunakan existing)
- Add user to database (ALL PRIVILEGES)

---

### 2️⃣ IMPORT SCHEMA (2 menit)

**Via phpMyAdmin:**
1. Select database: `update_server`
2. Tab: SQL
3. Copy-paste isi file: `database_updates_FINAL.sql`
4. Click: Go

---

### 3️⃣ ACTIVATE SAMPLE (1 menit)

**Run query:**
```sql
UPDATE updates SET is_active = 1 WHERE version = '1.0.1';
```

---

## ✅ VERIFY

**Run query:**
```sql
SELECT version, title, is_active, file_size FROM updates;
```

**Expected:**
- version: 1.0.1
- is_active: 1
- file_size: 1000000

---

## 🧪 TEST API

**Via browser:**
```
https://updatecbt.sakasalika.com/api/check?current_version=1.0.0
```

**Expected:**
```json
{
    "has_update": true,
    "latest_version": "1.0.1",
    ...
}
```

---

## 📄 FILES

- `database_updates_FINAL.sql` - Schema lengkap + sample data
- `insert_sample_update.sql` - Insert data saja
- `SETUP_DATABASE_FINAL.md` - Panduan lengkap

---

**Status:** Ready! 🚀

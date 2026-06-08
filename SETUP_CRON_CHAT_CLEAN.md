# Setup Cron Job — Cleanup Pesan Chat Otomatis

## Perintah

```bash
php spark chat:clean
```

Menghapus pesan chat (kelas & staff) yang lebih dari **7 hari** beserta file attachment dan mention orphan.

---

## Linux/cPanel (Shared Hosting)

Tambahkan di crontab (`crontab -e`):

```bash
# Jalankan setiap hari jam 02:00 dini hari
0 2 * * * /usr/bin/php /www/wwwroot/sikap.mabes11.my.id/spark chat:clean >> /www/wwwroot/sikap.mabes11.my.id/writable/logs/chat_clean.log 2>&1
```

Di cPanel → Cron Jobs → tambahkan:
- **Minute**: 0
- **Hour**: 2
- **Day**: *
- **Month**: *
- **Weekday**: *
- **Command**: `/usr/bin/php /www/wwwroot/sikap.mabes11.my.id/spark chat:clean`

---

## Windows (XAMPP Development)

Gunakan **Task Scheduler**:
1. Buka Task Scheduler → Create Basic Task
2. Name: `SIAKAD Chat Cleanup`
3. Trigger: Daily, jam 02:00
4. Action: Start a program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\siakad\spark chat:clean`

---

## Mengubah Retensi

Edit konstanta di `app/Commands/CleanChatMessages.php`:

```php
const RETENTION_DAYS = 7;  // ubah sesuai kebutuhan
```

---

## Log

Hasil cleanup dicatat di:
- `writable/logs/log-YYYY-MM-DD.log` (CI4 log)
- Output cron job (jika diarahkan ke file)

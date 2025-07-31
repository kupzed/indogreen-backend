# File-Based Activity Logging System

Sistem pencatatan aktivitas berbasis file untuk backend Laravel. Sistem ini menyimpan semua aktivitas user dalam file JSON per user, bukan di database, untuk menghemat ruang database dan performa server.

## Fitur Utama

-   ✅ **File-based storage** - Aktivitas disimpan dalam file JSON per user
-   ✅ **Automatic logging** - Otomatis mencatat CRUD operations
-   ✅ **File rotation** - Otomatis memutar file saat ukuran melebihi batas
-   ✅ **Cleanup system** - Menghapus file lama secara otomatis
-   ✅ **Filtering & search** - Pencarian dan filter aktivitas
-   ✅ **Export functionality** - Export log ke JSON
-   ✅ **Statistics** - Statistik aktivitas
-   ✅ **Middleware support** - Middleware untuk mencatat aktivitas otomatis

## Struktur File

```
storage/app/activity-logs/
├── user_1/
│   ├── 2025-07-29.json
│   ├── 2025-07-28.json
│   └── 2025-07-27.json
├── user_2/
│   ├── 2025-07-29.json
│   └── 2025-07-28.json
└── ...
```

## Konfigurasi

### Service Configuration

File: `app/Services/ActivityLogService.php`

```php
protected $logPath = 'activity-logs';           // Path penyimpanan
protected $maxFileSize = 10485760;              // 10MB per file
protected $maxFilesPerUser = 100;               // Max 100 file per user
```

## Penggunaan

### 1. Otomatis Logging (Model)

Tambahkan trait `LogsActivity` ke model:

```php
use App\Models\Traits\LogsActivity;

class Project extends Model
{
    use HasFactory, LogsActivity;

    // Method untuk nama yang deskriptif
    public function getActivityNameAttribute()
    {
        return $this->name ?? 'Project #' . $this->id;
    }
}
```

### 2. Manual Logging

```php
use App\Services\ActivityLogService;

$service = new ActivityLogService();

// Log aktivitas
$service->log(
    'created',                    // action
    Project::class,              // model_type
    $project->id,                // model_id
    $project->name,              // model_name
    'Created new project',       // description
    null,                        // old_values
    $project->toArray()          // new_values
);
```

### 3. Helper Functions

```php
use App\Helpers\ActivityHelper;

// Log user action
ActivityHelper::logUserAction('login', 'User logged in');

// Log model action
ActivityHelper::logModelAction('view', $project, 'Viewed project details');

// Log export
ActivityHelper::logExport(Project::class, 'Exported projects to Excel');
```

## API Endpoints

### Get Activity Logs

```http
GET /api/activity-logs
GET /api/activity-logs?action=created&model_type=App\Models\Project
GET /api/activity-logs?date_from=2025-07-01&date_to=2025-07-31
GET /api/activity-logs?search=project
```

### Get Recent Activities

```http
GET /api/activity-logs/recent
```

### Get Statistics

```http
GET /api/activity-logs/stats
```

### Get Filter Options

```http
GET /api/activity-logs/filter-options
```

### Get Model Logs

```http
GET /api/activity-logs/App\Models\Project/123
```

### Export Logs

```http
GET /api/activity-logs/export?action=created&date_from=2025-07-01
```

### Delete User Logs

```http
DELETE /api/activity-logs
```

## Maintenance Commands

### Clean Old Logs

```bash
# Clean logs older than 30 days (default)
php artisan activity-logs:clean

# Clean logs older than 60 days
php artisan activity-logs:clean --days=60

# Clean logs for specific user
php artisan activity-logs:clean --user=1 --days=30
```

### Schedule Cleanup (Optional)

Tambahkan ke `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Clean logs older than 30 days every week
    $schedule->command('activity-logs:clean --days=30')->weekly();
}
```

## Middleware

Aktifkan middleware untuk mencatat aktivitas otomatis:

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        // ... other middleware
        \App\Http\Middleware\LogUserActivity::class,
    ],
];
```

## Response Format

### Activity Log Response

```json
{
    "success": true,
    "data": [
        {
            "id": "log_64f8a1b2c3d4e5f6",
            "user_id": 1,
            "user_name": "John Doe",
            "action": "created",
            "model_type": "App\\Models\\Project",
            "model_id": 123,
            "model_name": "Project ABC",
            "description": "Created new Project",
            "ip_address": "192.168.1.1",
            "user_agent": "Mozilla/5.0...",
            "timestamp": "2025-07-29T10:30:00.000000Z",
            "created_at": "2025-07-29T10:30:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    }
}
```

### Statistics Response

```json
{
    "success": true,
    "data": {
        "total_activities": 1250,
        "today_activities": 45,
        "this_week_activities": 234,
        "this_month_activities": 890,
        "actions_count": {
            "created": 450,
            "updated": 600,
            "deleted": 50,
            "viewed": 150
        },
        "models_count": {
            "App\\Models\\Project": 300,
            "App\\Models\\Activity": 500,
            "App\\Models\\Mitra": 450
        }
    }
}
```

## Keuntungan File-Based System

1. **Database Performance** - Tidak membebani database
2. **Scalability** - Mudah di-scale dengan storage terpisah
3. **Backup** - Mudah di-backup dan restore
4. **Archive** - Bisa di-archive ke storage lama
5. **Cost Effective** - Lebih murah dari database storage
6. **Flexibility** - Mudah dimodifikasi format dan struktur

## Monitoring & Maintenance

### File Size Monitoring

-   Setiap file maksimal 10MB
-   Otomatis rotate saat melebihi batas
-   Maksimal 100 file per user

### Cleanup Strategy

-   Hapus file lama secara berkala
-   Gunakan command `activity-logs:clean`
-   Schedule cleanup otomatis

### Storage Monitoring

```bash
# Check storage usage
du -sh storage/app/activity-logs/

# Check file count per user
find storage/app/activity-logs/ -type f | wc -l
```

## Troubleshooting

### File Permission Issues

```bash
# Set proper permissions
chmod -R 755 storage/app/activity-logs/
chown -R www-data:www-data storage/app/activity-logs/
```

### Storage Full

```bash
# Clean old logs immediately
php artisan activity-logs:clean --days=7

# Check disk usage
df -h
```

### Performance Issues

-   Kurangi `maxFileSize` jika file terlalu besar
-   Kurangi `maxFilesPerUser` jika terlalu banyak file
-   Implementasi caching untuk query yang sering

## Security Considerations

1. **File Access** - Pastikan file log tidak bisa diakses publik
2. **Data Privacy** - Hapus data sensitif sebelum logging
3. **Audit Trail** - Jangan hapus log yang diperlukan untuk audit
4. **Encryption** - Pertimbangkan enkripsi untuk data sensitif

## Migration dari Database

Jika sebelumnya menggunakan database:

1. Export data dari tabel `activity_logs`
2. Convert ke format file JSON
3. Place di struktur folder yang sesuai
4. Update kode untuk menggunakan service baru
5. Drop tabel lama setelah testing

## Support

Untuk pertanyaan atau masalah:

1. Check log Laravel di `storage/logs/laravel.log`
2. Verify file permissions
3. Check storage disk space
4. Review service configuration

# API Documentation - Project Management

## Project Endpoints

### 1. Get All Projects

**GET** `/api/projects`

**Query Parameters:**

-   `status` (optional): Filter by status (Ongoing, Prospect, Complete, Cancel)
-   `kategori` (optional): Filter by kategori
-   `customer_id` (optional): Filter by customer/mitra ID
-   `date_from` (optional): Filter by start date from
-   `date_to` (optional): Filter by start date to
-   `search` (optional): Search in name, description, location, PO, SO, or mitra name
-   `is_cert_projects` (optional): Filter by certificate project status (true/false)
-   `page` (optional): Page number for pagination

**Response:**

```json
{
    "message": "Projects retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "PLTS Jakarta 01",
            "description": "Solar panel installation project",
            "status": "Ongoing",
            "start_date": "2024-01-15",
            "finish_date": "2024-06-15",
            "mitra_id": 1,
            "mitra": {
                "id": 1,
                "nama": "PT Customer ABC",
                "email": "contact@customerabc.com",
                "phone": "021-1234567"
            },
            "kategori": "PLTS Hybrid",
            "lokasi": "Jakarta",
            "no_po": "PO-0001",
            "no_so": "SO-0001",
            "is_cert_projects": true,
            "cert_projects_label": "Certificate Project",
            "activities_count": 5,
            "certificates_count": 2,
            "created_at": "2024-01-15 10:30:00",
            "updated_at": "2024-01-15 10:30:00"
        }
    ],
    "pagination": {
        "total": 50,
        "per_page": 10,
        "current_page": 1,
        "last_page": 5,
        "from": 1,
        "to": 10
    }
}
```

### 2. Create New Project

**POST** `/api/projects`

**Request Body:**

```json
{
    "name": "PLTS Bandung 02",
    "description": "Solar panel installation for office building",
    "status": "Prospect",
    "start_date": "2024-02-01",
    "finish_date": "2024-07-01",
    "mitra_id": 2,
    "kategori": "PLTS Ongrid",
    "lokasi": "Bandung",
    "no_po": "PO-0002",
    "no_so": "SO-0002",
    "is_cert_projects": false
}
```

**Response:**

```json
{
    "message": "Project created successfully",
    "data": {
        "id": 2,
        "name": "PLTS Bandung 02",
        "description": "Solar panel installation for office building",
        "status": "Prospect",
        "start_date": "2024-02-01",
        "finish_date": "2024-07-01",
        "mitra_id": 2,
        "mitra": {
            "id": 2,
            "nama": "PT Customer XYZ",
            "email": "contact@customerxyz.com",
            "phone": "022-7654321"
        },
        "kategori": "PLTS Ongrid",
        "lokasi": "Bandung",
        "no_po": "PO-0002",
        "no_so": "SO-0002",
        "is_cert_projects": false,
        "cert_projects_label": "Regular Project",
        "activities_count": null,
        "certificates_count": null,
        "created_at": "2024-01-15 11:00:00",
        "updated_at": "2024-01-15 11:00:00"
    }
}
```

### 3. Get Project Details

**GET** `/api/projects/{id}`

**Query Parameters:**

-   `jenis` (optional): Filter activities by jenis
-   `kategori` (optional): Filter activities by kategori
-   `date_from` (optional): Filter activities by date from
-   `date_to` (optional): Filter activities by date to
-   `search` (optional): Search in activity name, description, or project name

**Response:**

```json
{
    "message": "Project details retrieved successfully",
    "data": {
        "project": {
            "id": 1,
            "name": "PLTS Jakarta 01",
            "description": "Solar panel installation project",
            "status": "Ongoing",
            "start_date": "2024-01-15",
            "finish_date": "2024-06-15",
            "mitra_id": 1,
            "mitra": {
                "id": 1,
                "nama": "PT Customer ABC",
                "email": "contact@customerabc.com",
                "phone": "021-1234567"
            },
            "kategori": "PLTS Hybrid",
            "lokasi": "Jakarta",
            "no_po": "PO-0001",
            "no_so": "SO-0001",
            "is_cert_projects": true,
            "cert_projects_label": "Certificate Project",
            "activities_count": 5,
            "certificates_count": 2,
            "created_at": "2024-01-15 10:30:00",
            "updated_at": "2024-01-15 10:30:00"
        },
        "activities": [...],
        "activity_pagination": {...},
        "kategori_list": [...],
        "project_kategori_list": [...]
    }
}
```

### 4. Update Project

**PUT** `/api/projects/{id}`

**Request Body:** Same as Create Project

**Response:** Same as Create Project

### 5. Delete Project

**DELETE** `/api/projects/{id}`

**Response:**

```json
{
    "message": "Project deleted successfully"
}
```

### 6. Toggle Certificate Project Status

**PATCH** `/api/projects/{id}/toggle-cert`

**Response:**

```json
{
    "message": "Certificate project status toggled successfully",
    "data": {
        "id": 1,
        "name": "PLTS Jakarta 01",
        "is_cert_projects": true,
        "cert_projects_label": "Certificate Project",
        ...
    }
}
```

### 7. Get Certificate Projects Only

**GET** `/api/projects/certificate/list`

**Query Parameters:** Same as Get All Projects

**Response:** Same as Get All Projects (but only certificate projects)

### 8. Get Customers for Project

**GET** `/api/projects/customers`

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "nama": "PT Customer ABC"
        },
        {
            "id": 2,
            "nama": "PT Customer XYZ"
        }
    ]
}
```

## Field Descriptions

### Project Fields

-   `id`: Unique identifier
-   `name`: Project name
-   `description`: Project description
-   `status`: Project status (Ongoing, Prospect, Complete, Cancel)
-   `start_date`: Project start date (YYYY-MM-DD)
-   `finish_date`: Project finish date (YYYY-MM-DD, nullable)
-   `mitra_id`: Partner/Mitra ID (foreign key to partners table)
-   `mitra`: Related partner data (when loaded)
-   `kategori`: Project category
-   `lokasi`: Project location
-   `no_po`: Purchase Order number
-   `no_so`: Sales Order number
-   `is_cert_projects`: Boolean indicating if this is a certificate project
-   `cert_projects_label`: Human-readable label for certificate project status
-   `activities_count`: Number of related activities (when counted)
-   `certificates_count`: Number of related certificates (when counted)
-   `created_at`: Creation timestamp
-   `updated_at`: Last update timestamp

### Project Categories

-   PLTS Hybrid
-   PLTS Ongrid
-   PLTS Offgrid
-   PJUTS All In One
-   PJUTS Two In One
-   PJUTS Konvensional

### Project Statuses

-   Ongoing
-   Prospect
-   Complete
-   Cancel

## Error Responses

### Validation Error (422)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "name": ["Nama project harus diisi"],
        "mitra_id": ["Mitra yang dipilih tidak ditemukan"]
    }
}
```

### Not Found Error (404)

```json
{
    "message": "Project not found"
}
```

### Unauthorized Error (401)

```json
{
    "message": "Unauthenticated"
}
```

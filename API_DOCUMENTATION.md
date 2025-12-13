# CyberCon Database API System

## Overview
Complete database system with REST API endpoints for storing and retrieving registration data.

## Database Setup ✓

### SQLite (Default - Easier Setup)
- **Location**: `database/cybercon.db`
- **No installation required** - Works out of the box with PHP
- **Already configured and running**

### MySQL (Alternative)
To switch to MySQL:
1. Edit `config/database.php`
2. Set `private $useSQLite = false;`
3. Update MySQL credentials
4. Run: `mysql -u root -p < database/schema.sql`

## API Endpoints

### 1. Create Registration (POST)
**Endpoint**: `POST /api/registrations.php`

**Headers**:
```
Content-Type: application/json
```

**Request Body**:
```json
{
    "fullName": "John Doe",
    "studentId": "C211001",
    "email": "john@cuet.ac.bd",
    "phone": "+8801712345678",
    "university": "CUET",
    "ticketType": "Early Bird",
    "paymentMethod": "Bkash",
    "paymentNumber": "01712345678",
    "transactionId": "ABC123XYZ"
}
```

**Success Response** (201):
```json
{
    "success": true,
    "message": "Registration successful",
    "timestamp": "2025-12-11 15:40:31",
    "data": {
        "id": 1,
        "ticket_id": "CC-2025-TUOD",
        "full_name": "John Doe",
        "student_id": "C211001",
        "email": "john@cuet.ac.bd",
        "phone": "+8801712345678",
        "university": "CUET",
        "ticket_type": "Early Bird",
        "payment_method": "Bkash",
        "payment_number": "01712345678",
        "transaction_id": "ABC123XYZ",
        "registration_date": "2025-12-11 15:40:31",
        "status": "pending",
        "created_at": "2025-12-11 15:40:31",
        "updated_at": "2025-12-11 15:40:31"
    }
}
```

**Error Responses**:
- `400`: Missing required fields or invalid data
- `409`: Email already registered
- `500`: Database error

### 2. Get All Registrations (GET)
**Endpoint**: `GET /api/registrations.php`

**Response**:
```json
{
    "success": true,
    "message": "Registrations fetched successfully",
    "timestamp": "2025-12-11 15:40:43",
    "data": {
        "registrations": [
            {
                "id": 1,
                "ticket_id": "CC-2025-TUOD",
                "full_name": "John Doe",
                ...
            }
        ],
        "pagination": {
            "page": 1,
            "limit": 50,
            "total": 1,
            "total_pages": 1
        }
    }
}
```

### 3. Get Registration by Ticket ID
**Endpoint**: `GET /api/registrations.php?id={ticket_id}`

**Example**: `GET /api/registrations.php?id=CC-2025-TUOD`

### 4. Filter Registrations

**By Email**:
`GET /api/registrations.php?email=test@cuet.ac.bd`

**By University**:
`GET /api/registrations.php?university=CUET`

**By Status**:
`GET /api/registrations.php?status=pending`

**By Date Range**:
`GET /api/registrations.php?from_date=2025-01-01&to_date=2025-12-31`

**With Pagination**:
`GET /api/registrations.php?page=1&limit=10`

## Testing

### Using cURL

**Create Registration**:
```bash
curl -X POST http://localhost:8000/api/registrations.php \
  -H "Content-Type: application/json" \
  -d '{
    "fullName": "Test User",
    "studentId": "C211001",
    "email": "test@cuet.ac.bd",
    "phone": "+8801712345678",
    "university": "CUET",
    "ticketType": "Early Bird",
    "paymentMethod": "Bkash",
    "paymentNumber": "01712345678",
    "transactionId": "TEST123"
  }'
```

**Get All Registrations**:
```bash
curl http://localhost:8000/api/registrations.php
```

**Get by Ticket ID**:
```bash
curl "http://localhost:8000/api/registrations.php?id=CC-2025-TUOD"
```

**Get by Email**:
```bash
curl "http://localhost:8000/api/registrations.php?email=test@cuet.ac.bd"
```

### Using JavaScript (Frontend)

```javascript
// Create registration
fetch('/api/registrations.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        fullName: 'John Doe',
        studentId: 'C211001',
        email: 'john@cuet.ac.bd',
        phone: '+8801712345678',
        university: 'CUET',
        ticketType: 'Early Bird',
        paymentMethod: 'Bkash',
        paymentNumber: '01712345678',
        transactionId: 'ABC123'
    })
})
.then(response => response.json())
.then(data => console.log(data));

// Get all registrations
fetch('/api/registrations.php')
    .then(response => response.json())
    .then(data => console.log(data));

// Get by ticket ID
fetch('/api/registrations.php?id=CC-2025-TUOD')
    .then(response => response.json())
    .then(data => console.log(data));
```

## Files Structure

```
CyberCon/
├── config/
│   └── database.php          # Database connection class (SQLite/MySQL)
├── database/
│   ├── cybercon.db           # SQLite database file
│   ├── schema.sql            # MySQL schema (if using MySQL)
│   └── README.md             # Database documentation
├── api/
│   └── registrations.php     # REST API endpoints
├── assets/
│   └── js/
│       └── script.js         # Updated to use API
└── setup-database.php        # Database setup script
```

## Features

✓ **RESTful API** - Standard HTTP methods (GET, POST)
✓ **JSON Responses** - All responses in JSON format
✓ **Validation** - Email domain validation, required fields
✓ **Error Handling** - Proper HTTP status codes
✓ **Filtering** - Multiple filter options
✓ **Pagination** - Limit results per page
✓ **Unique Ticket IDs** - Auto-generated format: CC-YYYY-XXXX
✓ **Timestamps** - Created and updated timestamps
✓ **CORS Support** - Cross-origin requests allowed
✓ **SQLite/MySQL** - Choose your database

## Allowed University Domains

- cuet.ac.bd
- aust.edu
- du.ac.bd
- buet.ac.bd
- nsu.edu.bd

## Testing Results ✓

All endpoints tested and working:
- ✓ POST: Create registration - Success
- ✓ GET: Fetch all registrations - Success
- ✓ GET: Fetch by ticket ID - Success
- ✓ GET: Filter by email - Success

## Next Steps

1. **Frontend Integration**: Registration form now submits to `/api/registrations.php`
2. **View Database**: Use SQLite browser or phpMyAdmin
3. **Add More Endpoints**: Update, delete registrations
4. **Admin Panel**: Create admin interface to view registrations

## Support

For issues or questions, check:
- Database connection: `config/database.php`
- API logic: `api/registrations.php`
- Database file: `database/cybercon.db`

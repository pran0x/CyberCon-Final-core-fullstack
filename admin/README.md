# CyberCon Admin Panel

## Login Credentials
- **Username**: `pran0x`
- **Password**: `pranxten`

## Access URL
http://localhost:8000/admin/login.php

## Features

### 1. **Authentication System**
- Secure login with session management
- Auto-logout after 2 hours of inactivity
- Protected admin pages

### 2. **Dashboard** (`dashboard.php`)
- Real-time statistics:
  - Total registrations
  - Pending registrations
  - Confirmed registrations
  - Cancelled registrations
- Recent registrations table
- Search and filter functionality:
  - Search by name, email, ticket ID, student ID
  - Filter by status
  - Filter by university
- Pagination (20 records per page)

### 3. **View Registration** (`view.php`)
- Detailed view of registration information
- Personal details
- Payment information
- Update status (Pending/Confirmed/Cancelled)
- Edit registration details
- Print functionality
- Timestamps (registration, created, updated)

### 4. **Edit Registration** (`edit.php`)
- Full registration editing capability
- Update all fields:
  - Personal information
  - Contact details
  - University information
  - Payment details
  - Status
- Form validation

### 5. **Export Data** (`export.php`)
- Export all registrations to CSV
- Excel-compatible format
- Includes all fields
- Filename with timestamp

## File Structure

```
admin/
├── login.php           # Login page
├── auth.php            # Authentication checker
├── logout.php          # Logout handler
├── dashboard.php       # Main dashboard
├── view.php            # View registration details
├── edit.php            # Edit registration
├── registrations.php   # Registrations list (redirects to dashboard)
├── export.php          # CSV export
└── style.css           # Admin panel styles
```

## Security Features

- ✓ Session-based authentication
- ✓ Protected pages (redirect to login if not authenticated)
- ✓ Session timeout (2 hours)
- ✓ SQL injection prevention (prepared statements)
- ✓ XSS prevention (htmlspecialchars)

## Database Integration

The admin panel connects to the same database as the registration API:
- **Database**: SQLite (`database/cybercon.db`)
- **Table**: `registrations`
- **Real-time sync**: All changes reflect immediately

## Usage Guide

### Login
1. Go to http://localhost:8000/admin/login.php
2. Enter username: `pran0x`
3. Enter password: `pranxten`
4. Click "Login to Dashboard"

### View Statistics
- Dashboard shows real-time counts of all registrations
- Color-coded cards for different statuses

### Search & Filter
- Use search box to find registrations by:
  - Name
  - Email
  - Ticket ID
  - Student ID
- Filter by status (Pending/Confirmed/Cancelled)
- Filter by university

### Manage Registrations
1. Click "View" icon (eye) to see full details
2. Click "Edit" icon (pencil) to modify registration
3. Update status directly from view page
4. Save changes

### Export Data
1. Click "Export Data" in sidebar
2. CSV file downloads automatically
3. Open in Excel or Google Sheets

## Design Features

- Modern, clean interface
- Responsive design (mobile-friendly)
- Dark sidebar with gradient
- Color-coded status badges
- Smooth animations and transitions
- Professional typography
- Intuitive navigation

## Status Colors

- **Pending**: Yellow/Warning
- **Confirmed**: Green/Success
- **Cancelled**: Red/Danger

## Quick Actions

From Dashboard:
- View registration details
- Edit registration
- Search/filter registrations
- Export to CSV
- Logout

From View Page:
- Update status
- Edit details
- Print details
- Back to dashboard

## Notes

- Session expires after 2 hours of inactivity
- All timestamps are displayed in local server time
- CSV export includes all fields
- Changes are saved immediately to database
- Pagination shows 20 records per page

## Troubleshooting

**Can't login?**
- Verify username: `pran0x`
- Verify password: `pranxten`
- Check if session is supported in PHP

**No registrations showing?**
- Check database connection in `config/database.php`
- Verify database file exists at `database/cybercon.db`
- Run test registration through website

**Export not working?**
- Ensure write permissions
- Check PHP output buffering settings

## Future Enhancements

Possible additions:
- Dashboard charts/graphs
- Email notifications
- Bulk actions (approve/reject multiple)
- User management (multiple admins)
- Activity logs
- Advanced filtering options
- Registration statistics by date

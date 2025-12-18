# 🐳 Docker Quick Start Guide

## Running CyberCon with Docker

### Start the Application
```bash
docker-compose up -d --build
```

### Access the Application
- **Website**: http://localhost:6565
- **Admin Panel**: http://localhost:6565/admin/

### Useful Commands

**View logs:**
```bash
docker-compose logs -f
```

**Stop the application:**
```bash
docker-compose down
```

**Restart the application:**
```bash
docker-compose restart
```

**Rebuild after code changes:**
```bash
docker-compose up -d --build
```

**Access container shell:**
```bash
docker exec -it cybercon-app bash
```

### Features Included
✅ PHP 8.1 with Apache  
✅ SQLite database (pre-configured)  
✅ All PHP extensions installed  
✅ Persistent data volumes  
✅ Proper file permissions  
✅ Port 6565 configured  

### Troubleshooting

**Port already in use:**
Edit `docker-compose.yml` and change port 6565 to another port.

**Permission errors:**
```bash
chmod -R 777 database uploads admin/sent_emails
```

For more details, see `docker/README.md`

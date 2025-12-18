# CyberCon Docker Setup

This directory contains Docker configuration for the CyberCon application.

## Quick Start

### Prerequisites
- Docker installed on your system
- Docker Compose installed

### Running the Application

1. **Build and start the container:**
   ```bash
   docker-compose up -d --build
   ```

2. **Access the application:**
   - Main website: http://localhost:6565
   - Admin panel: http://localhost:6565/admin/

3. **View logs:**
   ```bash
   docker-compose logs -f
   ```

4. **Stop the container:**
   ```bash
   docker-compose down
   ```

## Default Admin Credentials

Check your database or application documentation for default admin credentials.

## File Structure

- `Dockerfile` - Main Docker image configuration
- `docker-compose.yml` - Docker Compose orchestration
- `docker/apache-config.conf` - Apache virtual host configuration
- `docker/docker-entrypoint.sh` - Container initialization script

## Persistent Data

The following directories are mounted as volumes to persist data:
- `./database` - SQLite database files
- `./uploads` - User uploaded files
- `./admin/sent_emails` - Email history

## Troubleshooting

### Permission Issues
If you encounter permission errors:
```bash
chmod -R 777 database uploads admin/sent_emails
```

### Rebuild Container
If you make changes to PHP code or configuration:
```bash
docker-compose down
docker-compose up -d --build
```

### Access Container Shell
To debug issues inside the container:
```bash
docker exec -it cybercon-app bash
```

### View Apache Logs
```bash
docker exec cybercon-app tail -f /var/log/apache2/error.log
```

## Port Configuration

The application runs on port **6565** as configured in `docker-compose.yml`. To change this, edit the port mapping in `docker-compose.yml`:

```yaml
ports:
  - "YOUR_PORT:80"
```

## Environment Variables

You can add environment variables in `docker-compose.yml` under the `environment` section.

## Production Deployment

For production deployment, consider:
1. Using environment-specific configuration files
2. Setting up proper SSL/TLS certificates
3. Configuring a reverse proxy (nginx)
4. Using Docker secrets for sensitive data
5. Setting up proper backup procedures for the database

# Rebuilding Development Environment After System Updates

This guide provides instructions for rebuilding your Docker, Lando, and Composer environments after system updates (such as Linux kernel updates or Docker version changes).

## Docker Cleanup

When system updates affect Docker, you may need to completely rebuild your Docker environment. Here's how to do it:

### Basic Docker Cleanup

First, Lando will need to be destroyed. Lando will later be rebuilt.

```bash
lando poweroff
lando destroy -y
```

Next, Docker will need to be cleaned up.
```bash
# Stop all running containers
docker stop $(docker ps -a -q)

# Remove all stopped containers
docker rm $(docker ps -a -q)
```

### Complete Docker Cleanup

For a more thorough cleanup (especially after major system updates):

```bash
# Complete cleanup in one command - removes containers, networks, images, and volumes
docker system prune -a --volumes -f
```

This will reclaim significant disk space (often 10+ GB) and remove:
- All stopped containers
- All networks not used by at least one container
- All dangling and unused images
- All build cache
- All volumes not used by at least one container

### Restart Docker Service

After cleanup, restart the Docker service:

```bash
sudo systemctl restart docker
```

## Rebuilding Lando Environment

After Docker is cleaned up and restarted, follow these steps to properly rebuild your Lando environment without accumulating disk space:

1. First, ensure you're in your project directory and stop any running Lando instances:
```bash
cd /path/to/your/project
lando poweroff  # or 'lando shutdown' - both work the same way
```

2. Remove any existing Lando files and caches:
```bash
# Remove Lando's project-specific files
rm -rf .lando.local.yml .lando.json .lando/

# Clear Lando's global cache
lando --clear
```

3. Rebuild with a clean slate:
```bash
# Start with a fresh rebuild
lando rebuild -y

# If rebuild fails, try with a more aggressive cleanup
lando rebuild --clean -y
```

4. After successful rebuild, clean up any remaining resources:
```bash
# Prune unused Docker resources
docker system prune -f

# Remove any dangling images
docker image prune -f

# Clean up the Lando build cache
docker builder prune -f
```

5. Verify everything is working:
```bash
# Check application status
lando info

# List all running Lando apps and containers
lando list
```

### Preventing Future Disk Bloat

To avoid disk space issues with frequent rebuilds:

1. Always use `lando rebuild` instead of `lando start` when making configuration changes
2. Regularly clean up unused Docker resources:
```bash
# Weekly maintenance
docker system prune -f
lando poweroff
```
3. Consider adding these to your shell's profile for convenience:
```bash
alias lando-clean="docker system prune -f && lando poweroff"
alias lando-fresh="lando-clean && lando rebuild -y"

# Rebuild the existing Lando app
lando rebuild -y

>Note: When you run 'lando rebuild', it will download all packages
and rebuild everything from scratch. This process can take several minutes.

# if lando rebuild didn't start it:
lando start
```

Wait for Lando to complete the startup process. You should see successful connection messages for all services.

## Install WordPress site data from MySQL file

```bash
# Navigate to your WordPress directory
cd /path/to/wordpress

# Install WordPress site data from MySQL file
lando db-import /app/path/to/wordpress.sql
# lando db-import /app/lc-database-2025-04-19.sql

```

## Rebuilding Composer Environment

After Lando is running properly, rebuild your Composer environment:

```bash
# Navigate to your plugin development directory
# (where the composer.json file is located)
# Not your WordPress directory
cd /path/to/yourplugin

# Clear Composer cache (optional but recommended after system updates)
composer clear-cache

# Reinstall all dependencies
composer install

# Update dependencies to latest versions (recommended)
composer update

# Regenerate autoloader
composer dump-autoload

# Sync plugin files to WordPress
composer sync:wp
```

## Rebuilding WordPress Test Environment

Finally, set up the WordPress test environment:

```bash
# Run the setup script
php bin/setup-plugin-tests.php
```

## Troubleshooting

### Lando Connection Issues

If you see errors like:
```
⚠ APPSERVER URLS
  ✖ connect ECONNREFUSED 127.0.0.1:8080
  ✖ Request failed with status code 500
```

Try these additional steps:
1. Check if Docker is running: `systemctl status docker`
2. Try restarting Lando: `lando restart`
3. Check for port conflicts: `sudo lsof -i :8080`
4. Rebuild the Lando app: `lando rebuild -y`

### Composer Issues

If Composer has issues after updates:
1. Clear Composer cache: `composer clear-cache`
2. Update Composer itself: `composer self-update`
3. Remove vendor directory and reinstall: `rm -rf vendor && composer install`

## Complete Rebuild Sequence

For convenience, here's the complete sequence of commands for a full rebuild:

```bash
# Docker cleanup
docker stop $(docker ps -a -q)
docker system prune -a --volumes -f
sudo systemctl restart docker

# Lando rebuild
cd /path/to/wordpress
lando destroy -y
lando start

# Composer rebuild
cd /path/to/phpunit-testing
composer install
composer sync:wp

# WordPress test environment setup
php bin/setup-plugin-tests.php
```

This sequence should resolve most issues that occur after system updates affecting Docker, Lando, or PHP.

# Environment Configuration Guide

## Overview

AliveChMS supports environment-aware security settings that automatically adjust based on whether you're running in development or production.

## Environment Settings

### Development Environment (Default)

- **HTTPS**: Not enforced (allows HTTP for local development)
- **HSTS**: Disabled (won't interfere with local development)
- **CSP**: Relaxed (no upgrade-insecure-requests directive)
- **Error Display**: Enabled for debugging

### Production Environment

- **HTTPS**: Automatically enforced (all HTTP redirected to HTTPS)
- **HSTS**: Enabled with 1-year max-age
- **CSP**: Strict with upgrade-insecure-requests
- **Error Display**: Disabled for security

## How to Switch Environments

### For Development (Current Setting)

In your `.env` file:

```env
APP_ENV=development
APP_DEBUG=true
```

### For Production Deployment

In your `.env` file:

```env
APP_ENV=production
APP_DEBUG=false
```

## Security Features by Environment

| Feature                       | Development | Production  |
| ----------------------------- | ----------- | ----------- |
| HTTPS Redirect                | ❌ Disabled | ✅ Enabled  |
| HSTS Header                   | ❌ Disabled | ✅ Enabled  |
| CSP upgrade-insecure-requests | ❌ Disabled | ✅ Enabled  |
| Error Display                 | ✅ Enabled  | ❌ Disabled |
| Debug Mode                    | ✅ Enabled  | ❌ Disabled |

## Testing HTTPS Enforcement

### To Test Production Security Locally:

1. Temporarily change `.env`:
   ```env
   APP_ENV=production
   ```
2. Access your local site via HTTP
3. You should be redirected to HTTPS
4. Change back to `development` when done testing

### Production Deployment Checklist:

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Ensure SSL certificate is installed
- [ ] Test HTTPS redirect works
- [ ] Verify HSTS header is present
- [ ] Check CSP headers are correct

## Benefits of Environment-Aware Security

✅ **Development Friendly**: No HTTPS issues during local development
✅ **Production Secure**: Automatic HTTPS enforcement in production
✅ **Easy Deployment**: Just change one environment variable
✅ **No Code Changes**: Security settings adjust automatically

## Troubleshooting

### Local Development Issues:

- If you see HTTPS redirects locally, check `APP_ENV=development` in `.env`
- Clear browser cache if HSTS was previously set

### Production Issues:

- If HTTPS isn't enforcing, verify `APP_ENV=production` in `.env`
- Check that Apache mod_rewrite and mod_headers are enabled
- Ensure SSL certificate is properly configured

## Manual Override (Advanced)

If you need to manually control HTTPS enforcement, you can modify the `.htaccess` file directly, but using the environment variable approach is recommended for maintainability.

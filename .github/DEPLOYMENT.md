# GitHub Actions Deployment Guide

This document explains how to set up and use the automated deployment workflows for the Email Domain Restriction plugin.

## Overview

The plugin uses GitHub Actions to automatically deploy to both:
- **Freemius** (PRO version with all features)
- **WordPress.org** (FREE version with PRO features stripped)

A single tag push triggers both deployments simultaneously.

---

## Required GitHub Secrets

Before deploying, configure these secrets in your GitHub repository:

**Settings → Secrets and variables → Actions → New repository secret**

### Freemius Secrets

| Secret Name | Description | Where to Find |
|-------------|-------------|---------------|
| `FREEMIUS_PUBLIC_KEY` | Your Freemius public key | Freemius Dashboard → Products → Your Plugin → Keys |
| `FREEMIUS_SECRET_KEY` | Your Freemius secret key | Freemius Dashboard → Products → Your Plugin → Keys |
| `FREEMIUS_DEV_ID` | Your Freemius developer ID | Freemius Dashboard → Profile → Developer ID |

**Current Values (reference only - add to GitHub Secrets):**
- Plugin ID: `21633`
- Plugin Slug: `email-domain-restriction`
- Public Key: `pk_03e8185f96740ac7102635d05644c`

### WordPress.org Secrets

| Secret Name | Description | Where to Find |
|-------------|-------------|---------------|
| `WP_SVN_REPOSITORY_URL` | WordPress.org SVN repository URL | https://plugins.svn.wordpress.org/email-domain-restriction |
| `WP_SVN_USERNAME` | Your WordPress.org username | Your WordPress.org account |
| `SVN_PASSWORD` | Your WordPress.org password | Your WordPress.org account (or SVN password) |

---

## Deployment Workflow

### 1. **Prepare for Release**

Ensure your code is ready:
- [ ] All features tested and working
- [ ] Version number updated in main plugin file (`email-domain-restriction.php`)
- [ ] `readme.txt` updated with new version and changelog
- [ ] All changes committed and pushed to `main` branch

### 2. **Create Version Tag**

```bash
# Create and push version tag (triggers deployment)
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

**Tag Format:** `v[MAJOR].[MINOR].[PATCH]`
- Examples: `v1.0.0`, `v1.2.5`, `v2.0.0`
- Must start with `v` followed by semantic version

### 3. **Automatic Deployment Process**

Once the tag is pushed, GitHub Actions automatically:

1. **Validates** version format
2. **Builds** plugin package (excludes dev files)
3. **Deploys to Freemius** - Full PRO version with all features
4. **Deploys to WordPress.org** - FREE version (PRO features stripped)
5. **Creates GitHub Release** - With changelog and ZIP file

### 4. **Monitor Deployment**

View progress:
- GitHub → Actions tab
- Check each workflow job (validate, build, deploy-freemius, deploy-wordpress, create-release)
- Review logs if any failures occur

---

## Deployment Workflow Details

### Main Workflow (`deploy.yml`)

**Triggered by:** Tag push matching `v[0-9]+.[0-9]+.[0-9]+`

**Jobs:**
1. **Validate**: Checks version format
2. **Build**: Creates plugin ZIP package
3. **Deploy to Freemius**: Uploads PRO version to Freemius
4. **Deploy to WordPress.org**: Uploads FREE version to WordPress.org SVN
5. **Create Release**: Creates GitHub release with changelog

### What Gets Deployed

**Freemius (PRO Version):**
- All plugin files
- Freemius SDK (`vendor/freemius/`)
- PRO features (`includes/pro/`, `admin/pro/`)
- WooCommerce & BuddyPress integrations
- Email validation, analytics, webhooks

**WordPress.org (FREE Version):**
- Core plugin files
- Basic domain whitelisting
- Ultimate Member support (basic)
- Analytics (basic)
- ❌ No Freemius SDK
- ❌ No PRO features
- ❌ No WooCommerce/BuddyPress integrations

### Excluded from Both Builds

See `.distignore` for full list:
- `.git`, `.github/`
- `tests/`, `node_modules/`
- `deploy/` directory
- Documentation files (`.md` files)
- IDE files (`.vscode/`, `.idea/`)

---

## Version Management

### Updating Version Numbers

Update version in these files before tagging:

1. **`email-domain-restriction.php`** (main plugin file):
```php
* Version: 1.0.0
```

2. **`readme.txt`** (WordPress.org):
```
Stable tag: 1.0.0
```

3. **`package.json`** (if using npm):
```json
"version": "1.0.0"
```

### Changelog

Update changelog in:
- `readme.txt` (WordPress.org format)
- `CHANGELOG.md` (GitHub format)

---

## Troubleshooting

### Deployment Fails

**Check:**
1. GitHub Secrets are configured correctly
2. Version format is correct (`v1.0.0` not `1.0.0`)
3. Version numbers match in plugin file and readme.txt
4. No syntax errors in code
5. Freemius credentials are valid
6. WordPress.org SVN credentials are correct

### Freemius Deploy Fails

**Common Issues:**
- Invalid `FREEMIUS_SECRET_KEY`
- Wrong `FREEMIUS_DEV_ID`
- Plugin ID mismatch
- Version already exists on Freemius

### WordPress.org Deploy Fails

**Common Issues:**
- Invalid SVN credentials
- SVN repository URL incorrect
- Version already exists on WordPress.org
- readme.txt validation errors

### View Logs

GitHub → Actions → Click failed workflow → View job logs

---

## Manual Deployment

### Freemius (Manual)

1. Build ZIP file:
```bash
zip -r email-domain-restriction-pro-1.0.0.zip . -x "*.git*" "*.github*" "*node_modules*" "*tests*"
```

2. Upload to Freemius Dashboard:
- Freemius → Products → Email Domain Restriction → Deployment → Upload ZIP

### WordPress.org (Manual)

1. Checkout SVN repository:
```bash
svn co https://plugins.svn.wordpress.org/email-domain-restriction
cd email-domain-restriction
```

2. Copy files to trunk:
```bash
cp -r /path/to/plugin/* trunk/
```

3. Remove PRO features:
```bash
rm -rf trunk/vendor/freemius trunk/includes/pro trunk/admin/pro
```

4. Commit to SVN:
```bash
svn add trunk/*
svn ci -m "Update to version 1.0.0"
```

5. Tag release:
```bash
svn cp trunk tags/1.0.0
svn ci -m "Tagging version 1.0.0"
```

---

## Release Checklist

Before creating a release tag:

- [ ] All code changes committed
- [ ] Tests passing
- [ ] Version numbers updated (plugin file, readme.txt)
- [ ] Changelog updated
- [ ] Screenshots updated (if UI changed)
- [ ] Documentation updated
- [ ] GitHub Secrets configured
- [ ] Freemius product configured
- [ ] WordPress.org plugin approved (first release only)

After tagging:

- [ ] Monitor GitHub Actions workflow
- [ ] Verify Freemius deployment
- [ ] Verify WordPress.org deployment
- [ ] Test PRO version download from Freemius
- [ ] Test FREE version download from WordPress.org
- [ ] Update website/documentation

---

## Additional Resources

- [Freemius Documentation](https://freemius.com/help/documentation/)
- [WordPress.org Plugin Handbook](https://developer.wordpress.org/plugins/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Semantic Versioning](https://semver.org/)

---

## Support

For issues with deployment:
- GitHub Actions: Check workflow logs
- Freemius: Contact Freemius support
- WordPress.org: Check WordPress.org forums

For plugin issues:
- GitHub Issues: https://github.com/your-username/email-domain-restriction/issues
- Support: https://codeeffina.com/support

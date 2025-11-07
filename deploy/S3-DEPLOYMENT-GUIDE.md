# S3 Deployment Guide for Email Domain Restriction Plugin

This guide explains how to deploy and host the Email Domain Restriction plugin update system on Amazon S3.

## Prerequisites

- AWS account with S3 access
- AWS CLI installed and configured (optional but recommended)
- Plugin ZIP file built and ready for deployment

## S3 Bucket Structure

Create the following directory structure in your S3 bucket:

```
codeeffina.com/
└── wordpress/
    └── plugins/
        └── email-domain-restriction/
            ├── index.html                          # Plugin homepage
            ├── css/
            │   └── style.css                      # Homepage styles
            ├── releases/
            │   ├── email-domain-restriction-1.0.0.zip
            │   ├── email-domain-restriction-1.0.1.zip
            │   └── email-domain-restriction-latest.zip  # Symlink or copy of latest
            ├── metadata/
            │   └── info.json                      # Update metadata
            └── assets/
                ├── banner-772x250.png             # WordPress.org style banner (low res)
                ├── banner-1544x500.png            # WordPress.org style banner (high res)
                ├── icon-128x128.png               # Plugin icon (1x)
                ├── icon-256x256.png               # Plugin icon (2x)
                └── screenshots/
                    ├── screenshot-1.png           # Analytics Dashboard
                    ├── screenshot-2.png           # Domain Whitelist
                    ├── screenshot-3.png           # Registration Log
                    └── screenshot-4.png           # Settings Page
```

## Step 1: Create S3 Bucket

### Option A: Using AWS Console

1. Go to AWS S3 Console
2. Click "Create bucket"
3. Bucket name: `codeeffina.com` (or your domain)
4. Region: Choose your preferred region
5. **Block Public Access settings**: Uncheck "Block all public access"
   - ⚠️ Important: We need public read access for plugin updates to work
6. Click "Create bucket"

### Option B: Using AWS CLI

```bash
aws s3 mb s3://codeeffina.com --region us-east-1
```

## Step 2: Configure Bucket for Static Website Hosting

### Using AWS Console

1. Select your bucket
2. Go to "Properties" tab
3. Scroll to "Static website hosting"
4. Click "Edit"
5. Enable static website hosting
6. Index document: `index.html`
7. Save changes

### Using AWS CLI

```bash
aws s3 website s3://codeeffina.com --index-document index.html
```

## Step 3: Set Bucket Policy for Public Read Access

Create a bucket policy to allow public read access to specific files:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PublicReadGetObject",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": [
        "arn:aws:s3:::codeeffina.com/wordpress/plugins/email-domain-restriction/*"
      ]
    }
  ]
}
```

### Apply Policy Using AWS Console

1. Go to bucket → Permissions tab
2. Scroll to "Bucket policy"
3. Click "Edit"
4. Paste the policy above
5. Save changes

### Apply Policy Using AWS CLI

Save the policy to `bucket-policy.json` and run:

```bash
aws s3api put-bucket-policy --bucket codeeffina.com --policy file://bucket-policy.json
```

## Step 4: Upload Plugin Files

### Prepare the Plugin ZIP

1. Build the plugin ZIP (exclude development files):

```bash
cd /Users/erik/Projects/EmailDomainRestriction/email-domain-restriction
zip -r email-domain-restriction-1.0.0.zip . \
  -x "*.git*" \
  -x "*node_modules*" \
  -x "*deploy*" \
  -x "*.DS_Store" \
  -x "*__MACOSX*" \
  -x "*.md" \
  -x "composer.json" \
  -x "composer.lock" \
  -x "package*.json"
```

2. Copy as latest version:

```bash
cp email-domain-restriction-1.0.0.zip email-domain-restriction-latest.zip
```

### Upload Files Using AWS CLI

```bash
# Upload homepage
aws s3 cp deploy/homepage/index.html s3://codeeffina.com/wordpress/plugins/email-domain-restriction/index.html --content-type "text/html"
aws s3 cp deploy/homepage/css/style.css s3://codeeffina.com/wordpress/plugins/email-domain-restriction/css/style.css --content-type "text/css"

# Upload metadata
aws s3 cp deploy/metadata/info.json s3://codeeffina.com/wordpress/plugins/email-domain-restriction/metadata/info.json --content-type "application/json"

# Upload plugin releases
aws s3 cp email-domain-restriction-1.0.0.zip s3://codeeffina.com/wordpress/plugins/email-domain-restriction/releases/email-domain-restriction-1.0.0.zip --content-type "application/zip"
aws s3 cp email-domain-restriction-latest.zip s3://codeeffina.com/wordpress/plugins/email-domain-restriction/releases/email-domain-restriction-latest.zip --content-type "application/zip"

# Upload assets (once created)
aws s3 cp assets/ s3://codeeffina.com/wordpress/plugins/email-domain-restriction/assets/ --recursive
```

### Upload Files Using AWS Console

1. Navigate to your bucket
2. Click "Upload"
3. Drag and drop files maintaining the directory structure
4. Set permissions to "Grant public-read access"
5. Click "Upload"

## Step 5: Configure CORS (Optional but Recommended)

If you want to enable cross-origin requests for the metadata:

```json
[
  {
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "HEAD"],
    "AllowedOrigins": ["*"],
    "ExposeHeaders": []
  }
]
```

Apply using AWS Console:
1. Go to Permissions tab
2. Scroll to "Cross-origin resource sharing (CORS)"
3. Click "Edit"
4. Paste the CORS configuration
5. Save

## Step 6: Set Up CloudFront (Optional - Recommended for Production)

CloudFront provides:
- SSL/TLS encryption (HTTPS)
- Global CDN distribution
- Better performance
- Custom domain support

### Create CloudFront Distribution

1. Go to CloudFront Console
2. Click "Create Distribution"
3. Origin domain: Select your S3 bucket
4. Origin path: Leave empty
5. Viewer protocol policy: Redirect HTTP to HTTPS
6. Allowed HTTP methods: GET, HEAD
7. Cache policy: CachingOptimized
8. Alternate domain names (CNAMEs): `codeeffina.com`
9. SSL certificate: Use ACM certificate for your domain
10. Create distribution

### Update DNS

Point your domain to CloudFront:
- Create CNAME record: `codeeffina.com` → `d1234567890.cloudfront.net`

## Step 7: Verify Deployment

Test all URLs to ensure they're accessible:

```bash
# Test homepage
curl -I https://codeeffina.com/wordpress/plugins/email-domain-restriction/index.html

# Test metadata
curl https://codeeffina.com/wordpress/plugins/email-domain-restriction/metadata/info.json

# Test download
curl -I https://codeeffina.com/wordpress/plugins/email-domain-restriction/releases/email-domain-restriction-latest.zip
```

Expected response: `200 OK`

## Releasing New Versions

When releasing a new version:

### 1. Update Plugin Code

```php
// email-domain-restriction.php
define('EDR_VERSION', '1.0.1'); // Update version

/**
 * Version: 1.0.1  // Update version in header
 */
```

### 2. Update CHANGELOG.md

Add new version entry with changes.

### 3. Update info.json

```json
{
  "version": "1.0.1",
  "download_url": "https://codeeffina.com/wordpress/plugins/email-domain-restriction/releases/email-domain-restriction-1.0.1.zip",
  "last_updated": "2025-01-20 12:00:00",
  "sections": {
    "changelog": "<h4>1.0.1 - 2025-01-20</h4><ul><li>Bug fixes...</li></ul>"
  }
}
```

### 4. Build and Upload New ZIP

```bash
# Build new version
zip -r email-domain-restriction-1.0.1.zip . \
  -x "*.git*" -x "*node_modules*" -x "*deploy*" -x "*.DS_Store" -x "*__MACOSX*"

# Update latest
cp email-domain-restriction-1.0.1.zip email-domain-restriction-latest.zip

# Upload to S3
aws s3 cp email-domain-restriction-1.0.1.zip s3://codeeffina.com/wordpress/plugins/email-domain-restriction/releases/email-domain-restriction-1.0.1.zip --content-type "application/zip"
aws s3 cp email-domain-restriction-latest.zip s3://codeeffina.com/wordpress/plugins/email-domain-restriction/releases/email-domain-restriction-latest.zip --content-type "application/zip"
aws s3 cp deploy/metadata/info.json s3://codeeffina.com/wordpress/plugins/email-domain-restriction/metadata/info.json --content-type "application/json"
```

### 5. Invalidate CloudFront Cache (if using CloudFront)

```bash
aws cloudfront create-invalidation --distribution-id E1234567890ABC --paths "/wordpress/plugins/email-domain-restriction/metadata/info.json"
```

## Security Considerations

1. **Never expose sensitive data** in public files
2. **Use IAM roles** with minimal permissions for deployment
3. **Enable S3 versioning** to prevent accidental overwrites
4. **Monitor access logs** for unusual activity
5. **Use CloudFront** for HTTPS and DDoS protection
6. **Implement integrity checks** in your plugin (checksum validation)

## Deployment Checklist

- [ ] S3 bucket created and configured
- [ ] Bucket policy allows public read access
- [ ] Static website hosting enabled
- [ ] Plugin ZIP built and uploaded to releases/
- [ ] info.json uploaded to metadata/
- [ ] Homepage uploaded (index.html, css/style.css)
- [ ] Assets uploaded (banners, icons, screenshots)
- [ ] All URLs tested and accessible
- [ ] CloudFront distribution configured (optional)
- [ ] DNS records updated (if using custom domain)
- [ ] Plugin Update Checker tested in WordPress
- [ ] Update notifications working in WordPress admin

## Troubleshooting

### Updates Not Showing in WordPress

1. Check info.json is accessible: `curl https://codeeffina.com/wordpress/plugins/email-domain-restriction/metadata/info.json`
2. Verify version number in info.json is higher than installed version
3. Clear WordPress transients: Delete transients with prefix `puc_`
4. Check WordPress error log for update checker errors
5. Verify Plugin Update Checker library is loaded correctly

### 403 Forbidden Errors

1. Check bucket policy allows public read
2. Verify file permissions are not blocking access
3. Check CloudFront distribution settings
4. Ensure CORS is configured if needed

### Download Link Not Working

1. Verify ZIP file uploaded correctly
2. Check download_url in info.json matches actual file location
3. Test direct download: `wget https://codeeffina.com/wordpress/plugins/email-domain-restriction/releases/email-domain-restriction-latest.zip`

## Costs

Estimated AWS costs for plugin hosting:

- **S3 Storage**: ~$0.023/GB per month (plugin is <5MB)
- **S3 Requests**: ~$0.005 per 1,000 GET requests
- **Data Transfer**: First 100GB free, then $0.09/GB
- **CloudFront**: First 1TB free tier for 12 months

**Expected monthly cost**: $0.50 - $5.00 depending on download volume

## Additional Resources

- [AWS S3 Documentation](https://docs.aws.amazon.com/s3/)
- [CloudFront Documentation](https://docs.aws.amazon.com/cloudfront/)
- [Plugin Update Checker Documentation](https://github.com/YahnisElsts/plugin-update-checker)
- [WordPress Plugin Header Reference](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)

## Support

For issues with deployment or updates:
- Check logs in WordPress: WP_DEBUG_LOG enabled
- Review AWS CloudWatch logs
- Contact: https://codeeffina.com

# Deployment Checklist

Use this checklist to ensure all steps are completed for a successful deployment.

## Pre-Deployment

### Code Preparation
- [ ] All code changes committed and pushed to repository
- [ ] Code reviewed and approved
- [ ] All tests passing
- [ ] No merge conflicts
- [ ] Version number updated (if applicable)
- [ ] CHANGELOG.md updated

### Environment Preparation
- [ ] `.env` file configured with production values
- [ ] Database credentials verified
- [ ] Redis credentials verified
- [ ] S3 credentials verified
- [ ] OpenAI API key configured
- [ ] Email service configured
- [ ] SSL certificate valid and not expiring soon

### Infrastructure Check
- [ ] Server resources adequate (CPU, RAM, Disk)
- [ ] Database backup completed
- [ ] Files backup completed
- [ ] Disk space > 20% free
- [ ] Memory usage < 80%
- [ ] All required services running (MySQL, Redis, Nginx, PHP-FPM)

### Pre-Deployment Tests
- [ ] Run `./scripts/pre-deploy-checks.sh`
- [ ] All checks passed or warnings addressed
- [ ] Staging environment tested
- [ ] Performance tests completed
- [ ] Security scan completed

### Communication
- [ ] Team notified of deployment window
- [ ] Maintenance window scheduled (if needed)
- [ ] Stakeholders informed
- [ ] Support team on standby

## Deployment

### Backup
- [ ] Create full backup: `./scripts/backup.sh full`
- [ ] Verify backup files created
- [ ] Backup uploaded to S3 (if configured)
- [ ] Note backup timestamp for rollback

### Deploy Application
- [ ] Run deployment script: `./deploy.sh production`
- [ ] Monitor deployment logs
- [ ] Verify no errors during deployment
- [ ] Check maintenance mode disabled

### Verification
- [ ] Application accessible at production URL
- [ ] Health check passing: `curl https://lms.yourcompany.com/health`
- [ ] Login functionality working
- [ ] Course catalog loading
- [ ] Video playback working
- [ ] Assessment submission working
- [ ] Certificate generation working
- [ ] Email sending working
- [ ] Search functionality working
- [ ] Queue workers running
- [ ] Horizon dashboard accessible (if using)

### Database
- [ ] Migrations completed successfully
- [ ] No migration errors in logs
- [ ] Database indexes created
- [ ] Data integrity verified

### Assets
- [ ] CSS loading correctly
- [ ] JavaScript loading correctly
- [ ] Images loading correctly
- [ ] Fonts loading correctly
- [ ] No 404 errors for assets

### Performance
- [ ] Page load times acceptable (< 3s)
- [ ] API response times acceptable (< 1s)
- [ ] Database query times acceptable (< 100ms avg)
- [ ] No N+1 query issues
- [ ] Cache hit rate > 80%

### Security
- [ ] HTTPS working correctly
- [ ] Security headers present
- [ ] No sensitive data exposed
- [ ] CSRF protection working
- [ ] Rate limiting working
- [ ] Authentication working

## Post-Deployment

### Monitoring
- [ ] Check error tracking (Sentry)
- [ ] Review application logs
- [ ] Monitor server metrics
- [ ] Check queue size
- [ ] Monitor memory usage
- [ ] Monitor CPU usage
- [ ] Check disk usage

### Smoke Tests
- [ ] Admin login
- [ ] Instructor login
- [ ] Employee login
- [ ] Create course
- [ ] Enroll in course
- [ ] View lesson
- [ ] Take assessment
- [ ] Generate certificate
- [ ] Send email
- [ ] Search courses
- [ ] AI features working

### Documentation
- [ ] Deployment notes documented
- [ ] Known issues documented
- [ ] Rollback plan documented
- [ ] Update deployment log

### Communication
- [ ] Team notified of successful deployment
- [ ] Stakeholders informed
- [ ] Support team updated
- [ ] Deployment notification sent

### Cleanup
- [ ] Old backups cleaned (if needed)
- [ ] Temporary files removed
- [ ] Logs rotated
- [ ] Cache warmed up

## Rollback (If Needed)

### Immediate Actions
- [ ] Enable maintenance mode: `php artisan down`
- [ ] Notify team of rollback
- [ ] Document reason for rollback

### Rollback Steps
- [ ] Restore database: `./scripts/restore.sh database [backup_file]`
- [ ] Revert code: `git reset --hard [commit]`
- [ ] Restore dependencies
- [ ] Clear caches
- [ ] Disable maintenance mode: `php artisan up`

### Post-Rollback
- [ ] Verify application working
- [ ] Check health endpoint
- [ ] Notify team of rollback completion
- [ ] Document issues encountered
- [ ] Plan fix and re-deployment

## Monitoring Schedule

### First Hour
- [ ] Monitor every 5 minutes
- [ ] Check error rates
- [ ] Check response times
- [ ] Check queue size

### First 24 Hours
- [ ] Monitor every hour
- [ ] Review error logs
- [ ] Check performance metrics
- [ ] Monitor user feedback

### First Week
- [ ] Daily monitoring
- [ ] Weekly performance review
- [ ] Address any issues
- [ ] Optimize as needed

## Sign-Off

### Deployment Team
- [ ] Developer: _________________ Date: _______
- [ ] DevOps: _________________ Date: _______
- [ ] QA: _________________ Date: _______

### Approval
- [ ] Tech Lead: _________________ Date: _______
- [ ] Product Owner: _________________ Date: _______

## Notes

### Deployment Details
- **Date:** _________________
- **Time:** _________________
- **Version:** _________________
- **Deployed By:** _________________
- **Duration:** _________________

### Issues Encountered
_Document any issues encountered during deployment:_

---

### Resolution
_Document how issues were resolved:_

---

### Follow-Up Actions
_List any follow-up actions needed:_

1. 
2. 
3. 

---

## Emergency Contacts

- **DevOps Lead:** devops@yourcompany.com
- **Tech Lead:** tech-lead@yourcompany.com
- **On-Call:** Check PagerDuty
- **Slack:** #lms-production

## Additional Resources

- [Deployment Guide](../docs/DEPLOYMENT_GUIDE.md)
- [Production Configuration Guide](../docs/PRODUCTION_CONFIGURATION_GUIDE.md)
- [Monitoring Guide](../docs/MONITORING_LOGGING_GUIDE.md)
- [Rollback Procedures](../docs/DEPLOYMENT_GUIDE.md#rollback-procedures)

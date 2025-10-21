# Monitoring and Logging Guide

This guide covers the setup and configuration of monitoring, logging, and alerting for the Corporate LMS in production.

## Table of Contents

1. [Overview](#overview)
2. [Error Tracking](#error-tracking)
3. [Application Performance Monitoring](#application-performance-monitoring)
4. [Log Aggregation](#log-aggregation)
5. [Health Checks](#health-checks)
6. [Metrics Collection](#metrics-collection)
7. [Alerting](#alerting)
8. [Dashboard Setup](#dashboard-setup)

## Overview

The LMS includes comprehensive monitoring capabilities:

- **Error Tracking**: Real-time error monitoring with Sentry
- **APM**: Application performance monitoring with New Relic or DataDog
- **Log Aggregation**: Centralized logging with CloudWatch, Papertrail, or Loggly
- **Health Checks**: Automated system health monitoring
- **Metrics**: Custom metrics collection and visualization
- **Alerting**: Multi-channel alerting for critical issues

## Error Tracking

### Sentry Setup

1. **Create Sentry Account**
   - Sign up at https://sentry.io
   - Create a new Laravel project
   - Copy your DSN

2. **Install Sentry SDK**

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=your-dsn-here
```

3. **Configure Environment**

```env
SENTRY_ENABLED=true
SENTRY_LARAVEL_DSN=https://your-key@sentry.io/project-id
SENTRY_ENVIRONMENT=production
SENTRY_TRACES_SAMPLE_RATE=0.2
```

4. **Test Sentry Integration**

```bash
php artisan sentry:test
```

5. **Configure Breadcrumbs**

In `config/sentry.php`:

```php
'breadcrumbs' => [
    'logs' => true,
    'sql_queries' => true,
    'sql_bindings' => false,
    'queue_info' => true,
    'command_info' => true,
],
```

### Error Context

Sentry automatically captures:
- Stack traces
- Request data
- User information
- Environment details
- Breadcrumbs (logs, queries, etc.)

### Custom Error Reporting

```php
use Sentry\Laravel\Integration;

try {
    // Your code
} catch (\Exception $e) {
    Integration::captureUnhandledException($e);
    throw $e;
}
```

## Application Performance Monitoring

### New Relic Setup

1. **Install New Relic Agent**

```bash
# For Ubuntu/Debian
wget -O - https://download.newrelic.com/548C16BF.gpg | sudo apt-key add -
echo "deb http://apt.newrelic.com/debian/ newrelic non-free" | sudo tee /etc/apt/sources.list.d/newrelic.list
sudo apt-get update
sudo apt-get install newrelic-php5
```

2. **Configure New Relic**

```bash
sudo newrelic-install install
```

3. **Environment Configuration**

```env
NEW_RELIC_ENABLED=true
NEW_RELIC_APP_NAME="Corporate LMS"
NEW_RELIC_LICENSE_KEY=your-license-key
```

4. **Verify Installation**

Check `/var/log/newrelic/php_agent.log` for connection status.

### DataDog Setup

1. **Install DataDog Agent**

```bash
DD_API_KEY=your-api-key DD_SITE="datadoghq.com" bash -c "$(curl -L https://s3.amazonaws.com/dd-agent/scripts/install_script.sh)"
```

2. **Configure PHP APM**

```bash
sudo apt-get install datadog-php-tracer
```

3. **Environment Configuration**

```env
DATADOG_ENABLED=true
DATADOG_API_KEY=your-api-key
DATADOG_APP_KEY=your-app-key
```

4. **Custom Metrics**

```php
use App\Services\MonitoringService;

$monitoring = app(MonitoringService::class);
$monitoring->recordMetric('custom.metric', 100, ['tag' => 'value']);
```

## Log Aggregation

### CloudWatch Logs

1. **Install CloudWatch Agent**

```bash
wget https://s3.amazonaws.com/amazoncloudwatch-agent/ubuntu/amd64/latest/amazon-cloudwatch-agent.deb
sudo dpkg -i -E ./amazon-cloudwatch-agent.deb
```

2. **Configure Log Streaming**

Create `/opt/aws/amazon-cloudwatch-agent/etc/config.json`:

```json
{
  "logs": {
    "logs_collected": {
      "files": {
        "collect_list": [
          {
            "file_path": "/var/www/lms/storage/logs/laravel.log",
            "log_group_name": "/aws/lms/application",
            "log_stream_name": "{instance_id}",
            "timezone": "UTC"
          }
        ]
      }
    }
  }
}
```

3. **Start Agent**

```bash
sudo /opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl \
  -a fetch-config \
  -m ec2 \
  -s \
  -c file:/opt/aws/amazon-cloudwatch-agent/etc/config.json
```

4. **Environment Configuration**

```env
CLOUDWATCH_LOGS_ENABLED=true
CLOUDWATCH_LOG_GROUP=/aws/lms
CLOUDWATCH_LOG_STREAM=application
CLOUDWATCH_LOG_RETENTION=14
```

### Papertrail Setup

1. **Get Papertrail Endpoint**
   - Sign up at https://papertrailapp.com
   - Create a new system
   - Note your log destination (host:port)

2. **Configure Laravel Logging**

In `config/logging.php`:

```php
'papertrail' => [
    'driver' => 'monolog',
    'level' => env('LOG_LEVEL', 'debug'),
    'handler' => SyslogUdpHandler::class,
    'handler_with' => [
        'host' => env('PAPERTRAIL_HOST'),
        'port' => env('PAPERTRAIL_PORT'),
        'connectionString' => 'tls://'.env('PAPERTRAIL_HOST').':'.env('PAPERTRAIL_PORT'),
    ],
],
```

3. **Environment Configuration**

```env
PAPERTRAIL_ENABLED=true
PAPERTRAIL_HOST=logs.papertrailapp.com
PAPERTRAIL_PORT=12345
```

### Loggly Setup

1. **Install Loggly Handler**

```bash
composer require loggly/monolog-loggly
```

2. **Configure in `config/logging.php`**

```php
'loggly' => [
    'driver' => 'monolog',
    'handler' => Monolog\Handler\LogglyHandler::class,
    'handler_with' => [
        'token' => env('LOGGLY_TOKEN'),
        'tag' => 'laravel,lms',
    ],
],
```

3. **Environment Configuration**

```env
LOGGLY_ENABLED=true
LOGGLY_TOKEN=your-loggly-token
```

## Health Checks

### Endpoint Configuration

The LMS includes a comprehensive health check endpoint at `/health`.

1. **Enable Health Checks**

```env
HEALTH_CHECKS_ENABLED=true
HEALTH_CHECK_ENDPOINT=/health
```

2. **Configure Checks**

In `config/monitoring.php`:

```php
'health_checks' => [
    'enabled' => true,
    'checks' => [
        'database' => true,
        'redis' => true,
        'storage' => true,
        'queue' => true,
        'cache' => true,
    ],
],
```

3. **Add Route**

In `routes/web.php`:

```php
Route::get('/health', [HealthCheckController::class, 'index'])
    ->name('health.check');
Route::get('/ping', [HealthCheckController::class, 'ping'])
    ->name('health.ping');
```

4. **Response Format**

```json
{
  "status": "healthy",
  "timestamp": "2024-01-15T10:30:00Z",
  "checks": {
    "database": {
      "status": "healthy",
      "response_time_ms": 5.23,
      "message": "Database connection successful"
    },
    "redis": {
      "status": "healthy",
      "response_time_ms": 2.15,
      "message": "Redis connection successful"
    },
    "storage": {
      "status": "healthy",
      "response_time_ms": 45.67,
      "disk": "s3",
      "message": "Storage is accessible"
    }
  },
  "version": "1.0.0",
  "environment": "production"
}
```

### External Monitoring

#### UptimeRobot

1. Sign up at https://uptimerobot.com
2. Create HTTP(s) monitor
3. Set URL to `https://lms.yourcompany.com/ping`
4. Configure alert contacts

#### Pingdom

1. Sign up at https://www.pingdom.com
2. Add new uptime check
3. Set URL to `https://lms.yourcompany.com/health`
4. Configure alert policies

## Metrics Collection

### Built-in Metrics

The LMS automatically collects:

- **Request Metrics**: Count, response time, status codes
- **Database Metrics**: Query count, query time, slow queries
- **Cache Metrics**: Hit rate, miss rate
- **Queue Metrics**: Job count, processing time, failures
- **Memory Metrics**: Peak usage, average usage

### Custom Metrics

```php
use App\Services\MonitoringService;

$monitoring = app(MonitoringService::class);

// Record a counter
$monitoring->recordMetric('user.login', 1, ['method' => 'email']);

// Record a gauge
$monitoring->recordMetric('active.users', 150);

// Record timing
$start = microtime(true);
// ... your code ...
$duration = (microtime(true) - $start) * 1000;
$monitoring->recordMetric('operation.duration', $duration, ['operation' => 'export']);
```

### Viewing Metrics

Metrics are stored in Redis and can be viewed:

1. **Via Dashboard**: Navigate to `/admin/monitoring`
2. **Via API**: `GET /api/metrics?name=http.request&period=1h`
3. **Via External Tools**: DataDog, New Relic dashboards

## Alerting

### Alert Channels

#### Slack Alerts

1. **Create Slack Webhook**
   - Go to https://api.slack.com/apps
   - Create new app
   - Enable Incoming Webhooks
   - Add webhook to workspace
   - Copy webhook URL

2. **Configure Environment**

```env
ALERT_SLACK_ENABLED=true
ALERT_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
ALERT_SLACK_CHANNEL=#alerts
```

#### Email Alerts

```env
ALERT_EMAIL_ENABLED=true
ALERT_EMAIL_RECIPIENTS=admin@company.com,devops@company.com
```

#### PagerDuty

1. **Create PagerDuty Integration**
   - Log in to PagerDuty
   - Go to Services → Service Directory
   - Create new service or select existing
   - Add integration → Events API v2
   - Copy integration key

2. **Configure Environment**

```env
PAGERDUTY_ENABLED=true
PAGERDUTY_INTEGRATION_KEY=your-integration-key
```

### Alert Thresholds

Configure in `.env`:

```env
ALERT_ERROR_RATE_THRESHOLD=5
ALERT_RESPONSE_TIME_THRESHOLD=3000
ALERT_QUEUE_SIZE_THRESHOLD=1000
ALERT_DISK_USAGE_THRESHOLD=85
ALERT_MEMORY_USAGE_THRESHOLD=90
```

### Alert Types

The system automatically alerts on:

- High error rate (> threshold per minute)
- Slow requests (> threshold ms)
- Slow database queries (> threshold ms)
- Large queue size (> threshold jobs)
- High disk usage (> threshold %)
- High memory usage (> threshold %)
- Service unavailability (health check failures)

## Dashboard Setup

### Grafana Dashboard

1. **Install Grafana**

```bash
sudo apt-get install -y software-properties-common
sudo add-apt-repository "deb https://packages.grafana.com/oss/deb stable main"
wget -q -O - https://packages.grafana.com/gpg.key | sudo apt-key add -
sudo apt-get update
sudo apt-get install grafana
sudo systemctl start grafana-server
sudo systemctl enable grafana-server
```

2. **Configure Data Sources**
   - Add Prometheus for metrics
   - Add Loki for logs
   - Add MySQL for database queries

3. **Import Dashboard**
   - Use provided `grafana-dashboard.json`
   - Customize panels as needed

### Kibana Dashboard (ELK Stack)

1. **Install ELK Stack**

```bash
# Elasticsearch
wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
echo "deb https://artifacts.elastic.co/packages/8.x/apt stable main" | sudo tee /etc/apt/sources.list.d/elastic-8.x.list
sudo apt-get update && sudo apt-get install elasticsearch

# Logstash
sudo apt-get install logstash

# Kibana
sudo apt-get install kibana
```

2. **Configure Logstash Pipeline**

Create `/etc/logstash/conf.d/laravel.conf`:

```conf
input {
  file {
    path => "/var/www/lms/storage/logs/laravel.log"
    start_position => "beginning"
    codec => multiline {
      pattern => "^\[\d{4}-\d{2}-\d{2}"
      negate => true
      what => "previous"
    }
  }
}

filter {
  grok {
    match => { "message" => "\[%{TIMESTAMP_ISO8601:timestamp}\] %{DATA:environment}\.%{DATA:level}: %{GREEDYDATA:message}" }
  }
}

output {
  elasticsearch {
    hosts => ["localhost:9200"]
    index => "lms-logs-%{+YYYY.MM.dd}"
  }
}
```

3. **Start Services**

```bash
sudo systemctl start elasticsearch
sudo systemctl start logstash
sudo systemctl start kibana
```

## Best Practices

### Logging

1. **Use Appropriate Log Levels**
   - `emergency`: System is unusable
   - `alert`: Action must be taken immediately
   - `critical`: Critical conditions
   - `error`: Error conditions
   - `warning`: Warning conditions
   - `notice`: Normal but significant
   - `info`: Informational messages
   - `debug`: Debug-level messages

2. **Include Context**

```php
Log::error('Payment processing failed', [
    'user_id' => $user->id,
    'amount' => $amount,
    'error' => $exception->getMessage(),
]);
```

3. **Avoid Logging Sensitive Data**
   - Never log passwords
   - Mask credit card numbers
   - Redact personal information

### Monitoring

1. **Set Realistic Thresholds**
   - Base on historical data
   - Account for traffic patterns
   - Adjust seasonally

2. **Reduce Alert Fatigue**
   - Group related alerts
   - Use escalation policies
   - Implement alert suppression

3. **Regular Review**
   - Review dashboards weekly
   - Analyze trends monthly
   - Update thresholds quarterly

### Performance

1. **Optimize Logging**
   - Use async logging
   - Rotate logs regularly
   - Archive old logs

2. **Efficient Metrics**
   - Sample high-frequency metrics
   - Aggregate before sending
   - Use appropriate retention

## Troubleshooting

### High Memory Usage

```bash
# Check PHP memory limit
php -i | grep memory_limit

# Monitor memory usage
watch -n 1 free -m

# Check for memory leaks
php artisan horizon:terminate
php artisan queue:restart
```

### Slow Queries

```bash
# Enable slow query log
mysql -e "SET GLOBAL slow_query_log = 'ON';"
mysql -e "SET GLOBAL long_query_time = 1;"

# View slow queries
tail -f /var/log/mysql/mysql-slow.log
```

### High Error Rate

```bash
# Check recent errors
tail -100 storage/logs/laravel.log | grep ERROR

# Check Sentry dashboard
# Review error patterns
# Identify common stack traces
```

## Maintenance

### Daily Tasks

- Review error dashboard
- Check health check status
- Monitor queue size

### Weekly Tasks

- Review slow query log
- Analyze performance trends
- Check disk usage

### Monthly Tasks

- Review and update alert thresholds
- Analyze long-term trends
- Update monitoring documentation
- Review and archive old logs

## Support

For monitoring issues:
- Slack: #monitoring
- Email: monitoring@yourcompany.com
- On-call: Check PagerDuty schedule

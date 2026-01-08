# Observability & Monitoring

API Sentinel provides comprehensive activity logging and metrics for monitoring, debugging, and compliance.

## Activity Logs

All important actions in the system are automatically logged for audit trail and troubleshooting.

### What Gets Logged

- **Contract Validation**: Every validation attempt with results
- **API Requests**: All API calls with authentication info
- **Webhook Deliveries**: Success/failure of webhook calls
- **Breaking Changes**: When detected and details
- **User Actions**: Contract uploads, version creation, etc

### Log Structure

Each activity log contains:
- `log_name`: Category (validation, api, webhook, contract)
- `description`: Human-readable description
- `subject`: The model being acted upon (contract, version, etc)
- `causer`: Who caused the action (user, API token)
- `properties`: Additional metadata (JSON)
- `event`: Event type (created, updated, validated, failed)
- `ip_address`: IP address of the request
- `timestamp`: When it occurred

## API Endpoints

### List Activity Logs

```bash
GET /api/v1/logs
```

**Query Parameters:**
- `log_name` - Filter by category (validation, api, webhook, contract)
- `event` - Filter by event type
- `from` - Start date (ISO 8601)
- `to` - End date (ISO 8601)
- `per_page` - Results per page (max 100, default 50)

**Example:**
```bash
curl "http://localhost:8080/api/v1/logs?log_name=validation&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "logs": [
    {
      "id": 123,
      "log_name": "validation",
      "description": "Contract User API v2.1.0 validated",
      "subject_type": "App\\Models\\ContractVersion",
      "subject_id": 45,
      "event": "validation_passed",
      "properties": {
        "contract_id": 5,
        "version": "2.1.0",
        "error_count": 0,
        "warning_count": 3,
        "breaking_changes_count": 0
      },
      "ip_address": "192.168.1.100",
      "created_at": "2026-01-08T18:30:00Z"
    }
  ],
  "pagination": {
    "total": 150,
    "per_page": 20,
    "current_page": 1,
    "last_page": 8
  }
}
```

### Get Activity Statistics

```bash
GET /api/v1/logs/stats
```

**Query Parameters:**
- `from` - Start date (default: 7 days ago)
- `to` - End date (default: now)

**Example:**
```bash
curl "http://localhost:8080/api/v1/logs/stats?from=2026-01-01&to=2026-01-08" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "stats": {
    "total_activities": 1250,
    "by_log_name": {
      "validation": 450,
      "api": 600,
      "webhook": 150,
      "contract": 50
    },
    "by_event": {
      "validation_passed": 380,
      "validation_failed": 70,
      "created": 50,
      "updated": 30
    },
    "recent_activities": [
      {
        "description": "Contract User API v2.1.0 validated",
        "log_name": "validation",
        "event": "validation_passed",
        "created_at": "2026-01-08T18:30:00Z"
      }
    ]
  },
  "period": {
    "from": "2026-01-01T00:00:00Z",
    "to": "2026-01-08T23:59:59Z"
  }
}
```

## Programmatic Logging

Use the `ActivityLogger` service to log custom activities:

```php
use App\Services\ActivityLogger;

// Simple log
$logger = app(ActivityLogger::class);
$logger->log('contract', 'New contract created')
    ->on($contract)
    ->by($user)
    ->event('created')
    ->withProperties([
        'title' => $contract->title,
        'initial_version' => '1.0.0'
    ]);
```

### Fluent Interface

```php
ActivityLogger::activity('api')
    ->log('API request received')
    ->on($contract)
    ->event('api_call')
    ->withProperties([
        'endpoint' => '/api/v1/validate',
        'method' => 'POST',
        'response_time_ms' => 150
    ]);
```

## Log Categories

### validation
- Contract validation attempts
- Breaking changes detection
- Validation reports generation

### api
- REST API calls
- Authentication attempts
- Rate limiting events

### webhook
- Webhook deliveries
- Retry attempts
- Success/failure tracking

### contract
- Contract CRUD operations
- Version uploads
- Configuration changes

## Querying Logs

Use the `ActivityLog` model for custom queries:

```php
use App\Models\ActivityLog;

// Get all validation failures
$failures = ActivityLog::inLog('validation')
    ->forEvent('validation_failed')
    ->whereBetween('created_at', [$from, $to])
    ->get();

// Get activities for a specific contract
$contractLogs = ActivityLog::forSubject($contract)
    ->orderBy('created_at', 'desc')
    ->get();

// Get activities caused by a user
$userActivities = ActivityLog::causedBy($user)
    ->get();
```

## Monitoring Dashboards

### Key Metrics to Track

1. **Validation Success Rate**
   - `validation_passed` vs `validation_failed` count
   - Track over time to identify trends

2. **API Performance**
   - Request volume by endpoint
   - Error rates
   - Response times (via properties)

3. **Breaking Changes Frequency**
   - How often breaking changes are detected
   - Which contracts have most changes

4. **Webhook Reliability**
   - Delivery success rate
   - Retry attempts
   - Failed deliveries

### Example Queries

**Daily validation counts:**
```php
ActivityLog::inLog('validation')
    ->whereBetween('created_at', [now()->subDays(30), now()])
    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
    ->groupBy('date')
    ->get();
```

**Top failing contracts:**
```php
ActivityLog::inLog('validation')
    ->forEvent('validation_failed')
    ->selectRaw('properties->>"$.contract_id" as contract_id, COUNT(*) as failures')
    ->groupBy('contract_id')
    ->orderByDesc('failures')
    ->limit(10)
    ->get();
```

## Log Retention

**Default**: Logs are kept indefinitely

**Recommended**: Implement a cleanup policy

```php
// Delete logs older than 90 days
ActivityLog::where('created_at', '<', now()->subDays(90))->delete();
```

Add to scheduler in `app/Console/Kernel.php`:
```php
$schedule->call(function () {
    ActivityLog::where('created_at', '<', now()->subDays(90))->delete();
})->weekly();
```

## Integration Examples

### Grafana/Prometheus

Export metrics from activity logs:

```php
// Validation success rate (last hour)
$total = ActivityLog::inLog('validation')
    ->where('created_at', '>', now()->subHour())
    ->count();

$passed = ActivityLog::inLog('validation')
    ->forEvent('validation_passed')
    ->where('created_at', '>', now()->subHour())
    ->count();

$successRate = $total > 0 ? ($passed / $total) * 100 : 0;
```

### ELK Stack

Ship logs to Elasticsearch:

```php
// In a scheduled job
$logs = ActivityLog::where('created_at', '>', $lastShippedTime)
    ->get();

foreach ($logs as $log) {
    $elasticsearch->index([
        'index' => 'api-sentinel-logs',
        'body' => [
            'log_name' => $log->log_name,
            'description' => $log->description,
            'event' => $log->event,
            'properties' => $log->properties,
            'timestamp' => $log->created_at->toIso8601String()
        ]
    ]);
}
```

### Custom Alerts

Monitor for critical events:

```php
// Alert on repeated validation failures
$recentFailures = ActivityLog::inLog('validation')
    ->forEvent('validation_failed')
    ->where('created_at', '>', now()->subMinutes(5))
    ->count();

if ($recentFailures > 10) {
    // Send alert via Slack, email, etc
    Notification::send($admins, new HighValidationFailureRate($recentFailures));
}
```

## Best Practices

1. **Don't log sensitive data** in properties (passwords, secrets, tokens)
2. **Use appropriate log levels** (validation, api, webhook, contract)
3. **Include context** in properties for debugging
4. **Monitor log volume** to avoid database bloat
5. **Set up retention policies** for old logs
6. **Create dashboards** for key metrics
7. **Alert on anomalies** (sudden spikes, high failure rates)

## Privacy & Compliance

- IP addresses are logged for security
- No user credentials are stored
- PII should not be in log descriptions
- Implement data retention as per your compliance requirements (GDPR, LGPD, etc)
- Logs can be exported for audit purposes


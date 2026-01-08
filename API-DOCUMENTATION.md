# API Sentinel - REST API Documentation

REST API for validating OpenAPI contracts and detecting breaking changes in CI/CD pipelines.

## Base URL

```
http://localhost:8080/api/v1
```

## Endpoints

### Health Check

Check if the API is running.

```bash
GET /api/v1/health
```

**Response:**
```json
{
  "status": "ok",
  "service": "API Sentinel",
  "version": "1.0.0",
  "timestamp": "2026-01-08T10:00:00Z"
}
```

---

### Validate Contract

Upload and validate an OpenAPI contract file.

```bash
POST /api/v1/validate
Content-Type: multipart/form-data
```

**Parameters:**
- `file` (required): OpenAPI contract file (YAML or JSON)
- `contract_id` (optional): Contract ID for context

**Example:**
```bash
curl -X POST http://localhost:8080/api/v1/validate \
  -F "file=@openapi.yaml"
```

**Success Response (200):**
```json
{
  "success": true,
  "status": "passed",
  "metadata": {
    "openapi": "3.0.0",
    "title": "My API",
    "version": "1.0.0",
    "description": "API description"
  },
  "validation": {
    "status": "passed",
    "error_count": 0,
    "warning_count": 2,
    "info_count": 5,
    "issues": [
      {
        "severity": "warning",
        "type": "missing_operation_id",
        "message": "operationId is recommended",
        "path": "paths./users.get"
      }
    ]
  },
  "endpoints": {
    "count": 12,
    "list": [...]
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "status": "failed",
  "validation": {
    "status": "failed",
    "error_count": 3,
    "issues": [...]
  }
}
```

---

### Compare Versions

Detect breaking changes between two contract versions.

```bash
POST /api/v1/compare
Content-Type: application/json
```

**Body:**
```json
{
  "old_version_id": 1,
  "new_version_id": 2
}
```

**Example:**
```bash
curl -X POST http://localhost:8080/api/v1/compare \
  -H "Content-Type: application/json" \
  -d '{
    "old_version_id": 1,
    "new_version_id": 2
  }'
```

**Success Response (200 - No blocking changes):**
```json
{
  "success": true,
  "comparison": {
    "old_version": "1.0.0",
    "new_version": "2.0.0",
    "has_breaking_changes": true,
    "has_blocking_changes": false
  },
  "breaking_changes": {
    "total": 5,
    "critical": 0,
    "warning": 5,
    "info": 0,
    "by_category": {
      "parameters": [...],
      "responses": [...]
    },
    "all": [...]
  },
  "recommendation": "WARN: Non-critical changes detected, review recommended"
}
```

**Error Response (422 - Blocking changes):**
```json
{
  "success": true,
  "comparison": {
    "has_blocking_changes": true
  },
  "breaking_changes": {
    "critical": 3,
    "all": [
      {
        "type": "endpoint_removed",
        "severity": "critical",
        "message": "Endpoint removed: GET /users",
        "path": "/users",
        "method": "GET",
        "category": "endpoints"
      }
    ]
  },
  "recommendation": "BLOCK: Critical breaking changes detected"
}
```

---

### Get Validation Status

Get the validation status of a specific contract version.

```bash
GET /api/v1/contracts/{contractId}/versions/{versionId}/status
```

**Example:**
```bash
curl http://localhost:8080/api/v1/contracts/1/versions/5/status
```

**Success Response (200):**
```json
{
  "success": true,
  "contract": {
    "id": 1,
    "title": "User API"
  },
  "version": {
    "id": 5,
    "version": "2.1.0",
    "status": "validated",
    "created_at": "2026-01-08T10:00:00Z"
  },
  "validation": {
    "status": "passed",
    "error_count": 0,
    "warning_count": 3,
    "issues": [...],
    "breaking_changes": [],
    "processed_at": "2026-01-08T10:05:00Z"
  },
  "endpoints": {
    "count": 15
  }
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "No validation report found. Please run analysis first."
}
```

---

## CI/CD Integration

### GitHub Actions

Add to `.github/workflows/api-validation.yml`:

```yaml
name: Validate API Contract

on:
  pull_request:
    paths:
      - 'api/openapi.yaml'

jobs:
  validate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Validate Contract
        run: |
          curl -X POST -F "file=@api/openapi.yaml" \
            ${{ secrets.API_SENTINEL_URL }}/api/v1/validate \
            | jq '.validation.error_count' | \
            grep -q '^0$' || exit 1
```

### GitLab CI

Add to `.gitlab-ci.yml`:

```yaml
validate-api:
  stage: test
  script:
    - |
      curl -X POST -F "file=@api/openapi.yaml" \
        $API_SENTINEL_URL/api/v1/validate \
        | jq -e '.validation.error_count == 0'
  only:
    changes:
      - api/openapi.yaml
```

### Jenkins

```groovy
stage('Validate API Contract') {
    steps {
        script {
            def response = sh(
                script: 'curl -X POST -F "file=@api/openapi.yaml" ${API_SENTINEL_URL}/api/v1/validate',
                returnStdout: true
            )
            def json = readJSON text: response
            if (json.validation.error_count > 0) {
                error("API validation failed with ${json.validation.error_count} errors")
            }
        }
    }
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| 200  | Success |
| 400  | Bad request / parsing error |
| 404  | Resource not found |
| 422  | Validation failed or blocking breaking changes |

---

## Response Status Values

### Validation Status
- `passed` - No errors, may have warnings
- `warning` - No errors, but has warnings
- `failed` - Has validation errors

### Breaking Change Severity
- `critical` - Will break existing clients (blocks deployment)
- `warning` - May cause issues (review recommended)
- `info` - Informational change

---

## Best Practices

1. **Validate on Every PR**: Add validation to your CI pipeline
2. **Block on Critical Changes**: Return exit code 1 when critical breaking changes detected
3. **Review Warnings**: Don't ignore warnings, they may become errors
4. **Version Your API**: Use semantic versioning in your OpenAPI spec
5. **Document Changes**: Use the changelog generation feature

---

## Examples

See full examples in `/examples` directory or the [GitHub Actions workflow](.github/workflows/api-contract-validation.yml).

---

## OpenAPI Specification

Full API specification available at: [api-sentinel-openapi.yaml](./api-sentinel-openapi.yaml)

Import into Postman, Insomnia, or any OpenAPI-compatible tool.


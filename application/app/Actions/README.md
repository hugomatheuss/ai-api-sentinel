# Actions

Actions are single-purpose classes that encapsulate specific business logic operations, following the Single Responsibility Principle.

## Contract

All actions implement the `HandlesAction` interface, which defines a single `handle()` method as the entry point.

```php
interface HandlesAction
{
    public function handle(mixed ...$parameters): mixed;
}
```

## Usage

Actions can be invoked directly or resolved from the container:

```php
// Direct instantiation
$action = new CalculateSuccessRateAction();
$rate = $action->handle(100, 85); // returns 85.0

// Via container (supports dependency injection)
$action = app(GetValidationTrendsAction::class);
$trends = $action->handle(30); // get last 30 days

// Using Real-Time Facades
$trends = GetValidationTrendsAction::handle(30);
```

## Available Actions

### Analytics

- **GetActivityTrendsAction**: Retrieve activity log trends grouped by date and type
- **GetValidationTrendsAction**: Get validation pass/fail trends over time
- **GetBreakingChangesTrendsAction**: Track breaking changes detected over time
- **GetCommonIssuesAction**: Identify the most frequent validation issues

### Calculations

- **CalculateSuccessRateAction**: Calculate percentage of successful validations

## Best Practices

1. **Keep actions focused**: One action per business operation
2. **Make them stateless**: Actions should not hold state between invocations
3. **Keep them testable**: All business logic should be easily unit-testable
4. **Use type hints**: Leverage PHP's type system for parameters and returns when possible
5. **Document intent**: Include PHPDoc blocks explaining why the action exists

## Converting Services to Actions

If a Service class has 2 or fewer public methods, consider converting it to Actions:

```php
// Before: Service with 2 methods
class ValidationService
{
    public function getTrends(int $days) { ... }
    public function getCommonIssues(int $days) { ... }
}

// After: Two separate Actions
class GetValidationTrendsAction implements HandlesAction { ... }
class GetCommonIssuesAction implements HandlesAction { ... }
```


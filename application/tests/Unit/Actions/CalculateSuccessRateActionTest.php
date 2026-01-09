<?php

use App\Actions\CalculateSuccessRateAction;
use function Pest\Laravel\{actingAs};

beforeEach(function () {
    $this->action = new CalculateSuccessRateAction();
});

test('calculates success rate correctly with valid numbers', function () {
    $rate = $this->action->handle(100, 85);

    expect($rate)->toBe(85.0);
});

test('returns zero when total is zero', function () {
    $rate = $this->action->handle(0, 0);

    expect($rate)->toBe(0.0);
});

test('returns zero when no successful items', function () {
    $rate = $this->action->handle(100, 0);

    expect($rate)->toBe(0.0);
});

test('returns 100 when all items successful', function () {
    $rate = $this->action->handle(50, 50);

    expect($rate)->toBe(100.0);
});

test('rounds to 2 decimal places', function () {
    $rate = $this->action->handle(3, 1);

    expect($rate)->toBe(33.33);
});

test('handles large numbers correctly', function () {
    $rate = $this->action->handle(1000000, 850000);

    expect($rate)->toBe(85.0);
});

test('calculates partial success correctly', function () {
    $rate = $this->action->handle(7, 3);

    expect($rate)->toBe(42.86);
});


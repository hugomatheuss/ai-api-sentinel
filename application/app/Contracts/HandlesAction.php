<?php

namespace App\Contracts;

/**
 * Contract for action classes.
 *
 * This interface defines the standard contract for all action classes in the application.
 * Actions are single-purpose classes that encapsulate specific business logic operations,
 * following the Single Responsibility Principle.
 *
 * Why this exists:
 * - Provides a consistent interface for all action classes
 * - Makes actions easily identifiable and testable
 * - Enables type-hinting and dependency injection of actions
 * - Enforces a standard execution method across all actions
 *
 * Callers should rely on:
 * - The handle() method being the single entry point for executing the action
 * - Actions being stateless and idempotent when possible
 */
interface HandlesAction
{
    /**
     * Execute the action.
     */
    public function handle(mixed ...$parameters): mixed;
}

import { AppError } from "./errors.js";

export interface RetryOptions {
  retries: number;
  baseDelayMs: number;
  shouldRetry?: (error: unknown) => boolean;
}

function delay(milliseconds: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, milliseconds));
}

export async function withRetry<T>(operation: () => Promise<T>, options: RetryOptions): Promise<T> {
  let attempt = 0;
  let lastError: unknown;
  while (attempt <= options.retries) {
    try {
      return await operation();
    } catch (error) {
      lastError = error;
      const retryable = options.shouldRetry?.(error) ?? (error instanceof AppError && error.retryable);
      if (!retryable || attempt === options.retries) break;
      const exponentialDelay = options.baseDelayMs * (2 ** attempt);
      const jitter = Math.floor(Math.random() * Math.max(1, options.baseDelayMs));
      await delay(exponentialDelay + jitter);
      attempt += 1;
    }
  }
  throw lastError;
}

export type ErrorCode =
  | "VALIDATION_ERROR"
  | "AUTHENTICATION_FAILED"
  | "AUTHORIZATION_FAILED"
  | "UPSTREAM_NOT_FOUND"
  | "UPSTREAM_RATE_LIMITED"
  | "UPSTREAM_UNAVAILABLE"
  | "UPSTREAM_REQUEST_FAILED"
  | "ANALYTICS_NOT_CONFIGURED"
  | "INTERNAL_ERROR";

export class AppError extends Error {
  public readonly code: ErrorCode;
  public readonly statusCode: number;
  public readonly retryable: boolean;

  constructor(code: ErrorCode, message: string, options: { statusCode?: number; retryable?: boolean; cause?: unknown } = {}) {
    super(message, { cause: options.cause });
    this.name = "AppError";
    this.code = code;
    this.statusCode = options.statusCode ?? 500;
    this.retryable = options.retryable ?? false;
  }
}

export class UpstreamHttpError extends AppError {
  constructor(statusCode: number, message = "The Artera service could not process the request.") {
    const code: ErrorCode = statusCode === 401
      ? "AUTHENTICATION_FAILED"
      : statusCode === 403
        ? "AUTHORIZATION_FAILED"
        : statusCode === 404
          ? "UPSTREAM_NOT_FOUND"
          : statusCode === 429
            ? "UPSTREAM_RATE_LIMITED"
            : statusCode >= 500
              ? "UPSTREAM_UNAVAILABLE"
              : "UPSTREAM_REQUEST_FAILED";
    super(code, message, { statusCode, retryable: statusCode === 429 || statusCode >= 500 });
  }
}

export function toSafeError(error: unknown): AppError {
  if (error instanceof AppError) return error;
  return new AppError("INTERNAL_ERROR", "The request could not be completed.", { cause: error });
}

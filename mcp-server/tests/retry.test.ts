import { describe, expect, it, vi } from "vitest";
import { AppError } from "../src/utils/errors.js";
import { withRetry } from "../src/utils/retry.js";

describe("withRetry", () => {
  it("retries a transient application error", async () => {
    const operation = vi.fn()
      .mockRejectedValueOnce(new AppError("UPSTREAM_UNAVAILABLE", "temporary", { retryable: true }))
      .mockResolvedValueOnce("ok");
    await expect(withRetry(operation, { retries: 1, baseDelayMs: 1 })).resolves.toBe("ok");
    expect(operation).toHaveBeenCalledTimes(2);
  });

  it("does not retry non-retryable errors", async () => {
    const operation = vi.fn().mockRejectedValue(new AppError("UPSTREAM_NOT_FOUND", "missing"));
    await expect(withRetry(operation, { retries: 2, baseDelayMs: 1 })).rejects.toThrow("missing");
    expect(operation).toHaveBeenCalledTimes(1);
  });
});

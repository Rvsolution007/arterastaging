import type { AppConfig } from "../types/index.js";
import { toSafeError } from "../utils/errors.js";

const blockedKey = /(?:^|_)(?:access_?token|refresh_?token|token|password|secret|authorization|cookie|api_?key|private_?key|session|remember_?token|ssn|aadhar|aadhaar|pan|bank_?account|card_?number|cvv)(?:$|_)/i;
const freeTextKey = /(?:^|_)(?:note|notes|description|comment|message|body|content|html|raw_text)(?:$|_)/i;
const emailKey = /email/i;
const phoneKey = /(?:phone|mobile|telephone|whatsapp)/i;

function maskEmail(value: string): string {
  const at = value.indexOf("@");
  if (at <= 1) return "***";
  return `${value.slice(0, 1)}***${value.slice(at)}`;
}

function maskPhone(value: string): string {
  const digits = value.replace(/\D/g, "");
  if (digits.length < 5) return "***";
  return `***${digits.slice(-4)}`;
}

interface SanitizationState {
  recordCount: number;
  truncatedRecords: boolean;
}

function sanitizeValue(value: unknown, config: Pick<AppConfig, "maskPii" | "includeFreeText" | "maxResponseRecords" | "maxResponseDepth">, state: SanitizationState, key = "", depth = 0): unknown {
  if (blockedKey.test(key)) return "[REDACTED]";
  if (!config.includeFreeText && freeTextKey.test(key)) return "[OMITTED]";
  if (depth >= config.maxResponseDepth) return "[TRUNCATED_DEPTH]";
  if (typeof value === "string") {
    if (config.maskPii && emailKey.test(key)) return maskEmail(value);
    if (config.maskPii && phoneKey.test(key)) return maskPhone(value);
    return value.length > 2000 ? `${value.slice(0, 2000)}…[TRUNCATED]` : value;
  }
  if (value === null || typeof value === "number" || typeof value === "boolean") return value;
  if (Array.isArray(value)) {
    const result: unknown[] = [];
    for (const item of value) {
      if (state.recordCount >= config.maxResponseRecords) {
        state.truncatedRecords = true;
        break;
      }
      state.recordCount += 1;
      result.push(sanitizeValue(item, config, state, key, depth + 1));
    }
    return result;
  }
  if (typeof value === "object") {
    return Object.fromEntries(
      Object.entries(value as Record<string, unknown>).map(([childKey, childValue]) => [childKey, sanitizeValue(childValue, config, state, childKey, depth + 1)]),
    );
  }
  return String(value);
}

export function formatSuccess(tool: string, data: unknown, config: Pick<AppConfig, "maskPii" | "includeFreeText" | "maxResponseRecords" | "maxResponseDepth">) {
  const state: SanitizationState = { recordCount: 0, truncatedRecords: false };
  const payload = {
    source: "Artera Admin Analytics",
    tool,
    data: sanitizeValue(data, config, state),
    ...(state.truncatedRecords ? { notice: `Result limited to ${config.maxResponseRecords} records.` } : {})
  };
  return {
    content: [{ type: "text" as const, text: JSON.stringify(payload, null, 2) }]
  };
}

export function formatError(tool: string, error: unknown) {
  const safeError = toSafeError(error);
  return {
    isError: true,
    content: [{
      type: "text" as const,
      text: JSON.stringify({
        source: "Artera Admin Analytics",
        tool,
        error: { code: safeError.code, message: safeError.message, retryable: safeError.retryable }
      })
    }]
  };
}

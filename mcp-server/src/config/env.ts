import "dotenv/config";
import { z } from "zod";
import { endpointDefinitions } from "./endpoints.js";
import type { AppConfig, EndpointKey } from "../types/index.js";

const schema = z.object({
  NODE_ENV: z.enum(["development", "test", "production"]).default("development"),
  PORT: z.coerce.number().int().min(1).max(65535).default(3001),
  MCP_TRANSPORT: z.enum(["http", "stdio"]).default("http"),
  MCP_PATH: z.string().regex(/^\//, "MCP_PATH must start with /").default("/mcp"),
  MCP_AUTH_MODE: z.enum(["none", "bearer"]).default("bearer"),
  MCP_ACCESS_TOKEN: z.string().min(32).optional(),
  CORS_ORIGINS: z.string().optional(),
  LOG_LEVEL: z.enum(["fatal", "error", "warn", "info", "debug", "trace", "silent"]).default("info"),
  ARTERA_API_BASE_URL: z.string().url(),
  ARTERA_API_TOKEN: z.string().min(20).optional(),
  ARTERA_API_TIMEOUT_MS: z.coerce.number().int().min(1000).max(120000).default(15000),
  ARTERA_API_MAX_RETRIES: z.coerce.number().int().min(0).max(3).default(2),
  ARTERA_API_RETRY_BASE_DELAY_MS: z.coerce.number().int().min(50).max(10000).default(250),
  GA4_PROPERTY_ID: z.string().regex(/^\d+$/, "GA4_PROPERTY_ID must be numeric.").optional(),
  GA4_SERVICE_ACCOUNT_JSON: z.string().min(2).optional(),
  ARTERA_MASK_PII: z.enum(["true", "false"]).default("true").transform((value) => value === "true"),
  ARTERA_INCLUDE_FREE_TEXT: z.enum(["true", "false"]).default("false").transform((value) => value === "true"),
  ARTERA_MAX_RESPONSE_RECORDS: z.coerce.number().int().min(1).max(200).default(50),
  ARTERA_MAX_RESPONSE_DEPTH: z.coerce.number().int().min(1).max(12).default(6),
  ARTERA_CACHE_MAX_ENTRIES: z.coerce.number().int().min(1).max(10000).default(500),
  ARTERA_CACHE_DEFAULT_TTL_SECONDS: z.coerce.number().int().min(0).max(3600).default(60)
}).superRefine((value, context) => {
  if (value.MCP_TRANSPORT === "http" && value.MCP_AUTH_MODE === "bearer" && !value.MCP_ACCESS_TOKEN) {
    context.addIssue({ code: z.ZodIssueCode.custom, path: ["MCP_ACCESS_TOKEN"], message: "Required when MCP_AUTH_MODE=bearer." });
  }
  if (!value.ARTERA_API_TOKEN) {
    context.addIssue({ code: z.ZodIssueCode.custom, path: ["ARTERA_API_TOKEN"], message: "Required: issue a scoped token with php artisan artera:mcp-issue-token." });
  }
  if (value.GA4_SERVICE_ACCOUNT_JSON) {
    try {
      JSON.parse(value.GA4_SERVICE_ACCOUNT_JSON);
    } catch {
      context.addIssue({ code: z.ZodIssueCode.custom, path: ["GA4_SERVICE_ACCOUNT_JSON"], message: "Must contain valid service-account JSON." });
    }
  }
});

export function loadConfig(raw: NodeJS.ProcessEnv = process.env): AppConfig {
  const parsed = schema.parse(raw);
  const endpointOverrides = Object.fromEntries(
    Object.values(endpointDefinitions)
      .map((endpoint) => [endpoint.key, raw[endpoint.envName]?.trim()] as const)
      .filter((entry): entry is readonly [EndpointKey, string] => Boolean(entry[1])),
  );

  return {
    nodeEnv: parsed.NODE_ENV,
    port: parsed.PORT,
    transport: parsed.MCP_TRANSPORT,
    mcpPath: parsed.MCP_PATH,
    mcpAuthMode: parsed.MCP_AUTH_MODE,
    mcpAccessToken: parsed.MCP_ACCESS_TOKEN,
    corsOrigins: parsed.CORS_ORIGINS?.split(",").map((origin) => origin.trim()).filter(Boolean) ?? [],
    logLevel: parsed.LOG_LEVEL,
    arteraApiBaseUrl: parsed.ARTERA_API_BASE_URL.replace(/\/$/, ""),
    arteraApiToken: parsed.ARTERA_API_TOKEN,
    apiTimeoutMs: parsed.ARTERA_API_TIMEOUT_MS,
    maxRetries: parsed.ARTERA_API_MAX_RETRIES,
    retryBaseDelayMs: parsed.ARTERA_API_RETRY_BASE_DELAY_MS,
    ga4PropertyId: parsed.GA4_PROPERTY_ID,
    ga4ServiceAccountJson: parsed.GA4_SERVICE_ACCOUNT_JSON,
    maskPii: parsed.ARTERA_MASK_PII,
    includeFreeText: parsed.ARTERA_INCLUDE_FREE_TEXT,
    maxResponseRecords: parsed.ARTERA_MAX_RESPONSE_RECORDS,
    maxResponseDepth: parsed.ARTERA_MAX_RESPONSE_DEPTH,
    cacheMaxEntries: parsed.ARTERA_CACHE_MAX_ENTRIES,
    cacheDefaultTtlSeconds: parsed.ARTERA_CACHE_DEFAULT_TTL_SECONDS,
    endpointOverrides
  };
}

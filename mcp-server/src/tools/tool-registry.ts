import { randomUUID } from "node:crypto";
import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import type { Logger } from "pino";
import type { AppConfig, EndpointKey, RequestOptions } from "../types/index.js";
import { LaravelClient } from "../services/laravel-client.js";
import { Ga4Client } from "../services/ga4-client.js";
import { TtlCache } from "../services/cache.js";
import { formatError, formatSuccess } from "../services/response-formatter.js";
import { entityId, pagingFields, type InputShape } from "./schemas.js";

type ToolInput = Record<string, unknown>;
type DataSource = "laravel" | "ga4";

export interface AnalyticsToolDefinition {
  name: string;
  description: string;
  source: DataSource;
  endpoint?: EndpointKey;
  inputSchema: InputShape;
  mapRequest?: (input: ToolInput) => RequestOptions;
  cacheTtlSeconds?: (config: AppConfig) => number;
}

const dateRange: InputShape = {
  startDate: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, "Use YYYY-MM-DD.").optional().describe("Start date. Defaults to today."),
  endDate: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, "Use YYYY-MM-DD.").optional().describe("End date. Defaults to today.")
};

function periodQuery(input: ToolInput): Record<string, unknown> {
  return { start_date: input.startDate, end_date: input.endDate };
}

export const toolDefinitions: AnalyticsToolDefinition[] = [
  {
    name: "admin_overview",
    description: "Read the Artera owner dashboard: registrations, app installs, completed package sales, support, and explicitly-labelled ad estimate. Laravel mapping: GET <ARTERA_API_BASE_URL>/admin/mcp/overview.",
    source: "laravel",
    endpoint: "overview",
    inputSchema: dateRange,
    mapRequest: (input) => ({ query: periodQuery(input) })
  },
  {
    name: "website_visitors",
    description: "Read ArteraPixel website visitors, sessions, page views, and engagement from the configured GA4 property. Mapping: Google Analytics Data API runReport for the Artera property.",
    source: "ga4",
    inputSchema: dateRange,
    cacheTtlSeconds: () => 300
  },
  {
    name: "install_summary",
    description: "Read total installs, unique first installs, and uninstall events. Laravel mapping: GET <ARTERA_API_BASE_URL>/admin/mcp/installs.",
    source: "laravel",
    endpoint: "installs",
    inputSchema: dateRange,
    mapRequest: (input) => ({ query: periodQuery(input) })
  },
  {
    name: "package_sales_summary",
    description: "Read completed package purchases, paid users, revenue in INR, and plan breakdown. Laravel mapping: GET <ARTERA_API_BASE_URL>/admin/mcp/sales.",
    source: "laravel",
    endpoint: "sales",
    inputSchema: dateRange,
    mapRequest: (input) => ({ query: periodQuery(input) })
  },
  {
    name: "ad_revenue_summary",
    description: "Read recorded banner/interstitial/rewarded ad events and the clearly labelled estimated INR revenue. Laravel mapping: GET <ARTERA_API_BASE_URL>/admin/mcp/ads. It does not claim actual AdMob revenue without AdMob reporting access.",
    source: "laravel",
    endpoint: "ads",
    inputSchema: dateRange,
    mapRequest: (input) => ({ query: periodQuery(input) })
  },
  {
    name: "support_ticket_summary",
    description: "Read support-ticket count, status breakdown, and categories without exposing ticket bodies. Laravel mapping: GET <ARTERA_API_BASE_URL>/admin/mcp/tickets.",
    source: "laravel",
    endpoint: "tickets",
    inputSchema: dateRange,
    mapRequest: (input) => ({ query: periodQuery(input) })
  },
  {
    name: "top_templates",
    description: "List the most-downloaded Artera templates, based on recorded download_template events. Laravel mapping: GET <ARTERA_API_BASE_URL>/admin/mcp/templates.",
    source: "laravel",
    endpoint: "templates",
    inputSchema: {
      ...dateRange,
      type: z.enum(["festival", "category", "custom"]).optional().describe("Optional template type filter."),
      limit: z.number().int().min(1).max(25).optional().describe("Maximum templates to return; default 10.")
    },
    mapRequest: (input) => ({ query: { ...periodQuery(input), type: input.type, limit: input.limit } }),
    cacheTtlSeconds: () => 120
  },
  {
    name: "review_summary",
    description: "Read synced Google Play review counts, rating average, positive reviews, and rating breakdown. Laravel mapping: GET <ARTERA_API_BASE_URL>/admin/mcp/reviews.",
    source: "laravel",
    endpoint: "reviews",
    inputSchema: dateRange,
    mapRequest: (input) => ({ query: periodQuery(input) }),
    cacheTtlSeconds: () => 300
  },
  {
    name: "search_users",
    description: "Search Artera users by ID, name, email, or mobile number. Returned email and phone values are masked by the MCP gateway. Laravel mapping: GET <ARTERA_API_BASE_URL>/admin/mcp/users/search.",
    source: "laravel",
    endpoint: "userSearch",
    inputSchema: {
      query: z.string().trim().min(2).max(120).describe("User ID, name, email, or mobile-number search text."),
      page: pagingFields.page,
      perPage: pagingFields.perPage
    },
    mapRequest: (input) => ({ query: { query: input.query, page: input.page, per_page: input.perPage } })
  },
  {
    name: "user_details",
    description: "Read a selected user's account, plan, business summary, recent payments, ticket counts, and activity summary. Sensitive identifiers are masked. Laravel mapping: GET <ARTERA_API_BASE_URL>/admin/mcp/users/:id.",
    source: "laravel",
    endpoint: "userDetails",
    inputSchema: { userId: entityId.describe("Numeric Artera user ID.").regex(/^\d+$/, "User ID must be numeric.") },
    mapRequest: (input) => ({ pathParams: { id: String(input.userId) } })
  },
  {
    name: "user_activity",
    description: "Read a selected user's activity-event summary without IP address, user-agent, or raw event payload. Laravel mapping: GET <ARTERA_API_BASE_URL>/admin/mcp/users/:id/activity.",
    source: "laravel",
    endpoint: "userActivity",
    inputSchema: { userId: entityId.describe("Numeric Artera user ID.").regex(/^\d+$/, "User ID must be numeric."), ...dateRange, page: pagingFields.page, perPage: pagingFields.perPage },
    mapRequest: (input) => ({ pathParams: { id: String(input.userId) }, query: { ...periodQuery(input), page: input.page, per_page: input.perPage } }),
    cacheTtlSeconds: () => 30
  }
];

function cacheKey(definition: AnalyticsToolDefinition, input: ToolInput): string {
  return JSON.stringify({ tool: definition.name, input });
}

function defaultDates(input: ToolInput): { startDate: string; endDate: string } {
  const today = new Date().toISOString().slice(0, 10);
  return {
    startDate: typeof input.startDate === "string" ? input.startDate : today,
    endDate: typeof input.endDate === "string" ? input.endDate : today
  };
}

export function createArteraMcpServer(dependencies: {
  config: AppConfig;
  client: LaravelClient;
  ga4Client: Ga4Client;
  cache: TtlCache;
  logger: Logger;
}): McpServer {
  const server = new McpServer({ name: "artera-admin-analytics-mcp", version: "1.0.0" });

  for (const definition of toolDefinitions) {
    server.registerTool(definition.name, {
      description: definition.description,
      inputSchema: definition.inputSchema
    }, async (input) => {
      const requestId = randomUUID();
      const startedAt = performance.now();
      const logger = dependencies.logger.child({ requestId, tool: definition.name, source: definition.source });
      try {
        const toolInput = input as ToolInput;
        const ttlSeconds = definition.cacheTtlSeconds?.(dependencies.config) ?? dependencies.config.cacheDefaultTtlSeconds;
        const key = cacheKey(definition, toolInput);
        const cached = dependencies.cache.get<unknown>(key);
        let data = cached;
        if (data === undefined) {
          if (definition.source === "ga4") {
            const dates = defaultDates(toolInput);
            data = await dependencies.ga4Client.visitors(dates.startDate, dates.endDate);
          } else if (definition.endpoint && definition.mapRequest) {
            data = await dependencies.client.call<unknown>(definition.endpoint, definition.mapRequest(toolInput));
          } else {
            throw new Error("Tool has no configured data source.");
          }
          dependencies.cache.set(key, data, ttlSeconds);
        }
        logger.info({ durationMs: Math.round(performance.now() - startedAt), cache: cached !== undefined, outcome: "success" }, "MCP analytics tool call completed");
        return formatSuccess(definition.name, data, dependencies.config);
      } catch (error) {
        logger.warn({ durationMs: Math.round(performance.now() - startedAt), outcome: "error", error: error instanceof Error ? error.name : "INTERNAL_ERROR" }, "MCP analytics tool call failed");
        return formatError(definition.name, error);
      }
    });
  }
  return server;
}

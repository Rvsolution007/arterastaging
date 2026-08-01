export type HttpMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

export type EndpointKey =
  | "overview"
  | "installs"
  | "sales"
  | "ads"
  | "tickets"
  | "templates"
  | "reviews"
  | "userSearch"
  | "userDetails"
  | "userActivity";

export interface EndpointDefinition {
  key: EndpointKey;
  envName: string;
  method: HttpMethod;
  path: string;
  cacheTtlSeconds?: number;
}

export interface AppConfig {
  nodeEnv: "development" | "test" | "production";
  port: number;
  transport: "http" | "stdio";
  mcpPath: string;
  mcpAuthMode: "none" | "bearer";
  mcpAccessToken?: string;
  corsOrigins: string[];
  logLevel: "fatal" | "error" | "warn" | "info" | "debug" | "trace" | "silent";
  arteraApiBaseUrl: string;
  arteraApiToken?: string;
  apiTimeoutMs: number;
  maxRetries: number;
  retryBaseDelayMs: number;
  ga4PropertyId?: string;
  ga4ServiceAccountJson?: string;
  maskPii: boolean;
  includeFreeText: boolean;
  maxResponseRecords: number;
  maxResponseDepth: number;
  cacheMaxEntries: number;
  cacheDefaultTtlSeconds: number;
  endpointOverrides: Partial<Record<EndpointKey, string>>;
}

export interface RequestOptions {
  pathParams?: Record<string, string | number>;
  query?: Record<string, unknown>;
}

import axios, { type AxiosInstance } from "axios";
import type { Logger } from "pino";
import { LaravelAdminToken } from "../auth/laravel-admin-token.js";
import { resolveEndpoint } from "../config/endpoints.js";
import type { AppConfig, EndpointKey, RequestOptions } from "../types/index.js";
import { AppError, UpstreamHttpError } from "../utils/errors.js";
import { withRetry } from "../utils/retry.js";

function interpolate(path: string, parameters: Record<string, string | number> = {}): string {
  return path.replace(/:([A-Za-z0-9_]+)/g, (_match, key: string) => {
    const value = parameters[key];
    if (value === undefined || value === null || value === "") {
      throw new AppError("VALIDATION_ERROR", `Missing required path parameter: ${key}.`, { statusCode: 400 });
    }
    return encodeURIComponent(String(value));
  });
}

function compact(query: Record<string, unknown> | undefined): Record<string, unknown> | undefined {
  if (!query) return undefined;
  return Object.fromEntries(Object.entries(query).filter(([, value]) => value !== undefined && value !== null && value !== ""));
}

export class LaravelClient {
  private readonly http: AxiosInstance;

  constructor(
    private readonly config: AppConfig,
    private readonly token: LaravelAdminToken,
    private readonly logger: Logger,
    httpClient?: AxiosInstance,
  ) {
    this.http = httpClient ?? axios.create({
      baseURL: config.arteraApiBaseUrl,
      timeout: config.apiTimeoutMs,
      validateStatus: () => true,
      headers: { Accept: "application/json" }
    });
  }

  async call<T>(key: EndpointKey, options: RequestOptions = {}): Promise<T> {
    const endpoint = resolveEndpoint(key, this.config.endpointOverrides);
    const path = interpolate(endpoint.path, options.pathParams);

    return withRetry(async () => {
      let response;
      try {
        response = await this.http.request<T>({
          method: endpoint.method,
          url: path,
          params: compact(options.query),
          headers: { Authorization: `Bearer ${this.token.get()}` }
        });
      } catch (error) {
        throw new AppError("UPSTREAM_UNAVAILABLE", "Artera analytics API did not respond before the timeout.", { statusCode: 504, retryable: true, cause: error });
      }
      if (response.status < 200 || response.status >= 300) {
        this.logger.warn({ endpoint: key, statusCode: response.status }, "Artera analytics API rejected request");
        throw new UpstreamHttpError(response.status, "Artera analytics API could not process this request.");
      }
      return response.data;
    }, { retries: this.config.maxRetries, baseDelayMs: this.config.retryBaseDelayMs });
  }
}

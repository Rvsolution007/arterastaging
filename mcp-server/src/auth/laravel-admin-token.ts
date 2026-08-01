import type { AppConfig } from "../types/index.js";
import { AppError } from "../utils/errors.js";

/**
 * Laravel issues a scoped Sanctum personal access token for the MCP server.
 * It is intentionally not obtained through /api/login: that endpoint rotates
 * the owner's mobile-app token and would sign their phone out.
 */
export class LaravelAdminToken {
  constructor(private readonly config: Pick<AppConfig, "arteraApiToken">) {}

  get(): string {
    if (!this.config.arteraApiToken) {
      throw new AppError("AUTHENTICATION_FAILED", "Artera analytics authentication is not configured.", { statusCode: 502 });
    }
    return this.config.arteraApiToken;
  }
}

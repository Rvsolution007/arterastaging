import pino from "pino";
import type { AppConfig } from "../types/index.js";

export function createLogger(config: Pick<AppConfig, "logLevel" | "nodeEnv">) {
  return pino({
    level: config.logLevel,
    base: { service: "artera-admin-analytics-mcp", environment: config.nodeEnv },
    redact: {
      paths: [
        "req.headers.authorization",
        "req.headers.cookie",
        "authorization",
        "accessToken",
        "access_token",
        "refreshToken",
        "refresh_token",
        "password",
        "clientSecret",
        "client_secret",
        "arteraApiToken",
        "ga4ServiceAccountJson"
      ],
      censor: "[REDACTED]"
    }
  }, pino.destination(2));
}

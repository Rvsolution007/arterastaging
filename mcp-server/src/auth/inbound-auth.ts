import { timingSafeEqual } from "node:crypto";
import type { RequestHandler } from "express";
import type { Logger } from "pino";
import type { AppConfig } from "../types/index.js";

function safeEqual(actual: string, expected: string): boolean {
  const actualBuffer = Buffer.from(actual);
  const expectedBuffer = Buffer.from(expected);
  return actualBuffer.length === expectedBuffer.length && timingSafeEqual(actualBuffer, expectedBuffer);
}

export function createInboundAuth(config: Pick<AppConfig, "mcpAuthMode" | "mcpAccessToken">, logger: Logger): RequestHandler {
  return (request, response, next) => {
    if (config.mcpAuthMode === "none") return next();
    const authorization = request.header("authorization");
    const suppliedToken = authorization?.match(/^Bearer\s+(.+)$/i)?.[1];
    if (!suppliedToken || !config.mcpAccessToken || !safeEqual(suppliedToken, config.mcpAccessToken)) {
      logger.warn({ method: request.method, path: request.path }, "Rejected unauthenticated MCP request");
      response.status(401).json({ error: "Unauthorized" });
      return;
    }
    next();
  };
}

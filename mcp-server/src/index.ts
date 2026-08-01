import "dotenv/config";
import { randomUUID } from "node:crypto";
import { createServer } from "node:http";
import cors from "cors";
import express, { type ErrorRequestHandler } from "express";
import helmet from "helmet";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { StreamableHTTPServerTransport } from "@modelcontextprotocol/sdk/server/streamableHttp.js";
import { loadConfig } from "./config/env.js";
import { LaravelAdminToken } from "./auth/laravel-admin-token.js";
import { createInboundAuth } from "./auth/inbound-auth.js";
import { TtlCache } from "./services/cache.js";
import { Ga4Client } from "./services/ga4-client.js";
import { LaravelClient } from "./services/laravel-client.js";
import { createArteraMcpServer } from "./tools/tool-registry.js";
import { createLogger } from "./utils/logger.js";

async function start(): Promise<void> {
  const config = loadConfig();
  const logger = createLogger(config);
  const token = new LaravelAdminToken(config);
  const client = new LaravelClient(config, token, logger);
  const ga4Client = new Ga4Client(config);
  const cache = new TtlCache(config.cacheMaxEntries);
  const mcpServer = createArteraMcpServer({ config, client, ga4Client, cache, logger });

  if (config.transport === "stdio") {
    const transport = new StdioServerTransport();
    await mcpServer.connect(transport);
    logger.info("Artera admin analytics MCP server started on stdio");
    return;
  }

  const app = express();
  app.disable("x-powered-by");
  app.use(helmet({ contentSecurityPolicy: false }));
  app.use(express.json({ limit: "1mb", strict: true }));
  if (config.corsOrigins.length > 0) {
    app.use(cors({ origin: config.corsOrigins, methods: ["GET", "POST", "DELETE"], allowedHeaders: ["Content-Type", "Authorization", "Mcp-Session-Id", "MCP-Protocol-Version"] }));
  }
  app.get("/healthz", (_request, response) => response.status(200).json({ status: "ok" }));
  app.use(config.mcpPath, createInboundAuth(config, logger));

  const transport = new StreamableHTTPServerTransport({ sessionIdGenerator: undefined });
  await mcpServer.connect(transport);
  app.all(config.mcpPath, async (request, response, next) => {
    const requestId = randomUUID();
    const startedAt = performance.now();
    try {
      await transport.handleRequest(request, response, request.body);
      logger.info({ requestId, method: request.method, path: config.mcpPath, durationMs: Math.round(performance.now() - startedAt) }, "MCP transport request completed");
    } catch (error) {
      next(error);
    }
  });

  const errorHandler: ErrorRequestHandler = (error, _request, response, _next) => {
    logger.error({ error: error instanceof Error ? error.name : "unknown" }, "Unhandled HTTP server error");
    if (!response.headersSent) response.status(500).json({ error: "Internal server error" });
  };
  app.use(errorHandler);

  const httpServer = createServer(app);
  httpServer.listen(config.port, "0.0.0.0", () => {
    logger.info({ port: config.port, path: config.mcpPath, authMode: config.mcpAuthMode }, "Artera admin analytics MCP server started");
  });

  const shutdown = (signal: string) => {
    logger.info({ signal }, "Shutting down MCP server");
    httpServer.close(() => process.exit(0));
    setTimeout(() => process.exit(1), 10_000).unref();
  };
  process.once("SIGINT", () => shutdown("SIGINT"));
  process.once("SIGTERM", () => shutdown("SIGTERM"));
}

start().catch((error: unknown) => {
  // Only identify the startup error class; environment values may contain secrets.
  process.stderr.write(`Failed to start Artera admin analytics MCP server: ${error instanceof Error ? error.message : "Unknown error"}\n`);
  process.exitCode = 1;
});

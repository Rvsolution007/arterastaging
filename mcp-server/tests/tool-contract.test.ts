import { describe, expect, it } from "vitest";
import { endpointDefinitions } from "../src/config/endpoints.js";
import { toolDefinitions } from "../src/tools/tool-registry.js";

describe("Artera analytics MCP tool contract", () => {
  it("registers every approved Artera analytics tool with a unique data mapping", () => {
    expect(toolDefinitions).toHaveLength(11);
    expect(new Set(toolDefinitions.map((tool) => tool.name)).size).toBe(11);
    for (const tool of toolDefinitions) {
      if (tool.source === "laravel") expect(endpointDefinitions[tool.endpoint ?? "overview"]).toBeDefined();
      expect(tool.description).toMatch(/mapping:/i);
    }
  });

  it("is strictly read-only", () => {
    expect(toolDefinitions.every((tool) => tool.description.includes("GET") || tool.source === "ga4")).toBe(true);
  });
});

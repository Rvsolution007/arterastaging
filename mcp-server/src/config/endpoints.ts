import type { EndpointDefinition, EndpointKey } from "../types/index.js";

export const endpointDefinitions: Record<EndpointKey, EndpointDefinition> = {
  overview: { key: "overview", envName: "ARTERA_ENDPOINT_OVERVIEW", method: "GET", path: "/admin/mcp/overview", cacheTtlSeconds: 60 },
  installs: { key: "installs", envName: "ARTERA_ENDPOINT_INSTALLS", method: "GET", path: "/admin/mcp/installs", cacheTtlSeconds: 60 },
  sales: { key: "sales", envName: "ARTERA_ENDPOINT_SALES", method: "GET", path: "/admin/mcp/sales", cacheTtlSeconds: 60 },
  ads: { key: "ads", envName: "ARTERA_ENDPOINT_ADS", method: "GET", path: "/admin/mcp/ads", cacheTtlSeconds: 60 },
  tickets: { key: "tickets", envName: "ARTERA_ENDPOINT_TICKETS", method: "GET", path: "/admin/mcp/tickets", cacheTtlSeconds: 60 },
  templates: { key: "templates", envName: "ARTERA_ENDPOINT_TEMPLATES", method: "GET", path: "/admin/mcp/templates", cacheTtlSeconds: 120 },
  reviews: { key: "reviews", envName: "ARTERA_ENDPOINT_REVIEWS", method: "GET", path: "/admin/mcp/reviews", cacheTtlSeconds: 300 },
  userSearch: { key: "userSearch", envName: "ARTERA_ENDPOINT_USER_SEARCH", method: "GET", path: "/admin/mcp/users/search", cacheTtlSeconds: 60 },
  userDetails: { key: "userDetails", envName: "ARTERA_ENDPOINT_USER_DETAILS", method: "GET", path: "/admin/mcp/users/:id", cacheTtlSeconds: 60 },
  userActivity: { key: "userActivity", envName: "ARTERA_ENDPOINT_USER_ACTIVITY", method: "GET", path: "/admin/mcp/users/:id/activity", cacheTtlSeconds: 30 }
};

export function resolveEndpoint(key: EndpointKey, overrides: Partial<Record<EndpointKey, string>>): EndpointDefinition {
  const definition = endpointDefinitions[key];
  const path = overrides[key];
  return path ? { ...definition, path } : definition;
}

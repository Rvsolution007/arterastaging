import { BetaAnalyticsDataClient } from "@google-analytics/data";
import type { AppConfig } from "../types/index.js";
import { AppError } from "../utils/errors.js";

export interface WebsiteVisitorReport {
  source: "Google Analytics 4";
  property_id: string;
  period: { start_date: string; end_date: string };
  visitors: {
    active_users: number;
    new_users: number;
    sessions: number;
    page_views: number;
    engagement_rate_percent: number;
  };
}

export class Ga4Client {
  private readonly client?: BetaAnalyticsDataClient;

  constructor(private readonly config: Pick<AppConfig, "ga4PropertyId" | "ga4ServiceAccountJson">) {
    if (!config.ga4PropertyId) return;
    this.client = new BetaAnalyticsDataClient(
      config.ga4ServiceAccountJson ? { credentials: JSON.parse(config.ga4ServiceAccountJson) } : undefined,
    );
  }

  async visitors(startDate: string, endDate: string): Promise<WebsiteVisitorReport> {
    if (!this.config.ga4PropertyId || !this.client) {
      throw new AppError("ANALYTICS_NOT_CONFIGURED", "GA4 is not configured. Add GA4_PROPERTY_ID and Application Default Credentials or GA4_SERVICE_ACCOUNT_JSON.", { statusCode: 503 });
    }
    try {
      const [report] = await this.client.runReport({
        property: `properties/${this.config.ga4PropertyId}`,
        dateRanges: [{ startDate, endDate }],
        metrics: [
          { name: "activeUsers" },
          { name: "newUsers" },
          { name: "sessions" },
          { name: "screenPageViews" },
          { name: "engagementRate" }
        ]
      });
      const values = report.rows?.[0]?.metricValues ?? [];
      const metric = (index: number) => Number(values[index]?.value ?? 0);

      return {
        source: "Google Analytics 4",
        property_id: this.config.ga4PropertyId,
        period: { start_date: startDate, end_date: endDate },
        visitors: {
          active_users: metric(0),
          new_users: metric(1),
          sessions: metric(2),
          page_views: metric(3),
          engagement_rate_percent: Math.round(metric(4) * 10000) / 100
        }
      };
    } catch (error) {
      throw new AppError("ANALYTICS_NOT_CONFIGURED", "GA4 visitor data could not be retrieved. Verify the Analytics Data API and service-account Viewer access.", { statusCode: 502, cause: error });
    }
  }
}

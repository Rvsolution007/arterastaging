import { z } from "zod";

export const entityId = z.string()
  .trim()
  .min(1)
  .max(120)
  .regex(/^[A-Za-z0-9_-]+$/, "ID may contain only letters, numbers, underscores, and hyphens.");

export const isoDate = z.string()
  .regex(/^\d{4}-\d{2}-\d{2}$/, "Use YYYY-MM-DD.");

export const pagingFields = {
  page: z.number().int().min(1).max(10_000).optional().describe("Page number, starting at 1."),
  perPage: z.number().int().min(1).max(100).optional().describe("Records per page, maximum 100."),
  sortBy: z.string().trim().min(1).max(64).regex(/^[A-Za-z0-9_.-]+$/, "Use a simple field name.").optional().describe("Field used to sort results."),
  sortDirection: z.enum(["asc", "desc"]).optional().describe("Sort direction."),
  fromDate: isoDate.optional().describe("Inclusive start date in YYYY-MM-DD."),
  toDate: isoDate.optional().describe("Inclusive end date in YYYY-MM-DD."),
  filters: z.record(z.string().max(64), z.union([z.string().max(200), z.number(), z.boolean(), z.array(z.string().max(100)).max(20)])).optional().describe("Additional approved analytics filters as key/value pairs.")
};

export const dateField = {
  date: isoDate.optional().describe("Date in YYYY-MM-DD. Defaults to the Artera server's current date.")
};

export type InputShape = Record<string, z.ZodTypeAny>;

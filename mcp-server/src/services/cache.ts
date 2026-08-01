interface CacheEntry<T> {
  value: T;
  expiresAt: number;
}

export class TtlCache {
  private readonly entries = new Map<string, CacheEntry<unknown>>();

  constructor(private readonly maxEntries: number) {}

  get<T>(key: string): T | undefined {
    const entry = this.entries.get(key);
    if (!entry) return undefined;
    if (entry.expiresAt <= Date.now()) {
      this.entries.delete(key);
      return undefined;
    }
    this.entries.delete(key);
    this.entries.set(key, entry);
    return entry.value as T;
  }

  set<T>(key: string, value: T, ttlSeconds: number): void {
    if (ttlSeconds <= 0) return;
    while (this.entries.size >= this.maxEntries) {
      const oldestKey = this.entries.keys().next().value;
      if (!oldestKey) break;
      this.entries.delete(oldestKey);
    }
    this.entries.set(key, { value, expiresAt: Date.now() + ttlSeconds * 1000 });
  }

  clear(): void {
    this.entries.clear();
  }
}

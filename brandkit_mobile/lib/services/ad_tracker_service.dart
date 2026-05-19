import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';

/// Lightweight service to batch-track ad events and send them to the server
/// periodically to minimize server load and network calls.
///
/// Events are queued in-memory and flushed:
///   - Every 5 minutes automatically
///   - When the app goes to background (call [flush] from lifecycle handler)
///   - When the queue reaches 20 events
class AdTrackerService {
  static final AdTrackerService _instance = AdTrackerService._internal();
  factory AdTrackerService() => _instance;
  AdTrackerService._internal();

  final List<Map<String, String>> _eventQueue = [];
  Timer? _flushTimer;
  static const int _maxQueueSize = 20;
  static const Duration _flushInterval = Duration(minutes: 5);

  /// Initialize the tracker - call once at app startup
  void init() {
    _flushTimer?.cancel();
    _flushTimer = Timer.periodic(_flushInterval, (_) => flush());
  }

  /// Track an ad impression event
  void trackImpression(String adType) {
    _addEvent(adType, 'impression');
  }

  /// Track an ad click event
  void trackClick(String adType) {
    _addEvent(adType, 'click');
  }

  /// Track a rewarded ad completion event
  void trackCompleted(String adType) {
    _addEvent(adType, 'completed');
  }

  void _addEvent(String adType, String event) {
    _eventQueue.add({
      'ad_type': adType,
      'event': event,
      'timestamp': DateTime.now().toIso8601String(),
    });

    // Auto-flush if queue is full
    if (_eventQueue.length >= _maxQueueSize) {
      flush();
    }
  }

  /// Flush all queued events to the server in a single batch API call.
  /// Call this when the app goes to background or periodically.
  Future<void> flush() async {
    if (_eventQueue.isEmpty) return;

    // Take a snapshot and clear the queue immediately to avoid duplicates
    final events = List<Map<String, String>>.from(_eventQueue);
    _eventQueue.clear();

    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';

      await ApiService.post('/track-ad-events', {
        'userId': userId,
        'events': events,
      });

      debugPrint('[AdTracker] Flushed ${events.length} ad events to server');
    } catch (e) {
      // If flush fails, put events back in queue for retry
      _eventQueue.insertAll(0, events);
      debugPrint('[AdTracker] Flush failed, ${events.length} events re-queued: $e');
    }
  }

  /// Dispose the timer when the service is no longer needed
  void dispose() {
    _flushTimer?.cancel();
    flush(); // Final flush before disposing
  }
}

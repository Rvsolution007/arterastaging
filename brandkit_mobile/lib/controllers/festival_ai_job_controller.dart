import 'dart:async';
import 'dart:convert';

import 'package:flutter/widgets.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../services/api_service.dart';
import 'home_controller.dart';

/// App-wide Festival AI tracking. The server remains the source of truth;
/// this controller only stores a non-sensitive generation ID so navigation or
/// an app restart never makes an in-progress visual appear to disappear.
class FestivalAiJobController extends GetxController
    with WidgetsBindingObserver {
  static const _activeJobKey = 'festival_ai_active_job_id';
  static const _pendingOutcomeKey = 'festival_ai_pending_outcome_id';

  final activeJob = Rxn<Map<String, dynamic>>();
  final latestOutcome = Rxn<Map<String, dynamic>>();
  final history = <Map<String, dynamic>>[].obs;
  final isRefreshingHistory = false.obs;

  Timer? _pollTimer;

  bool get hasActiveJob {
    final status = activeJob.value?['status']?.toString();
    return status == 'queued' ||
        status == 'processing' ||
        status == 'submitting';
  }

  Map<String, dynamic>? get visibleJob =>
      activeJob.value ?? latestOutcome.value;

  @override
  void onInit() {
    super.onInit();
    WidgetsBinding.instance.addObserver(this);
    _restoreSavedState();
  }

  @override
  void onClose() {
    WidgetsBinding.instance.removeObserver(this);
    _pollTimer?.cancel();
    super.onClose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      refreshActiveJob();
      refreshHistory();
    }
  }

  Future<void> track(Map<String, dynamic> job) async {
    await _applyServerJob(job);
    await refreshHistory();
  }

  Future<void> refreshActiveJob() async {
    final id = _jobId(activeJob.value);
    if (id == null) return;
    await _fetchJob(id);
  }

  Future<void> refreshHistory({bool showLoader = false}) async {
    if (showLoader) isRefreshingHistory.value = true;
    try {
      final response = await ApiService.get('/festival-ai/generations');
      final data = jsonDecode(response.body) as Map<String, dynamic>;
      if (response.statusCode != 200 || data['success'] != true) return;

      final jobs = List<dynamic>.from(
        data['jobs'] ?? [],
      ).map((job) => Map<String, dynamic>.from(job as Map)).toList();
      history.assignAll(jobs);

      final outcomeId = _jobId(latestOutcome.value);
      if (outcomeId != null) {
        final updated = _findJob(jobs, outcomeId);
        if (updated != null) latestOutcome.value = updated;
      }
    } catch (_) {
      // A temporary network/auth failure must not clear a visible job card.
    } finally {
      if (showLoader) isRefreshingHistory.value = false;
    }
  }

  Future<void> dismissOutcome() async {
    latestOutcome.value = null;
    final preferences = await SharedPreferences.getInstance();
    await preferences.remove(_pendingOutcomeKey);
  }

  Future<void> _restoreSavedState() async {
    final preferences = await SharedPreferences.getInstance();
    final activeId = preferences.getInt(_activeJobKey);
    final outcomeId = preferences.getInt(_pendingOutcomeKey);

    if (activeId != null) {
      await _fetchJob(activeId);
    }
    await refreshHistory();

    if (outcomeId != null && latestOutcome.value == null) {
      final savedOutcome = _findJob(history, outcomeId);
      if (savedOutcome != null && _isTerminal(savedOutcome)) {
        latestOutcome.value = savedOutcome;
      }
    }
  }

  Future<void> _fetchJob(int id) async {
    try {
      final response = await ApiService.get('/festival-ai/generations/$id');
      final data = jsonDecode(response.body) as Map<String, dynamic>;
      if (response.statusCode != 200 || data['success'] != true) return;
      await _applyServerJob(Map<String, dynamic>.from(data['job'] as Map));
    } catch (_) {
      // Keep polling later; the queued visual is still safely on the server.
    }
  }

  Future<void> _applyServerJob(Map<String, dynamic> job) async {
    _mergeHistory(job);
    final preferences = await SharedPreferences.getInstance();

    if (_isTerminal(job)) {
      activeJob.value = null;
      _pollTimer?.cancel();
      await preferences.remove(_activeJobKey);
      latestOutcome.value = job;
      final id = _jobId(job);
      if (id != null) await preferences.setInt(_pendingOutcomeKey, id);
      await _refreshNotificationBadge();
      return;
    }

    activeJob.value = job;
    final id = _jobId(job);
    if (id != null) await preferences.setInt(_activeJobKey, id);
    _startPolling();
  }

  void _startPolling() {
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(
      const Duration(seconds: 3),
      (_) => refreshActiveJob(),
    );
  }

  void _mergeHistory(Map<String, dynamic> job) {
    final id = _jobId(job);
    if (id == null) return;
    final index = history.indexWhere((entry) => _jobId(entry) == id);
    if (index < 0) {
      history.insert(0, job);
    } else {
      history[index] = job;
    }
  }

  Future<void> _refreshNotificationBadge() async {
    if (!Get.isRegistered<HomeController>()) return;
    try {
      final response = await ApiService.get('/notifications');
      if (response.statusCode != 200) return;
      final data = jsonDecode(response.body);
      if (data is List) {
        Get.find<HomeController>().notifications.assignAll(data);
      }
    } catch (_) {
      // The result card remains available even if the bell cannot refresh now.
    }
  }

  int? _jobId(Map<String, dynamic>? job) =>
      int.tryParse(job?['id']?.toString() ?? '');

  Map<String, dynamic>? _findJob(List<Map<String, dynamic>> jobs, int id) {
    for (final job in jobs) {
      if (_jobId(job) == id) return job;
    }
    return null;
  }

  bool _isTerminal(Map<String, dynamic> job) {
    final status = job['status']?.toString();
    return status == 'completed' || status == 'failed';
  }
}

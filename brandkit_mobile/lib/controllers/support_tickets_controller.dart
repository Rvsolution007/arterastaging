import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import 'dart:convert';

class SupportTicket {
  final int id;
  final String subject;
  final String status;
  final DateTime updatedAt;

  SupportTicket({required this.id, required this.subject, required this.status, required this.updatedAt});
}

class SupportTicketsController extends GetxController {
  var tickets = <SupportTicket>[].obs;
  var isLoading = false.obs;

  @override
  void onInit() {
    super.onInit();
    fetchTickets();
  }

  Future<void> fetchTickets() async {
    try {
      isLoading(true);
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId');
      if (userId == null) return;

      final response = await ApiService.get('/tickets?user_id=$userId');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          final List ticketList = data['tickets'] ?? [];
          tickets.value = ticketList.map((t) {
            return SupportTicket(
              id: t['id'],
              subject: t['subject'] ?? 'Ticket #${t['id']}',
              status: t['status'] ?? 'open',
              updatedAt: DateTime.parse(t['updated_at']),
            );
          }).toList();
        }
      }
    } catch (e) {
      print("Fetch tickets error: $e");
    } finally {
      isLoading(false);
    }
  }
}

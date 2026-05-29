import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../controllers/support_tickets_controller.dart';
import 'ai_chat_screen.dart';
import 'package:intl/intl.dart';

class SupportTicketsScreen extends StatelessWidget {
  const SupportTicketsScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final controller = Get.put(SupportTicketsController());

    return DefaultTabController(
      length: 3,
      child: Scaffold(
        backgroundColor: Colors.white,
        appBar: AppBar(
          title: Text('Support Tickets', style: AppTextStyles.heading4),
          backgroundColor: Colors.white,
          elevation: 1,
          iconTheme: const IconThemeData(color: Colors.black87),
          bottom: const TabBar(
            labelColor: AppColors.indigo600,
            unselectedLabelColor: Colors.grey,
            indicatorColor: AppColors.indigo600,
            tabs: [
              Tab(text: 'Open'),
              Tab(text: 'In Process'),
              Tab(text: 'Closed'),
            ],
          ),
        ),
        body: Obx(() {
          if (controller.isLoading.value && controller.tickets.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          return TabBarView(
            children: [
              _buildTicketList(controller, ['open']),
              _buildTicketList(controller, ['in_progress']),
              _buildTicketList(controller, ['resolved', 'ai_resolved', 'closed']),
            ],
          );
        }),
        floatingActionButton: FloatingActionButton(
          onPressed: () {
            Get.to(() => const AiChatScreen(ticketId: 0))?.then((_) {
              controller.fetchTickets();
            });
          },
          backgroundColor: AppColors.indigo600,
          child: const Icon(Icons.add, color: Colors.white, size: 28),
        ),
      ),
    );
  }

  Widget _buildTicketList(SupportTicketsController controller, List<String> statuses) {
    final filteredTickets = controller.tickets.where((t) => statuses.contains(t.status.toLowerCase())).toList();

    if (filteredTickets.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.support_agent, size: 80, color: AppColors.gray300),
            const SizedBox(height: 16),
            Text('No active tickets', style: AppTextStyles.heading3),
            const SizedBox(height: 8),
            Text(
              'Tap the + button to start a new chat',
              style: TextStyle(color: AppColors.gray500),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: controller.fetchTickets,
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(vertical: 8),
        itemCount: filteredTickets.length,
        separatorBuilder: (context, index) => const Divider(height: 1, indent: 72),
        itemBuilder: (context, index) {
          final ticket = filteredTickets[index];
          return ListTile(
            leading: CircleAvatar(
              backgroundColor: ticket.status == 'ai_resolved' || ticket.status == 'resolved' || ticket.status == 'closed'
                  ? Colors.green.withOpacity(0.1) 
                  : AppColors.indigo50,
              child: Icon(
                ticket.status == 'ai_resolved' || ticket.status == 'resolved' || ticket.status == 'closed'
                    ? Icons.check_circle_outline
                    : Icons.headset_mic_outlined,
                color: ticket.status == 'ai_resolved' || ticket.status == 'resolved' || ticket.status == 'closed'
                    ? Colors.green 
                    : AppColors.indigo600,
              ),
            ),
            title: Text(
              ticket.subject,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
            ),
            subtitle: Text(
              'Status: ${ticket.status.replaceAll('_', ' ').toUpperCase()}',
              style: TextStyle(
                color: ticket.status == 'open' ? Colors.orange : AppColors.gray500,
                fontWeight: ticket.status == 'open' ? FontWeight.w600 : FontWeight.normal,
              ),
            ),
            trailing: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  DateFormat('MMM dd').format(ticket.updatedAt),
                  style: TextStyle(color: AppColors.gray400, fontSize: 12),
                ),
                const SizedBox(height: 4),
                const Icon(Icons.chevron_right, size: 16, color: Colors.grey),
              ],
            ),
            onTap: () {
              Get.to(() => AiChatScreen(ticketId: ticket.id))?.then((_) {
                controller.fetchTickets();
              });
            },
          );
        },
      ),
    );
  }
}

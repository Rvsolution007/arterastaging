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

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text('Support Tickets', style: AppTextStyles.heading4),
        backgroundColor: Colors.white,
        elevation: 1,
        iconTheme: const IconThemeData(color: Colors.black87),
      ),
      body: Obx(() {
        if (controller.isLoading.value && controller.tickets.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }

        if (controller.tickets.isEmpty) {
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
            itemCount: controller.tickets.length,
            separatorBuilder: (context, index) => const Divider(height: 1, indent: 72),
            itemBuilder: (context, index) {
              final ticket = controller.tickets[index];
              return ListTile(
                leading: CircleAvatar(
                  backgroundColor: ticket.status == 'ai_resolved' || ticket.status == 'resolved' 
                      ? Colors.green.withOpacity(0.1) 
                      : AppColors.indigo50,
                  child: Icon(
                    ticket.status == 'ai_resolved' || ticket.status == 'resolved' 
                        ? Icons.check_circle_outline
                        : Icons.headset_mic_outlined,
                    color: ticket.status == 'ai_resolved' || ticket.status == 'resolved' 
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
      }),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Get.to(() => const AiChatScreen(ticketId: 0))?.then((_) {
            controller.fetchTickets();
          });
        },
        backgroundColor: AppColors.indigo600,
        child: const Icon(Icons.chat_bubble_outline, color: Colors.white),
      ),
    );
  }
}

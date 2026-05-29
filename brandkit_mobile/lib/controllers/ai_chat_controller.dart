import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import 'dart:convert';
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

class ChatMessage {
  final String text;
  final bool isMe;
  final DateTime timestamp;

  ChatMessage({required this.text, required this.isMe, required this.timestamp});
}

class AiChatController extends GetxController {
  var messages = <ChatMessage>[].obs;
  var isLoading = false.obs;
  var ticketId = 0.obs;
  var isTicketClosed = false.obs;
  PusherChannelsFlutter pusher = PusherChannelsFlutter.getInstance();

  AiChatController({int initialTicketId = 0}) {
    ticketId.value = initialTicketId;
  }

  @override
  void onInit() {
    super.onInit();
    if (ticketId.value > 0) {
      loadHistory();
    } else {
      // New chat greeting
      messages.add(ChatMessage(
        text: "Hi there! I'm your AI Support Assistant. How can I help you today?",
        isMe: false,
        timestamp: DateTime.now(),
      ));
    }
  }

  Future<void> loadHistory() async {
    if (ticketId.value == 0) return;
    
    try {
      isLoading(true);
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId');
      if (userId == null) return;

      final response = await ApiService.post('/ai-chat/history', {
        'user_id': userId,
        'ticket_id': ticketId.value
      });
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          if (data['status'] == 'closed' || data['status'] == 'ai_resolved') {
            isTicketClosed.value = true;
          }
          final List msgList = data['messages'] ?? [];
          messages.value = msgList.map((m) {
            return ChatMessage(
              text: m['message'],
              isMe: m['sender_type'] == 'user',
              timestamp: DateTime.parse(m['created_at']),
            );
          }).toList();
        }
      }
    } catch (e) {
      print("Chat history error: $e");
    } finally {
      isLoading(false);
      _initPusher();
    }
  }

  Future<void> _initPusher() async {
    try {
      await pusher.init(
        apiKey: "local_app_key",
        cluster: "mt1",
        onEvent: _onPusherEvent,
      );
      await pusher.subscribe(channelName: "ticket.${ticketId.value}");
      await pusher.connect();
    } catch (e) {
      print("Pusher init error: $e");
    }
  }

  void _onPusherEvent(PusherEvent event) {
    if (event.eventName == "message.created") {
      final data = jsonDecode(event.data);
      if (data['sender_type'] == 'user') return;
      
      messages.add(ChatMessage(
        text: data['message'],
        isMe: false,
        timestamp: DateTime.parse(data['created_at']),
      ));
    }
  }

  Future<void> sendMessage(String text) async {
    if (text.trim().isEmpty) return;

    messages.add(ChatMessage(
      text: text,
      isMe: true,
      timestamp: DateTime.now(),
    ));

    isLoading(true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId');

      Map<String, dynamic> body = {
        'user_id': userId,
        'message': text,
      };
      if (ticketId.value > 0) {
        body['ticket_id'] = ticketId.value;
      }

      final response = await ApiService.post('/ai-chat/send', body);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          if (ticketId.value == 0) {
            ticketId.value = data['ticket_id'];
            _initPusher();
          }
          
          messages.add(ChatMessage(
            text: data['reply'],
            isMe: false,
            timestamp: DateTime.parse(data['timestamp'] ?? DateTime.now().toIso8601String()),
          ));
        }
      }
    } catch (e) {
      messages.add(ChatMessage(
        text: "Sorry, I am having trouble connecting to the server. Please try again.",
        isMe: false,
        timestamp: DateTime.now(),
      ));
    } finally {
      isLoading(false);
    }
  }

  Future<bool> closeTicket(int rating) async {
    if (ticketId.value == 0) return false;
    isLoading(true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId');
      final response = await ApiService.post('/ai-chat/close', {
        'user_id': userId,
        'ticket_id': ticketId.value,
        'rating': rating
      });
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          isTicketClosed.value = true;
          return true;
        }
      }
      return false;
    } catch (e) {
      print("Error closing ticket: $e");
      return false;
    } finally {
      isLoading(false);
    }
  }
}

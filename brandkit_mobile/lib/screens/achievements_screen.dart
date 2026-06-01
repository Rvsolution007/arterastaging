import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../utils/app_colors.dart';
import '../services/api_service.dart';

class AchievementsScreen extends StatefulWidget {
  const AchievementsScreen({super.key});

  @override
  State<AchievementsScreen> createState() => _AchievementsScreenState();
}

class _AchievementsScreenState extends State<AchievementsScreen> {
  bool isLoading = true;
  List<dynamic> achievements = [];
  List<dynamic> challenges = [];
  int totalPosts = 0;
  int badgePostCount = 100;

  @override
  void initState() {
    super.initState();
    _fetchAchievements();
  }

  Future<void> _fetchAchievements() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId');
      if (userId == null) return;

      final response = await ApiService.get('/user-achievements?user_id=$userId');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          setState(() {
            achievements = data['data']['achievements'] ?? [];
            challenges = data['data']['challenges'] ?? [];
            totalPosts = data['data']['total_posts'] ?? 0;
            badgePostCount = data['data']['badge_post_count'] ?? 100;
          });
          
          // Save unlocked badges count to SharedPreferences for the header
          final completedChallenges = challenges.where((c) => c['status'] == 'completed').toList();
          final unlockedCount = achievements.length + completedChallenges.length;
          prefs.setInt('unlockedBadgesCount', unlockedCount);
        }
      }
    } catch (e) {
      debugPrint("Error fetching achievements: $e");
    } finally {
      setState(() => isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    // Calculate progress towards next badge
    // Example logic: Badges are given at multiples of badgePostCount
    int currentBadgeLevel = totalPosts ~/ badgePostCount;
    int nextBadgeGoal = (currentBadgeLevel + 1) * badgePostCount;
    double progress = totalPosts / nextBadgeGoal;

    return Scaffold(
      appBar: AppBar(
        title: const Text('My Achievements', style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black),
      ),
      backgroundColor: const Color(0xFFF8FAFC),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildActiveChallenges(),
                  const SizedBox(height: 24),
                  const Text('Unlocked Badges', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 16),
                  (achievements.isEmpty && challenges.where((c) => c['status'] == 'completed').isEmpty)
                      ? _buildEmptyState()
                      : _buildBadgesGrid(),
                ],
              ),
            ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32.0),
        child: Column(
          children: [
            Icon(Icons.military_tech, size: 80, color: Colors.grey.shade300),
            const SizedBox(height: 16),
            const Text(
              "You haven't unlocked any badges yet.",
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey, fontSize: 16),
            ),
            const SizedBox(height: 8),
            const Text(
              "Keep creating designs to earn your first badge!",
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey, fontSize: 14),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActiveChallenges() {
    final activeChallenges = challenges.where((c) => c['status'] != 'completed').toList();
    if (activeChallenges.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Live Challenges', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 16),
        ...activeChallenges.map((challenge) {
          final progress = challenge['progress'] ?? 0;
          final target = challenge['target_count'] ?? 1;
          final progressPercent = (target > 0) ? (progress / target) : 0.0;

          return Container(
            margin: const EdgeInsets.only(bottom: 16),
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(colors: [Color(0xFF6366F1), Color(0xFF4F46E5)]),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF6366F1).withValues(alpha: 0.3),
                  blurRadius: 10,
                  offset: const Offset(0, 5),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        challenge['title'] ?? 'Challenge',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.white),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        '$progress / $target',
                        style: TextStyle(color: Colors.yellow.shade400, fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  challenge['description'] ?? '',
                  style: TextStyle(color: Colors.white.withValues(alpha: 0.8), fontSize: 14),
                ),
                const SizedBox(height: 20),
                LinearProgressIndicator(
                  value: progressPercent,
                  backgroundColor: Colors.white.withValues(alpha: 0.2),
                  valueColor: AlwaysStoppedAnimation<Color>(Colors.yellow.shade400),
                  minHeight: 10,
                  borderRadius: BorderRadius.circular(10),
                ),
              ],
            ),
          );
        }),
      ],
    );
  }

  Widget _buildBadgesGrid() {
    final completedChallenges = challenges.where((c) => c['status'] == 'completed').toList();
    final totalBadgesCount = achievements.length + completedChallenges.length;

    return GridView.builder(
      physics: const NeverScrollableScrollPhysics(),
      shrinkWrap: true,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 16,
        mainAxisSpacing: 16,
        childAspectRatio: 0.9,
      ),
      itemCount: totalBadgesCount,
      itemBuilder: (context, index) {
        String badgeName = '';
        IconData badgeIcon = Icons.workspace_premium;
        Color badgeColor = Colors.orange.shade500;
        
        if (index < achievements.length) {
          badgeName = achievements[index]['badge_name'] ?? 'Achievement Unlocked';
        } else {
          final c = completedChallenges[index - achievements.length];
          badgeName = c['title'] ?? 'Challenge Completed';
          badgeIcon = Icons.emoji_events;
          badgeColor = Colors.purple.shade500;
        }

        int rewardPoints = 0;
        String badgeDesc = 'You unlocked this badge!';
        if (index >= achievements.length) {
          final c = completedChallenges[index - achievements.length];
          rewardPoints = int.tryParse(c['reward_points'].toString()) ?? 0;
          badgeDesc = c['description'] ?? 'You completed this challenge!';
        }

        return Card(
          elevation: 4,
          shadowColor: Colors.black12,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          child: InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: () {
              showDialog(
                context: context,
                builder: (context) => AlertDialog(
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                  title: Column(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: badgeColor.withValues(alpha: 0.1),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(badgeIcon, size: 48, color: badgeColor),
                      ),
                      const SizedBox(height: 16),
                      Text(badgeName, textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 20)),
                    ],
                  ),
                  content: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        badgeDesc,
                        textAlign: TextAlign.center,
                        style: const TextStyle(fontSize: 14, color: Colors.black87),
                      ),
                      const SizedBox(height: 20),
                      if (rewardPoints > 0)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                          decoration: BoxDecoration(
                            color: Colors.green.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Colors.green.withValues(alpha: 0.3)),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.stars, color: Colors.green),
                              const SizedBox(width: 8),
                              Text(
                                '+$rewardPoints Reward Points',
                                style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.green, fontSize: 16),
                              ),
                            ],
                          ),
                        )
                      else if (index >= achievements.length)
                        const Text('Badge Unlocked', style: TextStyle(color: Colors.purple, fontWeight: FontWeight.bold)),
                    ],
                  ),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: const Text('Awesome!', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ],
                ),
              );
            },
            child: Stack(
              children: [
                Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: badgeColor.withValues(alpha: 0.1),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(badgeIcon, size: 40, color: badgeColor),
                      ),
                      const SizedBox(height: 16),
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 8.0),
                        child: Text(
                          badgeName,
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                        ),
                      ),
                    ],
                  ),
                ),
                if (rewardPoints > 0)
                  Positioned(
                    top: 8,
                    right: 8,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.green.shade50,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.green.shade200),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.stars, size: 12, color: Colors.green.shade600),
                          const SizedBox(width: 4),
                          Text(
                            '+$rewardPoints',
                            style: TextStyle(
                              color: Colors.green.shade700,
                              fontSize: 11,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
        );
      },
    );
  }
}

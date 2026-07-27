import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../controllers/festival_ai_job_controller.dart';

/// Compact app-wide status tray displayed above the main navigation. It keeps
/// the Greetings visual language while leaving the user free to use any tab.
class FestivalAiProgressTray extends StatelessWidget {
  const FestivalAiProgressTray({super.key, required this.onOpen});

  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    final jobs = Get.find<FestivalAiJobController>();

    return Obx(() {
      final job = jobs.visibleJob;
      if (job == null) return const SizedBox.shrink();

      final status = job['status']?.toString() ?? 'queued';
      final completed = status == 'completed';
      final failed = status == 'failed';
      final active = !completed && !failed;
      final title = job['festival_title']?.toString().trim();
      final visualName = (title == null || title.isEmpty)
          ? 'Festival visual'
          : title;

      final foreground = failed
          ? const Color(0xFFB91C1C)
          : const Color(0xFF3730A3);
      final background = failed
          ? const Color(0xFFFFF1F2)
          : const Color(0xFFF4F1FF);
      final border = failed ? const Color(0xFFFECACA) : const Color(0xFFDCD6FF);
      final icon = failed
          ? Icons.error_outline_rounded
          : completed
          ? Icons.auto_awesome_rounded
          : Icons.auto_awesome_rounded;
      final headline = failed
          ? 'Visual needs your attention'
          : completed
          ? 'Your festival visual is ready'
          : 'Festival visual is being created';
      final detail = failed
          ? (job['error_message']?.toString() ?? 'Your quota was restored.')
          : completed
          ? '$visualName • Tap to preview'
          : '$visualName • You can keep using the app';

      return Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onOpen,
          borderRadius: BorderRadius.circular(16),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 220),
            padding: const EdgeInsets.fromLTRB(12, 10, 8, 10),
            decoration: BoxDecoration(
              color: background,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: border),
              boxShadow: const [
                BoxShadow(
                  color: Color(0x190F172A),
                  blurRadius: 16,
                  offset: Offset(0, 6),
                ),
              ],
            ),
            child: Row(
              children: [
                Container(
                  width: 34,
                  height: 34,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(11),
                  ),
                  child: active
                      ? const Padding(
                          padding: EdgeInsets.all(8),
                          child: CircularProgressIndicator(
                            strokeWidth: 2.4,
                            color: Color(0xFF5B4BE8),
                          ),
                        )
                      : Icon(icon, color: foreground, size: 20),
                ),
                const SizedBox(width: 9),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        headline,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: foreground,
                          fontSize: 11.5,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        detail,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: foreground.withValues(alpha: .76),
                          fontSize: 10,
                        ),
                      ),
                      if (active) ...[
                        const SizedBox(height: 6),
                        const ClipRRect(
                          borderRadius: BorderRadius.all(Radius.circular(4)),
                          child: LinearProgressIndicator(
                            minHeight: 3,
                            backgroundColor: Color(0xFFE1DDFF),
                            valueColor: AlwaysStoppedAnimation(
                              Color(0xFF5B4BE8),
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                if (!active)
                  IconButton(
                    tooltip: 'Dismiss',
                    onPressed: jobs.dismissOutcome,
                    icon: Icon(
                      Icons.close_rounded,
                      color: foreground,
                      size: 18,
                    ),
                    constraints: const BoxConstraints.tightFor(
                      width: 32,
                      height: 32,
                    ),
                    padding: EdgeInsets.zero,
                  )
                else
                  Icon(
                    Icons.arrow_forward_ios_rounded,
                    color: foreground,
                    size: 14,
                  ),
              ],
            ),
          ),
        ),
      );
    });
  }
}

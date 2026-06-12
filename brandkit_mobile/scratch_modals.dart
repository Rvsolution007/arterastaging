  void _showFontModal(Map<String, dynamic> layer) {
    // Basic list of available fonts
    final List<String> fonts = [
      'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Oswald',
      'Playfair Display', 'Merriweather', 'Nunito', 'Poppins', 'Raleway'
    ];
    
    showModalBottomSheet(
      context: context,
      builder: (context) {
        return Container(
          padding: const EdgeInsets.all(16),
          height: 300,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Select Font', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              Expanded(
                child: ListView.builder(
                  itemCount: fonts.length,
                  itemBuilder: (context, index) {
                    final font = fonts[index];
                    return ListTile(
                      title: Text(font, style: TextStyle(fontFamily: font)),
                      trailing: layer['fontFamily'] == font ? const Icon(Icons.check, color: Colors.blue) : null,
                      onTap: () {
                        controller.updateLayerProperty(layer['name'], 'fontFamily', font);
                        Navigator.pop(context);
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  void _showSizeModal(Map<String, dynamic> layer) {
    double currentSize = (layer['size'] ?? 48.0).toDouble();
    
    showModalBottomSheet(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setState) {
            return Container(
              padding: const EdgeInsets.all(24),
              height: 200,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Text Size: ${currentSize.toInt()}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 24),
                  Row(
                    children: [
                      const Icon(Icons.text_fields, size: 16),
                      Expanded(
                        child: Slider(
                          value: currentSize,
                          min: 8.0,
                          max: 150.0,
                          onChanged: (val) {
                            setState(() => currentSize = val);
                            controller.updateLayerProperty(layer['name'], 'size', val);
                          },
                        ),
                      ),
                      const Icon(Icons.text_fields, size: 32),
                    ],
                  ),
                ],
              ),
            );
          }
        );
      },
    );
  }

  void _showColorPickerModal(Map<String, dynamic> layer) {
    final List<Color> colors = [
      Colors.black, Colors.white, Colors.red, Colors.pink, Colors.purple,
      Colors.deepPurple, Colors.indigo, Colors.blue, Colors.lightBlue, Colors.cyan,
      Colors.teal, Colors.green, Colors.lightGreen, Colors.lime, Colors.yellow,
      Colors.amber, Colors.orange, Colors.deepOrange, Colors.brown, Colors.grey,
    ];
    
    // helper to format color to hex
    String colorToHex(Color color) {
      return '#${color.value.toRadixString(16).padLeft(8, '0').substring(2)}';
    }

    showModalBottomSheet(
      context: context,
      builder: (context) {
        return Container(
          padding: const EdgeInsets.all(16),
          height: 350,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Select Color', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              Expanded(
                child: GridView.builder(
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 5,
                    crossAxisSpacing: 10,
                    mainAxisSpacing: 10,
                  ),
                  itemCount: colors.length,
                  itemBuilder: (context, index) {
                    final color = colors[index];
                    final hex = colorToHex(color);
                    return GestureDetector(
                      onTap: () {
                        controller.updateLayerProperty(layer['name'], 'color', hex);
                        Navigator.pop(context);
                      },
                      child: Container(
                        decoration: BoxDecoration(
                          color: color,
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.black12, width: 1),
                        ),
                        child: layer['color']?.toString().toUpperCase() == hex.toUpperCase() 
                          ? Icon(Icons.check, color: color == Colors.white ? Colors.black : Colors.white) 
                          : null,
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  void _showNudgeModal(Map<String, dynamic> layer) {
    showModalBottomSheet(
      context: context,
      barrierColor: Colors.transparent, // Allow seeing the canvas
      builder: (context) {
        return Container(
          padding: const EdgeInsets.all(16),
          height: 250,
          decoration: BoxDecoration(
            color: Colors.white,
            boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 10)],
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              const Text('Nudge', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Column(
                    children: [
                      IconButton(
                        icon: const Icon(Icons.arrow_drop_up, size: 48),
                        onPressed: () {
                          controller.updateLayerProperty(layer['name'], 'y', (layer['y'] ?? 0) - 5);
                        },
                      ),
                      Row(
                        children: [
                          IconButton(
                            icon: const Icon(Icons.arrow_left, size: 48),
                            onPressed: () {
                              controller.updateLayerProperty(layer['name'], 'x', (layer['x'] ?? 0) - 5);
                            },
                          ),
                          const SizedBox(width: 32),
                          IconButton(
                            icon: const Icon(Icons.arrow_right, size: 48),
                            onPressed: () {
                              controller.updateLayerProperty(layer['name'], 'x', (layer['x'] ?? 0) + 5);
                            },
                          ),
                        ],
                      ),
                      IconButton(
                        icon: const Icon(Icons.arrow_drop_down, size: 48),
                        onPressed: () {
                          controller.updateLayerProperty(layer['name'], 'y', (layer['y'] ?? 0) + 5);
                        },
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

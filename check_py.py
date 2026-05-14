from PIL import Image

img = Image.open(r'C:\Users\Admin\.gemini\antigravity\brain\7896d00a-ea8f-4446-8920-23a40aa3f611\scratch\Frame2_Background.png')
img = img.convert("RGBA")
width, height = img.size
print(f"Size: {width}x{height}")

transparent_count = 0
opaque_count = 0

for y in range(height):
    for x in range(width):
        r, g, b, a = img.getpixel((x, y))
        if a == 0:
            transparent_count += 1
        elif a == 255:
            opaque_count += 1

print(f"Transparent pixels: {transparent_count}")
print(f"Opaque pixels: {opaque_count}")

# Check top middle pixel
r, g, b, a = img.getpixel((540, 100))
print(f"Pixel 540,100: R={r} G={g} B={b} A={a}")

# Check bottom middle pixel
r, g, b, a = img.getpixel((540, 1000))
print(f"Pixel 540,1000: R={r} G={g} B={b} A={a}")

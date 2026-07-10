from PIL import Image
try:
    img = Image.open(r"C:\Users\Admim\.gemini\antigravity\brain\619ae87b-566b-44ab-9bb1-e2d041322386\media__1783171808032.png")
    img.save(r"C:\xampp\htdocs\Artera\public\assets\images\logo.webp", "webp")
    print("Success")
except Exception as e:
    print("Error:", e)
